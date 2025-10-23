<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;

class DocumentVersionController extends Controller
{
    /**
     * Hien thi trang phien ban tai lieu
     */
    public function index($id)
    {
        $document = Document::with('subject.department')
            ->withCount('versions')
            ->find($id);

        return view('versions.index', [
            'document' => $document,
        ]);
    }
}
