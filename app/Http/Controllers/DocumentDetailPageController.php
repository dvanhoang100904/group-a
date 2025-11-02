<?php

namespace App\Http\Controllers;

use App\Models\Document;

class DocumentDetailPageController extends Controller
{
    public function show($id)
    {
        $document = Document::with(['user', 'type', 'subject', 'versions', 'tags'])
            ->findOrFail($id);

        return view('documents.See_Document_Details.Document_Detail', compact('document'));
    }
}
