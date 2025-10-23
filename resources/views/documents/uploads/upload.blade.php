@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h3 class="mb-3">📁 Upload Tài Liệu</h3>

    <!-- Thông báo -->
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <!-- Form upload -->
    <form method="POST" action="{{ route('upload.store') }}" enctype="multipart/form-data" id="uploadForm">
        @csrf

        <div class="card p-3 mb-4">
            <div class="mb-3">
                <label for="fileInput" class="form-label fw-bold">Chọn Tệp</label>
                <input type="file" id="fileInput" name="files[]" class="form-control" multiple required>
                <small class="text-muted">Cho phép tải nhiều file (PDF / DOCX / PPTX / TXT)</small>
            </div>

            <div class="progress mb-3" style="height: 22px;">
                <div id="progressBar" class="progress-bar progress-bar-striped" 
                    role="progressbar" style="width: 0%">0%</div>
            </div>

            <!-- Bộ lọc -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Chọn Khoa</label>
                    <select class="form-select" name="subject_id" required>
                        <option value="">-- Chọn khoa --</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->subject_id }}">{{ $subject->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Loại Tài Liệu</label>
                    <select class="form-select" name="type">
                        @foreach($types as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Chọn Thư Mục</label>
                    <select class="form-select" name="folder_id">
                        <option value="">-- Không chọn --</option>
                        @foreach($folders as $folder)
                            <option value="{{ $folder->folder_id }}">{{ $folder->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <button type="reset" class="btn btn-secondary">Thoát</button>
                <button type="submit" class="btn btn-primary">Lưu</button>
            </div>
        </div>
    </form>

    <!-- Danh sách tài liệu -->
    <div class="card p-3">
        <h5>📄 Danh sách tài liệu gần đây</h5>
        @if($documents->isEmpty())
            <p class="text-muted">Chưa có tài liệu nào.</p>
        @else
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Tên tài liệu</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($documents as $doc)
                        <tr>
                            <td>{{ $doc->title }}</td>
                            <td>{{ ucfirst($doc->status) }}</td>
                            <td>{{ $doc->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

<script>
const form = document.getElementById('uploadForm');
const progressBar = document.getElementById('progressBar');

form.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(form);

    const xhr = new XMLHttpRequest();
    xhr.open('POST', form.action, true);
    xhr.upload.onprogress = function(e) {
        if (e.lengthComputable) {
            const percent = Math.round((e.loaded / e.total) * 100);
            progressBar.style.width = percent + '%';
            progressBar.textContent = percent + '%';
        }
    };
    xhr.onload = function() {
        if (xhr.status === 200) {
            progressBar.classList.add('bg-success');
            progressBar.textContent = 'Hoàn tất';
            window.location.reload();
        }
    };
    xhr.send(formData);
});
</script>

<style>
.container { max-width: 900px; }
</style>
@endsection
