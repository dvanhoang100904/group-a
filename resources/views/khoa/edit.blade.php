@extends('layouts.app')

@section('title', 'Chỉnh sửa Khoa / Bộ môn')

@section('content')
<div class="py-4">
    <h4 class="fw-bold text-primary mb-3">
        <i class="bi bi-pencil-square"></i> Chỉnh sửa Khoa / Bộ môn
    </h4>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('khoa.update', $khoa->department_id) }}" method="POST">
                @csrf
                @method('PUT')

                {{-- Chỉ hiển thị mã, không cho chỉnh sửa --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Mã Khoa / Bộ môn</label>
                    <input type="text" class="form-control bg-light" value="{{ $khoa->code }}" readonly>
                </div>

                <div class="mb-3">
                    <label for="name" class="form-label fw-semibold">Tên Khoa / Bộ môn</label>
                    <input type="text" name="name" id="name" class="form-control"
                        value="{{ old('name', $khoa->name) }}" required>
                    @error('name')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label fw-semibold">Thông tin (Mô tả)</label>
                    <textarea name="description" id="description" class="form-control" rows="3">{{ old('description', $khoa->description) }}</textarea>
                    @error('description')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-end">
                    <a href="{{ route('khoa.index') }}" class="btn btn-secondary me-2">
                        <i class="bi bi-arrow-left-circle"></i> Quay lại
                    </a>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-pencil"></i> Cập nhật
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
