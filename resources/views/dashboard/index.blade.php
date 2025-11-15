@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="p-6 bg-white min-h-screen">

    <h2 class="text-3xl font-bold mb-6 text-blue-700">📊 Dashboard Admin</h2>
    <p class="text-gray-600 mb-6">Tổng quan hệ thống quản lý tài liệu</p>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <!-- Tổng user -->
        <div class="p-4 bg-blue-50 rounded-lg shadow hover:shadow-lg transition">
            <h3 class="text-lg font-semibold text-blue-700">Tổng số user</h3>
            <p class="text-3xl font-bold text-blue-800">{{ $totalUsers }}</p>
        </div>

        <!-- User tạo trong tháng -->
        <div class="p-4 bg-blue-50 rounded-lg shadow hover:shadow-lg transition">
            <h3 class="text-lg font-semibold text-blue-700">User tạo trong tháng</h3>
            <p class="text-3xl font-bold text-blue-800">{{ $usersThisMonth }}</p>
        </div>

        <!-- Tổng tài liệu -->
        <div class="p-4 bg-blue-50 rounded-lg shadow hover:shadow-lg transition">
            <h3 class="text-lg font-semibold text-blue-700">Tổng số tài liệu</h3>
            <p class="text-3xl font-bold text-blue-800">{{ $totalDocuments }}</p>
        </div>

        <!-- Tài liệu hôm nay -->
        <div class="p-4 bg-green-50 rounded-lg shadow hover:shadow-lg transition">
            <h3 class="text-lg font-semibold text-green-700">Tài liệu hôm nay</h3>
            <p class="text-3xl font-bold text-green-800">{{ $documentsToday }}</p>
        </div>

        <!-- Tài liệu tuần này -->
        <div class="p-4 bg-green-50 rounded-lg shadow hover:shadow-lg transition">
            <h3 class="text-lg font-semibold text-green-700">Tài liệu tuần này</h3>
            <p class="text-3xl font-bold text-green-800">{{ $documentsThisWeek }}</p>
        </div>

        <!-- Tài liệu tháng này -->
        <div class="p-4 bg-green-50 rounded-lg shadow hover:shadow-lg transition">
            <h3 class="text-lg font-semibold text-green-700">Tài liệu tháng này</h3>
            <p class="text-3xl font-bold text-green-800">{{ $documentsThisMonth }}</p>
        </div>

    </div>
</div>
@endsection
