@extends('layouts.app')

@section('title', 'Danh sách báo cáo vi phạm')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <i class="bi bi-flag text-danger me-2"></i>
                <h5 class="mb-0 fw-bold">Danh sách báo cáo vi phạm</h5>
            </div>
        </div>

        <div class="card-body">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Mã báo cáo</th>
                        <th>Tên tài liệu</th>
                        <th>Người báo cáo</th>
                        <th>Lý do</th>
                        <th>Ngày báo cáo</th>
                        <th>Trạng thái</th>
                        <th class="text-center">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reports as $report)
                        <tr>
                            <td>{{ $report->report_id }}</td>
                            <td>{{ $report->document->title ?? 'Không xác định' }}</td>
                            <td>{{ $report->user->name ?? 'Người dùng #'.$report->user_id }}</td>
                            <td>{{ $report->reason }}</td>
                            <td>{{ $report->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                @if ($report->status === 'resolved')
                                    <span class="badge bg-success">Đã xử lý</span>
                                @elseif ($report->status === 'processing')
                                    <span class="badge bg-warning text-dark">Đang xử lý</span>
                                @else
                                    <span class="badge bg-secondary">Chưa xử lý</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary view-btn" data-id="{{ $report->report_id }}">
                                    <i class="bi bi-eye"></i> Xem
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="d-flex justify-content-end mt-3">
                {{ $reports->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="reportModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold"><i class="bi bi-flag"></i> Chi tiết báo cáo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>Tài liệu:</strong> <span id="docTitle"></span></p>
                <p><strong>Người báo cáo:</strong> <span id="reportUser"></span></p>
                <p><strong>Lý do:</strong> <span id="reason"></span></p>
                <p><strong>Trạng thái:</strong> <span id="status"></span></p>
                <p><strong>Ngày tạo:</strong> <span id="created"></span></p>
            </div>
            <div class="modal-footer">
                <form id="resolveForm" method="POST">
                    @csrf
                    @method('PUT')
                    <button type="submit" class="btn btn-success">Đánh dấu đã xử lý</button>
                </form>
                <button class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.view-btn').forEach(btn => {
    btn.addEventListener('click', async function() {
        const id = this.dataset.id;
        const res = await fetch(`/dashboard/reports/${id}`);
        const data = await res.json();

        document.getElementById('docTitle').textContent = data.document?.title ?? 'Không xác định';
        document.getElementById('reportUser').textContent = data.user?.name ?? 'Người #' + data.user_id;
        document.getElementById('reason').textContent = data.reason;
        document.getElementById('status').innerHTML =
            data.status === 'resolved'
                ? '<span class="badge bg-success">Đã xử lý</span>'
                : '<span class="badge bg-secondary">Chưa xử lý</span>';
        document.getElementById('created').textContent = new Date(data.created_at).toLocaleString();

        document.getElementById('resolveForm').action = `/dashboard/reports/${id}/resolve`;
        new bootstrap.Modal(document.getElementById('reportModal')).show();
    });
});
</script>
@endsection
