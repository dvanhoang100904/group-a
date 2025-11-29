<?php
namespace App\Exports;

use App\Models\Subject;
use Maatwebsite\Excel\Concerns\FromCollection;

class MonHocExport implements FromCollection
{
    public function collection()
    {
        return Subject::select('code', 'name', 'credits')->get();
    }
}
