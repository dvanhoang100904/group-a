<?php

namespace App\Http\Controllers;

use App\Services\DocumentVersionService;
use Illuminate\Http\Request;

class DocumentVersionController extends Controller
{
    protected $documentVersionService;

    public function __construct(DocumentVersionService $documentVersionService)
    {
        $this->documentVersionService = $documentVersionService;
    }

    /**
     * Hien thi trang phien ban tai lieu
     */
    public function index($id)
    {
        $document = $this->documentVersionService->getDocumentWithRelations($id);

        if (!$document) {
            return redirect()->route('documents.index')->with('error', 'Tài liệu không tồn tại hoặc đã bị xóa.');
        }

        return view('documents.versions.index', [
            'document' => $document,
        ]);
    }
}
