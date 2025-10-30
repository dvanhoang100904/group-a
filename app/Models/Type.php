<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Type extends Model
{
    use HasFactory;

    protected $table = 'types';
    protected $primaryKey = 'type_id'; // giữ nếu đúng với DB của bạn
    protected $fillable = ['code', 'name', 'description', 'created_by', 'updated_by'];

    /**
     * Quan hệ: Một loại tài liệu có nhiều tài liệu
     */
    public function documents()
    {
        return $this->hasMany(Document::class, 'type_id');
    }

    /**
     * Quan hệ: Người tạo loại tài liệu
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

public function getRouteKeyName()
{
    return 'type_id';
}
    /**
     * Quan hệ: Người cập nhật loại tài liệu
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
