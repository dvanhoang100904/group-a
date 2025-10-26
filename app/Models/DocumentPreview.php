<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentPreview extends Model
{
    protected $table = 'document_previews';
    protected $primaryKey = 'preview_id';
    protected $fillable = [
        'preview_path',
        'expires_at',
        'generated_by',
        'document_id',
        'version_id'
    ];

    /** Chi lay preview con hieu luc */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    /** Lay moi nhat theo ngay tao */
    public function scopeLatestCreated($query)
    {
        return $query->orderByDesc('created_at');
    }

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function version()
    {
        return $this->belongsTo(DocumentVersion::class, 'version_id');
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
