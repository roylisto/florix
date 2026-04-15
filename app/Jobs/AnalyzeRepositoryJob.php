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
    public $timeout = 7200; // 2 hours

    public Project $project;
    public Analysis $analysis;
    public ?string $zipPath;
    public ?string $localPath;
    public array $targets;

    /**
     * Create a new job instance.
     */
    public function __construct(Project $project, Analysis $analysis, ?string $zipPath = null, ?string $localPath = null, array $targets = ['all'])
    {
        $this->project = $project;
        $this->analysis = $analysis;
        $this->zipPath = $zipPath;
        $this->localPath = $localPath;
        $this->targets = $targets;
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
        $this->logStep('Starting repository analysis...');

        $tempDir = storage_path('app/temp/' . uniqid('repo_'));

        try {
            $basePath = $this->localPath ? $this->resolveHostPath($this->localPath) : null;
            $parsedData = $this->analysis->parsed_data;

            // If we have previous extracted files, use that as base path if nothing else is provided
            if (!$basePath && $this->analysis->extracted_path && File::exists($this->analysis->extracted_path)) {
                $basePath = $this->analysis->extracted_path;
                $this->logStep('Reusing previously extracted files at: ' . $basePath);
            }

            if (!$parsedData && $this->zipPath) {
                $this->logStep('Opening ZIP at: ' . $this->zipPath);
                $this->analysis->update(['progress_message' => 'Extracting repository...']);

                if (!File::exists(dirname($tempDir))) {
                    File::makeDirectory(dirname($tempDir), 0755, true);
                }
                File::makeDirectory($tempDir, 0755, true);

                $zip = new ZipArchive();
                $res = $zip->open($this->zipPath);
                if ($res === true) {
                    $this->logStep('Successfully opened ZIP. Extracting all files to: ' . $tempDir);

                    // Extract all files
                    $zip->extractTo($tempDir);
                    $zip->close();
                    $this->logStep('ZIP extraction completed.');

                    // Fix permissions on extracted files immediately after extraction
                    // This prevents "Permission denied" errors during copy/delete
                    exec("chmod -R 775 " . escapeshellarg($tempDir));
                    $this->logStep('Permissions updated for extracted files.');

                    // Better detection of the base path (handle single root directory)
                    $basePath = $tempDir;
                    $directories = File::directories($tempDir);
                    $files = File::files($tempDir);

                    // If there's only one directory at the root and NO files (except maybe hidden ones like .DS_Store),
                    // then that directory is our base path.
                    if (count($directories) === 1) {
                        $rootFolderName = basename($directories[0]);
                        // Filter out common hidden files to see if the root is "clean"
                        $visibleFiles = array_filter($files, function ($file) {
                            return !str_starts_with($file->getFilename(), '.');
                        });

                        if (count($visibleFiles) === 0) {
                            $basePath = $directories[0];
                            $this->logStep("Detected single root directory in ZIP: {$rootFolderName}. Adjusting base path.");
                        }
                    }

                    $this->analysis->update(['extracted_path' => $basePath]);
                    $this->logStep('Base path set to: ' . $basePath);
                } else {
                    $this->logStep('Failed to open ZIP. Error code: ' . $res);
                    throw new \Exception('Could not open ZIP file. Error code: ' . $res . ' (Path: ' . $this->zipPath . ')');
                }
            }

            if (!$parsedData && (!$basePath || !File::exists($basePath))) {
                throw new \Exception('Repository path does not exist and no previous data found.');
            }

            // Step 1: Parse code (skip if we already have data)
            if (!$parsedData) {
                $this->logStep('Step 1: Parsing code...');
                $parsedData = $parser->parse($basePath, function ($message) {
                    $this->analysis->update(['progress_message' => $message]);
                    $this->logStep('Parsing: ' . $message);
                });
                $this->analysis->update([
                    'parsed_data' => $parsedData,
                    'progress_message' => 'Code parsed successfully.'
                ]);
            } else {
                $this->logStep('Step 1: Reusing existing parsed data (skipping parsing).');
            }

            $totalFiles = $parsedData['total_files'] ?? 0;
            $this->logStep('Code parsed successfully. Total source files found: ' . $totalFiles);

            if ($totalFiles === 0) {
                $this->logStep('No source files were found in the repository.');
                $this->analysis->update([
                    'llm_output' => "NO_DATA_FOUND: We couldn't find any source files in the provided path. Please ensure you uploaded a valid project with supported file extensions.",
                    'status' => 'completed',
                    'progress_message' => 'Analysis completed (no files found).'
                ]);
                return;
            }

            // Step 2: Call LLM
            $this->logStep('Step 2: Summarizing important files...');
            $this->analysis->update([
                'status' => 'generating_explanation',
                'progress_message' => 'AI is analyzing files...'
            ]);

            $structure = $parsedData['structure'] ?? [];

            // 1. Prepare "Full Map" of the project structure for context
            $fullStructureMap = array_map(function ($file) {
                return [
                    'path' => $file['path'],
                    'classes' => array_column($file['classes'] ?? [], 'name'),
                    'methods' => array_column($file['methods'] ?? [], 'name'),
                ];
            }, $structure);

            // 2. Select and batch core files for detailed summarization
            $importantFiles = array_filter($structure, function ($file) {
                $path = strtolower($file['path']);
                return str_contains($path, 'controller') ||
                    str_contains($path, 'model') ||
                    str_contains($path, 'route') ||
                    str_contains($path, 'service') ||
                    str_contains($path, 'blade.php');
            });

            // If no core files found OR the project is very small, take all files
            if (empty($importantFiles) || count($structure) <= 5) {
                $importantFiles = $structure;
                $this->logStep("Project is small or has no obvious core files. Including all " . count($importantFiles) . " files for analysis.");
            } else {
                $this->logStep("Selected " . count($importantFiles) . " core files for deep analysis.");
            }

            // Sort by priority (Controllers/Routes > Models > Views)
            usort($importantFiles, function ($a, $b) {
                $getWeight = function ($path) {
                    $path = strtolower($path);
                    if (str_contains($path, 'route')) return 1;
                    if (str_contains($path, 'controller')) return 2;
                    if (str_contains($path, 'service')) return 3;
                    if (str_contains($path, 'model')) return 4;
                    if (str_contains($path, 'blade.php')) return 5;
                    return 10;
                };
                return $getWeight($a['path']) <=> $getWeight($b['path']);
            });

            // Limit to top 25 core files for deep analysis
            $importantFiles = array_slice($importantFiles, 0, 25);
            $this->logStep("Limited to top " . count($importantFiles) . " core files to optimize speed.");

            $fileSummaries = $this->analysis->file_summaries ?? [];
            $summarizedPaths = array_column($fileSummaries, 'path');
            $this->logStep("Found " . count($fileSummaries) . " existing file summaries.");

            // Filter out already summarized files
            $filesToSummarize = array_filter($importantFiles, function ($file) use ($summarizedPaths) {
                return !in_array($file['path'], $summarizedPaths);
            });
            $this->logStep("Files remaining to summarize: " . count($filesToSummarize));

            // Process in batches of 5 for speed
            $batches = array_chunk($filesToSummarize, 5);
            $totalBatches = count($batches);

            foreach ($batches as $batchIndex => $batch) {
                $this->analysis->refresh();
                if ($this->analysis->stop_summarizing) {
                    $this->logStep("User requested to skip remaining file summarizations.");
                    break;
                }

                $batchPaths = array_column($batch, 'path');
                $this->logStep("Starting batch [" . ($batchIndex + 1) . "/{$totalBatches}]: " . implode(', ', $batchPaths));

                $batchPrompt = $this->buildBatchPrompt($batch);
                $this->analysis->update(['prompt' => $batchPrompt]);

                try {
                    $batchResult = $llm->generate($batchPrompt, function ($message) use ($batchIndex, $totalBatches) {
                        $this->logStep("Batch [" . ($batchIndex + 1) . "/{$totalBatches}] progress: {$message}");
                    }, [
                        'num_predict' => 400,
                        'temperature' => 0,    // Force absolute deterministic for speed
                        'num_ctx' => 2048,     // Smaller context = faster prefill
                        'num_thread' => 4,     // Ensure enough threads are assigned
                    ]);

                    // Expected format: "path: summary\npath: summary..."
                    $lines = explode("\n", trim($batchResult));
                    $batchCount = 0;
                    foreach ($lines as $line) {
                        if (str_contains($line, ':')) {
                            [$path, $summary] = explode(':', $line, 2);
                            $path = trim(str_replace('- ', '', $path));
                            $fileSummaries[] = [
                                'path' => $path,
                                'summary' => trim($summary)
                            ];
                            $batchCount++;
                        }
                    }
                    $this->logStep("Successfully summarized {$batchCount} files in batch " . ($batchIndex + 1) . ".");

                    $this->analysis->update(['file_summaries' => $fileSummaries]);
                } catch (\Exception $e) {
                    $this->logStep("Batch summarization failed: " . $e->getMessage());
                }
            }

            $this->logStep('Step 3: Generating final business explanation...');
            $this->analysis->update(['progress_message' => 'Generating final explanation...']);

            $summaryList = "";
            foreach ($fileSummaries as $item) {
                $summaryList .= "- {$item['path']}: {$item['summary']}\n";
            }

            $commonContext = "Context:\nStructure: " . json_encode($fullStructureMap) . "\nSummaries: " . $summaryList;

            $updates = [];

            // 3.1 Features
            if (in_array('all', $this->targets) || in_array('features', $this->targets)) {
                $this->logStep('Analyzing core features...');
                $featuresPrompt = "Task: List 3-5 high-level business features of this project.\n" . $commonContext . "\n\nFormat: Bullet points.\nNo chatter.";
                $features = $llm->generate($featuresPrompt, null, ['num_predict' => 500]);
                $features = trim($features);
                $updates['features_content'] = $features;
            } else {
                $features = $this->analysis->features_content;
            }

            // 3.2 UI
            if (in_array('all', $this->targets) || in_array('ui', $this->targets)) {
                $this->logStep('Analyzing user interface...');
                $uiPrompt = "Task: Describe what the user sees or the output format of this project.\n" . $commonContext . "\n\nFormat: Plain English paragraph.\nNo chatter.";
                $ui = $llm->generate($uiPrompt, null, ['num_predict' => 500]);
                $ui = trim($ui);
                $updates['ui_content'] = $ui;
            } else {
                $ui = $this->analysis->ui_content;
            }

            // 3.3 Flow
            if (in_array('all', $this->targets) || in_array('flow', $this->targets)) {
                $this->logStep('Analyzing user journey...');
                $flowPrompt = "Task: Describe the step-by-step user journey or logic flow of this project.\n" . $commonContext . "\n\nFormat: Numbered steps.\nNo chatter.";
                $flow = $llm->generate($flowPrompt, null, ['num_predict' => 500]);
                $flow = trim($flow);
                $updates['flow_content'] = $flow;
            } else {
                $flow = $this->analysis->flow_content;
            }

            // 3.4 Mermaid
            if (in_array('all', $this->targets) || in_array('mermaid', $this->targets)) {
                $this->logStep('Generating process flowchart...');
                $mermaidPrompt = "Task: Generate a beautiful, complex Mermaid.js 'graph TD' diagram for this project.\n" .
                    $commonContext .
                    "\n\nRULES:\n" .
                    "1. Use subgraphs to group related logic (e.g., 'subgraph Frontend', 'subgraph Backend', 'subgraph Database').\n" .
                    "2. Use different node shapes: [ ] for processes, { } for decisions, ([ ]) for start/end.\n" .
                    "3. Use styling: 'style nodeName fill:#f9f,stroke:#333,stroke-width:4px'.\n" .
                    "4. Use clear labels with arrows like '-->|user clicks|'.\n" .
                    "5. Format: ONLY raw Mermaid code. No backticks.\n" .
                    "Make it look professional and architectural. No chatter.";
                $mermaid = $llm->generate($mermaidPrompt, null, ['num_predict' => 1000]);
                $mermaid = trim($mermaid);
                $updates['mermaid_content'] = $mermaid;
            } else {
                $mermaid = $this->analysis->mermaid_content;
            }

            // Keep llm_output for compatibility (concatenated)
            $llmOutput = "[FEATURES]\n{$features}\n\n[UI]\n{$ui}\n\n[FLOW]\n{$flow}\n\n[DIAGRAM]\n{$mermaid}";
            $updates['llm_output'] = $llmOutput;
            $updates['status'] = 'completed';
            $updates['progress_message'] = 'Analysis completed successfully.';

            $this->analysis->update($updates);
            $this->analysis->refresh();

            $this->logStep('Final analysis completed.');

            // Step 4: Save results (only if we parsed new data OR the directory is in a temporary location)
            $projectDir = storage_path('app/projects/' . $this->project->id);
            $isTempDir = str_contains($basePath, '/app/temp/');

            if (!empty($basePath) && File::exists($basePath) && $isTempDir) {
                $this->logStep('Step 4: Moving temporary files to project directory...');
                if (File::exists($projectDir)) {
                    File::deleteDirectory($projectDir);
                }
                File::makeDirectory($projectDir, 0755, true);
                File::copyDirectory($basePath, $projectDir);
                $this->analysis->update(['extracted_path' => $projectDir]);
            } else {
                $this->logStep('Step 4: Project files already in place or no new files to move.');
            }
            $this->logStep('Analysis completed successfully.');
        } catch (\Exception $e) {
            $this->logStep('Analysis failed: ' . $e->getMessage());
            Log::error('Analysis failed: ' . $e->getMessage());
            $this->analysis->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);
        } finally {
            // Clean up temp files
            if (File::exists($tempDir)) {
                $this->logStep('Cleaning up temporary files...');
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
                    $this->logStep('ZIP file cleaned up.');
                }
            }
        }
    }

    protected function logStep(string $message): void
    {
        Log::info("Analysis {$this->analysis->id}: {$message}");

        // Use a more atomic update to prevent log loss
        $this->analysis->refresh();
        $newLogs = ($this->analysis->logs ? $this->analysis->logs . "\n" : "") . "[" . now()->toDateTimeString() . "] " . $message;

        $this->analysis->update([
            'logs' => $newLogs
        ]);
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

    protected function buildBatchPrompt(array $files): string
    {
        $fileList = "";
        foreach ($files as $file) {
            $methods = implode(', ', array_column($file['methods'] ?? [], 'name'));
            $summaryHint = $file['summary'] ?? '';
            $fileList .= "FILE: {$file['path']}\nMETHODS: {$methods}\nCODE HINT: {$summaryHint}\n---\n";
        }

        return <<<PROMPT
Task: Provide a ONE SENTENCE business summary for each file below based on its methods and code hint.
Format: "path: summary" (one per line)

FILES:
{$fileList}
PROMPT;
    }

    protected function buildFilePrompt(array $file): string
    {
        $json = json_encode([
            'path' => $file['path'],
            'name' => $file['name'],
            'classes' => $file['classes'] ?? [],
            'methods' => $file['methods'] ?? [],
            'summary' => $file['summary'] ?? ''
        ]);

        return "Analyze this file and provide a ONE SENTENCE business summary of what it does.\n\nDATA:\n{$json}\n\nSUMMARY:";
    }

    protected function buildFinalPrompt(array $fileSummaries, array $fullStructureMap = []): string
    {
        $summaryList = "";
        foreach ($fileSummaries as $item) {
            $summaryList .= "- {$item['path']}: {$item['summary']}\n";
        }

        $structureJson = json_encode($fullStructureMap);

        return <<<PROMPT
Instructions: Analyze the project data and respond using ONLY the tags below.
Rules: No conversational filler. No technical jargon. Describe exactly what is in the data.

[DATA]
Structure: {$structureJson}
Summaries: {$summaryList}

[FEATURES]
(List 3-5 features)

[UI]
(Description of user interface)

[FLOW]
(User journey steps)

[DIAGRAM]
graph TD
(Mermaid code here)
PROMPT;
    }

    protected function buildPrompt(array $parsedData): string
    {
        // Use all files for full context
        $structure = $parsedData['structure'] ?? [];

        $json = json_encode(['total_files' => $parsedData['total_files'] ?? 0, 'structure' => $structure]);

        return <<<PROMPT
You are a senior product manager and system architect. You are explaining a software project to a non-technical client.
I have provided a JSON representation of the project's source code structure, including file paths, classes, methods, and code snippets.

CRITICAL INSTRUCTION: You MUST include a DETAILED MERMAID DIAGRAM section at the end. This is the most important part of your report.

RULES:
- Do NOT mention code, controllers, APIs, database tables, or technical jargon.
- Use simple business language.
- Focus on what the system does from a user perspective.
- Infer the business purpose of the project based on the file names, class names, and method names provided.

INPUT DATA (JSON):
{$json}

OUTPUT FORMAT:
(Your response must follow this EXACT structure)

FEATURES
- Bullet list of high-level features provided by this system.

WHAT USER SEES
- Describe the user interface (e.g., "A dashboard with statistics", "A customer management portal").

USER FLOW
- Step-by-step: User does X → System does Y → Outcome Z.

MERMAID DIAGRAM
(MANDATORY: Provide ONLY the raw Mermaid code. Do NOT use markdown code blocks like ```mermaid. Start directly with 'graph TD', 'graph LR', 'flowchart TD', or 'flowchart LR'. Ensure the diagram is complex and shows multiple paths, decision points, and the full end-to-end user journey as represented by the file summaries)
graph TD
A[User action] --> B[System response]
B --> C{Decision}
C -->|Path 1| D[Result 1]
C -->|Path 2| E[Result 2]
PROMPT;
    }
}
