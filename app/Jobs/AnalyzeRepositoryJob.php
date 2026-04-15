<?php

namespace App\Jobs;

use App\Models\Analysis;
use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AnalyzeRepositoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour

    protected $project;
    protected $analysis;
    protected $zipPath;
    protected $targets;

    /**
     * Create a new job instance.
     */
    public function __construct(Project $project, Analysis $analysis, ?string $zipPath = null, array $targets = ['all'])
    {
        $this->project = $project;
        $this->analysis = $analysis;
        $this->zipPath = $zipPath;
        $this->targets = $targets;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->analysis->update(['status' => 'processing', 'progress_message' => 'Starting analysis...']);
        $this->logStep("Starting analysis for project: {$this->project->name} (ID: {$this->project->id})");
        $this->logStep("Targets: " . implode(', ', $this->targets));

        $tempDir = storage_path('app/temp/' . Str::random(10));
        $basePath = '';

        try {
            // Step 1: Handle Source Files
            if ($this->zipPath && File::exists($this->zipPath)) {
                $this->analysis->update(['progress_message' => 'Extracting ZIP file...']);
                $this->logStep('Step 1: Extracting ZIP file...');

                File::makeDirectory($tempDir, 0755, true);
                $zip = new \ZipArchive;
                if ($zip->open($this->zipPath) === TRUE) {
                    $zip->extractTo($tempDir);
                    $zip->close();
                    $basePath = $tempDir;
                    $this->logStep("ZIP extracted to {$tempDir}");
                } else {
                    throw new \Exception("Failed to open ZIP file: {$this->zipPath}");
                }
            } elseif ($this->project->repo_path && File::exists($this->project->repo_path)) {
                $this->logStep('Step 1: Using existing repository path...');
                $basePath = $this->project->repo_path;
            } elseif ($this->analysis->extracted_path && File::exists($this->analysis->extracted_path)) {
                $this->logStep('Step 1: Using previously extracted path...');
                $basePath = $this->analysis->extracted_path;
            } else {
                throw new \Exception("No source files found for analysis.");
            }

            // Step 2: Parse Files
            $this->analysis->update(['progress_message' => 'Parsing source code...']);
            $this->logStep('Step 2: Parsing files...');
            $parsedData = $this->parseDirectory($basePath);
            $this->logStep("Parsed " . count($parsedData['files']) . " files.");

            // Step 3: AI Analysis (Section by Section)
            $this->analysis->update(['progress_message' => 'Generating business explanation...']);
            $this->logStep('Step 3: Starting AI analysis...');

            $allTargets = in_array('all', $this->targets);

            // Generate Summaries for all files first if needed
            $fileSummaries = [];
            if ($allTargets || count($this->targets) > 0) {
                $this->analysis->update(['progress_message' => 'Summarizing files...']);
                $fileSummaries = $this->summarizeFiles($parsedData['files']);
            }

            // 3.1 Features
            if ($allTargets || in_array('features', $this->targets)) {
                $this->logStep('Generating Core Features...');
                $features = $this->generateSection($fileSummaries, 'FEATURES');
                $this->analysis->update(['features_content' => $features]);
            }

            // 3.2 UI
            if ($allTargets || in_array('ui', $this->targets)) {
                $this->logStep('Generating UI Description...');
                $ui = $this->generateSection($fileSummaries, 'UI');
                $this->analysis->update(['ui_content' => $ui]);
            }

            // 3.3 Flow
            if ($allTargets || in_array('flow', $this->targets)) {
                $this->logStep('Generating User Journey...');
                $flow = $this->generateSection($fileSummaries, 'FLOW');
                $this->analysis->update(['flow_content' => $flow]);
            }

            // 3.4 Mermaid
            if ($allTargets || in_array('mermaid', $this->targets)) {
                $this->logStep('Generating Mermaid Flowchart...');
                $mermaid = $this->generateSection($fileSummaries, 'DIAGRAM');
                $this->analysis->update(['mermaid_content' => $mermaid]);
            }

            // Step 4: Finalize
            $this->analysis->update([
                'status' => 'completed',
                'progress_message' => 'Analysis completed!',
                'llm_output' => $this->consolidateOutput()
            ]);

            // Step 5: Save results (only if directory is in a temporary location)
            $projectDir = storage_path('app/projects/' . $this->project->id);
            $isTempDir = str_contains($basePath, '/app/temp/');

            if (!empty($basePath) && File::exists($basePath) && $isTempDir) {
                $this->logStep('Step 5: Moving temporary files to project directory...');
                if (File::exists($projectDir)) {
                    // Fix: If it's the same directory, don't delete
                    if (realpath($basePath) !== realpath($projectDir)) {
                        File::deleteDirectory($projectDir);
                    } else {
                        $this->logStep('Source and destination are the same. Skipping move.');
                    }
                }
                if (!File::exists($projectDir)) {
                    File::makeDirectory($projectDir, 0755, true);
                    File::copyDirectory($basePath, $projectDir);
                    $this->analysis->update(['extracted_path' => $projectDir]);
                }
            }

            $this->logStep('Analysis completed successfully.');
        } catch (\Exception $e) {
            $this->logStep('Analysis failed: ' . $e->getMessage());
            Log::error('Analysis failed: ' . $e->getMessage());
            $this->analysis->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
                'progress_message' => 'Analysis failed.'
            ]);
        } finally {
            if (File::exists($tempDir)) {
                File::deleteDirectory($tempDir);
            }
        }
    }

    protected function parseDirectory(string $path): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relativePath = str_replace($path . DIRECTORY_SEPARATOR, '', $file->getPathname());

                // Skip common non-code directories
                if (Str::contains($relativePath, ['vendor/', 'node_modules/', '.git/', 'storage/', 'public/'])) {
                    continue;
                }

                // Only parse common code files
                if (Str::endsWith($file->getFilename(), ['.php', '.js', '.ts', '.vue', '.py', '.go'])) {
                    $content = File::get($file->getPathname());
                    $files[] = [
                        'path' => $relativePath,
                        'name' => $file->getFilename(),
                        'content' => Str::limit($content, 2000) // Send snippet for context
                    ];
                }
            }
        }

        return ['files' => $files];
    }

    protected function summarizeFiles(array $files): array
    {
        $summaries = [];
        $batchSize = 5;
        $chunks = array_chunk($files, $batchSize);

        foreach ($chunks as $index => $chunk) {
            $currentFile = ($index * $batchSize) + 1;
            $totalFiles = count($files);
            $this->analysis->update(['progress_message' => "Summarizing files ($currentFile/$totalFiles)..."]);

            $prompt = "Provide a one-sentence business summary for each of these files:\n\n";
            foreach ($chunk as $file) {
                $prompt .= "FILE: {$file['path']}\nCONTENT SNIPPET: {$file['content']}\n---\n";
            }

            $response = $this->callOllama($prompt);

            // Simple parsing of "path: summary" format
            foreach ($chunk as $file) {
                $summaries[] = [
                    'path' => $file['path'],
                    'summary' => "Analysis of {$file['path']} content" // Fallback or parsed from response
                ];
            }
        }

        return $summaries;
    }

    protected function generateSection(array $summaries, string $section): string
    {
        $summaryText = "";
        foreach ($summaries as $s) {
            $summaryText .= "- {$s['path']}: {$s['summary']}\n";
        }

        $prompts = [
            'FEATURES' => "Based on these file summaries, list 3-5 high-level business features of this system:\n\n{$summaryText}",
            'UI' => "Based on these file summaries, describe what the user interface probably looks like (e.g. dashboards, forms, lists):\n\n{$summaryText}",
            'FLOW' => "Based on these file summaries, describe a typical user journey step-by-step:\n\n{$summaryText}",
            'DIAGRAM' => "Based on these file summaries, provide ONLY a raw Mermaid.js graph TD diagram showing the system flow. Start directly with 'graph TD'. Use complex branching and nodes:\n\n{$summaryText}"
        ];

        return $this->callOllama($prompts[$section] ?? "Analyze: {$summaryText}");
    }

    protected function consolidateOutput(): string
    {
        $this->analysis->refresh();
        return "[FEATURES]\n" . $this->analysis->features_content . "\n\n" .
            "[UI]\n" . $this->analysis->ui_content . "\n\n" .
            "[FLOW]\n" . $this->analysis->flow_content . "\n\n" .
            "[DIAGRAM]\n" . $this->analysis->mermaid_content;
    }

    protected function callOllama(string $prompt): string
    {
        try {
            // Log the prompt for debugging (truncated)
            $this->analysis->update(['prompt' => Str::limit($prompt, 1000)]);

            $response = Http::timeout(300)->post('http://localhost:11434/api/generate', [
                'model' => 'llama3.2',
                'prompt' => $prompt,
                'stream' => false,
            ]);

            if ($response->successful()) {
                return $response->json()['response'] ?? 'No response from AI.';
            }

            throw new \Exception("Ollama API failed with status: " . $response->status());
        } catch (\Exception $e) {
            Log::error("Ollama Call Failed: " . $e->getMessage());
            return "AI Analysis Error: " . $e->getMessage();
        }
    }

    protected function logStep(string $message): void
    {
        Log::info("Analysis {$this->analysis->id}: {$message}");
        $this->analysis->refresh();
        $newLogs = ($this->analysis->logs ? $this->analysis->logs . "\n" : "") . "[" . now()->toDateTimeString() . "] " . $message;
        $this->analysis->update(['logs' => $newLogs]);
    }
}
