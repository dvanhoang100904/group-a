<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\DocumentPreview;
use App\Models\DocumentAccess;
use Exception;
use Illuminate\Support\Facades\DB;

class UploadController extends Controller
{
     public function index()
    {
        return view('documents.Upload_Documents.Index_Upload');
    }
    // ==================== UPLOAD MỚI (PHIÊN BẢN ĐẦU TIÊN) ====================
    public function store(Request $request)
    {
        // Idempotency Key để tránh double submit
        $idempotencyKey = $request->header('Idempotency-Key');
        if ($idempotencyKey) {
            $cached = cache()->get("upload_{$idempotencyKey}");
            if ($cached) {
                return response()->json($cached, 200);
            }
        }

        $request->validate([
            'file'          => 'required|file|mimes:doc,docx,pdf,jpg,jpeg,png,gif,mp4,mp3,zip,rar,pptx,xls,xlsx|max:51200',
            'title'         => 'nullable|string|max:255',
            'description'   => 'nullable|string',
            'type_id'       => 'required|exists:types,type_id',
            'folder_id'     => 'nullable|exists:folders,folder_id',
            'subject_id'    => 'nullable|exists:subjects,subject_id',
            'permissions'   => 'nullable|array',
            'permissions.*' => 'in:view,edit,download,delete,share',
            'share_user_id' => 'nullable|exists:users,user_id',
            'share_email'   => 'nullable|email',
        ]);

        // Sanitize title/description chống HTML injection
        $title = strip_tags($request->title ?? $request->file('file')->getClientOriginalName());
        $description = strip_tags($request->description ?? '');

        return DB::transaction(function () use ($request, $title, $description, $idempotencyKey) {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $extension = strtolower($file->getClientOriginalExtension());
            $uuid = Str::uuid();
            $safeName = pathinfo($originalName, PATHINFO_FILENAME) . '-' . $uuid . '.' . $extension;

            // Lưu file gốc vào thư mục versions theo dung lượng
            $versionsFolder = $this->getCurrentVersionsFolder();
            $filePath = $file->storeAs($versionsFolder, $safeName, 'public');

            // Tạo document
            $document = Document::create([
                'title'       => $title,
                'description' => $description,
                'status'      => 'private',
                'user_id'     => auth()->id() ?? 1,
                'folder_id'   => $request->folder_id,
                'type_id'     => $request->type_id,
                'subject_id'  => $request->subject_id ?? null,
            ]);

            // Tạo version đầu tiên
            $version = DocumentVersion::create([
                'version_number'     => 1,
                'file_path'          => $filePath,
                'file_size'          => $file->getSize(),
                'mime_type'          => $file->getMimeType(),
                'is_current_version' => true,
                'change_note'        => 'Tải lên lần đầu',
                'document_id'        => $document->document_id,
                'user_id'            => auth()->id() ?? 1,
            ]);

            $previewUrl = null;
            $previewReady = false;

            // ===== CHUẨN HÓA PREVIEW THEO CHUẨN MỚI: previews/{1000+version_id}.pdf =====
            $previewFileName = (1000 + $version->version_id) . '.pdf'; // ví dụ: 1001.pdf
            $previewPath = "previews/{$previewFileName}";

            if (in_array($extension, ['pdf'])) {
                // Nếu là PDF → copy thẳng làm preview
                Storage::disk('public')->copy($filePath, $previewPath);
                $previewReady = true;
                $previewUrl = asset('storage/' . $previewPath);
            } elseif (in_array($extension, ['doc', 'docx', 'pptx', 'xls', 'xlsx'])) {
                // Convert bằng LibreOffice
                $convertResult = $this->convertToPdf(
                    storage_path('app/public/' . $filePath),
                    $originalName,
                    $document->document_id,
                    $version->version_id
                );

                if ($convertResult['success']) {
                    // Di chuyển file PDF đã convert sang đúng đường dẫn chuẩn
                    Storage::disk('public')->move($convertResult['path'], $previewPath);
                    $previewReady = true;
                    $previewUrl = asset('storage/' . $previewPath);
                }
            }
            // Các file khác (ảnh, video...) có thể xử lý sau

            // Lưu record preview (dù ready hay không để sau này regenerate)
            DocumentPreview::updateOrCreate(
                ['version_id' => $version->version_id],
                [
                    'document_id'   => $document->document_id,
                    'preview_path'  => $previewPath,
                    'preview_ready' => $previewReady,
                    'expires_at'    => now()->addDays(180),
                    'generated_by'  => auth()->id() ?? 1,
                ]
            );

            // Xử lý chia sẻ
            $this->handleSharing($request, $document);

            $response = [
                'success'       => true,
                'message'       => 'Tải lên thành công!',
                'document_id'   => $document->document_id,
                'version_id'    => $version->version_id,
                'preview_ready' => $previewReady,
                'preview_url'   => $previewUrl,
            ];

            // Cache 5 phút nếu có idempotency key
            if ($idempotencyKey) {
                cache()->put("upload_{$idempotencyKey}", $response, now()->addMinutes(5));
            }

            return response()->json($response, 201);
        });
    }

    // ==================== UPLOAD PHIÊN BẢN MỚI ====================
    public function uploadNewVersion(Request $request, $documentId)
    {
        // Tương tự store nhưng cập nhật is_current_version
        $document = Document::findOrFail($documentId);

        $request->validate([
            'file' => 'required|file|mimes:doc,docx,pdf,pptx,xlsx,pptx|max:51200',
            'change_note' => 'nullable|string|max:500',
        ]);

        return DB::transaction(function () use ($request, $document) {
            // Tắt version hiện tại
            DocumentVersion::where('document_id', $document->document_id)
                ->where('is_current_version', true)
                ->update(['is_current_version' => false]);

            $file = $request->file('file');
            $extension = strtolower($file->getClientOriginalExtension());
            $uuid = Str::uuid();
            $safeName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '-' . $uuid . '.' . $extension;

            $versionsFolder = $this->getCurrentVersionsFolder();
            $filePath = $file->storeAs($versionsFolder, $safeName, 'public');

            $latestVersion = DocumentVersion::where('document_id', $document->document_id)->max('version_number') ?? 0;
            $newVersionNumber = $latestVersion + 1;

            $version = DocumentVersion::create([
                'version_number'     => $newVersionNumber,
                'file_path'          => $filePath,
                'file_size'          => $file->getSize(),
                'mime_type'          => $file->getMimeType(),
                'is_current_version' => true,
                'change_note'        => $request->change_note ?? 'Cập nhật phiên bản mới',
                'document_id'        => $document->document_id,
                'user_id'            => auth()->id() ?? 1,
            ]);

            // Xóa preview cũ của version hiện tại
            DocumentPreview::where('document_id', $document->document_id)
                ->where('version_id', '!=', $version->version_id)
                ->where('preview_ready', true)
                ->delete();

            $previewFileName = (1000 + $version->version_id) . '.pdf';
            $previewPath = "previews/{$previewFileName}";
            $previewReady = false;
            $previewUrl = null;

            if ($extension === 'pdf') {
                Storage::disk('public')->copy($filePath, $previewPath);
                $previewReady = true;
                $previewUrl = asset('storage/' . $previewPath);
            } elseif (in_array($extension, ['doc','docx','pptx','xls','xlsx'])) {
                $result = $this->convertToPdf(
                    storage_path('app/public/' . $filePath),
                    $file->getClientOriginalName(),
                    $document->document_id,
                    $version->version_id
                );
                if ($result['success']) {
                    Storage::disk('public')->move($result['path'], $previewPath);
                    $previewReady = true;
                    $previewUrl = asset('storage/' . $previewPath);
                }
            }

            DocumentPreview::updateOrCreate(
                ['version_id' => $version->version_id],
                [
                    'document_id'   => $document->document_id,
                    'preview_path'  => $previewPath,
                    'preview_ready' => $previewReady,
                    'expires_at'    => now()->addDays(180),
                    'generated_by'  => auth()->id() ?? 1,
                ]
            );

            return response()->json([
                'success'       => true,
                'message'       => 'Tải lên phiên bản mới thành công!',
                'version_id'    => $version->version_id,
                'preview_ready' => $previewReady,
                'preview_url'   => $previewUrl,
            ]);
        });
    }

    // ==================== CHECK PREVIEW STATUS (chuẩn mới) ====================
    public function checkPreviewStatus($documentId)
    {
        $currentVersion = DocumentVersion::where('document_id', $documentId)
            ->where('is_current_version', true)
            ->first();

        if (!$currentVersion) {
            return response()->json(['preview_ready' => false]);
        }

        $preview = DocumentPreview::where('version_id', $currentVersion->version_id)->first();

        if ($preview && $preview->preview_ready && $preview->expires_at > now()) {
            $fullPath = storage_path('app/public/' . $preview->preview_path);
            if (file_exists($fullPath)) {
                return response()->json([
                    'preview_ready' => true,
                    'preview_url'   => asset('storage/' . $preview->preview_path),
                ]);
            }
        }

        return response()->json(['preview_ready' => false]);
    }

    // ==================== CONVERT TO PDF (đã fix quoting, cleanup temp files) ====================
    protected function convertToPdf(string $filePath, string $fileName, int $documentId, int $versionId): array
    {
        $tempDir = storage_path("app/public/documents/{$documentId}/temp_convert");
        if (!is_dir($tempDir)) mkdir($tempDir, 0755, true);

        $command = sprintf(
            "libreoffice --headless --convert-to pdf --outdir %s %s",
            escapeshellarg($tempDir),
            escapeshellarg($filePath)
        );

        Log::info('Convert command', ['cmd' => $command]);
        exec($command . ' 2>&1', $output, $code);

        $generatedPdf = $tempDir . '/' . pathinfo($fileName, PATHINFO_FILENAME) . '.pdf';

        if ($code !== 0 || !file_exists($generatedPdf)) {
            // Dọn dẹp
            array_map('unlink', glob("$tempDir/*") ?: []);
            Log::error('Convert failed', ['code' => $code, 'output' => $output]);
            return ['success' => false, 'path' => null];
        }

        // Đường dẫn cuối cùng sẽ được move ở ngoài
        $finalPath = "documents/{$documentId}/preview_v{$versionId}.pdf";
        $fullFinal = storage_path('app/public/' . $finalPath);

        rename($generatedPdf, $fullFinal);

        // Dọn temp
        array_map('unlink', glob("$tempDir/*") ?: []);
        @rmdir($tempDir);

        return ['success' => true, 'path' => $finalPath];
    }

    // ==================== CÁC CÁC HÀM PHỤ ====================
    protected function getCurrentVersionsFolder(): string
    {
        $base = 'documents';
        $all = Storage::disk('public')->directories($base);
        $versionFolders = array_filter($all, fn($f) => preg_match('#^documents/versions\d*$#', $f));

        if (empty($versionFolders)) {
            Storage::disk('public')->makeDirectory('documents/versions');
            return 'documents/versions';
        }

        usort($versionFolders, fn($a, $b) => Storage::disk('public')->lastModified($b) - Storage::disk('public')->lastModified($a));

        $latest = $versionFolders[0];
        $files = Storage::disk('public')->files($latest);

        if (count($files) < 100) {
            return $latest;
        }

        $next = count($versionFolders) + 1;
        $new = "documents/versions{$next}";
        Storage::disk('public')->makeDirectory($new);
        return $new;
    }

    protected function handleSharing(Request $request, Document $document)
    {
        if ($request->filled('share_user_id')) {
            DocumentAccess::updateOrCreate(
                ['document_id' => $document->document_id, 'granted_to_user_id' => $request->share_user_id],
                [
                    'granted_by' => auth()->id() ?? 1,
                    'can_view' => in_array('view', $request->permissions ?? []),
                    'can_edit' => in_array('edit', $request->permissions ?? []),
                    'can_download' => in_array('download', $request->permissions ?? []),
                    'can_delete' => in_array('delete', $request->permissions ?? []),
                    'can_share' => in_array('share', $request->permissions ?? []),
                    'no_expiry' => true,
                ]
            );
        }

        // Tương tự cho share_email (gửi invite mail) – bạn có thể mở rộng sau
    }

    // Download + Destroy giữ nguyên như cũ của bạn, chỉ thêm kiểm tra file tồn tại
    public function download($versionId)
    {
        $version = DocumentVersion::findOrFail($versionId);
        $path = storage_path('app/public/' . $version->file_path);

        if (!file_exists($path)) {
            abort(404, 'File không tồn tại hoặc đã bị xóa.');
        }

        return response()->download($path, $version->version_number . '_' . basename($version->file_path));
    }

    public function destroy($documentId)
    {
        return DB::transaction(function () use ($documentId) {
            $document = Document::findOrFail($documentId);

            // Xóa file gốc tất cả version
            DocumentVersion::where('document_id', $documentId)->chunk(50, function ($versions) {
                foreach ($versions as $v) {
                    if (Storage::disk('public')->exists($v->file_path)) {
                        Storage::disk('public')->delete($v->file_path);
                    }
                }
            });

            // Xóa preview
            DocumentPreview::where('document_id', $documentId)->chunk(50, function ($previews) {
                foreach ($previews as $p) {
                    if (Storage::disk('public')->exists($p->preview_path)) {
                        Storage::disk('public')->delete($p->preview_path);
                    }
                }
            });

            $document->delete();

            return response()->json(['success' => true, 'message' => 'Xóa tài liệu thành công!']);
        });
    }
}