<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Analysis extends Model
{
    protected $fillable = [
        'project_id', 
        'parsed_data', 
        'llm_output', 
        'status', 
        'progress_message',
        'logs',
        'error', 
        'zip_path', 
        'extracted_path'
    ];

    protected $casts = [
        'parsed_data' => 'array',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
