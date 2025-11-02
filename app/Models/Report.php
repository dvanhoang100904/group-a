<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Report extends Model
{
    use HasFactory;

    protected $table = 'reports';
    protected $primaryKey = 'report_id';
    public $timestamps = true;

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


    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
