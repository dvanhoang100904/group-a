<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;

class DocumentVersionController extends Controller
{
    public function index(Request $request, $id)
    {
        $document = Document::with('subject.department', 'versions.user')->find($id);

        return view('documents.versions.index', [
            'document' => $document,
        ]);
    }
}
