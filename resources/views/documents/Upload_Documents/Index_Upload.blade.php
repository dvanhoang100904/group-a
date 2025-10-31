@extends('layouts.app')

@section('title', 'Upload Tài Liệu')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ url('/') }}">Trang chủ</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('documents.index', [], false) }}">Tài liệu</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        Upload
                    </li>
                </ol>
            </nav>

            <!-- Vue Component Mount Point -->
            <div id="document-upload"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/pages/document-upload.js'])
@endpush