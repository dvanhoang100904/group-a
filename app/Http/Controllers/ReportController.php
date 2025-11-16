<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    // Hiển thị danh sách báo cáo có phân trang
    public function index(Request $request)
    {
        $query = Report::with(['document', 'user'])->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $reports = $query->paginate(10);

        return view('reports.index', compact('reports'));
    }

    // Lấy chi tiết báo cáo (AJAX)
    public function show($id)
    {
        $report = Report::with(['document', 'user'])->find($id);

        if (!$report) {
            return response()->json(['error' => 'Không tìm thấy báo cáo.'], 404);
        }

        return response()->json($report);
    }

    // Đánh dấu đã xử lý
    public function resolve($id)
    {
        $report = Report::find($id);

        if (!$report) {
            return redirect()->back()->with('error', 'Không tìm thấy báo cáo.');
        }

        $report->status = 'resolved';
        $report->resolved_at = now();
        $report->save();

        return redirect()->back()->with('success', 'Báo cáo đã được đánh dấu là đã xử lý.');
    }
}
