@extends('layouts.app')

@section('content')
    <div class="container py-4">

        <!-- Nút quay lại + báo cáo -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <a href="{{ route('documents.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Quay lại
            </a>
            <button class="btn btn-danger">
                <i class="bi bi-flag"></i> Báo cáo vi phạm
            </button>
        </div>

        <!-- Thông tin chi tiết -->
        <div class="card shadow-sm border-0 rounded-4 p-4">
            <div class="mb-3">
                <h3 class="fw-bold">{{ $document->title }}</h3>
                <p class="text-muted mb-2">
                    {{ $document->description ?? 'Không có mô tả.' }}
                </p>
                <div>
                    {{-- tags --}}
                    @if ($document->tags && $document->tags->isNotEmpty())
                        @foreach ($document->tags->take(3) as $tag)
                            <span class="badge bg-primary me-1">{{ $tag->name }}</span>
                        @endforeach

                        @if ($document->tags->count() > 3)
                            <span class="badge bg-light text-muted">
                                +{{ $document->tags->count() - 3 }} thẻ khác
                            </span>
                        @endif
                    @elseif (!empty($document->tags))
                        @php
                            $tagsArray = array_map('trim', explode(',', $document->tags));
                            $visibleTags = array_slice($tagsArray, 0, 3);
                        @endphp

                        @foreach ($visibleTags as $tag)
                            <span class="badge bg-primary me-1">{{ $tag }}</span>
                        @endforeach

                        @if (count($tagsArray) > 3)
                            <span class="badge bg-light text-muted">
                                +{{ count($tagsArray) - 3 }} thẻ khác
                            </span>
                        @endif
                    @else
                        <span class="text-muted">Không có thẻ nào</span>
                    @endif
                </div>
            </div>

            <!-- Metadata -->
            <div class="row g-3 small text-muted mb-3">
                <div class="col-md-6">
                    <i class="bi bi-person-circle"></i>
                    Người tạo: <strong>{{ $document->user->name ?? 'Không xác định' }}</strong>
                </div>
                <div class="col-md-6">
                    <i class="bi bi-calendar-event"></i>
                    Ngày tạo: {{ $document->created_at->format('d/m/Y') }}
                </div>
                <div class="col-md-6">
                    <i class="bi bi-file-earmark-text"></i>
                    Kích thước: {{ number_format($document->size / 1024, 2) }} MB
                </div>
                <div class="col-md-6">
                    <i class="bi bi-shield-check"></i>
                    Chế độ chia sẻ:
                    <span class="fw-semibold text-{{ $document->status == 'public' ? 'success' : 'secondary' }}">
                        {{ ucfirst($document->status) }}
                    </span>
                </div>
                <div class="col-md-6">
                    <i class="bi bi-eye"></i>
                    Lượt xem: {{ $document->views ?? 0 }}
                </div>
                <div class="col-md-6">
                    <i class="bi bi-code-slash"></i>
                    Phiên bản hiện tại: v{{ $document->versions->count() ?? 1 }}
                </div>
            </div>

            <!-- Các nút hành động -->
            <div class="d-flex flex-wrap gap-2 mb-4">
                <a href="{{ asset('storage/' . $document->file_path) }}" class="btn btn-primary">
                    <i class="bi bi-download"></i> Tải xuống
                </a>
                <a href="#" class="btn btn-outline-primary">
                    <i class="bi bi-printer"></i> In tài liệu
                </a>
                <button class="btn btn-outline-secondary">
                    <i class="bi bi-link-45deg"></i> Copy link chia sẻ
                </button>

                {{-- versions --}}
                <a href="{{ route('documents.versions.index', ['id' => $document->document_id]) }}"
                    class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-clock-history me-1"></i> Phiên bản
                </a>

                {{-- accesses --}}
                <a href="{{ route('documents.accesses.index', ['id' => $document->document_id]) }}"
                    class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-share"></i> Cài đặt chia sẻ
                </a>
            </div>

            <!-- Xem trước tài liệu -->
            <div class="border rounded-3 overflow-hidden">
                @php
                    $preview = $document->previews->last();
                @endphp
                @if ($preview && $preview->preview_path)
                    <iframe src="{{ asset('storage/' . $preview->preview_path) }}" class="w-100"
                        style="height:600px;border:0;"></iframe>
                @else
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-file-earmark-x display-6"></i>
                        <p class="mt-2">Không có bản xem trước khả dụng.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
