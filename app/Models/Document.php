<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage; // تأكد من استيراد الـ Storage
use Illuminate\Support\Facades\URL;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'file_path',
        'documentable_id',
        'documentable_type',
    ];

    // --- 1. الإضافة هنا: نخبر لارافيل بإرجاع حقل url دائماً مع البيانات ---
    protected $appends = ['url'];

    // --- 2. الإضافة هنا: دالة بناء الرابط (Accessor) ---
    /**
     * Get the full URL for the document file.
     */
   public function getUrlAttribute(): ?string
    {
        if ($this->file_path) {
            // 2. التعديل هنا: توليد رابط موقع صالح لمدة 60 دقيقة
            // هذا الرابط يحتوي على توقيع أمني، إذا تم تغييره بحرف واحد سيفشل الطلب
            return URL::signedRoute(
                'documents.download',
                ['document' => $this->id],
                now()->addMinutes(60)
            );
        }

        return null;
    }


public function documentable()
{
    return $this->morphTo();
}
}
