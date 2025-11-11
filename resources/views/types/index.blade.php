@extends('layouts.app')

@section('title', 'Quản lý loại tài liệu')

@section('content')
<div class="container-fluid px-4">
    <h4 class="mb-3">Quản lý loại tài liệu</h4>

    <!-- Bộ lọc -->
    <form method="GET" action="{{ route('types.index') }}" class="row g-2 mb-3">
        <div class="col-md-3">
            <input type="text" name="code" class="form-control" placeholder="Lọc theo mã loại" value="{{ request('code') }}">
        </div>
        <div class="col-md-3">
            <input type="text" name="name" class="form-control" placeholder="Lọc theo tên loại" value="{{ request('name') }}">
        </div>
        <div class="col-md-3">
            <input type="text" name="search" class="form-control" placeholder="Tìm kiếm nhanh..." value="{{ request('search') }}">
        </div>
        <div class="col-md-3 text-end">
            <button class="btn btn-primary">Lọc</button>
            <a href="{{ route('types.index') }}" class="btn btn-secondary">Xóa lọc</a>
            <a href="{{ route('types.create') }}" class="btn btn-success">+ Thêm mới</a>
        </div>
    </form>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered align-middle">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Mã loại tài liệu</th>
                <th>Tên loại</th>
                <th>Mô tả</th>
                <th>Số lượng tài liệu</th>
                <th>Ngày tạo</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            @forelse($types as $i => $type)
            <tr>
                <td>{{ $types->firstItem() + $i }}</td>
                <td>{{ $type->code }}</td>
                <td>{{ $type->name }}</td>
                <td>{{ $type->description }}</td>
                <td class="text-center">{{ $type->documents_count ?? 0 }}</td>
                <td>{{ $type->created_at ? $type->created_at->format('d/m/Y H:i') : '-' }}</td>
                <td>
                    <a href="{{ route('types.show', $type->type_id) }}" class="btn btn-info btn-sm">👁 Xem</a>
                    <a href="{{ route('types.edit', $type->type_id) }}" class="btn btn-warning btn-sm">Sửa</a>
                    <form action="{{ route('types.destroy', $type->type_id) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Bạn có chắc chắn muốn xóa loại tài liệu này không?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger btn-sm">Xóa</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center text-muted">Chưa có loại tài liệu</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Phân trang -->
    <div class="mt-3">
        {{ $types->links() }}
    </div>
</div>
@endsection
