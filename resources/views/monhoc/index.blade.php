@extends('layouts.app')

@section('title', 'Danh sách Môn học')

@section('content')
<div class="py-4">

    {{-- Tiêu đề + nút thêm --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold text-primary mb-0">
            <i class="bi bi-journal-bookmark-fill me-2"></i> Danh sách Môn học
        </h4>

        <div class="d-flex gap-2">
            {{-- Xuất Excel --}}
            <a href="{{ route('monhoc.export.excel') }}" class="btn btn-success">
                <i class="bi bi-file-earmark-excel"></i> Xuất Excel
            </a>

            {{-- Xuất PDF --}}
            <a href="{{ route('monhoc.export.pdf') }}" class="btn btn-danger">
                <i class="bi bi-file-earmark-pdf"></i> Xuất PDF
            </a>

            {{-- Thêm mới --}}
            <a href="{{ route('monhoc.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Thêm mới
            </a>
        </div>
    </div>

    {{-- Form tìm kiếm nâng cấp --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body bg-light">

            <form action="{{ route('monhoc.index') }}" method="GET" class="row g-3 align-items-end">

                {{-- Ô tìm kiếm --}}
                <div class="col-md-4">
                    <label class="form-label fw-bold">Tìm kiếm</label>
                    <input type="text" name="keyword" class="form-control"
                        placeholder="Tìm theo mã hoặc tên môn học..."
                        value="{{ request('keyword') }}">
                </div>

                {{-- Bộ lọc khoa --}}
                <div class="col-md-4">
                    <label class="form-label fw-bold">Khoa / Bộ môn</label>
                    <select name="department_id" class="form-select">
                        <option value="">-- Tất cả khoa --</option>
                        @foreach ($departments as $dep)
                            <option value="{{ $dep->department_id }}"
                                {{ request('department_id') == $dep->department_id ? 'selected' : '' }}>
                                {{ $dep->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Nút tìm kiếm --}}
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-success flex-fill">
                        <i class="bi bi-search"></i> Tìm kiếm
                    </button>

                    <a href="{{ route('monhoc.index') }}" class="btn btn-secondary flex-fill">
                        <i class="bi bi-arrow-repeat"></i> Làm mới
                    </a>
                </div>

            </form>

        </div>
    </div>

    {{-- Thông báo --}}
    @if (session('success'))
        <div class="alert alert-success shadow-sm">{{ session('success') }}</div>
    @endif

    {{-- Bảng danh sách --}}
    <div class="card shadow-sm border-0">
        <div class="card-body table-responsive">

            <table class="table table-hover table-bordered align-middle">
                <thead class="table-primary text-center">
                    <tr>
                        <th>STT</th>
                        <th>Mã Môn học</th>
                        <th>Tên Môn học</th>
                        <th>Tín chỉ</th>
                        <th>Khoa / Bộ môn</th>
                        <th>Tài liệu</th>
                        <th width="200px">Thao tác</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($monhocs as $index => $monhoc)
                        <tr>
                            <td class="text-center fw-bold">{{ $monhocs->firstItem() + $index }}</td>
                            <td class="text-primary fw-semibold">{{ $monhoc->code }}</td>
                            <td>{{ $monhoc->name }}</td>
                            <td class="text-center">{{ $monhoc->credits }}</td>
                            <td>{{ $monhoc->department->name ?? '—' }}</td>
                            <td class="text-center">{{ $monhoc->documents_count }}</td>

                            <td class="text-center">

                                <a href="{{ route('monhoc.show', $monhoc->subject_id) }}"
                                   class="btn btn-info btn-sm me-1" title="Xem chi tiết">
                                    <i class="bi bi-eye"></i>
                                </a>

                                <a href="{{ route('monhoc.edit', $monhoc->subject_id) }}"
                                   class="btn btn-warning btn-sm me-1" title="Chỉnh sửa">
                                    <i class="bi bi-pencil"></i>
                                </a>

                                <form action="{{ route('monhoc.destroy', $monhoc->subject_id) }}"
                                      method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="btn btn-danger btn-sm"
                                            onclick="return confirm('Bạn có chắc chắn muốn xóa môn học này không?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>

                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-3">
                                <i class="bi bi-exclamation-circle"></i> Không có dữ liệu
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="mt-3">
                {{ $monhocs->links() }}
            </div>

        </div>
    </div>
</div>
@endsection
