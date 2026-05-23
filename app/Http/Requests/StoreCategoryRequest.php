<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
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
     * نقوم بالتحقق من هيكلية الطلب؛ إذا كان طلباً فردياً نقوم بلفه داخل مصفوفة المزامنة،
     * وإذا كان يحتوي بالفعل على مصفوفة الأقسام فلا نتدخل في البيانات لمنع تدميرها.
     */
    protected function prepareForValidation(): void
    {
        if ($this->isSingleRequest()) {
            $this->merge([
                'is_bulk' => false,
                'categories' => [
                    [
                        'id' => $this->input('id'),
                        'name' => $this->input('name'),
                        'is_active' => $this->input('is_active'),
                    ]
                ]
            ]);
        } else {
            // الطلب قادم كمصفوفة جماعية جاهزة من الكاشير
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
            'categories' => 'required|array|min:1',
            'categories.*.id' => 'required|integer',
            'categories.*.name' => 'required|string|max:255',
            'categories.*.is_active' => 'nullable|boolean',
        ];
    }

    /**
     * تخصيص أسماء الحقول باللغة العربية لرسائل الأخطاء.
     */
    public function attributes(): array
    {
        return [
            'categories' => 'قائمة الأقسام',
            'categories.*.id' => 'رقم المعرف للقسم',
            'categories.*.name' => 'اسم القسم',
            'categories.*.is_active' => 'حالة التفعيل',
        ];
    }

    /**
     * دالة مساعدة للتحقق مما إذا كان الطلب فردياً (يحتوي على الحقول مباشرة في الجذع).
     */
    private function isSingleRequest(): bool
    {
        // إذا كان الطلب لا يحتوي على مفتاح الـ categories ولديه حقول المعرف أو الاسم مباشرة في الـ Root
        return !$this->has('categories') && ($this->has('id') || $this->has('name'));
    }
}
