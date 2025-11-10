<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Folder;

class FolderController extends Controller
{
    public function index()
    {
        return response()->json(Folder::all());
    }
}
