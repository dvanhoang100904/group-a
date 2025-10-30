@extends('layouts.app')

@section('title', 'Quyền chia sẻ tài liệu')

@section('content')

    <!-- breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item">
                <a class="text-dark text-decoration-none" href="{{ route('documents.index') }}">
                    </i> Danh sách tài liệu
                </a>
            </li>
            <li class="breadcrumb-item">
                {{-- <a class="text-dark text-decoration-none"
                        href="{{ route('documents.show', ['id' => $document->document_id]) }}">
                        </i>{{ $document->title }}
                    </a> --}}
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                Quyền chia sẻ tài liệu
            </li>
        </ol>
    </nav>

    <!-- header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0 text-primary">
            <i class="bi bi-shield-lock me-2"></i>
            Quyền chia sẻ tài liệu
        </h3>
    </div>

    {{-- detail document --}}
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body">
            @if ($document)
                <h5 class="fw-bold mb-2">{{ $document->title }}</h5>
                <p class="text-muted small mb-0">
                    Môn học: <strong>{{ $document->subject->name ?? 'Chưa có' }}</strong> |
                    Khoa: <strong>{{ $document->subject->department->name ?? 'Chưa có' }}</strong>
                </p>
            @else
                <p class="text-danger">Tài liệu không tồn tại</p>
            @endif
        </div>
    </div>

    {{-- list accesses --}}
    @if ($document)
        <div id="document-access-list" data-document-id="{{ $document->document_id }}"></div>
    @endif

    {{-- vue js --}}
    @vite('resources/js/pages/document-accesses.js')
@endsection
