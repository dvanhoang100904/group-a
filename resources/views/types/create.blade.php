@extends('layouts.app')

@section('content')
<div class="container">
    <h4 class="mb-3">Thêm mới loại tài liệu</h4>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $err) <li>{{ $err }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('types.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label>Tên loại</label>
            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
        </div>
        <div class="mb-3">
            <label>Mô tả</label>
            <textarea name="description" class="form-control">{{ old('description') }}</textarea>
        </div>

        <button class="btn btn-success">Tạo mới</button>
        <a href="{{ route('types.index') }}" class="btn btn-secondary">Hủy</a>
    </form>
</div>
@endsection
