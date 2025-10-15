<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Type extends Model
{
    protected $table = 'types';
    protected $primaryKey = 'type_id';
    protected $fillable = ['name', 'description'];

    public function documents()
    {
        return $this->hasMany(Document::class, 'type_id');
    }
}
