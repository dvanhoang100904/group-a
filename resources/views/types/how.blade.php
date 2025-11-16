@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2 class="mb-4 text-primary">📄 Chi tiết loại tài liệu</h2>

    <div class="card shadow-sm">
        <div class="card-body">
            <p><strong>Mã loại:</strong> {{ $type->code }}</p>
            <p><strong>Tên loại:</strong> {{ $type->name }}</p>
            <p><strong>Mô tả:</strong> {{ $type->description ?? 'Không có' }}</p>
            
            <!-- 🧾 Hiển thị người tạo và người sửa -->
            <p><strong>Người tạo:</strong> {{ $type->creator?->name ?? 'Chưa có' }}</p>
            <p><strong>Người sửa:</strong> {{ $type->editor?->name ?? 'Chưa có' }}</p>

            <p><strong>Ngày tạo:</strong> {{ $type->created_at->format('d/m/Y H:i') }}</p>
            <p><strong>Ngày cập nhật:</strong> {{ $type->updated_at->format('d/m/Y H:i') }}</p>
        </div>
    </div>

    <div class="mt-4">
        <a href="{{ route('types.index') }}" class="btn btn-secondary">⬅ Quay lại danh sách</a>
        <a href="{{ route('types.edit', $type->id) }}" class="btn btn-warning">✏ Sửa</a>
        <form action="{{ route('types.destroy', $type->id) }}" method="POST" style="display:inline;">
            @csrf
            @method('DELETE')
            <button type="submit" onclick="return confirm('Xóa loại tài liệu này?')" class="btn btn-danger">🗑 Xóa</button>
        </form>
    </div>
</div>
@endsection
