<?php

namespace App\Http\Controllers;

use App\Jobs\AnalyzeRepositoryJob;
use App\Models\Analysis;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::with('latestAnalysis')->latest()->get();
        return view('projects.index', compact('projects'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'zip_file' => 'nullable|file|mimes:zip|max:1048576',
            'local_path' => 'nullable|string',
        ]);

        if (!$request->hasFile('zip_file') && !$request->local_path) {
            return back()->withErrors(['zip_file' => 'Please provide either a ZIP file or a local path.']);
        }

        $project = Project::create([
            'name' => $request->name,
            'repo_path' => $request->local_path,
        ]);

        $analysis = Analysis::create([
            'project_id' => $project->id,
            'status' => 'pending',
        ]);

        $zipPath = null;
        if ($request->hasFile('zip_file')) {
            $zipPath = $request->file('zip_file')->store('temp');
            $fullPath = Storage::disk('local')->path($zipPath);
            Log::info('Stored ZIP file at: ' . $fullPath . ' (Exists: ' . (file_exists($fullPath) ? 'Yes' : 'No') . ')');
            $zipPath = $fullPath;
        }

        AnalyzeRepositoryJob::dispatch($project, $analysis, $zipPath, $request->local_path);

        return redirect()->route('projects.show', $project);
    }

    public function show(Project $project)
    {
        $analysis = $project->latestAnalysis;
        $logTail = null;
        if ($analysis && $analysis->status === 'failed') {
            $logFile = storage_path('logs/laravel.log');
            if (file_exists($logFile)) {
                $lines = @file($logFile);
                if ($lines !== false) {
                    $tail = array_slice($lines, -200);
                    $logTail = implode('', $tail);
                }
            }
        }
        return view('projects.show', compact('project', 'analysis', 'logTail'));
    }

    public function retry(Project $project)
    {
        $analysis = $project->latestAnalysis;
        if (!$analysis || $analysis->status !== 'failed') {
            return back()->with('error', 'No failed analysis to retry.');
        }

        $newAnalysis = Analysis::create([
            'project_id' => $project->id,
            'status' => 'pending',
            'zip_path' => $analysis->zip_path,
        ]);

        AnalyzeRepositoryJob::dispatch($project, $newAnalysis, $analysis->zip_path, $project->repo_path);

        return redirect()->route('projects.show', $project);
    }

    public function cancel(Project $project)
    {
        $analysis = $project->latestAnalysis;

        if ($analysis && in_array($analysis->status, ['pending', 'processing', 'generating_explanation'])) {
            $analysis->update([
                'status' => 'failed',
                'error' => 'Analysis was manually cancelled by the user.',
            ]);

            return redirect()->route('projects.show', $project)->with('success', 'Analysis cancelled successfully.');
        }

        return back()->with('error', 'No active analysis to cancel.');
    }
}
