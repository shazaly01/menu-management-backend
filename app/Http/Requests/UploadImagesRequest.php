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
            'images' => 'required_without:image|array|min:1',
            'images.*' => 'file|image|mimes:jpeg,png,jpg,webp|max:2048', // الحد الأقصى 2 ميجابايت لكل صورة

            'image' => 'required_without:images|file|image|mimes:jpeg,png,jpg,webp|max:2048',
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
