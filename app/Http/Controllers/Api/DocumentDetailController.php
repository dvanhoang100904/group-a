<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;

class DocumentDetailController extends Controller
{
    public function show($id)
    {
        // sẽ sữa 
        $document = Document::with(['user', 'type', 'subject', 'versions', 'tags'])
            ->findOrFail($id);

        $currentVersion = $document->versions()
            ->where('is_current_version', true)
            ->first();

        return response()->json([
            'document' => $document,
            'current_version' => $currentVersion,
            'related_documents' => Document::where('subject_id', $document->subject_id)
                ->where('document_id', '!=', $id)
                ->take(3)
                ->get(),
        ]);
    }
}
