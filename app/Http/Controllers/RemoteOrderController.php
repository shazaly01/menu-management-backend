<?php

namespace App\Http\Controllers;

use App\Models\RemoteOrder;
use App\Models\Product;
use App\Models\User; // استدعاء موديل المستخدم صراحة
use App\Http\Requests\StoreRemoteOrderRequest;
use App\Http\Requests\UpdateRemoteOrderStatusRequest;
use App\Http\Resources\RemoteOrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class RemoteOrderController extends Controller
{
    /**
     * جلب قائمة الطلبات الوسيطة
     * يستخدم من قبل الكاشير لمراقبة الطلبات القادمة، أو الويتر لمراجعة طلباته
     */
    public function index(): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = Auth::user();

        // الويتر يستعرض طلباته المرسلة فقط، بينما الكاشير والإدارة يستعرضون كل شيء
        $ordersQuery = RemoteOrder::with(['user', 'table', 'details.product'])
            ->latest();

        if ($user && $user->hasRole('Waiter')) {
            $ordersQuery->where('user_id', $user->id);
        }

        $orders = $ordersQuery->get();

        return RemoteOrderResource::collection($orders);
    }

    /**
     * إنشاء طلب وسيط جديد من قبل الويتر عبر هاتف المحمول
     */
    public function store(StoreRemoteOrderRequest $request): JsonResponse
    {
        // استخدام Transaction لضمان حفظ الطلب وتفاصيله معاً أو التراجع في حال حدوث خطأ
        $order = DB::transaction(function () use ($request) {

            // 1. إنشاء رأس الطلب (Order Header) بحالة ابتدائية معلقة (pending)
            $remoteOrder = RemoteOrder::create([
                'user_id' => Auth::id(),
                'table_product_id' => $request->table_product_id,
                'status' => 'pending',
                'total_amount' => 0.00, // سيتم تحديثه تلقائياً بعد حساب تفاصيل الأصناف
                'notes' => $request->notes,
            ]);

            $totalAmount = 0;

            // 2. معالجة وتخزين تفاصيل الأصناف (Order Details)
            foreach ($request->items as $item) {
                // جلب الصنف من قاعدة البيانات لضمان قراءة السعر الحقيقي وحماية النظام من التلاعب بأسعار الطلبات
                $product = Product::findOrFail($item['product_id']);

                $unitPrice = $product->price;
                $quantity = $item['quantity'];
                $totalPrice = $unitPrice * $quantity;

                // تجميع الإجمالي الكلي للطلب
                $totalAmount += $totalPrice;

                // حفظ السجل في جدول تفاصيل الطلبات الوسيطة
                $remoteOrder->details()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                ]);
            }

            // 3. تحديث الإجمالي الكلي الفعلي للطلب الرئيسي
            $remoteOrder->update([
                'total_amount' => $totalAmount
            ]);

            return $remoteOrder;
        });

        // شحن البيانات الراجعة عبر الـ Resource وتحميل العلاقات المطلوبة لعرضها فوراً للويتر
        return response()->json([
            'message' => 'تم إرسال الطلب بنجاح إلى الكاشير',
            'data' => new RemoteOrderResource($order->load(['user', 'table', 'details.product']))
        ], 201);
    }

    /**
     * عرض تفاصيل طلب وسيط محدد بناءً على معرّفه
     */
    public function show(RemoteOrder $remoteOrder): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        // حماية مضافة: الويتر لا يمكنه استعراض طلب ينتمي لويتر آخر
        if ($user && $user->hasRole('Waiter') && $remoteOrder->user_id !== $user->id) {
            return response()->json(['message' => 'غير مصرح لك باستعراض هذا الطلب.'], 403);
        }

        return response()->json(
            new RemoteOrderResource($remoteOrder->load(['user', 'table', 'details.product']))
        );
    }

    /**
     * تحديث حالة الطلب من قبل الكاشير (تأكيد السحب confirmed أو إلغاء الطلب cancelled)
     */
    public function updateStatus(UpdateRemoteOrderStatusRequest $request, RemoteOrder $remoteOrder): JsonResponse
    {
        // حظر تحديث الطلبات التي تم البت في حالتها سابقاً منعا للتكرار أو التداخل
        if ($remoteOrder->status !== 'pending') {
            return response()->json([
                'message' => 'لا يمكن تعديل حالة هذا الطلب لأنه معالج مسبقاً وحالته الحالية هي: ' . $remoteOrder->status
            ], 422);
        }

        // تحديث الحالة بحسب ما أرسله جهاز الكاشير (confirmed / cancelled)
        $remoteOrder->update([
            'status' => $request->status
        ]);

        $message = $request->status === 'confirmed'
            ? 'تم تأكيد الطلب وسحبه بنجاح إلى شاشة الكاشير المحلي'
            : 'تم إلغاء الطلب بنجاح';

        return response()->json([
            'message' => $message,
            'data' => new RemoteOrderResource($remoteOrder->load(['user', 'table', 'details.product']))
        ]);
    }
}
