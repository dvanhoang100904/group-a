<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Document;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();

        // Tổng số user
        $totalUsers = User::count();

        // Tổng số user tạo trong tháng
        $usersThisMonth = User::whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->count();

        // Tổng số tài liệu
        $totalDocuments = Document::count();

        // Số tài liệu upload hôm nay
        $documentsToday = Document::whereDate('created_at', $now->toDateString())->count();

        // Số tài liệu upload tuần này
        $documentsThisWeek = Document::whereBetween('created_at', [
            $now->startOfWeek(),
            $now->endOfWeek()
        ])->count();

        // Số tài liệu upload tháng này
        $documentsThisMonth = Document::whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->count();

        return view('dashboard.index', compact(
            'totalUsers',
            'usersThisMonth',
            'totalDocuments',
            'documentsToday',
            'documentsThisWeek',
            'documentsThisMonth'
        ));
    }
}
