<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
{
    public function request()
    {
        $pageTitle = 'Request Service';
        $services = Product::where('type', 'service')->get();
        return view('ecommerce.requestservice', compact('pageTitle', 'services'));
    }


    public function submitRequest(Request $request)
    {
        $validated = $request->validate([
            'serviceType' => 'required|exists:products,id',
            'description' => 'required|string',
            'preferred_date' => 'required|date',
            'preferred_time' => 'required',
            'phone' => 'required|string',
            'billing_address_1' => 'required|string',
            'billing_city' => 'required|string',
            'billing_state' => 'required|string',
            'billing_zip_code' => 'required|string',
            'additional_notes' => 'nullable|string',
        ]);

        $product = Product::find($validated['serviceType']);
        $price = ($product->discount && $product->discount > 0) ? $product->discount : $product->price;

        try {
            $service = new Service();
            $service->service_number = 'SRV-' . date('YmdHis') . rand(1000, 9999);
            $service->user_id = optional(Auth::user())->id;
            $service->product_service_id = $validated['serviceType'];
            $service->service_type = 'other'; // or map from product if needed
            $service->requested_date = $validated['preferred_date'];
            $service->preferred_time = $validated['preferred_time'];
            $service->phone = $validated['phone'];
            $service->service_notes = $validated['description'];
            $service->admin_notes = $validated['additional_notes'] ?? null;
            $service->status = 'pending';
            $service->service_fee = $price;
            $service->travel_fee = 0;
            $service->discount = 0;
            $service->total = 0;
            $service->save();

            // Create Invoice for the service
           
            $invTemplate = \App\Models\InvoiceTemplate::where('is_default', 1)->first();

            $customer = User::find($service->user_id)->customer;
            $customer_id = $customer->id;
            do {
                $invoiceNumber = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            } while (\App\Models\Invoice::where('invoice_number', $invoiceNumber)->exists());
            $invoice = \App\Models\Invoice::create([
                'customer_id' => $customer_id,
                'template_id' => $invTemplate ? $invTemplate->id : null,
                'operated_by' => Auth::user()->id,
                'issue_date' => $service->requested_date,
                'due_date' => $service->requested_date,
                'send_date' => $service->requested_date,
                'subtotal' => $price,
                'total_amount' => $price,
                'discount_apply' => 0,
                'paid_amount' => 0,
                'due_amount' => $price,
                'status' => 'unpaid',
                'note' => $service->service_notes,
                'footer_text' => null,
                'created_by' => Auth::user()->id,
                'invoice_number' => $invoiceNumber,
            ]);
            \App\Models\InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => $price,
                'total_price' => $price,
            ]);
            \App\Models\InvoiceAddress::create([
                'invoice_id' => $invoice->id,
                'billing_address_1' => $validated['billing_address_1'],
                'billing_city' => $validated['billing_city'],
                'billing_state' => $validated['billing_state'],
                'billing_zip_code' => $validated['billing_zip_code'],
            ]);
            $service->invoice_id = $invoice->id;
            $service->save();

            return redirect()->route('profile.edit')->with('success', 'Service request submitted successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function show($service_number)
    {
        $service = Service::where('service_number',$service_number)->first();
        $pageTitle = $service->service_number;

        return view('ecommerce.requestedservicedetails',compact('service','pageTitle'));
    }
}
