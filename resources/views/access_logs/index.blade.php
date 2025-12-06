@extends('layouts.app')

@section('title', 'Nhật ký truy cập')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-clock-history me-2"></i>Nhật ký truy cập
            </h1>
            <p class="text-muted mb-0">Theo dõi toàn bộ hoạt động trong hệ thống</p>
        </div>
        <div>
            <a href="#" class="btn btn-primary">
                <i class="bi bi-bar-chart"></i> Thống kê
            </a>
            <a href="{{ route('access.logs.index', ['user_id' => auth()->id()]) }}" class="btn btn-outline-primary">
                <i class="bi bi-person"></i> Nhật ký của tôi
            </a>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('access.logs.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Người dùng</label>
                    <select name="user_id" class="form-select">
                        <option value="">-- Tất cả --</option>
                        @foreach($users as $user)
                            <option value="{{ $user->user_id }}" {{ request('user_id') == $user->user_id ? 'selected' : '' }}>
                                {{ $user->full_name }} ({{ $user->username }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Hành động</label>
                    <select name="action" class="form-select">
                        <option value="">-- Tất cả --</option>
                        @foreach($actions as $action)
                            <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $action)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Từ ngày</label>
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Đến ngày</label>
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-funnel"></i> Lọc
                    </button>
                    <a href="{{ route('access.logs.index') }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Xóa bộ lọc
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="150">Thời gian</th>
                            <th width="200">Người dùng</th>
                            <th width="150">Hành động</th>
                            <th>Tài liệu</th>
                            <th width="120">IP Address</th>
                            <th>Chi tiết</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <!-- ✅ FIX: Thời gian với timezone đúng -->
                                <td>
                                    <small class="text-muted">
                                        {{ $log->created_at->format('d/m/Y H:i:s') }}
                                    </small>
                                </td>

                                <!-- ✅ FIX: Hiển thị user đúng -->
                                <td>
                                    @if($log->user)
                                        <div class="d-flex align-items-center">
                                            @if($log->user->avatar)
                                                <img src="{{ asset('storage/' . $log->user->avatar) }}" 
                                                     alt="Avatar" 
                                                     class="rounded-circle me-2" 
                                                     width="32" 
                                                     height="32">
                                            @else
                                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" 
                                                     style="width: 32px; height: 32px;">
                                                    {{ substr($log->user->full_name, 0, 1) }}
                                                </div>
                                            @endif
                                            <div>
                                                <div class="fw-semibold">{{ $log->user->full_name }}</div>
                                                <small class="text-muted">{{ $log->user->username }}</small>
                                            </div>
                                        </div>
                                    @else
                                        <span class="badge bg-secondary">System</span>
                                    @endif
                                </td>

                                <!-- ✅ FIX: Hành động với icon -->
                                <td>
                                    @php
                                        $actionIcons = [
                                            'login' => 'bi-box-arrow-in-right text-success',
                                            'logout' => 'bi-box-arrow-right text-danger',
                                            'document_view' => 'bi-eye text-info',
                                            'document_upload' => 'bi-upload text-primary',
                                            'document_update' => 'bi-pencil text-warning',
                                            'document_comment' => 'bi-chat-left-text text-secondary',
                                            'document_report' => 'bi-exclamation-triangle text-danger',
                                            'document_download' => 'bi-download text-success',
                                            'document_delete' => 'bi-trash text-danger',
                                        ];
                                        $icon = $actionIcons[$log->action] ?? 'bi-info-circle text-muted';
                                        
                                        $actionNames = [
                                            'login' => 'Đăng nhập',
                                            'logout' => 'Đăng xuất',
                                            'document_view' => 'Xem tài liệu',
                                            'document_upload' => 'Tải lên',
                                            'document_update' => 'Cập nhật',
                                            'document_comment' => 'Bình luận',
                                            'document_report' => 'Báo cáo',
                                            'document_download' => 'Tải xuống',
                                            'document_delete' => 'Xóa',
                                        ];
                                        $actionName = $actionNames[$log->action] ?? ucfirst(str_replace('_', ' ', $log->action));
                                    @endphp
                                    <span>
                                        <i class="bi {{ $icon }} me-1"></i>
                                        {{ $actionName }}
                                    </span>
                                </td>

                                <!-- ✅ FIX: Tài liệu (kiểm tra target_type) -->
                                <td>
                                    @if($log->target_type === 'document' && $log->target_id)
                                        @if($log->document)
                                            <a href="{{ route('document.detail', $log->target_id) }}" 
                                               class="text-decoration-none"
                                               target="_blank">
                                                <i class="bi bi-file-earmark-text me-1"></i>
                                                {{ Str::limit($log->document->title, 40) }}
                                            </a>
                                        @else
                                            <span class="text-muted">
                                                <i class="bi bi-file-earmark-text me-1"></i>
                                                Tài liệu #{{ $log->target_id }}
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                <!-- IP Address -->
                                <td>
                                    <code class="small">{{ $log->ip_address ?? 'N/A' }}</code>
                                </td>

                                <!-- Chi tiết -->
                                <td>
                                    <small class="text-muted">
                                        {{ Str::limit($log->description, 60) }}
                                    </small>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <i class="bi bi-inbox display-1 text-muted"></i>
                                    <p class="text-muted mt-3">Không có nhật ký nào</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        @if($logs->hasPages())
            <div class="card-footer">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>

@if(session('success'))
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
        <div class="toast show" role="alert">
            <div class="toast-header bg-success text-white">
                <strong class="me-auto">Thành công</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
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
    .table th {
        font-weight: 600;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .table td {
        vertical-align: middle;
    }
    
    .badge {
        font-weight: 500;
    }
</style>
@endpush