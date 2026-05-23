<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RemoteOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'total_amount' => (float) $this->total_amount,
            'notes' => $this->notes,
            'created_at' => $this->created_at->toDateTimeString(),

            // بيانات الموظف (الويتر) الذي أنشأ الطلب
            'waiter' => [
                'id' => $this->user_id,
                'full_name' => $this->user?->full_name,
                'username' => $this->user?->username,
            ],

            // بيانات الطاولة (المستخرجة من جدول المنتجات بناءً على تعديلنا السابق)
            'table' => [
                'id' => $this->table_product_id,
                'name' => $this->table?->name,
            ],

            // تفاصيل الأصناف والكميات داخل الفاتورة
            'items' => $this->details->map(function ($detail) {
                return [
                    'id' => $detail->id,
                    'product_id' => $detail->product_id, // يطابق رقم الصنف بالكاشير
                    'product_name' => $detail->product?->name,
                    'quantity' => (int) $detail->quantity,
                    'unit_price' => (float) $detail->unit_price,
                    'total_price' => (float) $detail->total_price,
                ];
            }),
        ];
    }
}
