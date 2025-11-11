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

    public function documents()
    {
        return $this->hasMany(Document::class, 'type_id', 'type_id');
    }

    public function getRouteKeyName()
    {
        return 'type_id';
    }
}
