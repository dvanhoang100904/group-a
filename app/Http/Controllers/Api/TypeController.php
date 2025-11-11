<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Type;
use Illuminate\Http\Request;

class TypeController extends Controller
{
	/**
	 * API: Danh sách loại tài liệu (JSON) phù hợp schema hiện tại
	 */
	public function index(Request $request)
	{
		$query = Type::query();

		if ($request->filled('name')) {
			$query->where('name', 'like', '%' . $request->name . '%');
		}

		if ($request->filled('status')) {
			$query->where('status', (bool) $request->boolean('status'));
		}

		$types = $query
			->withCount('documents')
			->orderBy('created_at', 'desc')
			->paginate(15);

		return response()->json($types);
	}
}