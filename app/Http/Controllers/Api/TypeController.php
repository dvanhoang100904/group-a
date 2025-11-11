<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Type;

class TypeController extends Controller
{
    public function index()
    {
        return response()->json(Type::all());
    }
}
