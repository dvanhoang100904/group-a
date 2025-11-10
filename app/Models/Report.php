<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{

    protected $table = 'reports';
    protected $primaryKey = 'report_id';

    protected $fillable = [
        'reason',
        'status',
        'document_id',
        'user_id',
        'resolved_at'
    ];

    protected $casts = [
        'status' => 'boolean',
        'document_id' => 'integer',
        'user_id' => 'integer',
    ];

    /** Document */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id', 'document_id');
    }

    /** User */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
