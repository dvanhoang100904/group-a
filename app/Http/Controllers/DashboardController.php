<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
     public function index()
    {
         return view('dashboard.index');
        $reports = Report::orderBy('created_at', 'desc')->paginate(10);
        return view('dashboard.reports.index', compact('reports'));
    }

    // Đánh dấu đã xử lý
    public function resolve($id)
    {
        $report = Report::where('report_id', $id)->firstOrFail();
        $report->status = 'resolved';
        $report->resolved_at = now();
        $report->save();

        return redirect()->back()->with('success', 'Báo cáo đã được đánh dấu là "Đã xử lý"!');
    }

    // Xem chi tiết (AJAX)
    public function show($id)
    {
        $report = Report::findOrFail($id);
        return response()->json($report);
    }
    // public function index()
    // {
    //     return view('dashboard.index');
        
    // }
}
