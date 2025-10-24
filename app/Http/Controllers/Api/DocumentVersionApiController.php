<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadDocumentVersionRequest;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Services\DocumentVersionService;
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
    protected $documentVersionService;

    public function __construct(DocumentVersionService $documentVersionService)
    {
        $this->documentVersionService = $documentVersionService;
    }

    /**
     * Hien thi danh sach phien ban tai lieu co phan trang
     */
    public function index($id)
    {
        $versions = $this->documentVersionService->getDocumentVersionsHasPaginated($id);

        if (!$versions) {
            return response()->json([
                'success' => false,
                'message' => 'Tài liệu không tồn tại'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $versions,
            'message' => 'Danh sách phiên bản tải thành công'
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

    /**
     * Upload tai lieu phen ban moi
     */
    public function upload(UploadDocumentVersionRequest $request, $id)
    {
        $document = Document::find($id);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Tài liệu không tồn tại'
            ], 404);
        }

        if (!$request->hasFile('file')) {
            return response()->json([
                'success' => false,
                'message' => 'Không có tệp nào được tải lên.'
            ], 400);
        }

        // if (auth()->id() !== $document->user_id) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Bạn không có quyền upload phiên bản mới'
        //     ], 403);
        // }

        DB::beginTransaction();
        try {
            // Luu file 
            $file = $request->file('file');
            // Tao ten file an toan
            $fileName = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('docs', $fileName, 'public');

            // Xac dinh version_number tiep theo
            $lastVersion = $document->versions()->orderByDesc('version_number')->first();
            $newVersionNumber = $lastVersion ? $lastVersion->version_number + 1 : 1;

            // Tao moi
            $version = $document->versions()->create([
                'version_number' => $newVersionNumber,
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'change_note' => $request->change_note,
                'is_current_version' => true,
                'user_id' => auth()->id() ?? 1,
            ]);

            // Cap nhat cac phien ban khac ve cu
            $document->versions()
                ->where('version_id', '!=', $version->version_id)
                ->update(['is_current_version' => false]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tải lên phiên bản mới thành công',
                'data' => $version
            ]);
        } catch (Throwable $e) {
            DB::rollBack();

            // Xoa file neu da luu
            if (isset($path) && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra khi tải lên: ' . $e->getMessage()], 500);
        }
    }
}
