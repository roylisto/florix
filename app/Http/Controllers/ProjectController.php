<?php

namespace App\Http\Controllers;

use App\Jobs\AnalyzeRepositoryJob;
use App\Models\Analysis;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
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

    public function regenerate(Project $requestProject, \Illuminate\Http\Request $request)
    {
        $project = $requestProject;
        $analysis = $project->latestAnalysis;
        if (!$analysis || $analysis->status !== 'completed') {
            return back()->with('error', 'No completed analysis to re-generate.');
        }

        $targets = $request->input('targets', ['all']);

        $newAnalysisData = [
            'project_id' => $project->id,
            'status' => 'pending',
            'parsed_data' => $analysis->parsed_data,
            'file_summaries' => $analysis->file_summaries,
            'extracted_path' => $analysis->extracted_path,
            'zip_path' => $analysis->zip_path,
        ];

        // If not regenerating all, copy existing content for non-target sections
        if (!in_array('all', $targets)) {
            if (!in_array('features', $targets)) $newAnalysisData['features_content'] = $analysis->features_content;
            if (!in_array('ui', $targets)) $newAnalysisData['ui_content'] = $analysis->ui_content;
            if (!in_array('flow', $targets)) $newAnalysisData['flow_content'] = $analysis->flow_content;
            if (!in_array('mermaid', $targets)) $newAnalysisData['mermaid_content'] = $analysis->mermaid_content;
        }

        $newAnalysis = Analysis::create($newAnalysisData);

        AnalyzeRepositoryJob::dispatch($project, $newAnalysis, null, $project->repo_path, $targets);

        return redirect()->route('projects.show', $project);
    }

    public function resume(Project $project)
    {
        $analysis = $project->latestAnalysis;
        if (!$analysis || !in_array($analysis->status, ['failed', 'cancelled'])) {
            return back()->with('error', 'No failed or cancelled analysis to resume.');
        }

        // Reset status and clear skip flag to resume
        $analysis->update([
            'status' => 'pending',
            'stop_summarizing' => false,
            'error' => null
        ]);

        AnalyzeRepositoryJob::dispatch($project, $analysis, $analysis->zip_path, $project->repo_path);

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

    public function status(Project $project)
    {
        $analysis = $project->latestAnalysis;

        return response()->json([
            'status' => $analysis?->status ?? 'pending',
            'progress_message' => $analysis?->progress_message ?? '',
            'logs' => $analysis?->logs ?? '',
            'prompt' => $analysis?->prompt ?? '',
            'error' => $analysis?->error ?? null,
            'stop_summarizing' => $analysis?->stop_summarizing ?? false,
        ]);
    }

    public function browse(Project $project, ?string $path = null)
    {
        $analysis = $project->latestAnalysis;
        if (!$analysis || !$analysis->extracted_path) {
            return back()->with('error', 'Project source code not available. Run an analysis first.');
        }

        $basePath = $this->resolvePath($analysis->extracted_path);
        $fullPath = $path ? $basePath . '/' . $path : $basePath;

        if (!File::exists($fullPath)) {
            abort(404, 'Path not found.');
        }

        if (!str_starts_with(realpath($fullPath), realpath($basePath))) {
            abort(403, 'Unauthorized access.');
        }

        $directories = [];
        $files = [];

        foreach (File::directories($fullPath) as $dir) {
            $relPath = str_replace(rtrim($basePath, '/') . '/', '', $dir);
            $directories[] = [
                'name' => basename($dir),
                'path' => $relPath,
            ];
        }

        foreach (File::files($fullPath) as $file) {
            $relPath = str_replace(rtrim($basePath, '/') . '/', '', $file->getRealPath());
            $files[] = [
                'name' => $file->getFilename(),
                'path' => $relPath,
                'size' => number_format($file->getSize() / 1024, 2) . ' KB',
            ];
        }

        $breadcrumbs = [];
        if ($path) {
            $parts = explode('/', $path);
            $current = '';
            foreach ($parts as $part) {
                $current .= ($current ? '/' : '') . $part;
                $breadcrumbs[] = ['name' => $part, 'path' => $current];
            }
        }

        return view('projects.browse', compact('project', 'directories', 'files', 'path', 'breadcrumbs'));
    }

    public function viewFile(Project $project, Request $request)
    {
        $path = $request->query('path');
        if (!$path) {
            abort(404, 'Path required.');
        }

        $analysis = $project->latestAnalysis;
        if (!$analysis || !$analysis->extracted_path) {
            return back()->with('error', 'Project source code not available.');
        }

        $basePath = $this->resolvePath($analysis->extracted_path);
        $fullPath = $basePath . '/' . $path;

        if (!File::exists($fullPath) || File::isDirectory($fullPath)) {
            abort(404, 'File not found.');
        }

        if (!str_starts_with(realpath($fullPath), realpath($basePath))) {
            abort(403, 'Unauthorized access.');
        }

        $content = File::get($fullPath);
        $extension = pathinfo($fullPath, PATHINFO_EXTENSION);

        $breadcrumbs = [];
        $parts = explode('/', $path);
        $current = '';
        foreach ($parts as $part) {
            $current .= ($current ? '/' : '') . $part;
            $breadcrumbs[] = ['name' => $part, 'path' => $current];
        }

        return view('projects.view_file', compact('project', 'path', 'content', 'extension', 'breadcrumbs'));
    }

    public function stopSummarizing(Project $project)
    {
        $analysis = $project->latestAnalysis;
        if ($analysis && in_array($analysis->status, ['pending', 'processing', 'generating_explanation'])) {
            $analysis->update(['stop_summarizing' => true]);
            return back()->with('success', 'Skipping to final analysis...');
        }
        return back()->with('error', 'No active summarization to stop.');
    }

    private function resolvePath(string $path): string
    {
        $path = rtrim($path, '/');

        // If the path already exists, return it
        if (File::exists($path)) {
            return $path;
        }

        // Handle path from different environment (e.g., Docker container path vs local path)
        // If it looks like a storage/app/projects/{id} path, remap it to current environment
        if (preg_match('/storage\/app\/projects\/(\d+)/', $path, $matches)) {
            $projectId = $matches[1];
            return storage_path('app/projects/' . $projectId);
        }

        return $path;
    }

    public function destroy(Project $project)
    {
        // Delete associated project files
        $projectDir = storage_path('app/projects/' . $project->id);
        if (File::exists($projectDir)) {
            File::deleteDirectory($projectDir);
        }

        // Delete project and related data (cascading deletes for analyses should be handled in migration or model)
        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Project and its analysis data deleted successfully.');
    }
}
