@extends('layouts.app')

@section('title', 'Danh sách Môn học')

@section('content')
<div class="py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold text-primary">
            <i class="bi bi-journal-bookmark"></i> Danh sách Môn học
        </h4>
        <a href="{{ route('monhoc.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Thêm mới
        </a>
    </div>

    {{-- Thanh tìm kiếm và lọc --}}
    <form action="{{ route('monhoc.index') }}" method="GET" class="d-flex flex-wrap mb-3">
        <input type="text" name="keyword" class="form-control me-2 mb-2"
            placeholder="Tìm theo mã hoặc tên môn học..." value="{{ request('keyword') }}" style="max-width: 250px;">

        <select name="department_id" class="form-select me-2 mb-2" style="max-width: 250px;">
            <option value="">-- Lọc theo Khoa / Bộ môn --</option>
            @foreach ($departments as $dep)
                <option value="{{ $dep->department_id }}" {{ request('department_id') == $dep->department_id ? 'selected' : '' }}>
                    {{ $dep->name }}
                </option>
            @endforeach
        </select>

        <button type="submit" class="btn btn-success me-2 mb-2">
            <i class="bi bi-search"></i> Tìm kiếm
        </button>
        <a href="{{ route('monhoc.index') }}" class="btn btn-secondary mb-2">
            <i class="bi bi-arrow-repeat"></i> Làm mới
        </a>
    </form>

    {{-- Thông báo --}}
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Bảng danh sách --}}
    <div class="card shadow-sm">
        <div class="card-body table-responsive">
            <table class="table table-striped align-middle">
                <thead class="table-dark text-center">
                    <tr>
                        <th>STT</th>
                        <th>Mã Môn học</th>
                        <th>Tên Môn học</th>
                        <th>Tín chỉ</th>
                        <th>Khoa / Bộ môn</th>
                        <th>Số lượng tài liệu</th>
                        <th style="width: 200px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($monhocs as $index => $monhoc)
                        <tr>
                            <td class="text-center">{{ $monhocs->firstItem() + $index }}</td>
                            <td>{{ $monhoc->code }}</td>
                            <td>{{ $monhoc->name }}</td>
                            <td class="text-center">{{ $monhoc->credits }}</td>
                            <td>{{ $monhoc->department->name ?? '—' }}</td>
                            <td class="text-center">{{ $monhoc->documents_count ?? 0 }}</td>
                            <td class="text-center">
                                <a href="{{ route('monhoc.show', $monhoc->subject_id) }}" 
                                   class="btn btn-info btn-sm" title="Xem chi tiết">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('monhoc.edit', $monhoc->subject_id) }}" 
                                   class="btn btn-warning btn-sm" title="Chỉnh sửa">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('monhoc.destroy', $monhoc->subject_id) }}" method="POST" 
                                      class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm" title="Xóa"
                                        onclick="return confirm('Bạn có chắc chắn muốn xóa môn học này không?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-3">Chưa có dữ liệu</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{-- Phân trang --}}
            <div class="mt-3">
                {{ $monhocs->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
