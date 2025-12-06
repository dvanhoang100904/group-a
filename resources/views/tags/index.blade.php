@extends('layouts.app')

@section('title', 'Quản lý thẻ')

@section('content')
<div class="container-fluid px-4">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">🏷️ Quản lý thẻ</h4>
        <a href="{{ route('tags.create') }}" class="btn btn-success shadow-sm px-3">
            <i class="bi bi-plus-circle"></i> Thêm mới
        </a>
    </div>

    {{-- Alert --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Bộ lọc + tìm kiếm --}}
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" action="{{ route('tags.index') }}" class="row g-3">

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Tìm theo tên</label>
                    <input type="text" name="name" value="{{ request('name') }}" class="form-control" placeholder="Nhập tên thẻ...">
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Lọc</label>
                    <select name="filter" class="form-select">
                        <option value="">-- Không lọc --</option>
                        <option value="most_used" {{ request('filter')=='most_used' ? 'selected' : '' }}>
                            Tag được dùng nhiều nhất
                        </option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Sắp xếp</label>
                    <select name="sort" class="form-select">
                        <option value="created_at" {{ request('sort')=='created_at'?'selected':'' }}>Ngày tạo</option>
                        <option value="name" {{ request('sort')=='name'?'selected':'' }}>Tên</option>
                        <option value="documents_count" {{ request('sort')=='documents_count'?'selected':'' }}>Số tài liệu</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label d-block">&nbsp;</label>
                    <button class="btn btn-primary w-100 shadow-sm">Lọc</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Danh sách --}}
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-center">
                        <th>#</th>
                        <th>Mã thẻ</th>
                        <th>Tên thẻ</th>
                        <th>Mô tả</th>
                        <th>Số tài liệu</th>
                        <th>Ngày tạo</th>
                        <th>Hành động</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($tags as $i => $tag)
                    <tr>
                        <td class="text-center">{{ $tags->firstItem() + $i }}</td>

                        <td class="text-center text-primary fw-semibold">
                            {{ $tag->code }}
                        </td>

                        <td class="fw-semibold">{{ $tag->name }}</td>

                        <td class="text-muted">
                            {{ $tag->description ?: '—' }}
                        </td>

                        <td class="text-center fw-semibold">
                            {{ $tag->documents_count }}
                        </td>

                        <td class="text-center">
                            {{ $tag->created_at?->format('d/m/Y H:i') ?? '-' }}
                        </td>

                        <td class="text-center">
                            <a href="{{ route('tags.show', $tag->tag_id) }}" class="btn btn-outline-info btn-sm me-1">
                                <i class="bi bi-eye"></i>
                            </a>

                            <a href="{{ route('tags.edit', $tag->tag_id) }}" class="btn btn-outline-warning btn-sm me-1">
                                <i class="bi bi-pencil"></i>
                            </a>

                            <form action="{{ route('tags.destroy', $tag->tag_id) }}"
                                  method="POST" class="d-inline"
                                  onsubmit="return confirm('Xóa thẻ này?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            Chưa có thẻ nào
                        </td>
                    </tr>
                    @endforelse
                </tbody>

            </table>
        </div>
    </div>

    {{-- Phân trang --}}
    <div class="mt-3 d-flex justify-content-center">
        {{ $tags->links() }}
    </div>

</div>
@endsection
