<?php

namespace App\Http\Controllers;

use App\Services\DocumentAccess\DocumentAccessService;
use Illuminate\Http\Request;

class DocumentAccessController extends Controller
{
    protected DocumentAccessService $documentAccessService;

    public function __construct(DocumentAccessService $documentAccessService)
    {
        $this->documentAccessService = $documentAccessService;
    }

    /**
     * Hien thi trang quyen truy cap tai lieu
     */
    public function index(int $documentId)
    {
        $document = $this->documentAccessService->getDocument($documentId);

        if (!$document) {
            return redirect()->route('documents.index')
                ->with('error', 'Tài liệu không tồn tại. Vui lòng thử lại.');
        }

        $data = [
            'document' => $document,
            'subject' => $document->subject,
            'department' => $document->subject->department
        ];

        return view('documents.accesses.index', $data);
    }
}
