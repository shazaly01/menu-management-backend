<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use Illuminate\Http\JsonResponse;

class PublicMenuController extends Controller
{
    /**
     * جلب الأقسام للزبائن (استبعاد قسم الطاولات رقم 13)
     */
    public function getCategories(): JsonResponse
    {
        $categories = Category::where('is_active', true)
            ->where('id', '!=', 13) // استبعاد قسم الطاولات
            ->get();

        return response()->json(CategoryResource::collection($categories));
    }

    /**
     * جلب الأصناف للزبائن (استبعاد الطاولات التابعة للقسم 13)
     */
    public function getProducts(): JsonResponse
    {
        $products = Product::where('is_active', true)
            ->where('category_id', '!=', 13) // استبعاد منتجات الطاولات
            ->with('category')
            ->get();

        return response()->json(ProductResource::collection($products));
    }
}
