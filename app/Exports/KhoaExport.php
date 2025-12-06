<?php

namespace App\Exports;

use App\Models\Department;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class KhoaExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Department::withCount('subjects')
            ->get()
            ->map(function($d){
                return [
                    'code' => $d->code,
                    'name' => $d->name,
                    'description' => $d->description,
                    'subjects_count' => $d->subjects_count,
                    'created_at' => $d->created_at ? $d->created_at->format('Y-m-d H:i:s') : '',
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Mã Khoa',
            'Tên Khoa',
            'Mô tả',
            'Số lượng môn học',
            'Ngày tạo',
        ];
    }
}
