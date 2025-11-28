<h2>Danh sách môn học</h2>

<table width="100%" border="1" cellspacing="0" cellpadding="6">
    <thead>
        <tr style="background:#e8e8e8;">
            <th>Mã</th>
            <th>Tên môn học</th>
            <th>Tín chỉ</th>
            <th>Khoa</th>
        </tr>
    </thead>

    <tbody>
        @foreach ($monhocs as $mh)
        <tr>
            <td>{{ $mh->code }}</td>
            <td>{{ $mh->name }}</td>
            <td>{{ $mh->credits }}</td>
            <td>{{ $mh->department->name ?? '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
