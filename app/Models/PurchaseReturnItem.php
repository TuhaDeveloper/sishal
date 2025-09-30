<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturnItem extends Model
{
    protected $fillable = [
        'purchase_return_id',
        'purchase_item_id',
        'product_id',
        'returned_qty',
        'unit_price',
        'total_price',
        'reason',
        'return_from_type',
        'return_from_id'
    ];
    
    /**
     * Get the purchase return that this item belongs to
     */
    public function purchaseReturn()
    {
        return $this->belongsTo(PurchaseReturn::class, 'purchase_return_id');
    }

    /**
     * Get the original purchase item that this return item relates to
     */
    public function purchaseItem()
    {
        return $this->belongsTo(PurchaseItem::class, 'purchase_item_id');
    }

    /**
     * Get the product that is being returned
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the branch if return_from_type is 'branch'
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'return_from_id');
    }

    /**
     * Get the warehouse if return_from_type is 'warehouse'
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'return_from_id');
    }

    /**
     * Get the employee if return_from_type is 'employee'
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'return_from_id');
    }

    /**
     * Get the location (branch/warehouse/employee) where the item is being returned from
     */
    public function returnFrom()
    {
        switch ($this->return_from_type) {
            case 'branch':
                return $this->belongsTo(Branch::class, 'return_from_id');
            case 'warehouse':
                return $this->belongsTo(Warehouse::class, 'return_from_id');
            case 'employee':
                return $this->belongsTo(Employee::class, 'return_from_id');
            default:
                return $this->belongsTo(Branch::class, 'return_from_id'); // Fallback to prevent null
        }
    }

    /**
     * Scope to filter by return from type
     */
    public function scopeByReturnFromType($query, $type)
    {
        return $query->where('return_from_type', $type);
    }

    /**
     * Scope to filter by product
     */
    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }
}
