@extends('layouts.app')

@section('title', 'Chi tiết Khoa / Bộ môn')

@section('content')
<div class="py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold text-primary">
            <i class="bi bi-eye"></i> Chi tiết Khoa / Bộ môn
        </h4>
        <a href="{{ route('khoa.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Quay lại
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-bordered">
                <tr>
                    <th style="width: 200px;">Mã Khoa / Bộ môn</th>
                    <td>{{ $khoa->code }}</td>
                </tr>
                <tr>
                    <th>Tên Khoa / Bộ môn</th>
                    <td>{{ $khoa->name }}</td>
                </tr>
                <tr>
                    <th>Thông tin</th>
                    <td>{{ $khoa->description ?? '—' }}</td>
                </tr>
                <tr>
                    <th>Số lượng Môn học</th>
                    <td>{{ $khoa->subjects_count ?? 0 }}</td>
                </tr>
                <tr>
                    <th>Ngày tạo</th>
                    <td>{{ $khoa->created_at->format('d/m/Y H:i') }}</td>
                </tr>
                <tr>
                    <th>Ngày cập nhật</th>
                    <td>{{ $khoa->updated_at->format('d/m/Y H:i') }}</td>
                </tr>
            </table>

            <div class="text-end mt-3">
                <a href="{{ route('khoa.edit', $khoa->department_id) }}" class="btn btn-warning btn-sm">
                    <i class="bi bi-pencil"></i> Chỉnh sửa
                </a>
                <form action="{{ route('khoa.destroy', $khoa->department_id) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger btn-sm" onclick="return confirm('Bạn chắc chắn muốn xóa?')">
                        <i class="bi bi-trash"></i> Xóa
                    </button>
                </form>
            </div>
        </div>
    </div>
     
   {{-- Danh sách môn học liên quan --}}
    @if ($khoa->subjects && $khoa->subjects->count() > 0)
        <div class="card mt-4 shadow-sm">
            <div class="card-header bg-light fw-bold">
                <i class="bi bi-journal-bookmark"></i>
                Danh sách Môn học ({{ $khoa->subjects_count }})
            </div>

            <div class="card-body">
                <ul class="list-group list-group-flush">
                    @foreach ($khoa->subjects as $sub)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            {{ $sub->name }}

                            <a href="{{ route('monhoc.show', $sub->subject_id) }}"
                            class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i> Xem
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

</div>
@endsection
