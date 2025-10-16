<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;

class DocumentVersionController extends Controller
{
    const PER_PAGE = 1;

    public function index(Request $request, Document $document)
    {
        $versions = $document->versions()
            ->with('user')
            ->orderByDesc('version_number')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('documents.versions.index', compact('document', 'versions'));
    }
}
