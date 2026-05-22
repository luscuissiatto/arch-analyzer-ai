<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiagramAnalysis extends Model
{
    protected $table = 'diagram_analyses';

    protected $fillable = [
        'file_path',
        'status',
        'components',
        'risks',
        'recommendations'
    ];
}
