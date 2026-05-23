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
        // دعم استقبال المفتاحين 'images' أو 'images[]' بشكل تبادلي آمن
        'images'   => 'nullable|array',
        'images.*' => 'file|image|mimes:jpeg,jpg,png,webp,JPEG,JPG,PNG,WEBP|max:5120',

        'images[]'   => 'nullable|array',
        'images[].*' => 'file|image|mimes:jpeg,jpg,png,webp,JPEG,JPG,PNG,WEBP|max:5120',

        'image'    => 'nullable|file|image|mimes:jpeg,jpg,png,webp,JPEG,JPG,PNG,WEBP|max:5120',
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
