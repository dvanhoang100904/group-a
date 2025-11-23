<?php
namespace App\Exports;

use App\Models\Type;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TypesExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Type::withCount('documents')->get()->map(function($type){
            return [
                'Mã loại' => $type->code,
                'Tên loại' => $type->name,
                'Mô tả' => $type->description,
                'Số lượng tài liệu' => $type->documents_count,
                'Trạng thái' => $type->status ? 'Đang hoạt động' : 'Ngừng hoạt động',
                'Ngày tạo' => $type->created_at->format('d/m/Y H:i'),
            ];
        });
    }

    public function headings(): array
    {
        return ['Mã loại', 'Tên loại', 'Mô tả', 'Số lượng tài liệu', 'Trạng thái', 'Ngày tạo'];
    }
}
