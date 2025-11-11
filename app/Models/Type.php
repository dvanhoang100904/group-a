<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Type extends Model
{
    use HasFactory;

    protected $table = 'types';
    protected $primaryKey = 'type_id';
    protected $fillable = ['name', 'description', 'status'];

    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * Tự động thêm thuộc tính ảo 'code' khi chuyển model sang mảng hoặc JSON.
     */
    protected $appends = ['code'];

    /**
     * Quan hệ: Mỗi loại tài liệu có nhiều tài liệu.
     */
    public function documents()
    {
        return $this->hasMany(Document::class, 'type_id', 'type_id');
    }

    /**
     * Xác định khóa route dùng 'type_id' thay vì 'id'.
     */
    public function getRouteKeyName()
    {
        return 'type_id';
    }

    /**
     * Accessor: Sinh mã loại tài liệu (VD: TL001, TL015, TL120).
     * 
     * @return string
     */
    public function getCodeAttribute()
    {
        $prefix = 'TL'; // Tiền tố cho mã loại tài liệu (TL = Tài Liệu)
        $number = str_pad($this->type_id, 3, '0', STR_PAD_LEFT);
        return $prefix . $number;
    }
}
