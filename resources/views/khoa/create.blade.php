@extends('layouts.app')

@section('title', 'Thêm Khoa / Bộ môn')

@section('content')
<div class="py-4">
    <h4 class="fw-bold text-primary mb-3">
        <i class="bi bi-plus-circle"></i> Thêm Khoa / Bộ môn
    </h4>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('khoa.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-semibold">Tên Khoa / Bộ môn</label>
                    <input type="text" name="name" class="form-control"
                           placeholder="Nhập tên khoa..." value="{{ old('name') }}" required>
                    @error('name')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Mô tả</label>
                    <textarea name="description" class="form-control" rows="3"
                              placeholder="Mô tả ngắn (tùy chọn)">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('khoa.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Quay lại
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Lưu Khoa / Bộ môn
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
