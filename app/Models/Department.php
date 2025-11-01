<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $table = 'departments';
    protected $primaryKey = 'department_id';
    protected $fillable = ['name', 'description', 'code'];

    // 🔹 Tự động tạo mã khoa khi tạo mới
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($department) {
            // Tự động tạo code nếu chưa có
            if (empty($department->code)) {
                $lastId = self::max('department_id') ?? 0;
                $nextId = $lastId + 1;
                $department->code = 'KHOA' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
            }
        });
    }

    public function subjects()
    {
        return $this->hasMany(Subject::class, 'department_id');
    }
}
