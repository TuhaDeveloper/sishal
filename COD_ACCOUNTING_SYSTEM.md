# COD Percentage Accounting System

## Overview
The COD (Cash on Delivery) percentage is applied **only to the accounting system (Balance records)**, not to the invoice total that customers see.

## How It Works

### 1. Order Creation (Ecommerce OrderController)

**When a COD order is created:**

```php
// Step 1: Calculate invoice total (no COD discount)
$total = $subtotal + $tax + $shipping - $couponDiscount;

// Step 2: Calculate COD discount (for accounting only)
$codDiscount = round($total * $codPercentage, 2);

// Step 3: Create invoice with FULL amount
$invoice->total_amount = $total; // Customer sees full amount

// Step 4: Create Balance with COD discount applied
$accountingBalance = $total - $codDiscount;
Balance::create(['balance' => $accountingBalance]);
```

**Example:**
- Invoice Total: 1,000.00
- COD Percentage: 2%
- COD Discount: 20.00
- **Invoice shows:** 1,000.00 (customer pays this)
- **Balance created:** 980.00 (what we expect to receive)

### 2. Payment Processing (ERP OrderController)

**When payment is received for COD order:**

```php
// Step 1: Calculate COD discount again
$codDiscount = round($invoice->total_amount * $codPercentage, 2);

// Step 2: Calculate net payment (what we actually receive)
$netPaymentAmount = $paymentAmount - $codDiscount;

// Step 3: Update balance
$balance->balance -= $netPaymentAmount;
```

**Example:**
- Customer pays: 1,000.00 (full invoice)
- COD Discount: 20.00
- Net Payment: 980.00
- Balance before: 980.00
- Balance after: 980.00 - 980.00 = 0.00 ✓

## Test Results

All test scenarios passed:

✅ **Test 1:** Invoice 1,000 with 2% COD
- Discount: 20.00
- Balance: 980.00

✅ **Test 2:** Payment received
- Customer pays: 1,000.00
- Net received: 980.00
- Balance cleared: 0.00

✅ **Test 3:** Various COD percentages (1.5%, 2%, 2.5%, 3%, 5%)
- All calculations correct

✅ **Test 4:** Multiple invoice amounts
- All test cases passed

## Key Points

1. **Invoice Total:** Always shows full amount (no COD discount)
2. **Customer Payment:** Customer pays full invoice amount
3. **Accounting Balance:** Reflects net amount after COD charges
4. **COD Discount:** Only applied to Balance records, never to invoice

## Code Locations

- **Order Creation:** `app/Http/Controllers/Ecommerce/OrderController.php` (lines 273-514)
- **Payment Processing:** `app/Http/Controllers/Erp/OrderController.php` (lines 483-537)
- **Logging:** Both locations log COD calculations for debugging

## Verification

Run the test script to verify:
```bash
php test_cod_calculation.php
```

## Summary

The system correctly:
- ✅ Shows full invoice amount to customers
- ✅ Applies COD percentage to accounting only
- ✅ Calculates balance correctly when orders are created
- ✅ Updates balance correctly when payments are received
- ✅ Handles various COD percentages correctly

