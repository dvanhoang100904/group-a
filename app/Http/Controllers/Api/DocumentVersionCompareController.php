<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompareDocumentVersionRequest;
use App\Services\DocumentVersion\DocumentVersionCompareService;
use Illuminate\Http\Request;

class DocumentVersionCompareController extends Controller
{
    protected $compareService;

    public function __construct(DocumentVersionCompareService $compareService)
    {
        $this->compareService = $compareService;
    }

    /**
     * So sanh hai phien ban tai lieu
     */
    public function compare(CompareDocumentVersionRequest $request, $documentId)
    {
        $versionA = $request->query('version_a');
        $versionB = $request->query('version_b');

        if (!$versionA || !$versionB) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng chọn cả hai phiên bản'
            ], 422);
        }

        $differences = $this->compareService->compareVersions($documentId, $versionA, $versionB);

        if ($differences === null) {
            return response()->json([
                'success' => false,
                'message' => 'Phiên bản không tồn tại'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $differences
        ]);
    }
}
