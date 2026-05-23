<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * تحديد صلاحية الوصول للطلب.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * تجهيز البيانات قبل التحقق منها.
     * نقوم بفحص ما إذا كان الطلب جماعياً أم فردياً لتوحيد البنية البرمجية.
     */
    protected function prepareForValidation(): void
    {
        if ($this->isSingleRequest()) {
            $this->merge([
                'is_bulk' => false,
                'products' => [
                    [
                        'id' => $this->input('id'),
                        'category_id' => $this->input('category_id'),
                        'name' => $this->input('name'),
                        'description' => $this->input('description'),
                        'price' => $this->input('price'),
                        'is_active' => $this->input('is_active'),
                    ]
                ]
            ]);
        } else {
            $this->merge([
                'is_bulk' => true
            ]);
        }
    }

    /**
     * قواعد التحقق من البيانات.
     */
    public function rules(): array
    {
        return [
            'is_bulk' => 'required|boolean',
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|integer',
            'products.*.category_id' => 'required|integer|exists:categories,id',
            'products.*.name' => 'required|string|max:255',
            'products.*.description' => 'nullable|string|max:1000',
            'products.*.price' => 'required|numeric|min:0',
            'products.*.is_active' => 'nullable|boolean',
        ];
    }

    /**
     * تخصيص أسماء الحقول باللغة العربية لرسائل الأخطاء.
     */
    public function attributes(): array
    {
        return [
            'products' => 'قائمة الأصناف',
            'products.*.id' => 'رقم المعرف للصنف',
            'products.*.category_id' => 'القسم التابع له',
            'products.*.name' => 'اسم الوجبة/الصنف',
            'products.*.description' => 'الوصف',
            'products.*.price' => 'السعر',
            'products.*.is_active' => 'حالة التفعيل',
        ];
    }

    /**
     * دالة مساعدة للتحقق مما إذا كان الطلب فردياً (يحتوي على الحقول مباشرة في الجذع).
     */
    private function isSingleRequest(): bool
    {
        return !$this->has('products') && ($this->has('id') || $this->has('name'));
    }
}
