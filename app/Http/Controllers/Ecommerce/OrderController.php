<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceTemplate;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderConfirmation;
use App\Models\Balance;

class OrderController extends Controller
{
    public function checkoutPage()
    {
        $carts = Cart::where('user_id', auth()->id())->get();
        $cartTotal = 0;

        foreach ($carts as $cart) {
            $product = $cart->product;
            if (!$product)
                continue;
            $price = $product->discount && $product->discount > 0 ? $product->discount : $product->price;
            $total = $price * $cart->qty;
            $cartTotal += $total;
        }

        $pageTitle = 'Checkout';
        return view('ecommerce.checkout', compact('carts', 'cartTotal', 'pageTitle'));
    }

    public function makeOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'nullable|email',
            'phone' => 'required|string',
            'billing_address_1' => 'required|string',
            'billing_city' => 'required|string',
            'billing_state' => 'required|string',
            'billing_zip_code' => 'required|string',
            'shipping_address_1' => 'nullable|string',
            'shipping_city' => 'nullable|string',
            'shipping_state' => 'nullable|string',
            'shipping_zip_code' => 'nullable|string',
            'shipping_method' => 'required|in:standard,express,overnight',
            'payment_method' => 'required|in:cash,online-payment,bank-transfer',
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed', [
                'errors' => $validator->errors()->toArray(),
                'input' => $request->all()
            ]);
        
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = auth()->id();
        $carts = Cart::with('product')->where('user_id', $userId)->get();
        if ($carts->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Cart is empty.'], 400);
        }

        $subtotal = 0;
        $items = [];
        foreach ($carts as $cart) {
            $product = $cart->product;
            if (!$product) {
                \Log::warning("Missing product for cart ID {$cart->id}");
                continue;
            }

            $price = ($product->discount && $product->discount > 0) ? $product->discount : $product->price;
            $total = $price * $cart->qty;
            $subtotal += $total;
            $items[] = [
                'product_id' => $product->id,
                'quantity' => $cart->qty,
                'unit_price' => $price,
                'total_price' => $total,
            ];
        }

        $tax = round($subtotal * 0.05, 2);
        $shippingMap = ['standard' => 60, 'express' => 100, 'overnight' => 120];
        $shipping = $shippingMap[$request->shipping_method] ?? 60;
        $total = $subtotal + $tax + $shipping;

        DB::beginTransaction();
        try {
            $orderNumber = $this->generateOrderNumber();
            $order = Order::create([
                'order_number' => $orderNumber,
                'user_id' => $userId,
                'name' => $request->first_name. ' '.$request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'subtotal' => $subtotal,
                'vat' => $tax,
                'discount' => 0,
                'delivery' => $shipping,
                'total' => $total,
                'status' => 'pending',
                'payment_method' => $request->payment_method,
                'notes' => $request->notes ?? null,
                'created_by' => $userId
            ]);

            $invTemplate = InvoiceTemplate::where('is_default', 1)->first();
            $invoiceNumber = $this->generateInvoiceNumber();
            $invoice = Invoice::create([
                'customer_id' => $order->user_id,
                'template_id' => $invTemplate ? $invTemplate->id : null,
                'operated_by' => $userId,
                'issue_date' => now()->toDateString(),
                'due_date' => now()->toDateString(),
                'send_date' => now()->toDateString(),
                'subtotal' => $subtotal,
                'total_amount' => $total,
                'discount_apply' => 0,
                'paid_amount' => in_array($request->payment_method, ['online', 'bank']) ? $total : 0,
                'due_amount' => in_array($request->payment_method, ['online', 'bank']) ? 0 : $total,
                'status' => in_array($request->payment_method, ['online', 'bank']) ? 'paid' : 'unpaid',
                'note' => $order->notes,
                'footer_text' => null,
                'created_by' => $userId,
                'invoice_number' => $invoiceNumber,
            ]);

            $order->invoice_id = $invoice->id;
            $order->save();

            foreach ($items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price'],
                ]);
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price'],
                ]);
            }

            \App\Models\InvoiceAddress::create([
                'invoice_id' => $invoice->id,
                'billing_address_1' => $request->billing_address_1,
                'billing_address_2' => $request->billing_address_2 ?? null,
                'billing_city' => $request->billing_city,
                'billing_state' => $request->billing_state,
                'billing_country' => $request->billing_country ?? null,
                'billing_zip_code' => $request->billing_zip_code,
                'shipping_address_1' => $request->shipping_address_1 ?? $request->billing_address_1,
                'shipping_address_2' => $request->shipping_address_2 ?? $request->billing_address_2 ?? null,
                'shipping_city' => $request->shipping_city ?? $request->billing_city,
                'shipping_state' => $request->shipping_state ?? $request->billing_state,
                'shipping_country' => $request->shipping_country ?? $request->billing_country ?? null,
                'shipping_zip_code' => $request->shipping_zip_code ?? $request->billing_zip_code,
            ]);

            if (in_array($request->payment_method, ['online', 'bank'])) {
                Payment::create([
                    'payment_for' => 'order',
                    'invoice_id' => $invoice->id,
                    'payment_date' => now()->toDateString(),
                    'amount' => $total,
                    'payment_method' => $request->payment_method,
                    'note' => $order->notes,
                ]);
            }

            Cart::where('user_id', $userId)->delete();

            DB::commit();

            Balance::create([
                'source_type' => 'customer',
                'source_id' => $order->user_id,
                'balance' => $order->total - $request->paid_amount,
                'description' => 'Order Sale',
                'reference' => $order->order_number,
            ]);
            
            // Send Order Confirmation Email
            try {
                Mail::to($request->email)->send(new OrderConfirmation($order));
            } catch (\Exception $e) {
                \Log::error('Failed to send order confirmation email: ' . $e->getMessage());
                // Don't fail the order creation if email fails
            }
            
            return redirect()->route('order.success',$order->order_number);
        } catch (\Exception $e) {
            \Log::alert($e);
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function cancelOrder($id)
    {
        $order = Order::find($id);

        $order->status = 'cancelled';

        $order->save();

        return redirect()->back();
    }

    public function show($orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)->first();
        $pageTitle = $order->order_number;
        return view('ecommerce.orderdetails', compact('order', 'pageTitle'));
    }

    private function generateInvoiceNumber()
    {
        do {
            $number = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (Invoice::where('invoice_number', $number)->exists());
        return $number;
    }

    public function orderSuccess(string $orderId)
    {
        $pageTitle = $orderId;
        return view('ecommerce.order-success', compact('orderId', 'pageTitle'));
    }

    private function generateOrderNumber()
    {
        $today = now();
        $dateString = $today->format('dmy');
        
        $lastOrder = Order::latest()->first();
        if (!$lastOrder) {
            return "sko-{$dateString}01";
        }
        $serialNumber = str_pad($lastOrder->id + 1, 2, '0', STR_PAD_LEFT);
        
        return "sko-{$dateString}{$serialNumber}";
    }
}
