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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Routing\Controller as BaseController;

class UploadController extends Controller
{
    /**
     * Constructor - Middleware auth
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Hiển thị trang upload
     * Route: GET /upload
     */
    public function create()
    {
        return view('documents.Upload_Documents.Index_Upload', [
            'pageTitle' => 'Upload Tài Liệu',
        ]);
    }

    /**
     * API: Lấy metadata cho form (types, subjects, folders)
     * Route: GET /api/upload/metadata
     * Response: JSON {types, subjects, folders}
     */
    public function getMetadata(Request $request)
    {
        try {
            // Lấy types
            $types = Type::select('type_id', 'name', 'description')
                ->orderBy('name')
                ->get();

            // Lấy subjects
            $subjects = Subject::select('subject_id', 'name', 'code')
                ->orderBy('name')
                ->get();

            // Lấy folders của user hiện tại
            $folders = Folder::select('folder_id', 'name', 'parent_folder_id')
                ->where('user_id', Auth::id())
                ->orderBy('name')
                ->get();

            // Lấy popular tags (top 20)
            $popularTags = Tag::select('tag_id', 'name')
                ->withCount('documents')
                ->orderByDesc('documents_count')
                ->limit(20)
                ->get()
                ->pluck('name');

            return response()->json([
                'success' => true,
                'types' => $types,
                'subjects' => $subjects,
                'folders' => $folders,
                'popularTags' => $popularTags,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching metadata: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Không thể tải metadata',
            ], 500);
        }
    }

    /**
     * Xử lý upload file và lưu DB
     * Route: POST /upload/store
     * Request: multipart/form-data
     */
    public function store(Request $request)
    {
        // Validation rules
        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'max:51200', // 50MB
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png,zip,rar',
            ],
            'title' => 'required|string|max:150|min:3',
            'description' => 'nullable|string|max:255',
            'type_id' => 'required|exists:types,type_id',
            'subject_id' => 'required|exists:subjects,subject_id',
            'folder_id' => 'nullable|exists:folders,folder_id',
            'status' => 'required|in:private,public,restricted',
            'tags' => 'nullable|json',
        ], [
            'file.required' => 'Vui lòng chọn file để upload',
            'file.max' => 'File không được vượt quá 50MB',
            'file.mimes' => 'File không đúng định dạng cho phép',
            'title.required' => 'Tiêu đề là bắt buộc',
            'title.min' => 'Tiêu đề phải có ít nhất 3 ký tự',
            'type_id.required' => 'Vui lòng chọn loại tài liệu',
            'subject_id.required' => 'Vui lòng chọn môn học',
        ]);

        DB::beginTransaction();
        
        try {
            // Lấy file
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $mimeType = $file->getMimeType();
            $fileSize = $file->getSize();

            // Validate folder ownership (nếu có)
            if ($validated['folder_id']) {
                $folder = Folder::where('folder_id', $validated['folder_id'])
                    ->where('user_id', Auth::id())
                    ->first();
                
                if (!$folder) {
                    throw ValidationException::withMessages([
                        'folder_id' => 'Bạn không có quyền truy cập folder này'
                    ]);
                }
            }

            // Tạo tên file unique với format: YYYYMMDD_HHMMSS_random.ext
            $timestamp = now()->format('Ymd_His');
            $randomString = Str::random(8);
            $fileName = "{$timestamp}_{$randomString}.{$extension}";
            
            // Lưu vào storage/app/uploads/YYYY/MM/
            $uploadPath = 'uploads/' . now()->format('Y/m');
            $filePath = $file->storeAs($uploadPath, $fileName);

            // Kiểm tra file đã lưu thành công
            if (!Storage::exists($filePath)) {
                throw new \Exception('Lỗi khi lưu file vào storage');
            }

            // Tạo document record
            $document = Document::create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'status' => $validated['status'],
                'user_id' => Auth::id(),
                'folder_id' => $validated['folder_id'] ?? null,
                'type_id' => $validated['type_id'],
                'subject_id' => $validated['subject_id'],
            ]);

            // Tạo version đầu tiên (v1.0)
            $version = DocumentVersion::create([
                'document_id' => $document->document_id,
                'version_number' => 1,
                'file_name' => $originalName,
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'mime_type' => $mimeType,
                'uploaded_by' => Auth::id(),
                'is_current_version' => true,
                'change_notes' => 'Phiên bản đầu tiên',
            ]);

            // Xử lý tags
            if ($request->has('tags')) {
                $tagsArray = json_decode($request->tags, true);
                
                if (is_array($tagsArray) && !empty($tagsArray)) {
                    $tagIds = [];
                    
                    foreach ($tagsArray as $tagName) {
                        // Chuẩn hóa tag name
                        $tagName = trim(strtolower($tagName));
                        
                        if (empty($tagName)) continue;
                        
                        // Tìm hoặc tạo tag mới
                        $tag = Tag::firstOrCreate(
                            ['name' => $tagName],
                            ['created_at' => now(), 'updated_at' => now()]
                        );
                        
                        $tagIds[] = $tag->tag_id;
                    }
                    
                    // Gán tags cho document
                    if (!empty($tagIds)) {
                        $document->tags()->sync($tagIds);
                    }
                }
            }

            // Log activity (optional - nếu có bảng activities)
            // Activity::create([
            //     'user_id' => Auth::id(),
            //     'document_id' => $document->document_id,
            //     'action' => 'created',
            //     'description' => 'Đã tải lên tài liệu mới',
            // ]);

            DB::commit();

            // Generate URL để xem document
            $documentUrl = route('documents.show', $document->document_id);

            return response()->json([
                'success' => true,
                'message' => 'Upload thành công!',
                'data' => [
                    'document_id' => $document->document_id,
                    'title' => $document->title,
                    'version' => $version->version_number,
                    'file_size' => $this->formatFileSize($fileSize),
                ],
                'url' => $documentUrl,
            ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Xóa file đã upload nếu có lỗi
            if (isset($filePath) && Storage::exists($filePath)) {
                Storage::delete($filePath);
            }

            Log::error('Upload error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'file' => $originalName ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi upload: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload version mới cho document đã có
     * Route: POST /upload/new-version/{document}
     */
    public function uploadNewVersion(Request $request, $documentId)
    {
        $document = Document::findOrFail($documentId);

        // Chỉ owner mới upload version mới
        if ($document->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền upload version mới',
            ], 403);
        }

        $validated = $request->validate([
            'file' => 'required|file|max:51200',
            'change_notes' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();

        try {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $mimeType = $file->getMimeType();
            $fileSize = $file->getSize();

            // Tạo filename unique
            $timestamp = now()->format('Ymd_His');
            $randomString = Str::random(8);
            $fileName = "{$timestamp}_{$randomString}.{$extension}";
            
            $uploadPath = 'uploads/' . now()->format('Y/m');
            $filePath = $file->storeAs($uploadPath, $fileName);

            // Lấy version number tiếp theo
            $nextVersionNumber = $document->getNextVersionNumber();

            // Set tất cả versions cũ thành is_current_version = false
            $document->versions()->update(['is_current_version' => false]);

            // Tạo version mới
            $version = DocumentVersion::create([
                'document_id' => $document->document_id,
                'version_number' => $nextVersionNumber,
                'file_name' => $originalName,
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'mime_type' => $mimeType,
                'uploaded_by' => Auth::id(),
                'is_current_version' => true,
                'change_notes' => $validated['change_notes'] ?? "Version {$nextVersionNumber}",
            ]);

            // Update document updated_at
            $document->touch();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Đã upload version {$nextVersionNumber}",
                'data' => [
                    'version_id' => $version->version_id,
                    'version_number' => $version->version_number,
                    'file_size' => $this->formatFileSize($fileSize),
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            if (isset($filePath) && Storage::exists($filePath)) {
                Storage::delete($filePath);
            }

            Log::error('New version upload error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi upload version mới: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download file theo version
     * Route: GET /download/{version}
     */
    public function download($versionId)
    {
        $version = DocumentVersion::with('document')->findOrFail($versionId);
        $document = $version->document;

        // Kiểm tra quyền truy cập
        if ($document->status === 'private' && $document->user_id !== Auth::id()) {
            abort(403, 'Bạn không có quyền tải tài liệu này');
        }

        if ($document->status === 'restricted') {
            // TODO: Kiểm tra trong bảng document_access
            // Tạm thời chỉ cho owner
            if ($document->user_id !== Auth::id()) {
                abort(403, 'Tài liệu bị hạn chế truy cập');
            }
        }

        $filePath = storage_path('app/' . $version->file_path);
        
        if (!file_exists($filePath)) {
            abort(404, 'File không tồn tại trên server');
        }

        // Log download activity (optional)
        // Activity::create([...]);

        return response()->download($filePath, $version->file_name);
    }

    /**
     * Stream file (để preview PDF, images...)
     * Route: GET /stream/{version}
     */
    public function stream($versionId)
    {
        $version = DocumentVersion::with('document')->findOrFail($versionId);
        $document = $version->document;

        // Check access
        if ($document->status === 'private' && $document->user_id !== Auth::id()) {
            abort(403);
        }

        $filePath = storage_path('app/' . $version->file_path);
        
        if (!file_exists($filePath)) {
            abort(404);
        }

        return response()->file($filePath, [
            'Content-Type' => $version->mime_type,
            'Content-Disposition' => 'inline; filename="' . $version->file_name . '"',
        ]);
    }

    /**
     * Xóa tài liệu (soft delete hoặc hard delete)
     * Route: DELETE /documents/{document}
     */
    public function destroy($documentId)
    {
        $document = Document::findOrFail($documentId);

        // Chỉ owner mới xóa được
        if ($document->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xóa tài liệu này',
            ], 403);
        }

        DB::beginTransaction();
        
        try {
            // Xóa các file versions từ storage
            $deletedFiles = 0;
            foreach ($document->versions as $version) {
                if (Storage::exists($version->file_path)) {
                    Storage::delete($version->file_path);
                    $deletedFiles++;
                }
                $version->delete();
            }

            // Xóa relations
            $document->tags()->detach();
            
            // Xóa document
            $document->delete();

            DB::commit();

            Log::info("Document deleted", [
                'document_id' => $documentId,
                'user_id' => Auth::id(),
                'files_deleted' => $deletedFiles,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đã xóa tài liệu và ' . $deletedFiles . ' file',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Delete document error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper: Format file size
     */
    private function formatFileSize($bytes)
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / (1024 * 1024), 1) . ' MB';
    }
}