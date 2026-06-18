<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Presentation extends Model
{
    protected $fillable = [
        'id',
        'title',
        'original_name',
        'directory',
        'source_path',
        'pdf_path',
        'notes_path',
        'slide_count',
        'status',
        'error_message',
        'converted_at',
    ];

    protected $casts = [
        'converted_at' => 'datetime',
    ];

    public $incrementing = false;

    protected $keyType = 'string';
}
