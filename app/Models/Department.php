<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $table = 'departments';
    protected $primaryKey = 'department_id';


    protected $fillable = ['code', 'name', 'description', 'status'];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function subjects()
    {
        return $this->hasMany(Subject::class, 'department_id');
    }
}
