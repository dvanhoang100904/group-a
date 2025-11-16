<?php

namespace App\Http\Controllers;

use App\Http\Requests\DocumentAccess\DocumentAccessUpdateSettingRequest;
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

    /**
     * Thiet lap quyen truy cap tai lieu
     */
    public function updateSettings(DocumentAccessUpdateSettingRequest $request, int $documentId)
    {
        $document = $this->documentAccessService->getDocumentById($documentId);

        if (!$document) {
            return redirect()->route('documents.accesses.index', ['documentId' => $documentId])
                ->with('error', 'Tài liệu không tồn tại.');
        }

        $data = $request->only([
            'share_mode',
            'share_link',
            'expiration_date',
            'no_expiry'
        ]);

        $access = $this->documentAccessService->updateSettingAccess($documentId, $data);

        if (!$access) {
            return redirect()->route('documents.accesses.index', ['documentId' => $documentId])
                ->with('error', 'Không thể cập nhật quyền truy cập. Vui lòng thử lại.');
        }

        return redirect()->route('documents.accesses.index', ['documentId' => $documentId])
            ->with('success', 'Quyền truy cập đã được cập nhật.');
    }
}
