<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Type extends Model
{
    protected $table = 'types';
    protected $primaryKey = 'type_id';
    protected $fillable = ['name', 'description', 'status'];

    protected $casts = [
        'status' => 'boolean',
    ];

    /** Documents */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'type_id', 'type_id');
    }
}
