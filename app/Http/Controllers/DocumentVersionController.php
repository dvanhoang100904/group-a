<?php

namespace App\Http\Controllers;

use App\Services\DocumentVersion\DocumentVersionService;
use Illuminate\Http\Request;

class DocumentVersionController extends Controller
{
    protected DocumentVersionService $documentVersionService;

    public function __construct(DocumentVersionService $documentVersionService)
    {
        $this->documentVersionService = $documentVersionService;
    }

    /**
     * Hien thi trang phien ban tai lieu
     */
    public function index(int $documentId)
    {
        $document = $this->documentVersionService->getDocument($documentId);

        if (!$document) {
            return redirect()->route('documents.index')
                ->with('error', 'Tài liệu không tồn tại. Vui lòng thử lại.');
        }

        $data = [
            'document' => $document,
            'subject' => $document->subject,
            'department' => $document->subject->department
        ];

        return view('documents.versions.index', $data);
    }
}
