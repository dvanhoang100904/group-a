<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Type;
use App\Models\Subject;
use App\Models\Folder;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Routing\Controller as BaseController;

class DocumentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Hiển thị danh sách tài liệu
     * Route: GET /documents
     */
    public function index(Request $request)
    {
        $query = Document::with(['user', 'type', 'subject', 'folder', 'tags'])
            ->withCount('versions');

        // Filter theo status
        if ($request->has('status') && in_array($request->status, ['public', 'private', 'restricted'])) {
            $query->where('status', $request->status);
        } else {
            // Mặc định: public + private của user hiện tại
            $query->where(function($q) {
                $q->where('status', 'public')
                  ->orWhere(function($q2) {
                      $q2->where('status', 'private')
                         ->where('user_id', Auth::id());
                  });
            });
        }

        // Filter theo type
        if ($request->has('type_id')) {
            $query->where('type_id', $request->type_id);
        }

        // Filter theo subject
        if ($request->has('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        // Filter theo folder
        if ($request->has('folder_id')) {
            $query->where('folder_id', $request->folder_id);
        }

        // Filter theo user (chỉ xem tài liệu của mình)
        if ($request->has('my_documents')) {
            $query->where('user_id', Auth::id());
        }

        // Search theo title/description
        if ($request->has('q') && $request->q) {
            $searchTerm = $request->q;
            $query->where(function($q) use ($searchTerm) {
                $q->where('title', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('description', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        
        if (in_array($sortBy, ['created_at', 'updated_at', 'title'])) {
            $query->orderBy($sortBy, $sortDir);
        }

        // Pagination
        $documents = $query->paginate(20);

        // Lấy filters cho sidebar
        $types = Type::all();
        $subjects = Subject::all();
        $folders = Folder::where('user_id', Auth::id())->get();

        return view('documents.index', compact(
            'documents',
            'types',
            'subjects',
            'folders'
        ));
    }

    /**
     * Hiển thị chi tiết 1 document
     * Route: GET /documents/{document}
     */
    public function show($documentId)
    {
        $document = Document::with([
            'user',
            'type',
            'subject',
            'folder',
            'tags',
            'versions' => function($q) {
                $q->orderByDesc('version_number');
            },
            'versions.uploader'
        ])->findOrFail($documentId);

        // Check access
        if ($document->status === 'private' && $document->user_id !== Auth::id()) {
            abort(403, 'Bạn không có quyền xem tài liệu này');
        }

        // Lấy current version
        $currentVersion = $document->versions()
            ->where('is_current_version', true)
            ->first();

        // Related documents (cùng subject hoặc type)
        $relatedDocuments = Document::where('document_id', '!=', $document->document_id)
            ->where(function($q) use ($document) {
                $q->where('subject_id', $document->subject_id)
                  ->orWhere('type_id', $document->type_id);
            })
            ->where('status', 'public')
            ->limit(5)
            ->get();

        return view('documents.See_Document_Details.Document_Detail', compact(
            'document',
            'currentVersion',
            'relatedDocuments'
        ));
    }

    /**
     * Hiển thị form edit document
     * Route: GET /documents/{document}/edit
     */
    public function edit($documentId)
    {
        $document = Document::with(['tags'])->findOrFail($documentId);

        // Chỉ owner mới edit được
        if ($document->user_id !== Auth::id()) {
            abort(403);
        }

        $types = Type::all();
        $subjects = Subject::all();
        $folders = Folder::where('user_id', Auth::id())->get();

        return view('documents.edit', compact(
            'document',
            'types',
            'subjects',
            'folders'
        ));
    }

    /**
     * Update document metadata (không phải file)
     * Route: PUT /documents/{document}
     */
    public function update(Request $request, $documentId)
    {
        $document = Document::findOrFail($documentId);

        if ($document->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Không có quyền chỉnh sửa',
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'description' => 'nullable|string|max:255',
            'type_id' => 'required|exists:types,type_id',
            'subject_id' => 'required|exists:subjects,subject_id',
            'folder_id' => 'nullable|exists:folders,folder_id',
            'status' => 'required|in:private,public,restricted',
            'tags' => 'nullable|array',
        ]);

        DB::beginTransaction();
        
        try {
            // Update document
            $document->update([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'type_id' => $validated['type_id'],
                'subject_id' => $validated['subject_id'],
                'folder_id' => $validated['folder_id'] ?? null,
                'status' => $validated['status'],
            ]);

            // Update tags
            if ($request->has('tags')) {
                $tagIds = [];
                foreach ($validated['tags'] as $tagName) {
                    $tag = \App\Models\Tag::firstOrCreate(['name' => trim(strtolower($tagName))]);
                    $tagIds[] = $tag->tag_id;
                }
                $document->tags()->sync($tagIds);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đã cập nhật tài liệu',
                'data' => $document->fresh(['tags']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Move document sang folder khác
     * Route: POST /documents/{document}/move
     */
    public function move(Request $request, $documentId)
    {
        $document = Document::findOrFail($documentId);

        if ($document->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Không có quyền di chuyển',
            ], 403);
        }

        $validated = $request->validate([
            'folder_id' => 'nullable|exists:folders,folder_id',
        ]);

        // Verify folder ownership
        if ($validated['folder_id']) {
            $folder = Folder::where('folder_id', $validated['folder_id'])
                ->where('user_id', Auth::id())
                ->first();
            
            if (!$folder) {
                return response()->json([
                    'success' => false,
                    'message' => 'Folder không hợp lệ',
                ], 400);
            }
        }

        $document->update(['folder_id' => $validated['folder_id']]);

        return response()->json([
            'success' => true,
            'message' => 'Đã di chuyển tài liệu',
        ]);
    }

    /**
     * Duplicate document (tạo bản sao)
     * Route: POST /documents/{document}/duplicate
     */
    public function duplicate($documentId)
    {
        $original = Document::with(['tags', 'versions'])->findOrFail($documentId);

        // Check access
        if ($original->status === 'private' && $original->user_id !== Auth::id()) {
            abort(403);
        }

        DB::beginTransaction();
        
        try {
            // Tạo document mới
            $duplicate = Document::create([
                'title' => $original->title . ' (Copy)',
                'description' => $original->description,
                'status' => 'private', // Copy luôn là private
                'user_id' => Auth::id(),
                'folder_id' => null,
                'type_id' => $original->type_id,
                'subject_id' => $original->subject_id,
            ]);

            // Copy current version
            $currentVersion = $original->versions()
                ->where('is_current_version', true)
                ->first();

            if ($currentVersion && Storage::exists($currentVersion->file_path)) {
                // Copy file
                $newFileName = time() . '_' . Str::random(8) . '.' . 
                    pathinfo($currentVersion->file_name, PATHINFO_EXTENSION);
                $newPath = 'uploads/' . now()->format('Y/m') . '/' . $newFileName;
                
                Storage::copy($currentVersion->file_path, $newPath);

                // Tạo version mới
                DocumentVersion::create([
                    'document_id' => $duplicate->document_id,
                    'version_number' => 1,
                    'file_name' => $currentVersion->file_name,
                    'file_path' => $newPath,
                    'file_size' => $currentVersion->file_size,
                    'mime_type' => $currentVersion->mime_type,
                    'uploaded_by' => Auth::id(),
                    'is_current_version' => true,
                    'change_notes' => 'Copy từ document #' . $original->document_id,
                ]);
            }

            // Copy tags
            $duplicate->tags()->sync($original->tags->pluck('tag_id')->toArray());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đã tạo bản sao',
                'document_id' => $duplicate->document_id,
                'url' => route('documents.show', $duplicate->document_id),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi duplicate: ' . $e->getMessage(),
            ], 500);
        }
    }
}