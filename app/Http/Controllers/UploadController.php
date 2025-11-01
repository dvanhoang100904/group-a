<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\DocumentPreview;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpWord\IOFactory;
use setasign\Fpdi\Fpdi;
use Exception;

class UploadController extends Controller
{
    // 📄 Trang upload (Blade)
    public function index()
    {
        return view('documents.Upload_Documents.Index_Upload');
    }
    protected function convertToPdf($file, $documentId)
    {
        $pdfPath = "documents/previews/{$documentId}_" . Str::uuid() . ".pdf";
        // ⚠️ Ở đây bạn có thể dùng thư viện `unoconv`, `libreoffice`, hoặc queue job xử lý.
        // Để demo: tạm copy file gốc như file PDF giả
        Storage::disk('public')->put($pdfPath, file_get_contents($file->getRealPath()));
        return $pdfPath;
    }
     public function store(Request $request)
    {
        if (!$request->hasFile('file')) {
            return response()->json(['error' => 'Không có file tải lên.'], 400);
        }

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        $mime = $file->getMimeType();
        $uuid = Str::uuid()->toString();

        // 📂 Lưu file gốc
        $fileName = pathinfo($originalName, PATHINFO_FILENAME) . '-' . $uuid . '.' . $extension;
        $filePath = $file->storeAs('documents/versions', $fileName, 'public');
        $fileSize = $file->getSize();

        // ⚙️ Tạo Document (nếu cần)
        $document = Document::create([
            'title' => $originalName,
            'description' => $request->input('description', null),
            'status' => 'private',
            'user_id' => auth()->id() ?? 1, // tạm cho user_id = 1 nếu chưa auth
            'folder_id' => $request->input('folder_id', null),
            'type_id' => $request->input('type_id', 1),
            'subject_id' => $request->input('subject_id', 1),
        ]);

        // 🧾 Tạo Version
        $version = DocumentVersion::create([
            'version_number' => 1,
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'mime_type' => $mime,
            'is_current_version' => true,
            'change_note' => 'Initial upload',
            'document_id' => $document->document_id,
            'user_id' => auth()->id() ?? 1,
        ]);

        // 🧩 Convert sang PDF nếu là DOCX
        if (in_array($extension, ['doc', 'docx'])) {
            try {
                $phpWord = IOFactory::load($file->getPathname());
                $pdfFileName = pathinfo($fileName, PATHINFO_FILENAME) . '.pdf';
                $pdfPath = 'documents/previews/' . $pdfFileName;

                $pdfWriter = IOFactory::createWriter($phpWord, 'PDF');
                $pdfWriter->save(storage_path('app/public/' . $pdfPath));

                DocumentPreview::create([
                    'preview_path' => $pdfPath,
                    'expires_at' => now()->addDays(7),
                    'generated_by' => auth()->id() ?? 1,
                    'document_id' => $document->document_id,
                    'version_id' => $version->version_id,
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'Lỗi khi convert sang PDF: ' . $e->getMessage(),
                ], 500);
            }
        }

        return response()->json([
            'message' => 'Tải lên thành công!',
            'document' => $document,
            'version' => $version,
        ], 201);
    }


    // 📦 Download file
    public function download($version)
    {
        $path = base_path('app/Public_UploadFile/' . $version);

        if (!File::exists($path)) {
            abort(404, 'Không tìm thấy file.');
        }

        return response()->download($path);
    }

    // 🗑️ Xóa file
    public function destroy($document)
    {
        $path = base_path('app/Public_UploadFile/' . $document);

        if (File::exists($path)) {
            File::delete($path);
            return response()->json(['success' => true, 'message' => 'Đã xóa file ' . $document]);
        }

        return response()->json(['success' => false, 'message' => 'Không tìm thấy file.'], 404);
    }

    // 📜 Metadata (liệt kê file)
    public function getMetadata()
    {
        $dir = base_path('app/Public_UploadFile');
        if (!File::exists($dir)) {
            return response()->json([]);
        }

        $files = collect(File::files($dir))->map(function ($file) {
            return [
                'name' => $file->getFilename(),
                'size' => round($file->getSize() / 1024, 2) . ' KB',
                'updated' => date('Y-m-d H:i:s', $file->getMTime()),
            ];
        });

        return response()->json(['success' => true, 'files' => $files]);
    }
}
