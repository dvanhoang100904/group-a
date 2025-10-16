@extends('layouts.app')

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
            <h3 class="mb-0">
                <i class="bi bi-clock-history me-2 text-primary"></i>
                Phiên bản tài liệu
            </h3>
            <div>
                <!-- back -->
                <a href="#!" class="btn btn-outline-secondary me-2">
                    <i class="bi bi-arrow-left me-1"></i> Quay lại
                </a>

                <!-- upload -->
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadDocumentModal">
                    <i class="bi bi-upload me-2"></i> Upload tài liệu phiên bản mới
                </button>
            </div>
        </div>

        {{-- detail document --}}
        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body">
                <h5 class="fw-bold mb-1">{{ $document->title }}</h5>
                <p class="text-muted small mb-0">
                    Môn học: <strong>{{ $document->subject->name ?? 'Chưa có' }}</strong> |
                    Khoa: <strong>{{ $document->department->name ?? 'Chưa có' }}</strong> |
                    Số phiên bản:
                    <strong>{{ $document->versions()->count() }}</strong>
                </p>
            </div>
        </div>

        {{-- list versions --}}
        <div class="card shadow-sm border-0">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-list-ul me-1"></i> Danh sách các phiên bản</span>

                <div class="input-group input-group-sm w-auto">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" class="form-control border-start-0"
                        placeholder="Tìm theo ghi chú, người cập nhật..." />
                </div>
            </div>

            <!-- tables -->
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center">#</th>
                                <th>Phiên bản</th>
                                <th>Ghi chú thay đổi</th>
                                <th>Người cập nhật</th>
                                <th>Ngày cập nhật</th>
                                <th>Kích thước</th>
                                <th class="text-end">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($versions as $index => $version)
                                <tr>
                                    <td class="text-center">{{ $versions->firstItem() + $index }}</td>
                                    <td><strong>v{{ $version->version_number }}</strong></td>
                                    <td>{{ $version->note ?? 'Không có ghi chú' }}</td>
                                    <td>{{ $version->user->name ?? 'Chưa xác định' }}</td>
                                    <td>{{ $version->created_at->format('d/m/Y') }}</td>
                                    <td>{{ number_format($version->file_size / 1024 / 1024, 1) }} MB</td>
                                    <td class="text-end">
                                        <a href="#!" class="btn btn-sm btn-outline-primary me-1">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="#!" class="btn btn-sm btn-outline-primary me-1">
                                            <i class="bi bi-download"></i>
                                        </a>
                                        <form method="POST" action="#!" class="d-inline">
                                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-arrow-counterclockwise"></i> Khôi phục
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-3">
                                        Chưa có phiên bản nào cho tài liệu này.
                                    </td>
                                </tr>
                            @endforelse

                        </tbody>
                    </table>

                    {{-- paginate --}}
                    <div class="card-body border-top">
                        @include('layouts.paginate', ['pagination' => $versions])
                    </div>
                </div>
            </div>
        </div>

        <!-- compare -->
        <div class="card shadow-sm mt-4 border-0">
            <div class="card-header bg-light fw-bold">
                <i class="bi bi-shuffle me-1"></i> So sánh phiên bản
            </div>
            <div class="card-body">
                <form class="row g-3 align-items-center">
                    <div class="col-md-5">
                        <label for="versionA" class="form-label small fw-semibold">Phiên bản A</label>
                        <select id="versionA" class="form-select">
                            <option selected>Chọn phiên bản...</option>
                            <option value="v3">v3</option>
                            <option value="v2">v2</option>
                            <option value="v1">v1</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label for="versionB" class="form-label small fw-semibold">Phiên bản B</label>
                        <select id="versionB" class="form-select">
                            <option selected>Chọn phiên bản...</option>
                            <option value="v3">v3</option>
                            <option value="v2">v2</option>
                            <option value="v1">v1</option>
                        </select>
                    </div>
                    <div class="col-md-2 text-end">
                        <button class="btn btn-outline-primary w-100 mt-4">
                            <i class="bi bi-arrow-left-right me-1"></i> So sánh
                        </button>
                    </div>
                </form>

                <div class="mt-4 border-top pt-3" id="compareResult">
                    <h6 class="fw-bold text-primary mb-3">
                        Kết quả so sánh (v2 ↔ v3)
                    </h6>
                    <ul class="list-group small">
                        <li class="list-group-item">
                            <i class="bi bi-check2-circle text-primary me-2"></i>
                            Kích thước tăng từ <strong>3.6 MB → 3.8 MB</strong>
                        </li>
                        <li class="list-group-item">
                            <i class="bi bi-pencil-square text-primary me-2"></i>
                            Ghi chú mới: “Cập nhật phần hướng dẫn sinh viên”
                        </li>
                        <li class="list-group-item">
                            <i class="bi bi-person-check text-primary me-2"></i>
                            Người cập nhật thay đổi: “Trần Thị B → Nguyễn Văn A”
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- modal upload -->
    <div class="modal fade" id="uploadDocumentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-upload me-2 text-primary"></i> Upload tài liệu
                        mới
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="uploadDocumentForm" class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Tiêu đề</label>
                            <input type="text" class="form-control" name="title" />
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Tệp tài liệu</label>
                            <input type="file" class="form-control" name="files[]" multiple
                                accept=".pdf,.doc,.docx,.ppt,.pptx,.zip" />
                            <div class="form-text small text-muted">
                                Bạn có thể chọn nhiều file cùng lúc.
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">
                        Hủy
                    </button>
                    <button class="btn btn-primary" id="saveUploadDocument">
                        <i class="bi bi-save me-1"></i> Upload
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- modal detail -->
    <div class="modal fade" id="versionDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-file-text me-2 text-primary"></i>
                        Chi tiết phiên bản: <span id="versionTitle">v3</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>
                        <strong>Ghi chú thay đổi:</strong>
                        <span id="versionNote">Cập nhật phần hướng dẫn sinh viên</span>
                    </p>
                    <p>
                        <strong>Người cập nhật:</strong>
                        <span id="versionUser">Nguyễn Văn A</span>
                    </p>
                    <p>
                        <strong>Ngày cập nhật:</strong>
                        <span id="versionDate">08/10/2025</span>
                    </p>
                    <p>
                        <strong>Kích thước:</strong>
                        <span id="versionSize">3.8 MB</span>
                    </p>
                    <div class="mt-3">
                        <button class="btn btn-outline-primary me-2">
                            <i class="bi bi-download me-1"></i> Tải về
                        </button>
                        <button class="btn btn-outline-primary">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Khôi phục
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
