@extends('layouts.app')

@section('title', 'Quyền truy cập tài liệu')

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
                <a class="text-dark text-decoration-none"
                    href="{{ route('documents.show', ['id' => $document->document_id]) }}">
                    </i>{{ $document->title }}
                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                Quyền truy cập tài liệu
            </li>
        </ol>
    </nav>

    <!-- header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0 fw-bold text-primary fs-5">
            <i class="bi bi-shield-lock me-2"></i>
            Quyền truy cập tài liệu
        </h3>
    </div>

    {{-- detail document --}}
    <div class="card shadow-sm mb-4 border-0" style="background-color: #f8fafd ">
        <div class="card-body">
            @if ($document)
                <h3 class="fw-bold mb-2">{{ $document->title }}</h3>
                <p class="text-muted small mb-0">
                    Môn học: <strong>{{ $subject->name ?? 'Chưa có' }}</strong> |
                    Khoa: <strong>{{ $department->name ?? 'Chưa có' }}</strong>
                </p>
            @else
                <p class="text-danger">Tài liệu không tồn tại</p>
            @endif
        </div>
    </div>

    {{-- setup accesses --}}
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-header fw-bold" style="background-color: #f8fafd ">
            <i class="bi bi-gear me-2"></i> Thiết lập quyền truy cập
        </div>
        <div class="card-body">
            <form action="{{ route('documents.accesses.updateSettings', ['documentId' => $document->document_id]) }}"
                method="POST" class="row gy-3 align-items-center">
                @csrf
                @method('PUT')

                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Chế độ chia sẻ</label>
                    <select name="share_mode" class="form-select">
                        <option value="public" {{ $document->share_mode == 'public' ? 'selected' : '' }}>Công khai (Public)
                        </option>
                        <option value="private" {{ $document->share_mode == 'private' ? 'selected' : '' }}>Riêng tư
                            (Private)</option>
                        <option value="custom" {{ $document->share_mode == 'custom' ? 'selected' : '' }}>Tùy chỉnh (Custom)
                        </option>
                    </select>
                    @error('share_mode')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Liên kết chia sẻ tạm thời</label>
                    <div class="input-group">
                        <input type="text" name="share_link" class="form-control" value="{{ $document->share_link }}"
                            readonly>
                        <button type="button" class="btn btn-primary"
                            onclick="navigator.clipboard.writeText('{{ $document->share_link }}')"
                            {{ empty($document->share_link) ? 'disabled' : '' }}>
                            <i class="bi bi-clipboard me-1"></i> Copy
                        </button>
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Hết hạn sau</label>
                    <div class="d-flex gap-2 align-items-center">
                        <input type="date" name="expiration_date" class="form-control"
                            value="{{ optional($document->expiration_date)->format('Y-m-d') }}"
                            {{ $document->no_expiry ? 'disabled' : '' }}>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="no_expiry" value="1" id="noExpiry"
                                {{ $document->no_expiry ? 'checked' : '' }}
                                onchange="this.form.expiration_date.disabled = this.checked;">
                            <label class="form-check-label small" for="noExpiry">Không giới hạn</label>
                        </div>
                    </div>
                    @error('expiration_date')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-2 text-end">
                    <button type="submit" class="btn btn-sm rounded btn-primary">
                        <i class="bi bi-pencil me-1"></i> Cập nhật
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- list accesses --}}
    @if ($document)
        <div id="document-access-list" data-document-id="{{ $document->document_id }}"></div>
    @endif

    {{-- vue js --}}
    @vite('resources/js/pages/document-accesses.js')
@endsection
