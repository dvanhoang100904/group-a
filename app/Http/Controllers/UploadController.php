<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\DocumentPreview;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;
use Exception;

class UploadController extends Controller
{
  public function index()
    {
        return view('documents.Upload_Documents.Index_Upload');
    }

    /**
     * Convert DOCX → PDF với xử lý lỗi đầy đủ
     * @return array ['success' => bool, 'path' => string|null, 'message' => string]
     */
    protected function convertToPdf($filePath, $fileName, $documentId, $versionNumber)
    {
        try {
            // ✅ 1. Cấu hình thư viện PDF (bắt buộc!)
            Settings::setPdfRendererName(Settings::PDF_RENDERER_DOMPDF);
            Settings::setPdfRendererPath(base_path('vendor/dompdf/dompdf'));

            // ✅ 2. Load file Word
            $phpWord = IOFactory::load($filePath);

            // ✅ 3. Tạo đường dẫn lưu preview
            $pdfFileName = "preview_{$versionNumber}.pdf";
            $pdfPath = "documents/{$documentId}/{$pdfFileName}";
            $fullPath = storage_path('app/public/' . $pdfPath);

            // ✅ 4. Tạo thư mục nếu chưa có (QUAN TRỌNG!)
            $directory = dirname($fullPath);
            if (!file_exists($directory)) {
                if (!mkdir($directory, 0755, true)) {
                    throw new Exception("Không thể tạo thư mục: {$directory}");
                }
            }

            // ✅ 5. Convert sang PDF
            $pdfWriter = IOFactory::createWriter($phpWord, 'PDF');
            $pdfWriter->save($fullPath);

            // ✅ 6. Kiểm tra file đã được tạo thành công chưa
            if (!file_exists($fullPath)) {
                throw new Exception("File PDF không được tạo: {$fullPath}");
            }

            return [
                'success' => true,
                'path' => $pdfPath,
                'message' => 'Convert PDF thành công'
            ];

        } catch (Exception $e) {
            Log::error("❌ Lỗi convert DOCX → PDF", [
                'file' => $fileName,
                'document_id' => $documentId,
                'version' => $versionNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'path' => null,
                'message' => 'Không thể tạo preview: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Lấy thư mục versions hiện tại (auto-create khi đầy 100 file)
     */
    protected function getCurrentVersionsFolder()
    {
        $basePath = storage_path('app/public/documents');
        $folders = glob($basePath . '/versions*');
        
        if (empty($folders)) {
            $currentFolder = $basePath . '/versions';
            mkdir($currentFolder, 0755, true);
            return 'documents/versions';
        }

        // Lấy thư mục mới nhất
        usort($folders, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        $latestFolder = $folders[0];
        $fileCount = count(glob($latestFolder . '/*'));

        // Nếu đầy 100 file → tạo versions2, versions3...
        if ($fileCount >= 100) {
            $folderNumber = count($folders) + 1;
            $newFolder = $basePath . '/versions' . $folderNumber;
            mkdir($newFolder, 0755, true);
            return 'documents/versions' . $folderNumber;
        }

        return str_replace($basePath . '/', 'documents/', $latestFolder);
    }

    public function store(Request $request)
    {
        // ✅ 1. Validate request
        $validated = $request->validate([
            'file' => 'required|file|max:51200', // max 50MB
            'title' => 'nullable|string|max:255',
            'type_id' => 'required|exists:types,type_id',
            'permission' => 'nullable|string|in:view,edit,download,full',
            'folder_index' => 'nullable|string',
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        $mime = $file->getMimeType();
        $uuid = Str::uuid()->toString();

        // ✅ 2. Lưu file gốc vào versions folder
        $versionsFolder = $this->getCurrentVersionsFolder();
        $fileName = pathinfo($originalName, PATHINFO_FILENAME) . '-' . $uuid . '.' . $extension;
        $filePath = $file->storeAs($versionsFolder, $fileName, 'public');
        $fileSize = $file->getSize();

        // ✅ 3. Tạo Document
        $document = Document::create([
            'title' => $validated['title'] ?? $originalName,
            'description' => $request->input('description'),
            'status' => 'private',
            'user_id' => auth()->id() ?? 1,
            'folder_id' => $request->input('folder_id'),
            'type_id' => $validated['type_id'],
            'subject_id' => $request->input('subject_id', 1),
        ]);

        // ✅ 4. Tạo Version
        $versionNumber = 1;
        $version = DocumentVersion::create([
            'version_number' => $versionNumber,
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'mime_type' => $mime,
            'is_current_version' => true,
            'change_note' => 'Initial upload',
            'document_id' => $document->document_id,
            'user_id' => auth()->id() ?? 1,
        ]);

        // ✅ 5. Convert PDF (nếu là Word)
        $conversionResult = null;
        $previewUrl = null;

        if (in_array($extension, ['doc', 'docx'])) {
            $conversionResult = $this->convertToPdf(
                storage_path('app/public/' . $filePath),
                $fileName,
                $document->document_id,
                $versionNumber
            );

            if ($conversionResult['success']) {
                DocumentPreview::create([
                    'preview_path' => $conversionResult['path'],
                    'expires_at' => now()->addDays(7),
                    'generated_by' => auth()->id() ?? 1,
                    'document_id' => $document->document_id,
                    'version_id' => $version->version_id,
                ]);
                $previewUrl = asset('storage/' . $conversionResult['path']);
            }
        }

        // ✅ 6. Trả response chính xác
        $responseData = [
            'document' => $document,
            'version' => $version,
            'preview_url' => $previewUrl,
            'preview_ready' => $conversionResult && $conversionResult['success'],
            'conversion_started' => false,
            'message' => 'Tải lên thành công!',
        ];

        // ❌ Nếu convert thất bại nhưng upload OK
        if ($conversionResult && !$conversionResult['success']) {
            $responseData['success'] = true; // upload vẫn thành công
            $responseData['preview_ready'] = false;
            $responseData['preview_error'] = $conversionResult['message'];
            $responseData['message'] = 'Tải lên thành công nhưng không tạo được preview PDF';
        } else {
            $responseData['success'] = true;
        }

        return response()->json($responseData, 201);
    }

    /**
     * API: Lấy folder index hiện tại
     */
    public function getCurrentFolder()
    {
        return response()->json([
            'folderIndex' => $this->getCurrentVersionsFolder()
        ]);
    }

    /**
     * API: Kiểm tra trạng thái preview (cho polling)
     */
    public function checkPreviewStatus($documentId)
    {
        $preview = DocumentPreview::where('document_id', $documentId)
            ->where('version_id', function($query) use ($documentId) {
                $query->select('version_id')
                    ->from('document_versions')
                    ->where('document_id', $documentId)
                    ->where('is_current_version', true)
                    ->limit(1);
            })
            ->first();

        if ($preview && file_exists(storage_path('app/public/' . $preview->preview_path))) {
            return response()->json([
                'preview_ready' => true,
                'preview_url' => asset('storage/' . $preview->preview_path)
            ]);
        }

        return response()->json(['preview_ready' => false]);
    }
    // 📦 Download file gốc
    public function download($versionId)
    {
        $version = DocumentVersion::findOrFail($versionId);
        $path = storage_path('app/public/' . $version->file_path);

        if (!File::exists($path)) {
            abort(404, 'Không tìm thấy file.');
        }

        return response()->download($path, basename($path));
    }

    // 🗑️ Xóa file (và preview nếu có)
    public function destroy($documentId)
    {
        $document = Document::findOrFail($documentId);
        $versions = DocumentVersion::where('document_id', $documentId)->get();

        foreach ($versions as $version) {
            $filePath = storage_path('app/public/' . $version->file_path);
            if (File::exists($filePath)) File::delete($filePath);
        }

        $previews = DocumentPreview::where('document_id', $documentId)->get();
        foreach ($previews as $preview) {
            $previewPath = storage_path('app/public/' . $preview->preview_path);
            if (File::exists($previewPath)) File::delete($previewPath);
        }

        $document->delete();

        return response()->json(['success' => true, 'message' => 'Đã xóa tài liệu và bản xem trước.']);
    }
}
