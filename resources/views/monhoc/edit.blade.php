@extends('layouts.app')

@section('title', 'Chỉnh sửa Môn học')

@section('content')
<div class="container py-4">
    <h2 class="mb-3 text-primary">
        <i class="bi bi-pencil-square"></i> Chỉnh sửa Môn học
    </h2>

    <form action="{{ route('monhoc.update', $monhoc->subject_id) }}" method="POST">
        @csrf
        @method('PUT')

        {{-- Mã môn học (không cho phép chỉnh sửa) --}}
        <div class="mb-3">
            <label for="code" class="form-label">Mã môn học</label>
            <input type="text" id="code" class="form-control bg-light" value="{{ $monhoc->code }}" readonly>
        </div>

        <div class="mb-3">
            <label for="name" class="form-label">Tên môn học</label>
            <input type="text" name="name" id="name" class="form-control"
                   value="{{ old('name', $monhoc->name) }}" required>
        </div>

        <div class="mb-3">
            <label for="credits" class="form-label">Số tín chỉ</label>
            <input type="number" name="credits" id="credits" class="form-control"
                   value="{{ old('credits', $monhoc->credits) }}" min="1" required>
        </div>

        <div class="mb-3">
            <label for="department_id" class="form-label">Khoa / Bộ môn</label>
            <select name="department_id" id="department_id" class="form-select" required>
                @foreach($departments as $dept)
                    <option value="{{ $dept->department_id }}"
                        {{ $monhoc->department_id == $dept->department_id ? 'selected' : '' }}>
                        {{ $dept->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">Mô tả</label>
            <textarea name="description" id="description" class="form-control" rows="3">{{ old('description', $monhoc->description) }}</textarea>
        </div>

        <div class="mb-3">
            <label for="status" class="form-label">Trạng thái</label>
            <select name="status" id="status" class="form-select">
                <option value="1" {{ $monhoc->status ? 'selected' : '' }}>Đang hoạt động</option>
                <option value="0" {{ !$monhoc->status ? 'selected' : '' }}>Ngừng hoạt động</option>
            </select>
        </div>

        <div class="d-flex justify-content-between">
            <a href="{{ route('monhoc.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left-circle"></i> Quay lại
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> Cập nhật
            </button>
        </div>
    </form>
</div>
@endsection
