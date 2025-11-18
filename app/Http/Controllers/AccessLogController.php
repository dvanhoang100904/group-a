<?php

namespace App\Http\Controllers;

use App\Models\AccessLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccessLogController extends Controller
{
    /**
     * Hiển thị danh sách logs với filter
     */
    public function index(Request $request)
    {
        $query = AccessLog::with('user');

        // Filter theo user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter theo action
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Filter theo target type
        if ($request->filled('target_type')) {
            $query->where('target_type', $request->target_type);
        }

        // Filter theo ngày
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Sắp xếp và phân trang
        $logs = $query->latest('created_at')->paginate(20)->withQueryString();

        // Lấy danh sách users và actions cho filter
        $users = User::orderBy('name')->get(['user_id', 'name', 'email']);
        $actions = AccessLog::select('action')->distinct()->orderBy('action')->pluck('action');

        return view('access_logs.index', compact('logs', 'users', 'actions'));
    }

    /**
     * Hiển thị chi tiết một log
     */
    public function show($id)
    {
        $log = AccessLog::with('user')->findOrFail($id);
        return view('access_logs.show', compact('log'));
    }

    /**
     * Dashboard thống kê logs
     */
    public function statistics(Request $request)
    {
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        // Tổng logs
        $totalLogs = AccessLog::whereBetween('created_at', [$startDate, $endDate])->count();

        // Số user khác nhau
        $uniqueUsers = AccessLog::whereBetween('created_at', [$startDate, $endDate])
                                ->distinct('user_id')->count('user_id');

        // Top 10 users hoạt động nhiều nhất
        $topUsers = AccessLog::select('user_id', DB::raw('count(*) as total'))
            ->with('user:user_id,name,email')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // Thống kê theo action
        $topActions = AccessLog::select('action', DB::raw('count(*) as total'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('action')
            ->orderByDesc('total')
            ->get();

        // Logs hôm nay
        $todayLogs = AccessLog::whereDate('created_at', today())->count();

        // Logs trong tuần
        $weekLogs = AccessLog::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();

        return view('access_logs.statistics', compact(
            'totalLogs',
            'uniqueUsers',
            'topUsers',
            'topActions',
            'todayLogs',
            'weekLogs',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Xem logs của một document cụ thể
     */
    public function documentLogs($documentId)
    {
        $logs = AccessLog::with('user')
            ->where('target_id', $documentId)
            ->where('target_type', 'document')
            ->latest('created_at')
            ->paginate(20);

        return view('access_logs.document_logs', compact('logs', 'documentId'));
    }

    /**
     * Xóa logs cũ (admin)
     */
    public function cleanup(Request $request)
    {
        $request->validate([
            'days' => 'required|integer|min:1|max:365'
        ]);

        $deletedCount = AccessLog::where('created_at', '<', now()->subDays($request->days))->delete();

        return back()->with('success', "Đã xóa {$deletedCount} bản ghi log cũ hơn {$request->days} ngày.");
    }
}
