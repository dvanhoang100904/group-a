<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $table = 'subjects';
    protected $primaryKey = 'subject_id';

    // ✅ Thêm 'credits' vào $fillable để cho phép lưu dữ liệu tín chỉ
    protected $fillable = ['code', 'name', 'credits', 'description', 'status', 'department_id'];

    protected $casts = [
        'status' => 'boolean',
        'department_id' => 'integer',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id')->withDefault([
            'name' => 'Chưa có',
        ]);
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'subject_id');
    }
}
