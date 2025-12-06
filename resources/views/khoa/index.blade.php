@extends('layouts.app')

@section('title', 'Danh sách Khoa / Bộ môn')

@section('content')
<div class="py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold text-primary">
            <i class="bi bi-building"></i> Danh sách Khoa / Bộ môn
        </h4>

        <div class="d-flex gap-2">
            <a href="{{ route('khoa.export.excel') }}" class="btn btn-success">
                <i class="bi bi-file-earmark-excel"></i> Xuất Excel
            </a>

            <a href="{{ route('khoa.export.pdf') }}" class="btn btn-danger">
                <i class="bi bi-file-earmark-pdf"></i> Xuất PDF
            </a>

            <a href="{{ route('khoa.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Thêm mới
            </a>
        </div>
    </div>

    {{-- TÌM KIẾM --}}
    <form action="{{ route('khoa.index') }}" method="GET" class="d-flex mb-3">
        <input type="text" name="keyword" class="form-control me-2"
               placeholder="Tìm theo tên hoặc mã khoa..."
               value="{{ request('keyword') }}">

        <button class="btn btn-success me-2">
            <i class="bi bi-search"></i> Tìm kiếm
        </button>

        <a href="{{ route('khoa.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-repeat"></i> Làm mới
        </a>
    </form>

    {{-- THÔNG BÁO --}}
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- DANH SÁCH --}}
    <div class="card shadow-sm">
        <div class="card-body table-responsive">

            <table class="table table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th width="50">STT</th>
                        <th width="120">Mã Khoa</th>
                        <th>Tên Khoa / Bộ môn</th>
                        <th width="300">Mô tả</th>
                        <th width="150">Số lượng Môn học</th>
                        <th class="text-center" width="200">Thao tác</th>
                    </tr>
                </thead>

                <tbody>
                @forelse ($khoas as $index => $khoa)
                    <tr>
                        <td>{{ $khoas->firstItem() + $index }}</td>
                        <td>{{ $khoa->code }}</td>
                        <td>{{ $khoa->name }}</td>
                        <td>{{ $khoa->description ?? '—' }}</td>

                        <!-- <td>  {{ $khoa->subjects_count }}</td> -->
                          <td>
                                <a href="{{ route('monhoc.index', ['department_id' => $khoa->department_id]) }}"
                                   class="badge bg-primary text-decoration-none">
                                    {{ $khoa->subjects_count }}
                                </a>
                            </td>

                        <td class="text-center">
                            <a href="{{ route('khoa.show', $khoa->department_id) }}"
                               class="btn btn-info btn-sm" title="Xem chi tiết">
                                <i class="bi bi-eye"></i>
                            </a>

                            <a href="{{ route('khoa.edit', $khoa->department_id) }}"
                               class="btn btn-warning btn-sm" title="Chỉnh sửa">
                                <i class="bi bi-pencil"></i>
                            </a>

                            <form action="{{ route('khoa.destroy', $khoa->department_id) }}"
                                  method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')

                                <button class="btn btn-danger btn-sm"
                                        onclick="return confirm('Bạn chắc chắn muốn xóa khoa này?')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-3">
                            Không có khoa nào phù hợp.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>

            <div class="mt-3">
                {{ $khoas->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
