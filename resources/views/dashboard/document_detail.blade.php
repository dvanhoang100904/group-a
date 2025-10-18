@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="card shadow-lg p-4 rounded-4">
        <h2 class="mb-3">{{ $document->title }}</h2>

        <div class="mb-2 text-muted">
            <strong>Người tạo:</strong> {{ $document->user->name ?? 'Không xác định' }} <br>
            <strong>Khoa:</strong> {{ $document->subject->name ?? 'Chưa phân loại' }} <br>
            <strong>Trạng thái:</strong> {{ $document->status ?? 'Không rõ' }}
        </div>

        <p class="mt-3"><strong>Mô tả:</strong> {{ $document->description ?? 'Không có mô tả' }}</p>
        <p><strong>Tags:</strong> {{ $document->tags ?? '-' }}</p>

        <hr>

        <h5>Xem trước tài liệu</h5>
        @php
            $preview = $document->previews->last();
        @endphp

        @if($preview && $preview->preview_path)
            <iframe src="{{ asset('storage/' . $preview->preview_path) }}" 
                    width="100%" height="600px" 
                    class="border rounded mb-3"></iframe>
        @else
            <p class="text-danger">Không có bản xem trước khả dụng.</p>
        @endif

        <hr>

        <h5>Phiên bản tài liệu</h5>
        @if($document->versions->count() > 0)
            <ul class="list-group mb-3">
                @foreach($document->versions as $version)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Phiên bản:</strong> {{ $version->version_name ?? 'N/A' }}
                            <br>
                            <small>Cập nhật: {{ $version->updated_at->format('d/m/Y H:i') }}</small>
                        </div>
                        <a href="{{ asset('storage/' . $version->file_path) }}" class="btn btn-sm btn-outline-primary" target="_blank">Xem</a>
                    </li>
                @endforeach
            </ul>
        @else
            <p>Chưa có phiên bản nào được tải lên.</p>
        @endif

        <hr>

        <h5>Bình luận</h5>
        <div id="comments">
            <p><em>Tính năng bình luận sẽ được thêm ở giai đoạn sau...</em></p>
        </div>

        <div class="mt-4">
            <a href="{{ route('documents.index') }}" class="btn btn-secondary">← Quay lại danh sách</a>
        </div>
    </div>
</div>
@endsection
