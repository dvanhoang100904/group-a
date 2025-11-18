@extends('layouts.app')

@section('title', 'Chi tiết nhật ký')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Back Button -->
            <div class="mb-4">
                <a href="{{ route('access.logs.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Quay lại
                </a>
            </div>

            <!-- Log Detail Card -->
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-info-circle"></i> Chi tiết nhật ký truy cập
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <!-- Time -->
                        <div class="col-md-6">
                            <label class="text-muted small">Thời gian</label>
                            <div class="fw-medium">
                                {{ $log->created_at->format('d/m/Y H:i:s') }}
                                <small class="text-muted">({{ $log->created_at->diffForHumans() }})</small>
                            </div>
                        </div>

                        <!-- Action -->
                        <div class="col-md-6">
                            <label class="text-muted small">Hành động</label>
                            <div>
                                <span class="badge bg-primary fs-6">
                                    <i class="bi {{ $log->action_icon }}"></i>
                                    {{ $log->action_name }}
                                </span>
                            </div>
                        </div>

                        <!-- User -->
                        <div class="col-12">
                            <label class="text-muted small">Người thực hiện</label>
                            @if($log->user)
                            <div class="d-flex align-items-center mt-2">
                                @if($log->user->avatar)
                                    <img src="{{ asset('storage/' . $log->user->avatar) }}" 
                                         class="rounded-circle me-3" width="48" height="48">
                                @else
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" 
                                         style="width: 48px; height: 48px; font-size: 20px;">
                                        {{ substr($log->user->full_name, 0, 1) }}
                                    </div>
                                @endif
                                <div>
                                    <div class="fw-medium">{{ $log->user->full_name }}</div>
                                    <small class="text-muted">{{ $log->user->email }}</small>
                                </div>
                            </div>
                            @else
                            <div class="text-muted">Không xác định</div>
                            @endif
                        </div>

                        <!-- Document (if applicable) -->
                        @if($log->document)
                        <div class="col-12">
                            <label class="text-muted small">Tài liệu liên quan</label>
                            <div class="card mt-2">
                                <div class="card-body">
                                    <h6 class="card-title mb-2">
                                        <a href="{{ route('documents.show', $log->document->document_id) }}">
                                            {{ $log->document->title }}
                                        </a>
                                    </h6>
                                    <p class="card-text text-muted small mb-0">
                                        {{ Str::limit($log->document->description, 100) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- IP Address -->
                        <div class="col-md-6">
                            <label class="text-muted small">Địa chỉ IP</label>
                            <div>
                                <code class="fs-6">{{ $log->ip_address ?? 'N/A' }}</code>
                            </div>
                        </div>

                        <!-- Target Type -->
                        <div class="col-md-6">
                            <label class="text-muted small">Loại đối tượng</label>
                            <div>
                                <span class="badge bg-secondary">{{ $log->target_type ?? 'N/A' }}</span>
                            </div>
                        </div>

                        <!-- URL -->
                        @if($log->url)
                        <div class="col-12">
                            <label class="text-muted small">URL</label>
                            <div class="small">
                                <code style="word-break: break-all;">{{ $log->url }}</code>
                            </div>
                        </div>
                        @endif

                        <!-- User Agent -->
                        @if($log->user_agent)
                        <div class="col-12">
                            <label class="text-muted small">Thiết bị / Trình duyệt</label>
                            <div class="small text-muted">
                                {{ $log->user_agent }}
                            </div>
                        </div>
                        @endif

                        <!-- Description -->
                        @if($log->description)
                        <div class="col-12">
                            <label class="text-muted small">Mô tả</label>
                            <div class="alert alert-info mb-0">
                                {{ $log->description }}
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Related Logs -->
            @if($log->user)
            <div class="card shadow-sm mt-4">
                <div class="card-header">
                    <h6 class="mb-0">Nhật ký liên quan của người dùng này</h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        @php
                            $relatedLogs = App\Models\AccessLog::where('user_id', $log->user_id)
                                ->where('access_log_id', '!=', $log->access_log_id)
                                ->orderBy('created_at', 'desc')
                                ->limit(5)
                                ->get();
                        @endphp

                        @forelse($relatedLogs as $relatedLog)
                        <a href="{{ route('access.logs.show', $relatedLog->access_log_id) }}" 
                           class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi {{ $relatedLog->action_icon }} me-2"></i>
                                    {{ $relatedLog->action_name }}
                                </div>
                                <small class="text-muted">
                                    {{ $relatedLog->created_at->diffForHumans() }}
                                </small>
                            </div>
                        </a>
                        @empty
                        <div class="text-center text-muted py-3">
                            Không có nhật ký liên quan
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
code {
    background-color: #f8f9fa;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.9em;
}

.list-group-item:hover {
    background-color: #f8f9fa;
}
</style>
@endpush