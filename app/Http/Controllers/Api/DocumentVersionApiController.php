<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;

class DocumentVersionApiController extends Controller
{
    const PER_PAGE = 3;

    public function index(Request $request, $id)
    {
        $document = Document::find($id);

        $versions = $document->versions()
            ->with('user')
            ->latest()
            ->paginate(self::PER_PAGE);

        return response()->json($versions);
    }
}
