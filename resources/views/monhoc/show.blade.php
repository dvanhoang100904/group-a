@extends('layouts.app')

@section('title', 'Chi tiết Môn học')

@section('content')
<div class="py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold text-primary"><i class="bi bi-journal-bookmark"></i> Chi tiết Môn học</h4>
        <a href="{{ route('monhoc.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left-circle"></i> Quay lại danh sách
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">

            <table class="table table-bordered">
                <tr><th>Mã môn học</th><td>{{ $monhoc->code }}</td></tr>
                <tr><th>Tên môn học</th><td>{{ $monhoc->name }}</td></tr>
                <tr><th>Tín chỉ</th><td>{{ $monhoc->credits }}</td></tr>
                <tr><th>Khoa / Bộ môn</th><td>{{ $monhoc->department->name ?? 'Không xác định' }}</td></tr>
                <tr><th>Số lượng tài liệu</th><td>{{ $monhoc->documents_count ?? 0 }}</td></tr>
                <tr><th>Mô tả</th><td>{{ $monhoc->description ?? '—' }}</td></tr>

                <tr>
                    <th>Trạng thái</th>
                    <td>
                        @if ($monhoc->status)
                            <span class="badge bg-success">Đang hoạt động</span>
                        @else
                            <span class="badge bg-secondary">Ngừng hoạt động</span>
                        @endif
                    </td>
                </tr>

                <tr><th>Ngày tạo</th><td>{{ $monhoc->created_at?->format('d/m/Y H:i') }}</td></tr>
                <tr><th>Cập nhật lần cuối</th><td>{{ $monhoc->updated_at?->format('d/m/Y H:i') }}</td></tr>
            </table>

            <div class="mt-3 text-end">
                <a href="{{ route('monhoc.edit', $monhoc->subject_id) }}" class="btn btn-warning">
                    <i class="bi bi-pencil"></i> Chỉnh sửa
                </a>

                <form action="{{ route('monhoc.destroy', $monhoc->subject_id) }}"
                    method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')

                    <button type="submit" class="btn btn-danger"
                        onclick="return confirm('Bạn có chắc chắn muốn xóa môn học này không?')">
                        <i class="bi bi-trash"></i> Xóa
                    </button>
                </form>
            </div>

        </div>
    </div>

    @if ($monhoc->documents_count > 0)
        <div class="card mt-4 shadow-sm">
            <div class="card-header bg-light fw-bold">
                <i class="bi bi-file-earmark-text"></i>
                Tài liệu liên quan ({{ $monhoc->documents_count }})
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    @foreach ($monhoc->documents as $doc)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            {{ $doc->title }}
                            <a href="{{ route('documents.show', $doc->document_id) }}"
                                class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i> Xem
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif
</div>
@endsection
