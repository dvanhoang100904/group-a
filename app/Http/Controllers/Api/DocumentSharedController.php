<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentShared\DocumentSharedFilterRequest;
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

    public function index(DocumentSharedFilterRequest $request): JsonResponse
    {
        $userId = auth()->id() ?? 1;

        $filters = $request->only([
            'keyword',
            'user_id',
            'from_date',
            'to_date'
        ]);

        $data = $this->sharedService->getSharedDocumentsPaginated($userId, $filters);

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

    /**
     * Hien thi danh sach nguoi dung de loc tai lieu chia se voi toi
     */
    public function listUsers()
    {
        $userId = auth()->id() ?? 1;

        $users = $this->sharedService->getSharedForUser($userId);

        if (!$users) {
            return response()->json([
                'success' => false,
                'message' => 'Chưa có người dùng nào.'
            ]);
        }
        return response()->json([
            'success' => true,
            'data' => $users,
            'message' => 'Danh sách người dùng tải thành công.'
        ]);
    }
}
