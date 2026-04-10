<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = ['name', 'repo_path'];

    public function analyses()
    {
        return $this->hasMany(Analysis::class);
    }

    public function latestAnalysis()
    {
        return $this->hasOne(Analysis::class)->latestOfMany();
    }
}
