<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Type extends Model
{
    use HasFactory;

    protected $table = 'types';

    /**
     * Khóa chính của bảng.
     */
    protected $primaryKey = 'type_id';

    /**
     * Tự động tăng khóa chính.
     */
    public $incrementing = true;

    /**
     * Loại khóa chính (int).
     */
    protected $keyType = 'int';

    /**
     * Các cột cho phép fill mass-assignment.
     */
    protected $fillable = [
        'name',
        'description',
        'status',
        'created_by',
        'updated_by'
    ];

    /**
     * Ép kiểu dữ liệu.
     */
    protected $casts = [
        'status' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Gắn thêm thuộc tính ảo khi convert sang JSON/array.
     */
    protected $appends = ['code'];

    /**
     * Quan hệ: 1 loại có nhiều tài liệu.
     */
    public function documents()
    {
        return $this->hasMany(Document::class, 'type_id', 'type_id');
    }

    /**
     * Cho phép Route Model Binding dùng `type_id` thay vì mặc định `id`.
     */
    public function getRouteKeyName()
    {
        return 'type_id';
    }

    /**
     * Accessor: Trả về mã định danh loại tài liệu (VD: TL001)
     */
    public function getCodeAttribute()
    {
        $prefix = 'TL';
        $number = str_pad($this->type_id, 3, '0', STR_PAD_LEFT);
        return $prefix . $number;
    }
}
