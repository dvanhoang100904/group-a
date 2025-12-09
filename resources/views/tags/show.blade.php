@extends('layouts.app')

@section('title', 'Chi tiết thẻ')

@section('content')
<div class="container mt-4">
    <h2 class="mb-4">Chi tiết thẻ: {{ $tag->name }}</h2>

    {{-- Thông báo success (nếu có) --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered mb-0">
                <tbody>
                    <tr>
                        <th>Mã thẻ</th>
                        <td>{{ $tag->code ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Tên thẻ</th>
                        <td>{{ $tag->name }}</td>
                    </tr>
                    <tr>
                        <th>Mô tả</th>
                        <td>{{ $tag->description ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Ảnh</th>
                        <td>
                            @if($tag->image_path)
                                <img src="{{ asset('storage/' . $tag->image_path) }}" alt="Ảnh thẻ" class="img-thumbnail" style="max-width: 200px;">
                            @else
                                <span class="text-muted">Chưa có ảnh</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Trạng thái</th>
                        <td>
                            @if($tag->status)
                                <span class="badge bg-success">Kích hoạt</span>
                            @else
                                <span class="badge bg-secondary">Chưa kích hoạt</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Ngày tạo</th>
                        <td>{{ $tag->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    <tr>
                        <th>Ngày cập nhật</th>
                        <td>{{ $tag->updated_at->format('d/m/Y H:i') }}</td>
                    </tr>
                </tbody>
            </table>

            <div class="mt-3">
                <a href="{{ route('tags.edit', $tag->tag_id) }}" class="btn btn-primary">Chỉnh sửa</a>
                <a href="{{ route('tags.index') }}" class="btn btn-secondary">Quay lại danh sách</a>
            </div>
        </div>
    </div>
</div>
@endsection
