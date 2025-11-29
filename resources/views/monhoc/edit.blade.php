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

        {{-- Mã môn học --}}
        <div class="mb-3">
            <label class="form-label">Mã môn học</label>
            <input type="text" class="form-control bg-light" value="{{ $monhoc->code }}" readonly>
        </div>

        {{-- Hidden code to ensure value sent --}}
        <input type="hidden" name="code" value="{{ $monhoc->code }}">

        <div class="mb-3">
            <label class="form-label">Tên môn học</label>
            <input type="text" name="name" class="form-control"
                   value="{{ old('name', $monhoc->name) }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Số tín chỉ</label>
            <input type="number" name="credits" class="form-control"
                   value="{{ old('credits', $monhoc->credits) }}"
                   min="1" max="10" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Khoa / Bộ môn</label>
            <select name="department_id" class="form-select" required>
                @foreach($departments as $dept)
                    <option value="{{ $dept->department_id }}"
                        {{ old('department_id', $monhoc->department_id) == $dept->department_id ? 'selected' : '' }}>
                        {{ $dept->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Mô tả</label>
            <textarea name="description" class="form-control" rows="3">{{ old('description', $monhoc->description) }}</textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Trạng thái</label>
            <select name="status" class="form-select">
                <option value="1" {{ old('status', $monhoc->status) == 1 ? 'selected' : '' }}>Đang hoạt động</option>
                <option value="0" {{ old('status', $monhoc->status) == 0 ? 'selected' : '' }}>Ngừng hoạt động</option>
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
