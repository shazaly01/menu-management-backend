<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RemoteOrderDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'remote_order_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price',
    ];

    /**
     * العلاقة مع الطلب الرئيسي
     */
    public function remoteOrder(): BelongsTo
    {
        return $this->belongsTo(RemoteOrder::class);
    }

    /**
     * العلاقة مع الأصناف: التفاصيل تنتمي لصنف محدد من المنيو
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
