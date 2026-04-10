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
     * Parse the repository at the given path.
     *
     * @param string $basePath
     * @param callable|null $onProgress
     * @return array
     */
    public function parse(string $basePath, ?callable $onProgress = null): array
    {
        \Illuminate\Support\Facades\Log::info("CodeParserService: Starting parse of {$basePath}");
        
        if ($onProgress) $onProgress("Scanning routes...");
        $routes = $this->parseRoutes($basePath);
        
        if ($onProgress) $onProgress("Scanning controllers...");
        $controllers = $this->parseControllers($basePath);
        
        if ($onProgress) $onProgress("Scanning models...");
        $models = $this->parseModels($basePath);

        return [
            'routes' => $routes,
            'controllers' => $controllers,
            'models' => $models,
        ];
    }

    /**
     * Check if a path should be excluded.
     *
     * @param string $path
     * @return bool
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

    /**
     * Parse routes/web.php and routes/api.php.
     */
    protected function parseRoutes(string $basePath): array
    {
        $routes = [];
        $files = ['routes/web.php', 'routes/api.php'];

        foreach ($files as $file) {
            $path = rtrim($basePath, '/') . '/' . $file;
            if (File::exists($path)) {
                $content = File::get($path);
                // Simple regex to find Route::get, Route::post, etc.
                // Matches Route::get('/path', [Controller::class, 'method']) or Route::get('/path', 'Controller@method')
                preg_match_all('/Route::(get|post|put|patch|delete|any)\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*(?:\[([^\]]+)\]|[\'"]([^\'"]+)[\'"])/i', $content, $matches, PREG_SET_ORDER);

                foreach ($matches as $match) {
                    $method = strtoupper($match[1]);
                    $uri = $match[2];
                    $action = $match[3] ?: $match[4];

                    // Clean up action if it's [Controller::class, 'method']
                    if (str_contains($action, '::class')) {
                        $action = preg_replace('/(\w+)::class\s*,\s*[\'"](\w+)[\'"]/', '$1@$2', $action);
                    }

                    $routes[] = "$method $uri → $action";
                }
            }
        }

        return $routes;
    }

    /**
     * Parse app/Http/Controllers directory.
     */
    protected function parseControllers(string $basePath): array
    {
        $controllers = [];
        $controllersPath = rtrim($basePath, '/') . '/app/Http/Controllers';

        if (File::exists($controllersPath)) {
            $files = File::allFiles($controllersPath);

            foreach ($files as $file) {
                if ($this->isExcluded($file->getRelativePathname())) {
                    continue;
                }
                if ($file->getExtension() === 'php') {
                    $content = $file->getContents();
                    $filename = $file->getFilenameWithoutExtension();

                    if ($filename === 'Controller') {
                        continue;
                    }

                    // Extract public methods
                    preg_match_all('/public\s+function\s+(\w+)\s*\(/i', $content, $matches);
                    $methods = array_diff($matches[1], ['__construct', 'middleware']);

                    if (!empty($methods)) {
                        $controllers[] = [
                            'name' => $filename,
                            'methods' => array_values($methods),
                        ];
                    }
                }
            }
        }

        return $controllers;
    }

    /**
     * Parse app/Models directory.
     */
    protected function parseModels(string $basePath): array
    {
        $models = [];
        $modelsPath = rtrim($basePath, '/') . '/app/Models';

        if (File::exists($modelsPath)) {
            $files = File::allFiles($modelsPath);

            foreach ($files as $file) {
                if ($this->isExcluded($file->getRelativePathname())) {
                    continue;
                }
                if ($file->getExtension() === 'php') {
                    $filename = $file->getFilenameWithoutExtension();
                    $models[] = $filename;
                }
            }
        }

        return $models;
    }
}
