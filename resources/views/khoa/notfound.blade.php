@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="alert alert-danger">
        <h4 class="mb-2">Không tìm thấy dữ liệu</h4>
        <p>{{ $message ?? 'Dữ liệu không tồn tại hoặc đã bị xoá.' }}</p>

        <a href="{{ route('khoa.index') }}" class="btn btn-primary mt-3">
            Quay lại danh sách khoa
        </a>
    </div>
</div>
@endsection
