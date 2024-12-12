<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Thread extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';
    protected $fillable = [
        'id',
        'instructions',
        'created_at'
    ];

    public function messages()
    {
        return $this->hasMany(Message::class, 'thread_id', 'id')
            ->orderBy('created_at', 'asc');
    }
}
