<?php

namespace App\Http\Controllers;

use App\Services\DocumentAccess\DocumentAccessService;
use Illuminate\Http\Request;

class DocumentAccessController extends Controller
{
    protected $documentAccessService;

    public function __construct(DocumentAccessService $documentAccessService)
    {
        $this->documentAccessService = $documentAccessService;
    }

    /**
     * Hien thi trang phan quyen va chia se tai lieu
     */
    public function index($id)
    {
        $document = $this->documentAccessService->getDocumentWithRelations($id);

        if (!$document) {
            return redirect()->route('documents.index')->with('error', 'Tài liệu không tồn tại hoặc đã bị xóa.');
        }

        return view('documents.accesses.index', [
            'document' => $document,
        ]);
    }
}
