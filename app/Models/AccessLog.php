<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessLog extends Model
{
    use HasFactory;

    protected $table = 'access_logs';
    protected $primaryKey = 'access_log_id';
    public $timestamps = false; // Chỉ dùng created_at

    protected $fillable = [
        'user_id',
        'action',
        'target_id',
        'target_type',
        'ip_address',
        'user_agent',
        'url',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Quan hệ với User
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Quan hệ với Document (nếu target_type = 'document')
     */
    public function document()
    {
        return $this->belongsTo(Document::class, 'target_id', 'document_id')
                    ->where('target_type', 'document');
    }

    /**
     * Scope để lọc theo action
     */
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope để lọc theo user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope để lọc theo khoảng thời gian
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Lấy tên hành động dễ đọc
     */
    public function getActionNameAttribute()
    {
        $actions = [
            'login' => 'Đăng nhập',
            'logout' => 'Đăng xuất',
            'document_view' => 'Xem tài liệu',
            'document_upload' => 'Tải lên tài liệu',
            'document_update' => 'Cập nhật tài liệu',
            'document_comment' => 'Bình luận tài liệu',
            'document_report' => 'Báo cáo tài liệu',
            'document_delete' => 'Xóa tài liệu',
            'document_download' => 'Tải xuống tài liệu',
        ];

        return $actions[$this->action] ?? $this->action;
    }

    /**
     * Lấy icon cho từng loại action
     */
    public function getActionIconAttribute()
    {
        $icons = [
            'login' => 'bi-box-arrow-in-right text-success',
            'logout' => 'bi-box-arrow-left text-danger',
            'document_view' => 'bi-eye text-info',
            'document_upload' => 'bi-cloud-upload text-primary',
            'document_update' => 'bi-pencil-square text-warning',
            'document_comment' => 'bi-chat-left-text text-secondary',
            'document_report' => 'bi-flag text-danger',
            'document_delete' => 'bi-trash text-danger',
            'document_download' => 'bi-download text-success',
        ];

        return $icons[$this->action] ?? 'bi-circle text-muted';
    }
}