<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

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
     * قواعد التحقق المرنة والشاملة التي تقهر مشكلات الامتداد والحجم
     */
    public function rules(): array
    {
        return [
            'images'     => 'nullable|array',
            'images.*'   => 'file|image|mimes:jpeg,jpg,png,webp,JPEG,JPG,PNG,WEBP|max:5120',

            'images[]'   => 'nullable|array',
            'images[].*' => 'file|image|mimes:jpeg,jpg,png,webp,JPEG,JPG,PNG,WEBP|max:5120',

            'image'      => 'nullable|file|image|mimes:jpeg,jpg,png,webp,JPEG,JPG,PNG,WEBP|max:5120',
        ];
    }

    /**
     * تخصيص الأسماء باللغة العربية.
     */
    public function attributes(): array
    {
        return [
            'images'     => 'ملفات الصور المرفوعة',
            'images.*'   => 'ملف الصورة',
            'images[]'   => 'مصفوفة الصور',
            'images[].*' => 'ملف الصورة المرفق',
            'image'      => 'ملف الصورة المنفرد',
        ];
    }

    /**
     * تخصيص الاستجابة عند فشل التحقق لإرسال نص الخطأ الدقيق للكاشير
     */
    protected function failedValidation(Validator $validator)
    {
        // جلب أول خطأ تسبب في المشكلة وصياغته بشكل نصي مبسط
        $errors = $validator->errors()->all();
        $customMessage = "فشل التحقق بالسيرفر: " . implode(' | ', $errors);

        throw new HttpResponseException(
            response()->json([
                'message' => $customMessage,
                'errors'  => $validator->errors()
            ], 422)
        );
    }
}
