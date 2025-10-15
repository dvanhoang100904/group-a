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
