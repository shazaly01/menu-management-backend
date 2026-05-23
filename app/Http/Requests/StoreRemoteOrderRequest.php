<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule; // تمت إضافة هذا السطر لاستخدام قواعد التحقق المخصصة

class StoreRemoteOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // التحقق من أن رقم الطاولة موجود، وبنفس الوقت يتبع للقسم رقم 13 (قسم الطاولات)
            'table_product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where(function ($query) {
                    $query->where('category_id', 13);
                }),
            ],
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',

            // التحقق من أن الصنف موجود، وأنه ليس طاولة (يجب ألا يكون من القسم 13)
            'items.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where(function ($query) {
                    $query->where('category_id', '!=', 13);
                }),
            ],
            'items.*.quantity' => 'required|integer|min:1',
        ];
    }

    public function attributes(): array
    {
        return [
            'table_product_id' => 'رقم الطاولة المختارة',
            'items' => 'قائمة الطلبيات',
            'items.*.product_id' => 'الصنف المطلوب',
            'items.*.quantity' => 'الكمية المطلوبة',
        ];
    }
}
