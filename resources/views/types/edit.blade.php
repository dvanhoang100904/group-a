@extends('layouts.app')

@section('title', 'Chỉnh sửa loại tài liệu')

@section('content')
<div class="container">
    <h1 class="mb-4">Chỉnh sửa loại tài liệu: {{ $type->code }}</h1>

    <form action="{{ route('types.update', $type) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label">Tên loại</label>
            <input type="text" name="name" class="form-control"
                   value="{{ old('name', $type->name) }}" required>
            @error('name')
                <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Mô tả</label>
            <textarea name="description" class="form-control" rows="3">{{ old('description', $type->description) }}</textarea>
            @error('description')
                <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Trạng thái</label>
            <select name="status" class="form-select">
                <option value="1" {{ $type->status ? 'selected' : '' }}>Hoạt động</option>
                <option value="0" {{ !$type->status ? 'selected' : '' }}>Tắt</option>
            </select>
        </div>

        <div class="mb-3">
            <small>Người tạo: {{ $type->created_by ?? 'System' }} | Cập nhật gần nhất: {{ $type->updated_by ?? 'System' }}</small>
        </div>

        <button type="submit" class="btn btn-primary">Cập nhật</button>
        <a href="{{ route('types.index') }}" class="btn btn-secondary">Quay lại</a>
    </form>
</div>
@endsection
