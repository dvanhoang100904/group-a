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
            <div>
                <!-- back -->
                <a href="#!" class="btn btn-outline-secondary me-2">
                    <i class="bi bi-arrow-left me-1"></i> Quay lại
                </a>

                <!-- upload -->
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadDocumentModal">
                    <i class="bi bi-upload me-2"></i> Tải lên
                </button>
            </div>
        </div>

        {{-- detail document --}}
        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body">
                <h5 class="fw-bold mb-1">{{ $document->title }}</h5>
                <p class="text-muted small mb-0">
                    Môn học: <strong>{{ $document->subject->name ?? 'Chưa có' }}</strong> |
                    Khoa: <strong>{{ $document->subject->department->name ?? 'Chưa có' }}</strong> |
                    Số phiên bản: <strong>{{ $document->versions()->count() }}</strong>
                </p>
            </div>
        </div>

        {{-- list versions --}}
        <div class="card shadow-sm border-0">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-list-ul me-1"></i> Danh sách các phiên bản</span>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <div id="document-version-list" data-document-id="{{ $document->document_id }}"></div>
                </div>
            </div>
        </div>

    </div>

    {{-- vue js --}}
    @vite('resources/js/pages/document-versions.js')
@endsection
