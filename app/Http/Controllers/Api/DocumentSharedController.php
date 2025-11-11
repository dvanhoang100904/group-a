<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DocumentShared\DocumentSharedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentSharedController extends Controller
{
    protected DocumentSharedService $sharedService;

    public function __construct(DocumentSharedService $sharedService)
    {
        $this->sharedService = $sharedService;
    }

    public function index(): JsonResponse
    {
        $userId = auth()->id() ?? 1;

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa đăng nhập.',
            ]);
        }

        $data = $this->sharedService->getSharedDocumentsPaginated($userId);

        return response()->json([
            'success' => true,
            'data' => $data->items(),
            'pagination' => [
                'total' => $data->total(),
                'per_page' => $data->perPage(),
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
            ],
        ]);
    }
}
