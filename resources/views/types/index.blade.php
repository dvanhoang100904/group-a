@extends('layouts.app')

@section('title', 'Quản lý loại tài liệu')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">📚 Quản lý loại tài liệu</h4>
        <div>
            <a href="{{ route('types.exportExcel') }}" class="btn btn-info btn-sm">
                <i class="bi bi-file-earmark-excel"></i> Xuất Excel
            </a>
            <a href="{{ route('types.create') }}" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Thêm mới
            </a>
        </div>
    </div>

    <!-- Tổng số -->
    <div class="mb-2">
        <span class="badge bg-primary">Tổng loại: {{ $totalTypes ?? 0 }}</span>
        <span class="badge bg-success">Tổng tài liệu: {{ $totalDocuments ?? 0 }}</span>
    </div>

    <!-- Bộ lọc -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('types.index') }}" class="row g-2">
                <div class="col-md-3">
                    <input type="text" name="name" class="form-control" placeholder="Lọc theo tên loại" value="{{ request('name') }}">
                </div>
                <div class="col-md-2">
                    <select name="has_documents" class="form-select">
                        <option value="">Tất cả</option>
                        <option value="1" {{ request('has_documents')=='1' ? 'selected':'' }}>Có tài liệu</option>
                        <option value="0" {{ request('has_documents')=='0' ? 'selected':'' }}>Không có tài liệu</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="sort_by" class="form-select">
                        <option value="">Sắp xếp</option>
                        <option value="name" {{ request('sort_by')=='name' ? 'selected':'' }}>Tên</option>
                        <option value="documents_count" {{ request('sort_by')=='documents_count' ? 'selected':'' }}>Số lượng tài liệu</option>
                        <option value="created_at" {{ request('sort_by')=='created_at' ? 'selected':'' }}>Ngày tạo</option>
                    </select>
                </div>
                <div class="col-md-5 text-end">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Lọc</button>
                    <a href="{{ route('types.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i> Xóa lọc</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Thông báo -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Bảng danh sách -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-center">
                    <tr>
                        <th style="width:5%">#</th>
                        <th style="width:10%">Mã loại</th>
                        <th style="width:20%">Tên loại</th>
                        <th>Mô tả</th>
                        <th style="width:10%">Số lượng tài liệu</th>
                        <th style="width:15%">Ngày tạo</th>
                        <th style="width:15%">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($types as $i => $type)
                        <tr>
                            <td class="text-center">{{ $types->firstItem() + $i }}</td>
                            <td class="text-center text-primary fw-semibold">{{ $type->code }}</td>
                            <td>{{ $type->name }}</td>
                            <td>{!! $type->description ?: '<i>—</i>' !!}</td>
                            <td class="text-center"><span class="badge bg-info">{{ $type->documents_count }}</span></td>
                            <td class="text-center">{{ $type->created_at->format('d/m/Y H:i') }}</td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <a href="{{ route('types.show', $type->type_id) }}" class="btn btn-outline-info btn-sm"><i class="bi bi-eye"></i></a>
                                    <a href="{{ route('types.edit', $type->type_id) }}" class="btn btn-outline-warning btn-sm"><i class="bi bi-pencil-square"></i></a>
                                    <form action="{{ route('types.destroy', $type->type_id) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa loại tài liệu này không?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4"><i class="bi bi-inbox"></i> Chưa có loại tài liệu nào</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Phân trang -->
    <div class="mt-3 d-flex justify-content-center">
        {{ $types->links() }}
    </div>
</div>
@endsection
