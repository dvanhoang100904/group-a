<?php
namespace App\Services\DocumentPreview;

use App\Models\DocumentPreview;
use Illuminate\Support\Facades\Storage;

class PreviewGenerator
{
    public function generate($documentId, $version)
    {
        $sourcePath = $version->file_path; 
        $versionId = $version->version_id;

        $previewPath = "documents/$documentId/preview_$versionId.pdf";

        // Nếu đã có preview → trả ngay
        if (Storage::disk('public')->exists($previewPath)) {
            return Storage::url($previewPath);
        }

        // Tạo thư mục preview
        Storage::disk('public')->makeDirectory("documents/$documentId");

        $inputFile = storage_path("app/public/$sourcePath");
        $outputDir = storage_path("app/public/documents/$documentId");

        // Lấy EXT
        $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));

        if ($ext === 'pdf') {

            Storage::disk('public')->copy($sourcePath, $previewPath);

        } else {

            // Convert DOCX → PDF
            shell_exec(
                "libreoffice --headless --convert-to pdf --outdir \"$outputDir\" \"$inputFile\""
            );

            $convertedPdf = $outputDir . '/' . pathinfo($sourcePath, PATHINFO_FILENAME) . '.pdf';
            $finalPath = storage_path("app/public/$previewPath");

            // Nếu file tồn tại → đổi tên cho đúng preview_x.pdf
            if (file_exists($convertedPdf)) {
                rename($convertedPdf, $finalPath);
            }
        }

        // Nếu vẫn không có file → trả null
        if (!Storage::disk('public')->exists($previewPath)) {
            return null;
        }

        // Lưu DB
        DocumentPreview::create([
            'preview_path' => $previewPath,
            'document_id' => $documentId,
            'version_id' => $versionId,
            'generated_by' => auth()->id(),
        ]);

        return Storage::url($previewPath);
    }
}
