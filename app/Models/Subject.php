<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    /** Department */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    /** Documents */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'subject_id', 'subject_id');
    }
}
