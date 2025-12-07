@extends('layouts.app')

@section('title', 'Chỉnh sửa thẻ')

@section('content')
<div class="container mt-4">
    <h2 class="mb-4">Chỉnh sửa thẻ: {{ $tag->name }}</h2>

    {{-- Hiển thị thông báo lỗi --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Form chỉnh sửa tag --}}
    <form action="{{ route('tags.update', $tag->tag_id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        {{-- Mã thẻ --}}
        <div class="mb-3">
            <label for="code" class="form-label">Mã thẻ</label>
            <input type="text" id="code" name="code" class="form-control" value="{{ old('code', $tag->code) }}" readonly>
        </div>

        {{-- Tên thẻ --}}
        <div class="mb-3">
            <label for="name" class="form-label">Tên thẻ <span class="text-danger">*</span></label>
            <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $tag->name) }}" required>
        </div>

        {{-- Mô tả --}}
        <div class="mb-3">
            <label for="description" class="form-label">Mô tả</label>
            <textarea id="description" name="description" class="form-control" rows="3">{{ old('description', $tag->description) }}</textarea>
        </div>

        {{-- Ảnh hiện tại --}}
        <div class="mb-3">
            <label class="form-label">Ảnh hiện tại</label>
            <div>
                @if($tag->image_path)
                    <img src="{{ asset('storage/' . $tag->image_path) }}" alt="Ảnh thẻ" class="img-thumbnail" style="max-width: 150px;">
                @else
                    <span class="text-muted">Chưa có ảnh</span>
                @endif
            </div>
        </div>

        {{-- Upload ảnh mới --}}
        <div class="mb-3">
            <label for="image_path" class="form-label">Cập nhật ảnh</label>
            <input type="file" id="image_path" name="image_path" class="form-control">
        </div>

        {{-- Status --}}
        <div class="mb-3 form-check">
            <input type="checkbox" id="status" name="status" class="form-check-input" value="1" {{ old('status', $tag->status) ? 'checked' : '' }}>
            <label for="status" class="form-check-label">Kích hoạt</label>
        </div>

        {{-- Submit --}}
        <button type="submit" class="btn btn-primary">Cập nhật thẻ</button>
        <a href="{{ route('tags.index') }}" class="btn btn-secondary">Hủy</a>
    </form>
</div>
@endsection
