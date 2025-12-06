<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Danh sách Khoa</title>

    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #333; padding: 6px; }
        th { background: #eaeaea; }
        h2 { text-align: center; }
    </style>
</head>
<body>
    <h2>DANH SÁCH KHOA / BỘ MÔN</h2>

    <table>
        <thead>
            <tr>
                <th>STT</th>
                <th>Mã Khoa</th>
                <th>Tên Khoa</th>
                <th>Mô tả</th>
                <th>Trạng thái</th>
                <th>Số môn học</th>
                <th>Ngày tạo</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($khoas as $index => $khoa)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $khoa->code }}</td>
                    <td>{{ $khoa->name }}</td>
                    <td>{{ $khoa->description }}</td>
                    <td>{{ $khoa->status ? 'Hoạt động' : 'Ngừng' }}</td>
                    <td>{{ $khoa->subjects_count }}</td>
                    <td>{{ $khoa->created_at->format('d/m/Y') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
