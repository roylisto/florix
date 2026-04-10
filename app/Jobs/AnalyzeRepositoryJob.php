<?php

namespace App\Jobs;

use App\Models\Analysis;
use App\Models\Project;
use App\Services\CodeParserService;
use App\Services\LLMService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class AnalyzeRepositoryJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 600; // 10 minutes

    public Project $project;
    public Analysis $analysis;
    public ?string $zipPath;
    public ?string $localPath;

    /**
     * Create a new job instance.
     */
    public function __construct(Project $project, Analysis $analysis, ?string $zipPath = null, ?string $localPath = null)
    {
        $this->project = $project;
        $this->analysis = $analysis;
        $this->zipPath = $zipPath;
        $this->localPath = $localPath;
    }

    /**
     * Execute the job.
     */
    public function handle(CodeParserService $parser, LLMService $llm): void
    {
        $this->analysis->update([
            'status' => 'processing',
            'zip_path' => $this->zipPath,
        ]);
        $tempDir = storage_path('app/temp/' . uniqid('repo_'));

        try {
            $basePath = $this->localPath ? $this->resolveHostPath($this->localPath) : null;

            if ($this->zipPath) {
                Log::info('Worker trying to open ZIP at: ' . $this->zipPath . ' (Exists: ' . (File::exists($this->zipPath) ? 'Yes' : 'No') . ')');
                $this->analysis->update(['progress_message' => 'Extracting repository...']);
                File::makeDirectory($tempDir, 0755, true);
                $zip = new ZipArchive();
                $res = $zip->open($this->zipPath);
                if ($res === true) {
                    Log::info('Successfully opened ZIP. Extracting selected files to: ' . $tempDir);

                    // Instead of extractTo, we extract files individually to skip excluded dirs
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $filename = $zip->getNameIndex($i);

                        // Use the same exclusion logic as CodeParserService
                        if (!$parser->isExcluded($filename)) {
                            $zip->extractTo($tempDir, $filename);
                        }
                    }

                    $zip->close();

                    // Fix: If the ZIP contains a single root directory, use that as the base path
                    $directories = File::directories($tempDir);
                    $files = File::files($tempDir);
                    if (count($directories) === 1 && count($files) === 0) {
                        $basePath = $directories[0];
                        Log::info("Detected single root directory in ZIP: {$basePath}");
                    } else {
                        $basePath = $tempDir;
                    }

                    $this->analysis->update(['extracted_path' => $basePath]);
                } else {
                    throw new \Exception('Could not open ZIP file. Error code: ' . $res . ' (Path: ' . $this->zipPath . ')');
                }
            }

            if (!$basePath || !File::exists($basePath)) {
                throw new \Exception('Repository path does not exist.');
            }

            // Step 1: Parse code
            Log::info('Step 1: Parsing code...');
            $parsedData = $parser->parse($basePath, function ($message) {
                $this->analysis->update(['progress_message' => $message]);
            });
            $this->analysis->update([
                'parsed_data' => $parsedData,
                'progress_message' => 'Code parsed successfully.'
            ]);

            // Step 2: Call LLM
            Log::info('Step 2: Calling LLM for explanation...');
            $this->analysis->update([
                'status' => 'generating_explanation',
                'progress_message' => 'AI is generating explanation...'
            ]);
            $prompt = $this->buildPrompt($parsedData);
            $llmOutput = $llm->generate($prompt);

            // Step 3: Save results
            Log::info('Step 3: Saving results...');
            $this->analysis->update([
                'llm_output' => $llmOutput,
                'status' => 'completed',
                'progress_message' => 'Analysis completed.'
            ]);
        } catch (\Exception $e) {
            Log::error('Analysis failed: ' . $e->getMessage());
            $this->analysis->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);
        } finally {
            // Clean up temp files
            if (File::exists($tempDir)) {
                File::deleteDirectory($tempDir);
            }

            // Handle ZIP file cleanup
            if ($this->zipPath && File::exists($this->zipPath)) {
                // If extraction failed OR analysis succeeded, delete the ZIP.
                // We know extraction failed if extracted_path is still null.
                $extractionSuccessful = !empty($this->analysis->fresh()->extracted_path);

                if (!$extractionSuccessful || $this->analysis->status === 'completed') {
                    File::delete($this->zipPath);
                    $this->analysis->update(['zip_path' => null]);
                }
            }
        }
    }

    protected function resolveHostPath(string $path): ?string
    {
        if (File::exists($path)) {
            return $path;
        }

        $candidates = [];
        if (str_starts_with($path, '/Users/')) {
            $candidates[] = '/hosthome' . substr($path, strlen('/Users'));
        }
        if (str_starts_with($path, '/home/')) {
            $candidates[] = '/hosthome' . substr($path, strlen('/home'));
        }

        foreach ($candidates as $candidate) {
            if (File::exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    protected function buildPrompt(array $parsedData): string
    {
        $json = json_encode($parsedData, JSON_PRETTY_PRINT);

        return <<<PROMPT
You are a product manager explaining software to a non-technical client.
RULES:
Do NOT mention code, controllers, APIs, or technical terms
Use simple business language
Focus on what the system does from user perspective
INPUT:
{$json}
OUTPUT:
FEATURES
Bullet list of features
WHAT USER SEES
Describe UI like dashboard, filters, tables
USER FLOW
Step-by-step:
User opens X → System shows Y → User clicks → System updates
MERMAID DIAGRAM
graph TD
A[User opens app] --> B[System shows dashboard]
B --> C[User applies filter]
C --> D[System updates data]
PROMPT;
    }
}
