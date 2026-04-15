<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Analysis extends Model
{
    protected $fillable = [
        'project_id',
        'parsed_data',
        'file_summaries',
        'llm_output',
        'status',
        'stop_summarizing',
        'progress_message',
        'logs',
        'prompt',
        'error',
        'zip_path',
        'extracted_path',
        'features_content',
        'ui_content',
        'flow_content',
        'mermaid_content'
    ];

    protected $casts = [
        'parsed_data' => 'array',
        'file_summaries' => 'array',
        'stop_summarizing' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
