<?php

namespace App\Services;

use App\Models\Supplier;
use App\Models\PurchaseItem;
use App\Models\PurchaseBill;
use App\Models\SupplierPayment;
use App\Models\SupplierLedger;
use App\Models\PosItem;
use App\Models\OrderItem;
use App\Models\PurchaseReturnItem;
use App\Models\FinancialAccount;
use App\Models\ChartOfAccount;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\Balance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class PayOnSaleSettlementService
{
    /**
     * Get Pay-On-Sale (Consignment) summary for a supplier
     */
    public function getSupplierPayOnSaleSummary($supplierId, $startDate = null, $endDate = null)
    {
        if (!$supplierId) {
            return [
                'items' => [],
                'totals' => [
                    'purchased_qty' => 0,
                    'sold_qty' => 0,
                    'in_stock_qty' => 0,
                    'sold_cost_payable' => 0,
                    'total_paid' => 0,
                    'net_due_payable' => 0,
                ]
            ];
        }

        // Fetch purchase items from this supplier (with bill for net_due calculation)
        $purchaseItemsQuery = PurchaseItem::with(['product', 'variation', 'purchase', 'purchase.bill'])
            ->whereHas('purchase', function ($q) use ($supplierId) {
                $q->where('supplier_id', $supplierId);
            });

        if ($startDate) {
            $purchaseItemsQuery->whereHas('purchase', fn($q) => $q->whereDate('created_at', '>=', $startDate));
        }
        if ($endDate) {
            $purchaseItemsQuery->whereHas('purchase', fn($q) => $q->whereDate('created_at', '<=', $endDate));
        }

        $purchaseItems = $purchaseItemsQuery->get();

        // Group purchase items by product_id and variation_id, preserving individual purchase items in FIFO order
        $grouped = [];

        foreach ($purchaseItems as $item) {
            $key = $item->product_id . '_' . ($item->variation_id ?? '0');

            if (!isset($grouped[$key])) {
                $displayName = $item->product?->name ?? 'Unknown Product';
                if ($item->variation && !empty($item->variation->variation_value)) {
                    $displayName .= ' (' . $item->variation->variation_value . ')';
                }

                $grouped[$key] = [
                    'product_id'     => $item->product_id,
                    'variation_id'   => $item->variation_id,
                    'product_name'   => $displayName,
                    'sku'            => $item->variation?->sku ?? $item->product?->sku ?? 'N/A',
                    'style_number'   => $item->product?->style_number ?? 'N/A',
                    'purchased_qty'  => 0,
                    'total_cost'     => 0,
                    'latest_unit_cost' => $item->unit_price,
                    'purchase_items' => [],
                ];
            }

            $grouped[$key]['purchased_qty'] += $item->quantity;
            $grouped[$key]['total_cost']    += ($item->quantity * $item->unit_price);
            $grouped[$key]['latest_unit_cost'] = $item->unit_price;
            $grouped[$key]['purchase_items'][] = $item;
        }

        $items = [];
        $totalPurchasedQty    = 0;
        $totalSoldQty         = 0;
        $totalInStockQty      = 0;
        $totalSoldCostPayable = 0;
        $totalNetDuePayable   = 0;
        $totalAlreadyPaid     = 0;

        foreach ($grouped as $key => $data) {
            $productId   = $data['product_id'];
            $variationId = $data['variation_id'];

            // POS Sales
            $posSoldQuery = PosItem::where('product_id', $productId);
            if ($variationId) {
                $posSoldQuery->where('variation_id', $variationId);
            }
            $posSoldQty = (float) $posSoldQuery->sum('quantity');

            // Online Sales (excluding cancelled)
            $onlineSoldQuery = OrderItem::where('product_id', $productId)
                ->whereHas('order', fn($q) => $q->where('status', '!=', 'cancelled'));
            if ($variationId) {
                $onlineSoldQuery->where('variation_id', $variationId);
            }
            $onlineSoldQty = (float) $onlineSoldQuery->sum('quantity');

            // Deduct purchase returns for this product/variation
            $returnedQuery = PurchaseReturnItem::where('product_id', $productId);
            if ($variationId) {
                $returnedQuery->where('variation_id', $variationId);
            }
            $returnedQty = (float) $returnedQuery->sum('returned_qty');

            $netPurchasedQty = max(0, $data['purchased_qty'] - $returnedQty);

            $soldQty = $posSoldQty + $onlineSoldQty;
            // Cap sold quantity at net purchased quantity for consignment accounting accuracy
            $soldQty = min($soldQty, $netPurchasedQty);
            $inStockQty = max(0, $netPurchasedQty - $soldQty);

            $avgUnitCost = $data['purchased_qty'] > 0 ? ($data['total_cost'] / $data['purchased_qty']) : $data['latest_unit_cost'];

            // FIFO Item-Level Allocation across purchase items for this product
            $remSold              = $soldQty;
            $itemSoldCostPayable  = 0;
            $itemAlreadyPaid      = 0;
            $itemNetDue           = 0;

            foreach ($data['purchase_items'] as $pi) {
                if ($remSold <= 0) break;

                $piReturned = (float) PurchaseReturnItem::where('purchase_item_id', $pi->id)->sum('returned_qty');
                $piNetQty   = max(0, $pi->quantity - $piReturned);
                if ($piNetQty <= 0) continue;

                $allocSold = min($remSold, $piNetQty);
                $allocCost = round($allocSold * (float)$pi->unit_price, 2);

                $bill      = $pi->purchase?->bill;
                $billTotal = $bill ? (float)$bill->total_amount : 0;
                $billPaid  = $bill ? (float)$bill->paid_amount : 0;
                $piGross   = (float)($pi->quantity * $pi->unit_price);

                $piPaidShare = $billTotal > 0 ? round($billPaid * ($piGross / $billTotal), 2) : 0;

                $allocPaid   = min($piPaidShare, $allocCost);
                $allocNetDue = max(0, round($allocCost - $piPaidShare, 2));

                $itemSoldCostPayable += $allocCost;
                $itemAlreadyPaid     += $allocPaid;
                $itemNetDue          += $allocNetDue;

                $remSold -= $allocSold;
            }

            $items[] = [
                'group_key'         => $key,
                'product_name'      => $data['product_name'],
                'sku'               => $data['sku'],
                'style_number'      => $data['style_number'],
                'purchased_qty'     => $netPurchasedQty,
                'sold_qty'          => $soldQty,
                'in_stock_qty'      => $inStockQty,
                'unit_cost'         => round($avgUnitCost, 2),
                'sold_cost_payable' => round($itemSoldCostPayable, 2),
                'already_paid'      => round($itemAlreadyPaid, 2),
                'net_due'           => round($itemNetDue, 2),
            ];

            $totalPurchasedQty    += $netPurchasedQty;
            $totalSoldQty         += $soldQty;
            $totalInStockQty      += $inStockQty;
            $totalSoldCostPayable += round($itemSoldCostPayable, 2);
            $totalAlreadyPaid     += round($itemAlreadyPaid, 2);
            $totalNetDuePayable   += round($itemNetDue, 2);
        }

        return [
            'items'  => $items,
            'totals' => [
                'purchased_qty'     => $totalPurchasedQty,
                'sold_qty'          => $totalSoldQty,
                'in_stock_qty'      => $totalInStockQty,
                'sold_cost_payable' => round($totalSoldCostPayable, 2),
                'total_paid'        => round($totalAlreadyPaid, 2),
                'net_due_payable'   => round($totalNetDuePayable, 2),
            ]
        ];
    }

    /**
     * Process Pay-on-Sale Settlement Payment
     * Allocates payment to the specific purchase bills that contain the sold products.
     * Each sold product's payment amount targets the bill of the purchase it came from.
     */
    public function processPayOnSaleSettlement($supplierId, $payAmount, $paymentDate, $paymentMethod, $accountId, $reference, $note, $userId)
    {
        return DB::transaction(function () use ($supplierId, $payAmount, $paymentDate, $paymentMethod, $accountId, $reference, $note, $userId) {
            $financialAccount = FinancialAccount::findOrFail($accountId);
            $remainingPayment = (float) $payAmount;

            $createdPayments = [];
            $hasPaymentTypeColumn = Schema::hasColumn('supplier_payments', 'payment_type');

            // Build a bill → payable_amount map based on sold products per purchase
            // For each purchase bill, calculate how much of the sold cost came from that bill
            $billPayableMap = []; // bill_id => ['bill' => ..., 'payable' => float]

            // Get all purchase items for this supplier with their purchase bills
            $purchaseItems = PurchaseItem::with(['purchase.bill', 'product', 'variation'])
                ->whereHas('purchase', fn($q) => $q->where('supplier_id', $supplierId))
                ->get();

            foreach ($purchaseItems as $pItem) {
                $bill = $pItem->purchase?->bill ?? null;
                if (!$bill || (float) $bill->due_amount <= 0) continue;

                $productId   = $pItem->product_id;
                $variationId = $pItem->variation_id;

                // How many of this product/variation were sold?
                $posSold = PosItem::where('product_id', $productId)
                    ->when($variationId, fn($q) => $q->where('variation_id', $variationId))
                    ->sum('quantity');
                $onlineSold = OrderItem::where('product_id', $productId)
                    ->when($variationId, fn($q) => $q->where('variation_id', $variationId))
                    ->whereHas('order', fn($q) => $q->where('status', '!=', 'cancelled'))
                    ->sum('quantity');

                $totalSold = min((float)($posSold + $onlineSold), (float)$pItem->quantity);
                if ($totalSold <= 0) continue;

                $unitCost = (float) $pItem->unit_price;
                $soldCost = round($totalSold * $unitCost, 2);

                $billId = $bill->id;
                if (!isset($billPayableMap[$billId])) {
                    $billPayableMap[$billId] = ['bill' => $bill, 'payable' => 0.0];
                }
                $billPayableMap[$billId]['payable'] += $soldCost;
            }

            // Sort by bill id DESCENDING: newest purchase bill first.
            // This ensures payment targets the most recent purchase first,
            // matching the user's intent of paying for what they just purchased.
            krsort($billPayableMap);

            // Distribute payment proportionally to bills with sold-product payable amounts
            if (!empty($billPayableMap)) {
                foreach ($billPayableMap as $billId => $entry) {
                    if ($remainingPayment <= 0) break;

                    // Refresh bill from DB to get the latest due_amount (avoids stale cached values)
                    $bill    = PurchaseBill::find($billId);
                    if (!$bill || (float) $bill->due_amount <= 0) continue;
                    $billDue = (float) $bill->due_amount;

                    // Allocate minimum of: remaining payment, this bill's current due, this bill's sold-product payable
                    $allocAmount = min($remainingPayment, $billDue, $entry['payable']);
                    if ($allocAmount <= 0) continue;

                    $paymentData = [
                        'supplier_id'      => $supplierId,
                        'purchase_bill_id' => $bill->id,
                        'amount'           => $allocAmount,
                        'payment_date'     => $paymentDate,
                        'payment_method'   => $paymentMethod,
                        'account_id'       => $accountId,
                        'reference'        => $reference,
                        'note'             => $note
                            ? $note . " (Pay-on-Sale for Bill #{$bill->bill_number})"
                            : "Pay-on-Sale Settlement for Bill #{$bill->bill_number}",
                        'created_by'       => $userId,
                    ];

                    if ($hasPaymentTypeColumn) {
                        $paymentData['payment_type'] = 'pay_on_sale';
                    }

                    $payment = SupplierPayment::create($paymentData);

                    // Update PurchaseBill due/paid/status
                    $bill->paid_amount += $allocAmount;
                    $bill->due_amount  -= $allocAmount;
                    if ($bill->due_amount <= 0) {
                        $bill->status    = 'paid';
                        $bill->due_amount = 0;
                    } elseif ($bill->paid_amount > 0) {
                        $bill->status = 'partial';
                    }
                    $bill->save();

                    $this->recordPaymentAccountingDetails($payment, $financialAccount, $supplierId, $allocAmount, $paymentDate, $reference, $userId);
                    $createdPayments[] = $payment;

                    $remainingPayment -= $allocAmount;
                }
            }

            // Any remainder (no matching sold-product bill found) goes as advance
            if ($remainingPayment > 0) {
                // Fallback: apply remaining to oldest due bill
                $fallbackBills = PurchaseBill::where('supplier_id', $supplierId)
                    ->where('due_amount', '>', 0)
                    ->orderBy('created_at', 'asc')
                    ->get();

                foreach ($fallbackBills as $bill) {
                    if ($remainingPayment <= 0) break;
                    $allocAmount = min($remainingPayment, (float) $bill->due_amount);
                    if ($allocAmount <= 0) continue;

                    $paymentData = [
                        'supplier_id'      => $supplierId,
                        'purchase_bill_id' => $bill->id,
                        'amount'           => $allocAmount,
                        'payment_date'     => $paymentDate,
                        'payment_method'   => $paymentMethod,
                        'account_id'       => $accountId,
                        'reference'        => $reference,
                        'note'             => ($note ? $note . ' ' : '') . "(Pay-on-Sale Remainder for Bill #{$bill->bill_number})",
                        'created_by'       => $userId,
                    ];
                    if ($hasPaymentTypeColumn) {
                        $paymentData['payment_type'] = 'pay_on_sale';
                    }

                    $payment = SupplierPayment::create($paymentData);
                    $bill->paid_amount += $allocAmount;
                    $bill->due_amount  -= $allocAmount;
                    $bill->status       = $bill->due_amount <= 0 ? 'paid' : 'partial';
                    if ($bill->due_amount < 0) $bill->due_amount = 0;
                    $bill->save();

                    $this->recordPaymentAccountingDetails($payment, $financialAccount, $supplierId, $allocAmount, $paymentDate, $reference, $userId);
                    $createdPayments[] = $payment;
                    $remainingPayment -= $allocAmount;
                }
            }

            return $createdPayments;
        });
    }

    /**
     * Record Ledger, Balance, and Auto Journal entries for supplier payment
     */
    private function recordPaymentAccountingDetails($payment, $financialAccount, $supplierId, $amount, $paymentDate, $reference, $userId)
    {
        $supplier = Supplier::find($supplierId);
        $description = "Pay-on-Sale Settlement Payment #" . $payment->id . " to " . ($supplier?->name ?? 'Supplier');

        // 1. Supplier Ledger
        SupplierLedger::recordTransaction(
            $supplierId,
            'debit',
            $amount,
            $description,
            $paymentDate,
            $payment
        );

        // 2. Supplier Balance
        $balance = Balance::where('source_type', 'supplier')->where('source_id', $supplierId)->first();
        if ($balance) {
            $balance->balance -= $amount;
            $balance->save();
        } else {
            Balance::create([
                'source_type' => 'supplier',
                'source_id' => $supplierId,
                'balance' => -$amount,
                'description' => 'Supplier Payment',
            ]);
        }

        // 3. Financial Account Balance
        $financialAccount->balance -= $amount;
        $financialAccount->save();

        // 4. Auto Journal Entry
        $paymentChartAccountId = $financialAccount->account_id;
        $payableChartAccount = ChartOfAccount::where('name', 'like', '%payable%')
            ->orWhere('name', 'like', '%creditor%')
            ->first();

        if ($paymentChartAccountId && $payableChartAccount) {
            $voucherNo = 'POS-PAY-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT);
            while (Journal::where('voucher_no', $voucherNo)->exists()) {
                $voucherNo = 'POS-PAY-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT) . '-' . rand(10, 99);
            }

            $journal = Journal::create([
                'voucher_no'     => $voucherNo,
                'entry_date'     => $paymentDate,
                'type'           => 'Payment',
                'description'    => 'Auto: Pay-on-Sale Settlement #' . $payment->id . ' to ' . ($supplier?->name ?? 'Supplier'),
                'supplier_id'    => $supplierId,
                'branch_id'      => null,
                'voucher_amount' => $amount,
                'paid_amount'    => $amount,
                'reference'      => $reference,
                'created_by'     => $userId,
                'updated_by'     => $userId,
            ]);

            // DEBIT: Accounts Payable
            JournalEntry::create([
                'journal_id'           => $journal->id,
                'chart_of_account_id'  => $payableChartAccount->id,
                'financial_account_id' => null,
                'debit'                => $amount,
                'credit'               => 0,
                'memo'                 => 'Pay-on-Sale Settlement to ' . ($supplier?->name ?? 'Supplier'),
                'created_by'           => $userId,
                'updated_by'           => $userId,
            ]);

            // CREDIT: Bank/Cash
            JournalEntry::create([
                'journal_id'           => $journal->id,
                'chart_of_account_id'  => $paymentChartAccountId,
                'financial_account_id' => $financialAccount->id,
                'debit'                => 0,
                'credit'               => $amount,
                'memo'                 => 'Payment via ' . $financialAccount->provider_name,
                'created_by'           => $userId,
                'updated_by'           => $userId,
            ]);
        }
    }
}
