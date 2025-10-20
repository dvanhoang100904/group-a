<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;
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

        //Lay preview moi nhat va chua het han
        $preview = $version->previews()
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('created_at')
            ->first();

        if ($preview) {
            return response()->json([
                'success' => true,
                'data' => [
                    'preview_path' => '/storage/' . $preview->preview_path,
                    'expires_at' => $preview->expires_at
                ]
            ]);
        }

        //  Neu la file DOCX, convert sang PDF
        $ext = pathinfo($version->file_path, PATHINFO_EXTENSION);
        if (strtolower($ext) === 'docx') {
            $sourcePath = storage_path('app/public/' . $version->file_path);
            $targetPath = 'previews/doc' . $version->document_id . '_v' . $version->version_number . '_preview.pdf';
            // $fullTargetPath = storage_path('app/public/' . $targetPath);

            try {
                // Cau hinh PHPWORD su dung mPDF lam renderer
                Settings::setPdfRendererName('MPDF');
                Settings::setPdfRendererPath(base_path('vendor/mpdf/mpdf'));

                // Doc file Word goc
                $phpWord = IOFactory::load($sourcePath);

                // Tao file PDF tam trong thu muc he thong
                $tempFile = tempnam(sys_get_temp_dir(), 'preview_');
                $pdfWriter = IOFactory::createWriter($phpWord, 'PDF');
                $pdfWriter->save($tempFile);

                // Ghi file PDF vao storage/public qua Storage facade
                Storage::disk('public')->put($targetPath, file_get_contents($tempFile));

                // Xoa file tam sau khi luu
                @unlink($tempFile);

                // Luu thong tin preview vao db
                $preview = $version->previews()->create([
                    'preview_path' => $targetPath,
                    'generated_by' => $version->user_id,
                    'expires_at' => now()->addDays(7),
                ]);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'preview_path' => '/storage/' . $preview->preview_path,
                        'expires_at' => $preview->expires_at
                    ]
                ]);
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể tạo preview: ' . $e->getMessage()
                ], 500);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Không có preview cho phiên bản này'
        ]);
    }
}
