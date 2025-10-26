<?php

namespace App\Services\DocumentVersion;

use App\Models\DocumentVersion;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\Section;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Log;
use Throwable;

class DocumentVersionPreviewService
{
    // Dinh nghia thu muc luu file
    private const PREVIEW_DIR = 'previews/';

    /** 
     * Lay preview neu co, neu chua co thi sinh moi
     */
    public function getOrGeneratePreview(int $versionId, ?int $documentId = null): ?array
    {
        // Lay phien ban tai lieu kem previews
        $version = DocumentVersion::with('previews')->find($versionId);

        if (!$version) {
            return null;
        }

        if ($documentId && $version->document_id != $documentId) {
            return null;
        }

        // Lay preview Con thoi han, moi nhat
        $preview = $version->previews()->active()->latestCreated()->first();

        // Neu preview ton tai và file thuc su con trên disk
        if ($preview && Storage::disk('public')->exists($preview->preview_path)) {
            return $this->buildPreviewResponse(
                $preview->preview_path,
                $version->file_path,
                $preview->expires_at
            );
        }

        // Neu chua co preview hop le, sinh moi
        return $this->generatePreviewFile($version);
    }

    /**
     * Sinh preview moi neu can
     */
    private function generatePreviewFile(DocumentVersion $version): ?array
    {
        $filePath = storage_path('app/public/' . $version->file_path);
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        // Neu la DOCX thi convert sang PDF
        if (in_array($ext, ['docx'], true)) {
            return $this->generatePdfPreview($version, $filePath);
        }

        // Cac file khac thi tra truc tiep duong dan
        return $this->buildPreviewResponse($version->file_path, $version->file_path);
    }

    /**
     * Convert DOCX sang PDF và luu vao preview
     */
    private function generatePdfPreview(DocumentVersion $version, string $filePath): ?array
    {
        $pdfPreviewPath = 'previews/' . $version->version_id . '.pdf';
        $pdfFullPath = storage_path('app/public/' . $pdfPreviewPath);

        if (!file_exists(dirname($pdfFullPath))) {
            mkdir(dirname($pdfFullPath), 0755, true);
        }

        if (!file_exists($pdfFullPath)) {
            try {
                $phpWord = IOFactory::load($filePath);

                // Set font Unicode toan bo document
                foreach ($phpWord->getSections() as $section) {
                    $this->setFontRecursive($section, 'DejaVu Sans');
                }

                // Xuat DOCX sang HTML
                $htmlWriter = IOFactory::createWriter($phpWord, 'HTML');
                ob_start();
                $htmlWriter->save('php://output');
                $htmlContent = ob_get_clean();

                // Chuyen HTML sang PDF bang DomPDF
                $options = new Options();
                $options->set('isRemoteEnabled', true);
                $options->set('defaultFont', 'DejaVu Sans');

                $dompdf = new Dompdf($options);
                $dompdf->loadHtml($htmlContent);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();

                file_put_contents($pdfFullPath, $dompdf->output());

                Log::info("Preview generated for version {$version->version_id}", ['path' => $pdfPreviewPath]);
            } catch (Throwable $e) {
                report($e);
                return null;
            }
        }

        return $this->buildPreviewResponse($pdfPreviewPath, $version->file_path);
    }

    /**
     * Chuan bi du lieu tra ve preview
     */
    private function buildPreviewResponse(string $previewPath, string $filePath, ?string $expiresAt = null): array
    {
        return [
            // URL truy cap file
            'preview_path' => Storage::url($previewPath),
            // Ten file goc
            'file_name' => basename($filePath),
            // Thoi han
            'expires_at' => $expiresAt,
        ];
    }

    /** 
     * Ap font Unicode toan bo tai lieu 
     */
    private function setFontRecursive(object $element, string $fontName = 'DejaVu Sans'): void
    {
        if ($element instanceof Text) {
            $element->getFontStyle()?->setName($fontName);
        } elseif ($element instanceof TextRun || $element instanceof Section) {
            foreach ($element->getElements() ?? [] as $child) {
                $this->setFontRecursive($child, $fontName);
            }
        } elseif ($element instanceof Table) {
            foreach ($element->getRows() ?? [] as $row) {
                foreach ($row->getCells() ?? [] as $cell) {
                    foreach ($cell->getElements() ?? [] as $cellElement) {
                        $this->setFontRecursive($cellElement, $fontName);
                    }
                }
            }
        }
    }
}
