<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'directory_id',
        'name',
        'path',
        'size',
        'unique_link',
        'is_public'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function directory()
    {
        return $this->belongsTo(Directory::class);
    }
}
