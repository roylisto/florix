<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class CodeParserService
{
    /**
     * Directories to exclude from scanning.
     *
     * @var array
     */
    protected array $excludedDirs = [
        'node_modules',
        'vendor',
        '.venv',
        'env',
        'venv',
        '.git',
        '.svn',
        '.hg',
        'storage',
        'bootstrap',
        'tests',
        'dist',
        'build',
        'coverage',
        '.idea',
        '.vscode',
    ];

    /**
     * File extensions to scan.
     *
     * @var array
     */
    protected array $sourceExtensions = ['php', 'js', 'jsx', 'ts', 'tsx', 'py', 'go', 'rb', 'java'];

    /**
     * Parse the repository at the given path.
     *
     * @param string $basePath
     * @param callable|null $onProgress
     * @return array
     */
    public function parse(string $basePath, ?callable $onProgress = null): array
    {
        \Illuminate\Support\Facades\Log::info("CodeParserService: Starting generic parse of {$basePath}");

        if ($onProgress) $onProgress("Scanning all source files...");

        $files = File::allFiles($basePath);
        $projectStructure = [];
        $totalFiles = 0;

        foreach ($files as $file) {
            $relativePath = str_replace(rtrim($basePath, '/') . '/', '', $file->getRealPath());

            if ($this->isExcluded($relativePath)) {
                continue;
            }

            if (!in_array($file->getExtension(), $this->sourceExtensions)) {
                continue;
            }

            $totalFiles++;
            if ($totalFiles % 50 === 0 && $onProgress) {
                $onProgress("Scanned {$totalFiles} files...");
            }

            $content = $file->getContents();
            $filename = $file->getFilenameWithoutExtension();

            // Basic structure extraction
            $classes = $this->extractClasses($content);
            $methods = $this->extractMethods($content);

            $projectStructure[] = [
                'path' => $relativePath,
                'name' => $filename,
                'extension' => $file->getExtension(),
                'classes' => $classes,
                'methods' => $methods,
                // If it's a small file, we can include some context or top-level comments
                'summary' => $this->extractSummary($content),
            ];
        }

        if ($onProgress) $onProgress("Scan complete. Found {$totalFiles} source files.");

        return [
            'total_files' => $totalFiles,
            'structure' => $projectStructure,
            // Keep empty arrays for backward compatibility if needed,
            // but we'll update the prompt to use 'structure'
            'routes' => [],
            'controllers' => [],
            'models' => [],
        ];
    }

    /**
     * Extract class names from content.
     */
    protected function extractClasses(string $content): array
    {
        preg_match_all('/(?:class|interface|trait)\s+(\w+)/i', $content, $matches);
        return array_unique($matches[1] ?? []);
    }

    /**
     * Extract method/function names from content.
     */
    protected function extractMethods(string $content): array
    {
        // Matches "public function name(" or "function name(" or "const name = () =>" or "async function name("
        preg_match_all('/(?:public|protected|private|static|async)?\s*function\s+(\w+)\s*\(|const\s+(\w+)\s*=\s*(?:async\s*)?\([^)]*\)\s*=>/i', $content, $matches);

        $methods = array_merge($matches[1] ?? [], $matches[2] ?? []);
        return array_values(array_unique(array_filter($methods)));
    }

    /**
     * Extract a brief summary or top-level comments.
     */
    protected function extractSummary(string $content): string
    {
        // Just take the first 400 characters of the file for context,
        // removing excessive whitespace to keep the prompt compact
        $summary = substr($content, 0, 400);
        return preg_replace('/\s+/', ' ', $summary);
    }

    /**
     * Check if a path should be excluded.
     */
    public function isExcluded(string $path): bool
    {
        // Normalize path to use forward slashes for consistent checking
        $normalizedPath = str_replace(DIRECTORY_SEPARATOR, '/', $path);

        foreach ($this->excludedDirs as $dir) {
            $pattern = '/(^|\/)' . preg_quote($dir, '/') . '(\/|$)/';
            if (preg_match($pattern, $normalizedPath)) {
                \Illuminate\Support\Facades\Log::debug("CodeParserService: Excluding path: {$path} (matches {$dir})");
                return true;
            }
        }
        return false;
    }
}
