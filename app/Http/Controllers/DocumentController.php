<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\User;
use App\Models\Subject;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        $documents = Document::with(['user', 'subject'])
            ->orderBy($sortBy == 'user_name' ? 'user_id' : $sortBy, $sortOrder)
            ->paginate(10);

        $subjects = Subject::all();

        return view('dashboard.my_documents', compact('documents', 'subjects', 'sortBy', 'sortOrder'));
    }
    public function edit($id)
    {
    
    }

    public function update(Request $request, $id)
    {
        
    }

    public function destroy($id)
    {
        
    }
}
