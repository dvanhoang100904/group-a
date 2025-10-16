@extends('layouts.app')

@section('content')
<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3><i class="bi bi-person-circle"></i> Tài liệu của tôi</h3>
        <!-- Nút mở modal upload -->
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
            <i class="bi bi-upload"></i> Upload
        </button>
    </div>

    <!-- Bộ lọc sắp xếp -->
    <form method="GET" class="row mb-3 g-2">
        <div class="col-md-3">
            <select name="sort_by" class="form-select">
                <option value="created_at" {{ $sortBy=='created_at'?'selected':'' }}>Ngày tạo</option>
                <option value="user_name" {{ $sortBy=='user_name'?'selected':'' }}>Tên người dùng</option>
                <option value="size" {{ $sortBy=='size'?'selected':'' }}>Dung lượng</option>
            </select>
        </div>

        <div class="col-md-3">
            <select name="sort_order" class="form-select">
                <option value="asc" {{ $sortOrder=='asc'?'selected':'' }}>Tăng dần</option>
                <option value="desc" {{ $sortOrder=='desc'?'selected':'' }}>Giảm dần</option>
            </select>
        </div>

        <div class="col-md-2">
            <button type="submit" class="btn btn-outline-secondary w-100">
                <i class="bi bi-funnel"></i> Sắp xếp
            </button>
        </div>
    </form>

    <!-- Bảng danh sách tài liệu -->
    <div class="card">
        <div class="card-body p-0">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-light text-center">
                    <tr>
                        <th>#</th>
                        <th>Tiêu đề</th>
                        <th>Loại</th>
                        <th>Người dùng</th>
                        <th>Dung lượng</th>
                        <th>Ngày tạo</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($documents as $doc)
                        <tr>
                            <td>{{ $doc->document_id }}</td>
                            <td>{{ $doc->title }}</td>
                            <td>{{ $doc->subject->name ?? '-' }}</td>
                            <td>{{ $doc->user->name ?? 'User #'.$doc->user_id }}</td>
                            <td class="text-end">{{ number_format($doc->size / 1024, 2) }} KB</td>
                            <td>{{ $doc->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <span class="badge bg-{{ $doc->status == 'public' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($doc->status) }}
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('documents.show', $doc->document_id) }}" class="btn btn-sm btn-outline-info">View</a>
                                <a href="{{ route('documents.edit', $doc->document_id) }}" class="btn btn-sm btn-outline-warning">Edit</a>
                                <form method="POST" action="{{ route('documents.destroy', $doc->document_id) }}" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa tài liệu này?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-3">Không có tài liệu nào</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="text-center">
        {{ $documents->links() }}
    </div>
</div>

<!-- Modal Upload -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form method="POST" action="{{ route('upload.store') }}" enctype="multipart/form-data" class="modal-content">
        @csrf
        <div class="modal-header">
            <h5 class="modal-title" id="uploadModalLabel"><i class="bi bi-upload"></i> Tải tài liệu lên</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
        </div>
        <div class="modal-body">
            <div class="mb-3">
                <label class="form-label">Chọn file</label>
                <input type="file" name="file" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Tiêu đề</label>
                <input type="text" name="title" class="form-control" placeholder="Nhập tiêu đề tài liệu">
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Môn học</label>
                    <select name="subject_id" class="form-select">
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->subject_id }}">{{ $subject->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Trạng thái</label>
                    <select name="status" class="form-select">
                        <option value="private">Riêng tư</option>
                        <option value="public">Công khai</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
            <button type="submit" class="btn btn-success">Tải lên</button>
        </div>
    </form>
  </div>
</div>

@endsection
