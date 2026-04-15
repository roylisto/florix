@extends('layouts.app')

@section('content')
    <div class="space-y-8">
        <div class="mb-8 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-3 mb-2">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-dark-text break-words">View Source</h1>
                </div>
                <div class="flex items-center text-sm text-gray-500 dark:text-dark-muted">
                    <a href="{{ route('projects.index') }}" class="hover:text-green-600 dark:hover:text-green-500 transition-colors">Projects</a>
                    <span class="mx-2">/</span>
                    <a href="{{ route('projects.show', $project) }}" class="hover:text-green-600 dark:hover:text-green-500 transition-colors">{{ $project->name }}</a>
                    <span class="mx-2">/</span>
                    <a href="{{ route('projects.browse', $project) }}" class="hover:text-green-600 dark:hover:text-green-500 transition-colors">Browse Code</a>
                    <span class="mx-2">/</span>
                    <span class="text-gray-900 dark:text-dark-text font-medium">{{ basename($path) }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-dark-card rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-dark-border transition-colors duration-200">
            <div class="p-4 border-b border-gray-100 dark:border-dark-border bg-gray-50/50 dark:bg-dark-bg/50 flex justify-between items-center transition-colors">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-400 dark:text-dark-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    <code class="text-sm font-bold text-gray-700 dark:text-dark-text">{{ $path }}</code>
                </div>
                <button onclick="copyToClipboard()" class="text-xs font-bold text-green-600 dark:text-green-500 hover:text-green-700 dark:hover:text-green-400 flex items-center gap-1 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                    </svg>
                    Copy Code
                </button>
            </div>
            <div class="relative">
                <pre class="line-numbers"><code class="language-{{ $extension }}">{{ $content }}</code></pre>
            </div>
        </div>
    </div>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css" rel="stylesheet" />
    <style>
        pre[class*="language-"] {
            margin: 0 !important;
            border-radius: 0 !important;
            background: #1e293b !important;
            font-size: 13px !important;
            line-height: 1.6 !important;
        }
        .line-numbers .line-numbers-rows {
            border-right: 1px solid #334155 !important;
            padding-right: 10px !important;
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/line-numbers/prism-line-numbers.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-php.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-typescript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-python.min.js"></script>
    
    <script>
        function copyToClipboard() {
            const code = {!! json_encode($content) !!};
            navigator.clipboard.writeText(code).then(() => {
                alert('Code copied to clipboard!');
            });
        }
    </script>
@endsection
