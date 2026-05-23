<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Document\StoreDocumentRequest;
use App\Http\Resources\Api\DocumentResource;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function __construct()
    {
        // التغيير 1: استثناء 'destroy' من التحقق التلقائي لنتمكن من تخصيص رسالة الخطأ
        $this->authorizeResource(Document::class, 'document', ['except' => ['destroy']]);
    }

   public function index(): AnonymousResourceCollection
{
    $this->authorize('viewAny', Document::class);

    // 1. استخدام العلاقة الجديدة documentable
    $query = Document::with('documentable');

    // 2. الفلترة بناءً على المعطيات الجديدة القادمة من الواجهة
    if (request()->has('target_id') && request()->has('target_type')) {
        $modelType = request('target_type') === 'company'
            ? \App\Models\Company::class
            : \App\Models\Project::class;

        $query->where('documentable_id', request('target_id'))
              ->where('documentable_type', $modelType);
    }

    $documents = $query->latest()->paginate(20);
    return DocumentResource::collection($documents);
}

 public function store(StoreDocumentRequest $request): JsonResponse
    {
        // 1. التعديل هنا: التخزين في 'local' داخل مجلد 'private_documents'
        // هذا المسار (storage/app/private_documents) غير متاح عبر الويب مباشرة
        $path = $request->file('file')->store('private_documents', 'local');

        $modelType = $request->target_type === 'company'
            ? \App\Models\Company::class
            : \App\Models\Project::class;

        $document = Document::create([
            'name' => $request->name,
            'file_path' => $path,
            'documentable_id' => $request->target_id,
            'documentable_type' => $modelType,
        ]);

        return response()->json([
            'message' => 'Document uploaded successfully.',
            'data' => DocumentResource::make($document),
        ], Response::HTTP_CREATED);
    }

    public function show(Document $document): DocumentResource
    {
        $document->load('documentable');
        return DocumentResource::make($document);
    }

    /**
     * دالة التحميل الجديدة
     * يتم الوصول لها عبر الرابط الموقع فقط
     */
 public function download(Document $document)
    {
        // 1. التأكد من وجود الملف
        if (! Storage::disk('local')->exists($document->file_path)) {
            abort(404);
        }

        // 2. جلب المسار الحقيقي
        $path = Storage::disk('local')->path($document->file_path);

        // 3. هذا هو السطر السحري (وليس ترقيعاً)
        // response()->file: يرسل الملف مع الهيدر "inline"
        // هذا يسمح للمتصفح بعرضه كصورة أو PDF، ولا يجبره على التنزيل
        return response()->file($path);
    }

    public function destroy(Document $document)
    {
        // التحقق اليدوي من الصلاحية
        if (request()->user()->cannot('delete', $document)) {
            return response()->json([
                'message' => 'عذراً، ليس لديك صلاحية لحذف هذا الملف.'
            ], 403); // 403 Forbidden
        }

        $document->delete();

        return response()->noContent();
    }

    }
