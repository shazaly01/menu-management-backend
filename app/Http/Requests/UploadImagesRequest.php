<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadImagesRequest extends FormRequest
{
    /**
     * تحديد صلاحية الوصول للطلب.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * قواعد التحقق من الصور لضمان أمان السيرفر وحمايته من الملفات الخبيثة.
     * ندعم استقبال المفتاحين 'images' كمصفوفة أو 'image' كملف منفرد بشكل ديناميكي ومباشر.
     */
  public function rules(): array
{
    return [
        // جعل الحقل يستقبل المصفوفة بشكل مرن دون إجبار صارم يعطل بقية الصور
        'images' => 'nullable|array',
        'images.*' => 'file|image|mimes:jpeg,jpg,png,webp,JPEG,JPG,PNG,WEBP|max:5120', // رفع الحد لـ 5 ميجا ودعم الحروف الكبيرة والصغيرة للامتداد

        'image' => 'nullable|file|image|mimes:jpeg,jpg,png,webp,JPEG,JPG,PNG,WEBP|max:5120',
    ];
}

    /**
     * تخصيص الأسماء باللغة العربية.
     */
    public function attributes(): array
    {
        return [
            'images' => 'ملفات الصور المرفوعة',
            'images.*' => 'ملف الصورة',
            'image' => 'ملف الصورة المنفرد',
        ];
    }
}
