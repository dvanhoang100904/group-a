<?php

namespace App\Services\DocumentVersion;

use App\Models\DocumentVersion;

class DocumentVersionCompareService
{
    public function compareVersions(int $documentId, int $versionAId, int $versionBId): ?array
    {
        $versionA = DocumentVersion::byDocument($documentId)->find($versionAId);
        $versionB = DocumentVersion::byDocument($documentId)->find($versionBId);

        if (!$versionA || !$versionB) {
            return null;
        }

        $differences = [];

        // So sanh kich thuoc file
        if ($versionA->file_size !== $versionB->file_size) {
            $differences[] = [
                'type' => 'size',
                'old' => $versionA->file_size,
                'new' => $versionB->file_size,
            ];
        }

        // So sanh ghi chu
        if ($versionA->change_note !== $versionB->change_note) {
            $differences[] = [
                'type' => 'note',
                'old' => $versionA->change_note,
                'new' => $versionB->change_note,
            ];
        }

        // So sánh người cập nhật
        if ($versionA->user_id !== $versionB->user_id) {
            $differences[] = [
                'type' => 'user',
                'old' => optional($versionA->user)->name ?? 'Không rõ',
                'new' => optional($versionB->user)->name ?? 'Không rõ',
            ];
        }

        return $differences;
    }
}
