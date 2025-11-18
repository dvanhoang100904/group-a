@extends('layouts.app')

@section('title', 'Nhật ký truy cập')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">
                        <i class="bi bi-journal-text text-primary"></i> Nhật ký truy cập
                    </h2>
                    <p class="text-muted mb-0">Theo dõi toàn bộ hoạt động trong hệ thống</p>
                </div>
                <div>
                    <a href="{{ route('access.logs.statistics') }}" class="btn btn-outline-primary">
                        <i class="bi bi-bar-chart"></i> Thống kê
                    </a>
                    <a href="{{ route('access.logs.my') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-person"></i> Nhật ký của tôi
                    </a>
                </div>
            </div>

            <!-- Filter Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('access.logs.index') }}" class="row g-3">
                        <!-- User Filter -->
                        <div class="col-md-3">
                            <label class="form-label">Người dùng</label>
                            <select name="user_id" class="form-select">
                                <option value="">-- Tất cả --</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->user_id }}" 
                                        {{ request('user_id') == $user->user_id ? 'selected' : '' }}>
                                        {{ $user->full_name }} ({{ $user->username }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Action Filter -->
                        <div class="col-md-3">
                            <label class="form-label">Hành động</label>
                            <select name="action" class="form-select">
                                <option value="">-- Tất cả --</option>
                                @foreach($actions as $action)
                                    <option value="{{ $action }}" 
                                        {{ request('action') == $action ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_', ' ', $action)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Start Date -->
                        <div class="col-md-2">
                            <label class="form-label">Từ ngày</label>
                            <input type="date" name="start_date" class="form-control" 
                                   value="{{ request('start_date') }}">
                        </div>

                        <!-- End Date -->
                        <div class="col-md-2">
                            <label class="form-label">Đến ngày</label>
                            <input type="date" name="end_date" class="form-control" 
                                   value="{{ request('end_date') }}">
                        </div>

                        <!-- Submit Button -->
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-funnel"></i> Lọc
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Logs Table -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 160px">Thời gian</th>
                                    <th>Người dùng</th>
                                    <th>Hành động</th>
                                    <th>Tài liệu</th>
                                    <th>IP Address</th>
                                    <th style="width: 80px">Chi tiết</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($logs as $log)
                                <tr>
                                    <!-- Time -->
                                    <td>
                                        <small class="text-muted">
                                            {{ $log->created_at->format('d/m/Y H:i:s') }}
                                        </small>
                                    </td>

                                    <!-- User -->
                                    <td>
                                        @if($log->user)
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm me-2">
                                                    @if($log->user->avatar)
                                                        <img src="{{ asset('storage/' . $log->user->avatar) }}" 
                                                             class="rounded-circle" width="32" height="32">
                                                    @else
                                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                                             style="width: 32px; height: 32px;">
                                                            {{ substr($log->user->full_name, 0, 1) }}
                                                        </div>
                                                    @endif
                                                </div>
                                                <div>
                                                    <div class="fw-medium">{{ $log->user->full_name }}</div>
                                                    <small class="text-muted">{{ $log->user->username }}</small>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>

                                    <!-- Action -->
                                    <td>
                                        @php
                                            $actionIcons = [
                                                'login' => 'bi-box-arrow-in-right',
                                                'logout' => 'bi-box-arrow-right',
                                                'document_view' => 'bi-eye',
                                                'document_upload' => 'bi-upload',
                                                'document_update' => 'bi-pencil',
                                                'document_comment' => 'bi-chat-left-text',
                                                'document_report' => 'bi-exclamation-triangle',
                                                'document_download' => 'bi-download',
                                                'document_delete' => 'bi-trash',
                                            ];
                                            $icon = $actionIcons[$log->action] ?? 'bi-info-circle';
                                            $actionName = ucfirst(str_replace('_', ' ', $log->action));
                                        @endphp
                                        <span class="d-inline-flex align-items-center">
                                            <i class="bi {{ $icon }} me-2"></i>
                                            {{ $actionName }}
                                        </span>
                                    </td>

                                    <!-- Document -->
                                    <td>
                                        @if($log->document)
                                            <a href="{{ route('documents.show', $log->document->document_id) }}" 
                                               class="text-decoration-none">
                                                {{ Str::limit($log->document->title, 40) }}
                                            </a>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>

                                    <!-- IP -->
                                    <td>
                                        <code class="small">{{ $log->ip_address ?? 'N/A' }}</code>
                                    </td>

                                    <!-- Actions -->
                                    <td>
                                        <a href="{{ route('access.logs.show', $log->access_log_id) }}" 
                                           class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <i class="bi bi-inbox display-4 text-muted"></i>
                                        <p class="text-muted mt-3">Không có nhật ký nào</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $logs->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
<div class="toast-container position-fixed top-0 end-0 p-3">
    <div class="toast show" role="alert">
        <div class="toast-header bg-success text-white">
            <i class="bi bi-check-circle me-2"></i>
            <strong class="me-auto">Thành công</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">
            {{ session('success') }}
        </div>
    </div>
</div>
@endif
@endsection

@push('styles')
<style>
.avatar-sm img,
.avatar-sm div {
    object-fit: cover;
}

.table th {
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
}

code {
    background-color: #f8f9fa;
    padding: 2px 6px;
    border-radius: 3px;
}
</style>
@endpush
