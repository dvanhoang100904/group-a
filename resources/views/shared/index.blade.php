@extends('layouts.app')

@section('title', 'Tài liệu chia sẻ với tôi')

@section('content')
    <!-- header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0 fw-bold text-dark fs-5">
            <i class="bi bi-people me-2"></i>
            Tài liệu chia sẻ với tôi
        </h3>
    </div>

    {{-- List documents shared --}}
    <document-shared-list id="document-shared-list"></document-shared-list>

    {{-- vue js --}}
    @vite('resources/js/pages/document-shared.js')
@endsection
