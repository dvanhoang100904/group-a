@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2 class="mb-4 text-primary">📄 Chi tiết loại tài liệu</h2>

    {{-- Thông tin loại --}}
    <div class="card shadow-sm">
        <div class="card-body">
            <p><strong>Mã loại:</strong> {{ $type->code }}</p>
            <p><strong>Tên loại:</strong> {{ $type->name }}</p>
            <p><strong>Mô tả:</strong> {{ $type->description ?? 'Không có' }}</p>
            <p><strong>Trạng thái:</strong>
                @if($type->status)
                    <span class="badge bg-success">Đang hoạt động</span>
                @else
                    <span class="badge bg-secondary">Ngừng hoạt động</span>
                @endif
            </p>
            <p><strong>Số lượng tài liệu đang dùng loại này:</strong>
                <span class="badge bg-info text-dark">{{ $type->documents_count }} tài liệu</span>
            </p>
            <p><strong>Ngày tạo:</strong> {{ $type->created_at->format('d/m/Y H:i') }}</p>
            <p><strong>Ngày cập nhật:</strong> {{ $type->updated_at->format('d/m/Y H:i') }}</p>
        </div>
    </div>

    {{-- Tabs --}}
    <ul class="nav nav-tabs mt-4" id="typeTab">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#docs">
                📚 Danh sách tài liệu ({{ $type->documents_count }})
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#logs">
                📌 Hoạt động gần đây
            </button>
        </li>
    </ul>

    <div class="tab-content p-3 border rounded-bottom">
        {{-- TAB 1 - DANH SÁCH TÀI LIỆU --}}
        <div class="tab-pane fade show active" id="docs">
            @if($documents->count() > 0)
                <table class="table table-bordered table-hover mt-2">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tên tài liệu</th>
                            <th>Ngày tạo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($documents as $i => $doc)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>
                                <a href="{{ route('documents.show', $doc->document_id) }}" class="text-primary fw-bold">
                                    {{ $doc->title }}
                                </a>
                            </td>
                            <td>{{ $doc->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-muted mt-2">Không có tài liệu nào thuộc loại này.</p>
            @endif
        </div>

        {{-- TAB 2 - HOẠT ĐỘNG GẦN ĐÂY --}}
        <div class="tab-pane fade" id="logs">
            @if($logs->count() > 0)
                <ul class="list-group mt-2">
                    @foreach($logs as $log)
                        <li class="list-group-item">
                            <strong>{{ $log->action }}</strong><br>
                            <small class="text-muted">
                                {{ $log->created_at->format('d/m/Y H:i') }} — bởi {{ $log->user->name ?? 'Hệ thống' }}
                            </small>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-muted mt-2">Chưa có hoạt động nào.</p>
            @endif
        </div>
    </div>

    {{-- Nút hành động --}}
    <div class="mt-4">
        <a href="{{ route('types.index') }}" class="btn btn-secondary">⬅ Quay lại danh sách</a>
        <a href="{{ route('types.edit', $type->type_id) }}" class="btn btn-warning">✏ Sửa</a>
        @if($type->documents_count == 0)
            <form action="{{ route('types.destroy', $type->type_id) }}" method="POST" class="d-inline">
                @csrf
                @method('DELETE')
                <button onclick="return confirm('Bạn có chắc muốn xóa loại tài liệu này?')" class="btn btn-danger">
                    🗑 Xóa
                </button>
            </form>
        @endif
    </div>

</div>
@endsection
