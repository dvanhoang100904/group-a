@extends('layouts.app')

@section('title', 'Thêm môn học')

@section('content')
<div class="py-4">
    <h4 class="fw-bold text-primary mb-3">
        <i class="bi bi-journal-plus"></i> Thêm môn học mới
    </h4>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('monhoc.store') }}" method="POST">
                @csrf

                {{-- Mã môn học --}}
                <div class="mb-3">
                    <label class="form-label fw-bold">Mã môn học</label>
                    <input type="text" name="code" class="form-control"
                           value="{{ old('code') }}" placeholder="Nhập mã môn học"
                           required>
                </div>

                {{-- Tên môn học --}}
                <div class="mb-3">
                    <label class="form-label fw-bold">Tên môn học</label>
                    <input type="text" name="name" class="form-control"
                           value="{{ old('name') }}" placeholder="Nhập tên môn học"
                           required>
                </div>

                {{-- Số tín chỉ --}}
                <div class="mb-3">
                    <label class="form-label fw-bold">Số tín chỉ</label>
                    <input type="number" name="credits" class="form-control"
                           value="{{ old('credits', 3) }}"
                           min="1" max="10" required>
                </div>

                {{-- Khoa / Bộ môn --}}
                <div class="mb-3">
                    <label class="form-label fw-bold">Khoa / Bộ môn</label>
                    <select name="department_id" class="form-select" required>
                        <option value="">-- Chọn khoa / bộ môn --</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept->department_id }}"
                                {{ old('department_id') == $dept->department_id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Mô tả --}}
                <div class="mb-3">
                    <label class="form-label fw-bold">Mô tả</label>
                    <textarea name="description" class="form-control" rows="3"
                        placeholder="Nhập mô tả (nếu có)">{{ old('description') }}</textarea>
                </div>

                <div class="d-flex justify-content-end">
                    <a href="{{ route('monhoc.index') }}" class="btn btn-secondary me-2">
                        <i class="bi bi-arrow-left-circle"></i> Quay lại
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Lưu môn học
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection
