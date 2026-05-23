<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\UploadImagesRequest;

class MenuSynchronizationController extends Controller
{
    /**
     * جلب كافة الأقسام النشطة (متاح للويتر لتحديث التطبيق)
     */
    public function getCategories(): JsonResponse
    {
        $categories = Category::where('is_active', true)->get();
        return response()->json(CategoryResource::collection($categories));
    }

    /**
     * جلب كافة الأصناف والوجبات النشطة
     */
    public function getProducts(): JsonResponse
    {
        $products = Product::where('is_active', true)
            ->with('category')
            ->get();

        return response()->json(ProductResource::collection($products));
    }

    /**
     * استقبال ومزامنة الأقسام القادمة من الكاشير المحلي (فردي وجماعي)
     */
    public function syncCategory(StoreCategoryRequest $request): JsonResponse
    {
        $categoriesData = $request->input('categories');
        $isBulk = $request->input('is_bulk');

        DB::transaction(function () use ($categoriesData, $isBulk) {
            $processedIds = [];

            foreach ($categoriesData as $data) {
                Category::updateOrCreate(
                    ['id' => $data['id']],
                    [
                        'name' => $data['name'],
                        'is_active' => $data['is_active'] ?? true,
                    ]
                );
                $processedIds[] = $data['id'];
            }

            // إذا كانت المزامنة جماعية، نحذف مرنًا كل الأقسام التي لم يرسلها الكاشير
            if ($isBulk) {
                Category::whereNotIn('id', $processedIds)->delete();
            }
        });

        return response()->json([
            'message' => $isBulk ? 'تمت المزامنة الجماعية للأقسام بنجاح' : 'تمت مزامنة القسم بنجاح'
        ], 200);
    }

    /**
     * استقبال ومزامنة الأصناف والوجبات القادمة من الكاشير المحلي (فردي وجماعي)
     * معالجة نصية فقط بدون ملفات صور لضمان خفة الطلب وسرعته
     */
    public function syncProduct(StoreProductRequest $request): JsonResponse
    {
        $productsData = $request->input('products');
        $isBulk = $request->input('is_bulk');

        DB::transaction(function () use ($productsData, $isBulk) {
            $processedIds = [];

            foreach ($productsData as $data) {
                // نبحث عن الصنف الحالي للحفاظ على مسار الصورة الحالي ولا نقوم بتصفيره
                $existingProduct = Product::find($data['id']);
                $currentImage = $existingProduct ? $existingProduct->image : null;

                Product::updateOrCreate(
                    ['id' => $data['id']],
                    [
                        'category_id' => $data['category_id'],
                        'name' => $data['name'],
                        'description' => $data['description'] ?? null,
                        'price' => $data['price'],
                        'image' => $currentImage, // الحفاظ على الصورة القديمة حتى يتم تحديثها عبر رابط الصور
                        'is_active' => $data['is_active'] ?? true,
                    ]
                );
                $processedIds[] = $data['id'];
            }

            // إذا كانت المزامنة جماعية، نحذف مرنًا كل الأصناف التي لم يرسلها الكاشير
            if ($isBulk) {
                Product::whereNotIn('id', $processedIds)->delete();
            }
        });

        return response()->json([
            'message' => $isBulk ? 'تمت المزامنة الجماعية للأصناف بنجاح' : 'تمت مزامنة الصنف بنجاح'
        ], 200);
    }

    /**
     * حذف قسم (Soft Delete) عند حذفه يدويًا منفردًا من الكاشير
     */
    public function destroyCategory($id): JsonResponse
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return response()->json([
            'message' => 'تم نقل القسم إلى سلة المحذوفات بنجاح في السيرفر'
        ]);
    }

    /**
     * حذف صنف (Soft Delete) عند حذفه يدويًا منفردًا من الكاشير
     */
    public function destroyProduct($id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json([
            'message' => 'تم نقل الصنف إلى سلة المحذوفات بنجاح في السيرفر'
        ]);
    }




    /**
     * استقبال ورفع صور الأصناف (فردي وجماعي) بربط تلقائي بناءً على اسم الملف الأصلي
     */
    public function uploadProductImages(UploadImagesRequest $request): JsonResponse
    {
        // جلب الصور التي تم التحقق من صحتها وأمانها
        $files = $request->file('images') ?? $request->file('image');

        // تحويل الملف المفرد إلى مصفوفة لتوحيد المعالجة بالتكرار (Loop)
        $filesArray = is_array($files) ? $files : [$files];

        $synchronizedImages = [];
        $failedImages = [];

        DB::transaction(function () use ($filesArray, &$synchronizedImages, &$failedImages) {
            foreach ($filesArray as $file) {
                // 1. جلب اسم الملف الأصلي المرفوع من الكاشير (مثال: "product_15.jpg" أو "15.png")
                $originalName = $file->getClientOriginalName();

                // 2. استخراج الأرقام فقط من اسم الملف باستخدام التعبيرات النمطية لتكون هي الـ Product ID
                preg_match('/\d+/', $originalName, $matches);
                $productId = $matches[0] ?? null;

                // إذا لم نجد رقماً في اسم الملف، نضعه في مصفوفة الفشل وننتقل للملف التالي
                if (!$productId) {
                    $failedImages[] = [
                        'file_name' => $originalName,
                        'reason' => 'اسم الملف لا يحتوي على رقم معرف الصنف'
                    ];
                    continue;
                }

                // 3. البحث عن الصنف المطابق للرقم المستخرج
                $product = Product::find($productId);

                if (!$product) {
                    $failedImages[] = [
                        'file_name' => $originalName,
                        'reason' => "الصنف رقم ({$productId}) غير موجود في قاعدة البيانات النصية، يرجى مزامنة البيانات أولاً"
                    ];
                    continue;
                }

                // 4. إدارة الملفات: حذف الصورة القديمة إذا كانت موجودة لمنع امتلاء القرص الصلب
                if ($product->image && Storage::disk('public')->exists($product->image)) {
                    Storage::disk('public')->delete($product->image);
                }

                // 5. توليد الاسم الجديد والآمن للسيرفر بالصيغة المطلوبة: (رقم الصنف _ وقت الرفع _ هاش عشوائي)
                $extension = $file->getClientOriginalExtension();
                $newFileName = 'product_' . $productId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;

                // 6. حفظ الملف في مجلد products على القرص العام وتحديث السجل في قاعدة البيانات
                $imagePath = $file->storeAs('products', $newFileName, 'public');

                $product->update([
                    'image' => $imagePath
                ]);

                $synchronizedImages[] = [
                    'product_id' => $productId,
                    'file_name' => $originalName,
                    'stored_path' => $imagePath
                ];
            }
        });

        // 7. صياغة استجابة JSON دقيقة توضح الكاشير ما تم رفعه بنجاح وما فشل وأسباب الفشل
        return response()->json([
            'message' => 'اكتملت عملية معالجة ورفع الصور',
            'success_count' => count($synchronizedImages),
            'failed_count' => count($failedImages),
            'synchronized_images' => $synchronizedImages,
            'failed_images' => $failedImages
        ], count($failedImages) > 0 && count($synchronizedImages) == 0 ? 422 : 200);
    }
}
