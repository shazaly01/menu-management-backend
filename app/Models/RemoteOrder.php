<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RemoteOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'table_product_id',
        'status',
        'total_amount',
        'notes',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * العلاقة المحدثة: الطاولة هنا هي عبارة عن سجل في جدول الأصناف (Product)
     */
    public function table(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'table_product_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(RemoteOrderDetail::class);
    }
}
