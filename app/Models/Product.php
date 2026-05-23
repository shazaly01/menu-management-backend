<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    // إعلام الموديل بأن حقل المعرف ليس ترقيماً تلقائياً قادماً من السيرفر
    public $incrementing = false;

    // تحديد نوع المعرف كـ Integer
    protected $keyType = 'int';

    protected $fillable = [
        'id', // السماح بإدخال الـ ID يدوياً لمطابقة الكاشير المحلي
        'category_id',
        'name',
        'description',
        'price',
        'image',
        'is_active',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function orderDetails(): HasMany
    {
        return $this->hasMany(RemoteOrderDetail::class);
    }
}
