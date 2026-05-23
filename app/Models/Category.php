<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    // إعلام الموديل بأن حقل المعرف يتم إدخاله يدوياً وليس ترقيماً تلقائياً
    public $incrementing = false;

    // تحديد نوع المعرف كـ Integer
    protected $keyType = 'int';

    protected $fillable = [
        'id', // السماح بإدخال معرف القسم يدوياً لمطابقة الكاشير المحلي
        'name',
        'is_active',
    ];

    /**
     * العلاقة مع الأصناف
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
