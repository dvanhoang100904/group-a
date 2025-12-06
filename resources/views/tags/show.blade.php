@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2 class="mb-4 text-primary">🏷️ Chi tiết thẻ</h2>

    <div class="card shadow-sm">
        <div class="card-body">
            <p><strong>Mã thẻ:</strong> {{ $tag->code }}</p>
            <p><strong>Tên thẻ:</strong> {{ $tag->name }}</p>
            <p><strong>Mô tả:</strong> {{ $tag->description ?? 'Không có' }}</p>
            <p><strong>Ngày tạo:</strong> {{ $tag->created_at->format('d/m/Y H:i') }}</p>
            <p><strong>Ngày cập nhật:</strong> {{ $tag->updated_at->format('d/m/Y H:i') }}</p>
        </div>
    </div>

    <div class="mt-4">
        <a href="{{ route('tags.index') }}" class="btn btn-secondary">⬅ Quay lại danh sách</a>
        <a href="{{ route('tags.edit', $tag->tag_id) }}" class="btn btn-warning">✏ Sửa</a>
        <form action="{{ route('tags.destroy', $tag->tag_id) }}" method="POST" style="display:inline;">
            @csrf @method('DELETE')
            <button type="submit" onclick="return confirm('Xóa thẻ này?')" class="btn btn-danger">🗑 Xóa</button>
        </form>
    </div>
</div>
@endsection
