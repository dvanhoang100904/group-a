@extends('layouts.app')

@section('title', 'Upload Tài Liệu')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Trang chủ</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('documents.index') }}">Tài liệu</a></li>
                    <li class="breadcrumb-item active">Upload</li>
                </ol>
            </nav>

            <!-- Vue Component Mount Point -->
            <div id="document-upload"></div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    @vite(['resources/js/pages/document-upload.js'])
@endsection