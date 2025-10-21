@extends('layouts.app')

@section('title', 'Phiên bản tài liệu')

@section('content')
    <div class="container-fluid pt-4">
        <!-- breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a class="text-dark text-decoration-none" href="#!">Tài liệu</a>
                </li>
                <li class="breadcrumb-item">
                    <a class="text-dark text-decoration-none" href="#!">Chi tiết tài liệu</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    Phiên bản tài liệu
                </li>
            </ol>
        </nav>
        <!-- header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0 fw-bold text-primary fs-4">
                <i class="bi bi-clock-history me-2"></i>
                Phiên bản tài liệu
            </h3>
        </div>

        {{-- detail document --}}
        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body">
                @if ($document)
                    <h5 class="fw-bold mb-1">{{ $document->title }}</h5>
                    <p class="text-muted small mb-0">
                        Môn học: <strong>{{ $document->subject->name ?? 'Chưa có' }}</strong> |
                        Khoa: <strong>{{ $document->subject->department->name ?? 'Chưa có' }}</strong> |
                        Số phiên bản: <strong>{{ $document->versions()->count() }}</strong>
                    </p>
                @else
                    <p class="text-danger">Tài liệu không tồn tại</p>
                @endif
            </div>
        </div>

        {{-- list versions --}}
        @if ($document)
            <div id="document-version-list" data-document-id="{{ $document->document_id }}"></div>
        @endif
    </div>

    {{-- vue js --}}
    @vite('resources/js/pages/document-versions.js')
@endsection
