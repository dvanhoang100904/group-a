<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadDocumentVersionRequest;
use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\Section;
use Dompdf\Dompdf;
use Dompdf\Options;
use Throwable;
use Illuminate\Support\Str;

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

        // Lay preview moi nhat con han
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

        $filePath = storage_path('app/public/' . $version->file_path);
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $previewUrl = null;

        if ($ext === 'docx') {
            $pdfPreviewPath = 'previews/' . $version->version_id . '.pdf';
            $pdfFullPath = storage_path('app/public/' . $pdfPreviewPath);

            if (!file_exists($pdfFullPath)) {
                if (!file_exists(storage_path('app/public/previews'))) {
                    mkdir(storage_path('app/public/previews'), 0755, true);
                }

                try {
                    $phpWord = IOFactory::load($filePath);

                    // Set font Unicode cho toàn bộ document
                    foreach ($phpWord->getSections() as $section) {
                        $this->setFontRecursive($section, 'DejaVu Sans');
                    }

                    // Nếu DomPDF đã cài
                    if (class_exists(Dompdf::class)) {

                        // Cấu hình font directory & cache để DomPDF nhận font Unicode
                        $fontDir = storage_path('fonts');
                        if (!file_exists($fontDir)) {
                            mkdir($fontDir, 0755, true);
                        }

                        $options = new Options();
                        $options->set('fontDir', $fontDir);
                        $options->set('fontCache', $fontDir);
                        $options->set('defaultFont', 'DejaVu Sans');
                        $options->set('isRemoteEnabled', true); // nếu muốn load asset ngoài

                        // $dompdf = new Dompdf($options);

                        Settings::setPdfRendererName(Settings::PDF_RENDERER_DOMPDF);
                        Settings::setPdfRendererPath(base_path('vendor/dompdf/dompdf'));

                        $pdfWriter = IOFactory::createWriter($phpWord, 'PDF');
                        $pdfWriter->save($pdfFullPath);

                        $previewUrl = Storage::url($pdfPreviewPath);
                    } else {
                        // Fallback Office Online Viewer
                        $docxUrl = asset('storage/' . $version->file_path);
                        $previewUrl = "https://view.officeapps.live.com/op/embed.aspx?src=" . urlencode($docxUrl);
                    }
                } catch (Throwable $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Không thể tạo preview PDF/Word: ' . $e->getMessage()
                    ]);
                }
            } else {
                // PDF đã tồn tại thi dùng luôn
                $previewUrl = Storage::url($pdfPreviewPath);
            }
        } else {
            // PDF hoặc file khác thi trả thẳng
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

    /**
     * set font
     */
    private function setFontRecursive($element, string $fontName = 'DejaVu Sans')
    {
        if ($element instanceof Text) {
            $fontStyle = $element->getFontStyle();
            if ($fontStyle) {
                $fontStyle->setName($fontName);
            }
        } elseif ($element instanceof TextRun && method_exists($element, 'getElements')) {
            foreach ($element->getElements() as $child) {
                $this->setFontRecursive($child, $fontName);
            }
        } elseif ($element instanceof Table && method_exists($element, 'getRows')) {
            foreach ($element->getRows() as $row) {
                if (method_exists($row, 'getCells')) {
                    foreach ($row->getCells() as $cell) {
                        if (method_exists($cell, 'getElements')) {
                            foreach ($cell->getElements() as $child) {
                                $this->setFontRecursive($child, $fontName);
                            }
                        }
                    }
                }
            }
        } elseif ($element instanceof Section && method_exists($element, 'getElements')) {
            foreach ($element->getElements() as $child) {
                $this->setFontRecursive($child, $fontName);
            }
        }
    }
}
