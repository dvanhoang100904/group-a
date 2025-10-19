<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;

class DocumentVersionApiController extends Controller
{
    const PER_PAGE = 5;

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
}
