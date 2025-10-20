<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Writer\PDF\Mpdf as PDFWriter;
use Throwable;

class DocumentVersionApiController extends Controller
{
    const PER_PAGE = 3;

    /**
     * Hien thi danh sach phien ban tai lieu co phan trang
     */
    public function index($id)
    {
        $document = Document::find($id);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Tài liệu không tồn tại'
            ], 404);
        }

        $versions = $document->versions()
            ->with('user')
            ->latest()
            ->paginate(self::PER_PAGE);

        return response()->json([
            'success' => true,
            'data' => $versions,
            'message' => 'Danh sách phiên bản'
        ]);
    }

    /**
     * Hien thi preview file
     */
    public function preview($id)
    {
        $version = DocumentVersion::with('previews')->find($id);

        if (!$version) {
            return response()->json([
                'success' => false,
                'message' => 'Phiên bản không tồn tại'
            ], 404);
        }

        // Lấy preview mới nhất còn hạn
        $preview = $version->previews()
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('created_at')
            ->first();

        if ($preview && Storage::disk('public')->exists($preview->preview_path)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'preview_path' => Storage::url($preview->preview_path),
                    'file_name' => basename($version->file_path),
                    'expires_at' => $preview->expires_at
                ]
            ]);
        }

        // Chuyển DOCX sang PDF nếu cần
        $filePath = storage_path('app/public/' . $version->file_path);
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);

        if ($ext === 'docx') {
            $pdfPreviewPath = 'previews/' . $version->version_id . '.pdf';
            $pdfFullPath = storage_path('app/public/' . $pdfPreviewPath);

            if (!file_exists($pdfFullPath)) {
                if (!file_exists(storage_path('app/public/previews'))) {
                    mkdir(storage_path('app/public/previews'), 0755, true);
                }

                try {
                    $phpWord = IOFactory::load($filePath);
                    $pdfWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'PDF');
                    $pdfWriter->save($pdfFullPath);
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Không thể tạo preview PDF: ' . $e->getMessage()
                    ]);
                }
            }

            $previewUrl = Storage::url($pdfPreviewPath);
        } else {
            // File PDF hoặc khác
            $previewUrl = Storage::url($version->file_path);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'preview_path' => $previewUrl,
                'file_name' => basename($version->file_path)
            ]
        ]);
    }
}
