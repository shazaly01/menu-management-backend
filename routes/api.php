<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// --- استيراد الـ Controllers الخاصة بإدارة النظام الأساسي والمطعم ---
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\BackupController;
use App\Http\Controllers\RemoteOrderController;
use App\Http\Controllers\MenuSynchronizationController;
use App\Http\Controllers\PublicMenuController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// --- المسارات العامة (Public Routes) ---
Route::post('/login', [AuthController::class, 'login']);

// --- مسارات قائمة الطعام العامة للزبائن (بدون مصادقة) ---
Route::prefix('public-menu')->name('public.menu.')->group(function () {
    Route::get('/categories', [PublicMenuController::class, 'getCategories']);
    Route::get('/products', [PublicMenuController::class, 'getProducts']);
});


// === [تعديل] مسارات المزامنة مع الكاشير المحلي VB.NET ===
// تم إخراجها من Sanctum وحمايتها بـ توكن ثابت وصريح تماماً كما أردت
Route::prefix('sync')->name('sync.')->middleware('validate.cashier.token')->group(function () {

    // استقبال وحفظ الأقسام والمنتجات
    Route::post('/categories', [MenuSynchronizationController::class, 'syncCategory']);
    Route::post('/products', [MenuSynchronizationController::class, 'syncProduct']);

    // الرابط الموحد والمستقل لرفع ومعالجة صور الأصناف
    Route::post('/products/upload-images', [MenuSynchronizationController::class, 'uploadProductImages']);

    // الحذف الناعم للكيانات المحذوفة يدوياً من الكاشير
    Route::delete('/category/{id}', [MenuSynchronizationController::class, 'destroyCategory']);
    Route::delete('/product/{id}', [MenuSynchronizationController::class, 'destroyProduct']);
});


// --- المسارات المحمية الخاصة بالويتر ولوحة التحكم (Protected Routes) ---
Route::middleware('auth:sanctum')->group(function () {

    // --- مسارات الويتر وتنزيل المنيو (Menu APIs) ---
    Route::prefix('menu')->name('menu.')->group(function () {
        Route::get('/categories', [MenuSynchronizationController::class, 'getCategories'])
            ->middleware('can:categories.view');

        Route::get('/products', [MenuSynchronizationController::class, 'getProducts'])
            ->middleware('can:products.view');
    });

    // --- مسارات إدارة وعمليات الطلبات الوسيطة (Remote Orders APIs) ---
    Route::prefix('remote-orders')->name('remote-orders.')->group(function () {
        Route::get('/', [RemoteOrderController::class, 'index'])
            ->middleware('can:viewAny,App\Models\RemoteOrder');

        Route::post('/', [RemoteOrderController::class, 'store'])
            ->middleware('can:create,App\Models\RemoteOrder');

        Route::get('/{remoteOrder}', [RemoteOrderController::class, 'show'])
            ->middleware('can:view,remoteOrder');

        Route::patch('/{remoteOrder}/status', [RemoteOrderController::class, 'updateStatus']);
    });

    Route::prefix('backups')->name('backups.')->group(function () {
        Route::get('/', [BackupController::class, 'index'])->middleware('can:backup.view');
        Route::post('/', [BackupController::class, 'store'])->middleware('can:backup.create');
        Route::get('/download', [BackupController::class, 'download'])->middleware('can:backup.download');
        Route::delete('/', [BackupController::class, 'destroy'])->middleware('can:backup.delete');
    });

    Route::get('roles/permissions', [RoleController::class, 'getAllPermissions'])->name('roles.permissions');
    Route::apiResource('roles', RoleController::class);
    Route::apiResource('users', UserController::class);

    Route::get('/user', function (Request $request) {
        $user = $request->user()->load('roles:id,name', 'roles.permissions:id,name');
        return response()->json($user);
    });

    Route::post('/logout', [AuthController::class, 'logout']);
});
