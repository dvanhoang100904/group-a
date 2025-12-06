@extends('layouts.app')

@section('content')
<div class="container col-md-6">
    <h4 class="mb-3">✏ Chỉnh sửa thẻ</h4>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach ($errors->all() as $err) <li>{{ $err }}</li> @endforeach</ul>
        </div>
    @endif

    <form action="{{ route('tags.update', $tag->tag_id) }}" method="POST" class="card p-4 shadow-sm" id="tagEditForm">
        @csrf
        @method('PUT')

        {{-- optimistic locking --}}
        <input type="hidden" name="client_updated_at" value="{{ $tag->updated_at?->toIso8601String() }}">

        <div class="mb-3">
            <label class="form-label">Tên thẻ</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $tag->name) }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Mô tả</label>
            <textarea name="description" class="form-control" rows="3">{{ old('description', $tag->description) }}</textarea>
        </div>

        <button id="updateBtn" class="btn btn-primary">💾 Cập nhật</button>
        <a href="{{ route('tags.index') }}" class="btn btn-secondary">↩ Hủy</a>
    </form>
</div>

@push('scripts')
<script>
document.getElementById('tagEditForm').addEventListener('submit', function(e){
    const btn = document.getElementById('updateBtn');
    if (btn) {
        btn.disabled = true;
        btn.innerText = 'Đang xử lý...';
    }
});
</script>
@endpush
@endsection
