@extends('layouts.app')

@section('content')
    <div class="space-y-8">
        <div class="mb-8 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-3 mb-2">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-dark-text break-words">{{ $project->name }}</h1>
                    @php
                        $statusClasses = match ($analysis?->status) {
                            'completed' => 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800',
                            'processing', 'generating_explanation' => 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800',
                            'failed' => 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800',
                            default => 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700',
                        };
                    @endphp
                    <span id="status-badge"
                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold border {{ $statusClasses }}">
                        {{ ucfirst(str_replace('_', ' ', $analysis?->status ?? 'pending')) }}
                    </span>
                </div>
                <div class="flex items-center text-sm text-gray-500 dark:text-dark-muted">
                    <a href="{{ route('projects.index') }}" class="hover:text-green-600 dark:hover:text-green-500 flex items-center transition-colors">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Projects
                    </a>
                    <span class="mx-2">/</span>
                    <span class="text-gray-900 dark:text-dark-text font-medium">{{ $project->name }}</span>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3 shrink-0 lg:mt-1">
                @if ($analysis?->extracted_path)
                    <a href="{{ route('projects.browse', $project) }}"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center shadow-md hover:shadow-lg whitespace-nowrap gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                        </svg>
                        Browse Code
                    </a>
                @endif

                @if ($analysis?->status === 'completed' || $analysis?->status === 'failed')
                    <div class="relative inline-block text-left">
                        <button type="button"
                            class="bg-green-600 hover:bg-green-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center shadow-md hover:shadow-lg whitespace-nowrap gap-2"
                            id="regen-menu-button" aria-expanded="false" aria-haspopup="true">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span>Re-generate</span>
                            <svg class="ml-1 h-4 w-4 text-green-200" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                                    clip-rule="evenodd" />
                            </svg>
                        </button>

                        <div id="regen-dropdown"
                            class="absolute right-0 z-20 mt-2 w-56 origin-top-right rounded-xl bg-white dark:bg-dark-card shadow-2xl ring-1 ring-black ring-opacity-5 focus:outline-none hidden border border-gray-100 dark:border-dark-border overflow-hidden"
                            role="menu" aria-orientation="vertical" aria-labelledby="regen-menu-button" tabindex="-1">
                            <div class="py-1" role="none">
                                <form action="{{ route('projects.regenerate', $project) }}" method="POST" role="none">
                                    @csrf
                                    <input type="hidden" name="targets[]" value="all">
                                    <button type="submit"
                                        class="text-gray-700 dark:text-dark-text block px-4 py-3 text-sm hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-green-600 dark:hover:text-green-400 font-semibold w-full text-left transition-colors"
                                        role="menuitem">Full Analysis (All)</button>
                                </form>
                                <div class="border-t border-gray-100 dark:border-dark-border"></div>
                                <form action="{{ route('projects.regenerate', $project) }}" method="POST" role="none">
                                    @csrf
                                    <input type="hidden" name="targets[]" value="features">
                                    <button type="submit"
                                        class="text-gray-700 dark:text-dark-text block px-4 py-3 text-sm hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-green-600 dark:hover:text-green-400 font-medium w-full text-left transition-colors"
                                        role="menuitem">Core Features only</button>
                                </form>
                                <form action="{{ route('projects.regenerate', $project) }}" method="POST" role="none">
                                    @csrf
                                    <input type="hidden" name="targets[]" value="ui">
                                    <button type="submit"
                                        class="text-gray-700 dark:text-dark-text block px-4 py-3 text-sm hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-green-600 dark:hover:text-green-400 font-medium w-full text-left transition-colors"
                                        role="menuitem">User Interface only</button>
                                </form>
                                <form action="{{ route('projects.regenerate', $project) }}" method="POST" role="none">
                                    @csrf
                                    <input type="hidden" name="targets[]" value="flow">
                                    <button type="submit"
                                        class="text-gray-700 dark:text-dark-text block px-4 py-3 text-sm hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-green-600 dark:hover:text-green-400 font-medium w-full text-left transition-colors"
                                        role="menuitem">User Journey only</button>
                                </form>
                                <form action="{{ route('projects.regenerate', $project) }}" method="POST" role="none">
                                    @csrf
                                    <input type="hidden" name="targets[]" value="mermaid">
                                    <button type="submit"
                                        class="text-gray-700 dark:text-dark-text block px-4 py-3 text-sm hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-green-600 dark:hover:text-green-400 font-medium w-full text-left transition-colors"
                                        role="menuitem">Process Flowchart only</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif

                <form action="{{ route('projects.destroy', $project) }}" method="POST" class="inline"
                    onsubmit="return confirm('Are you sure you want to delete this project and all its analysis data?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="text-red-500 hover:text-red-700 p-2.5 rounded-xl border border-red-100 dark:border-red-900/30 hover:border-red-200 transition-all bg-white dark:bg-dark-card shadow-sm hover:shadow-md hover:bg-red-50 dark:hover:bg-red-900/20"
                        title="Delete Project">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>

        @if (
            $analysis?->status === 'pending' ||
                $analysis?->status === 'processing' ||
                $analysis?->status === 'generating_explanation')
            <div id="processing-view" class="bg-white dark:bg-dark-card rounded-xl shadow-lg p-8 md:p-12 text-center border border-gray-100 dark:border-dark-border transition-colors duration-200">
                <div class="relative inline-block mb-6">
                    <!-- Custom Florix Plant Animation -->
                    <div class="h-24 w-24 mx-auto mb-2 relative flex items-center justify-center">
                        <svg class="w-full h-full text-green-500" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <style>
                                .branch { stroke-dasharray: 100; stroke-dashoffset: 100; animation: grow 3s ease-out infinite; }
                                .leaf { opacity: 0; animation: fadeIn 3s ease-out infinite; }
                                @keyframes grow { 0% { stroke-dashoffset: 100; } 50%, 100% { stroke-dashoffset: 0; } }
                                @keyframes fadeIn { 0%, 40% { opacity: 0; transform: scale(0); } 60%, 100% { opacity: 1; transform: scale(1); } }
                                .branch-1 { animation-delay: 0s; }
                                .branch-2 { animation-delay: 0.5s; }
                                .leaf-1 { animation-delay: 1.2s; }
                                .leaf-2 { animation-delay: 1.5s; }
                                .leaf-3 { animation-delay: 1.8s; }
                            </style>
                            <!-- Main Stem -->
                            <path class="branch branch-1" d="M50 90V40" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                            <!-- Branches -->
                            <path class="branch branch-2" d="M50 70C65 60 75 55 75 40" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                            <path class="branch branch-2" d="M50 60C35 50 25 45 25 30" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                            <!-- Leaves -->
                            <circle class="leaf leaf-1" cx="50" cy="35" r="5" fill="currentColor"/>
                            <circle class="leaf leaf-2" cx="75" cy="35" r="4" fill="currentColor"/>
                            <circle class="leaf leaf-3" cx="25" cy="25" r="4" fill="currentColor"/>
                        </svg>
                    </div>
                </div>

                <h2 id="processing-title" class="text-xl font-bold text-gray-900 dark:text-dark-text mb-2">
                    @if ($analysis?->status === 'generating_explanation')
                        Generating AI Explanation...
                    @else
                        Analyzing Repository...
                    @endif
                </h2>
                <p id="processing-description" class="text-sm text-gray-600 dark:text-dark-muted max-w-lg mx-auto leading-relaxed mb-6">
                    @if ($analysis?->status === 'generating_explanation')
                        The AI is now processing the parsed data to generate a business-friendly explanation.
                    @else
                        We are parsing your code and preparing it for AI analysis.
                    @endif
                </p>

                <div id="progress-container"
                    class="py-2.5 px-5 bg-green-50 dark:bg-green-900/10 rounded-full inline-flex items-center space-x-3 border border-green-100 dark:border-green-900/30 {{ $analysis?->progress_message ? '' : 'hidden' }}">
                    <div class="flex space-x-1">
                        <div class="h-1.5 w-1.5 bg-green-600 dark:bg-green-500 rounded-full animate-bounce" style="animation-delay: 0s"></div>
                        <div class="h-1.5 w-1.5 bg-green-600 dark:bg-green-500 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                        <div class="h-1.5 w-1.5 bg-green-600 dark:bg-green-500 rounded-full animate-bounce" style="animation-delay: 0.4s"></div>
                    </div>
                    <span id="progress-message"
                        class="text-xs font-semibold text-green-800 dark:text-green-400">{{ $analysis?->progress_message }}</span>
                </div>

                <!-- Debugging Tabs -->
                <div class="mt-8 text-left bg-gray-50 dark:bg-dark-bg rounded-xl border border-gray-200 dark:border-dark-border overflow-hidden transition-colors duration-200">
                    <div class="border-b border-gray-200 dark:border-dark-border flex items-center justify-between bg-white dark:bg-dark-card px-6 transition-colors duration-200">
                        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                            <button onclick="switchTab('logs')" id="tab-logs"
                                class="border-green-600 dark:border-green-500 text-green-700 dark:text-green-400 whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm transition-all">
                                Detailed Logs
                            </button>
                            <button onclick="switchTab('prompt')" id="tab-prompt"
                                class="border-transparent text-gray-500 dark:text-dark-muted hover:text-gray-700 dark:hover:text-dark-text hover:border-gray-300 dark:hover:border-dark-border whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-all">
                                AI Prompt
                            </button>
                        </nav>
                        <div id="logs-actions">
                            <button onclick="copyLogs()"
                                class="bg-white dark:bg-dark-card hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-700 dark:text-dark-text text-xs px-3 py-1.5 rounded-lg border border-gray-300 dark:border-dark-border transition flex items-center gap-2 shadow-sm font-semibold"
                                title="Copy logs to clipboard">
                                <svg class="w-4 h-4 text-gray-400 dark:text-dark-muted" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                </svg>
                                <span>Copy Logs</span>
                            </button>
                        </div>
                    </div>

                    <div id="content-logs" class="p-4">
                        <pre id="realtime-logs"
                            class="bg-gray-900 text-green-400 p-6 rounded-lg text-xs font-mono overflow-auto max-h-80 border border-gray-800 shadow-inner scrollbar-thin scrollbar-thumb-gray-700 scrollbar-track-transparent leading-relaxed">{{ $analysis?->logs ?? 'Waiting for logs...' }}</pre>
                    </div>

                    <div id="content-prompt" class="p-4 hidden">
                        <pre id="realtime-prompt"
                            class="bg-gray-900 text-blue-300 p-6 rounded-lg text-xs font-mono overflow-auto max-h-80 border border-gray-800 shadow-inner leading-relaxed">{{ $analysis?->prompt ?? 'Prompt will appear here...' }}</pre>
                    </div>
                </div>

                <div class="mt-10 flex flex-wrap items-center justify-center gap-4">
                    @if (in_array($analysis?->status, ['pending', 'processing', 'generating_explanation']) && !$analysis?->stop_summarizing)
                        <form id="skip-analysis-form" action="{{ route('projects.stop_summarizing', $project) }}"
                            method="POST">
                            @csrf
                            <button type="submit"
                                class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold px-6 py-3 rounded-xl transition shadow-md hover:shadow-lg flex items-center gap-2"
                                onclick="return confirm('Stop summarizing remaining files and jump straight to the final analysis?')">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                                </svg>
                                Skip to Final Analysis
                            </button>
                        </form>
                    @endif

                    <form action="{{ route('projects.cancel', $project) }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="text-red-600 hover:text-red-700 text-sm font-bold border-2 border-red-100 dark:border-red-900/30 hover:border-red-200 px-6 py-3 rounded-xl transition bg-white dark:bg-dark-card hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center gap-2"
                            onclick="return confirm('Are you sure you want to cancel the current analysis?')">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Cancel Processing
                        </button>
                    </form>
                </div>

                <script>
                    function copyLogs() {
                        const logs = document.getElementById('realtime-logs').innerText;
                        navigator.clipboard.writeText(logs).then(() => {
                            const btn = document.querySelector('button[onclick="copyLogs()"]');
                            const span = btn.querySelector('span');
                            const originalText = span.innerText;
                            span.innerText = 'Copied!';
                            btn.classList.add('bg-green-50', 'text-green-700', 'border-green-200');
                            setTimeout(() => {
                                span.innerText = originalText;
                                btn.classList.remove('bg-green-50', 'text-green-700', 'border-green-200');
                            }, 2000);
                        });
                    }

                    function switchTab(tab) {
                        const logsBtn = document.getElementById('tab-logs');
                        const promptBtn = document.getElementById('tab-prompt');
                        const logsContent = document.getElementById('content-logs');
                        const promptContent = document.getElementById('content-prompt');
                        const logsActions = document.getElementById('logs-actions');

                        if (tab === 'logs') {
                            logsBtn.className =
                                'border-green-600 dark:border-green-500 text-green-700 dark:text-green-400 whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm transition-all';
                            promptBtn.className =
                                'border-transparent text-gray-500 dark:text-dark-muted hover:text-gray-700 dark:hover:text-dark-text hover:border-gray-300 dark:hover:border-dark-border whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-all';
                            logsContent.classList.remove('hidden');
                            promptContent.classList.add('hidden');
                            logsActions.classList.remove('hidden');
                        } else {
                            promptBtn.className =
                                'border-green-600 dark:border-green-500 text-green-700 dark:text-green-400 whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm transition-all';
                            logsBtn.className =
                                'border-transparent text-gray-500 dark:text-dark-muted hover:text-gray-700 dark:hover:text-dark-text hover:border-gray-300 dark:hover:border-dark-border whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-all';
                            promptContent.classList.remove('hidden');
                            logsContent.classList.add('hidden');
                            logsActions.classList.add('hidden');
                        }
                    }

                    function updateStatus() {
                        fetch('{{ route('projects.status', $project) }}')
                            .then(response => response.json())
                            .then(data => {
                                // Update status badge
                                const badge = document.getElementById('status-badge');
                                badge.innerText = data.status.charAt(0).toUpperCase() + data.status.slice(1).replace('_', ' ');

                                // Update badge colors
                                badge.className = 'inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold border ';
                                if (data.status === 'completed') badge.classList.add('bg-green-100', 'text-green-800', 'border-green-200', 'dark:bg-green-900/30', 'dark:text-green-400', 'dark:border-green-800');
                                else if (data.status === 'processing' || data.status === 'generating_explanation') badge.classList
                                    .add('bg-blue-100', 'text-blue-800', 'border-blue-200', 'dark:bg-blue-900/30', 'dark:text-blue-400', 'dark:border-blue-800');
                                else if (data.status === 'failed') badge.classList.add('bg-red-100', 'text-red-800', 'border-red-200', 'dark:bg-red-900/30', 'dark:text-red-400', 'dark:border-red-800');
                                else badge.classList.add('bg-gray-100', 'text-gray-800', 'border-gray-200', 'dark:bg-gray-800', 'dark:text-gray-400', 'dark:border-gray-700');

                                // If completed or failed, refresh page to show results/error
                                if (data.status === 'completed' || data.status === 'failed') {
                                    window.location.reload();
                                    return;
                                }

                                // Update progress message
                                const progressContainer = document.getElementById('progress-container');
                                const progressMessage = document.getElementById('progress-message');
                                if (data.progress_message) {
                                    progressContainer.classList.remove('hidden');
                                    progressMessage.innerText = data.progress_message;
                                }

                                // Update logs
                                const logsPre = document.getElementById('realtime-logs');
                                if (data.logs) {
                                    const shouldScroll = logsPre.scrollTop + logsPre.clientHeight === logsPre.scrollHeight;
                                    logsPre.innerText = data.logs;
                                    if (shouldScroll) {
                                        logsPre.scrollTop = logsPre.scrollHeight;
                                    }
                                }

                                // Update prompt
                                const promptPre = document.getElementById('realtime-prompt');
                                if (data.prompt) {
                                    promptPre.innerText = data.prompt;
                                }

                                // Update skip button visibility
                                const skipForm = document.getElementById('skip-analysis-form');
                                if (skipForm) {
                                    if (data.stop_summarizing || !['pending', 'processing', 'generating_explanation'].includes(data
                                            .status)) {
                                        skipForm.classList.add('hidden');
                                    } else {
                                        skipForm.classList.remove('hidden');
                                    }
                                }

                                // Update title/description based on status
                                const title = document.getElementById('processing-title');
                                const desc = document.getElementById('processing-description');
                                if (data.status === 'generating_explanation') {
                                    title.innerText = 'Generating AI Explanation...';
                                    desc.innerText =
                                        'The AI is now processing the parsed data to generate a business-friendly explanation. This step can take a few minutes depending on the repository size.';
                                }

                                // Poll again in 2 seconds
                                setTimeout(updateStatus, 2000);
                            })
                            .catch(error => {
                                console.error('Error fetching status:', error);
                                setTimeout(updateStatus, 5000);
                            });
                    }

                    document.addEventListener('DOMContentLoaded', function() {
                        const logsPre = document.getElementById('realtime-logs');
                        if (logsPre) logsPre.scrollTop = logsPre.scrollHeight;
                        setTimeout(updateStatus, 2000);
                    });
                </script>
            </div>
        @elseif($analysis?->status === 'failed')
            <div class="bg-white dark:bg-dark-card rounded-2xl shadow-xl p-12 text-center border border-red-100 dark:border-red-900/30 max-w-2xl mx-auto transition-colors duration-200">
                <div class="bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 p-4 rounded-full inline-block mb-6">
                    <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-dark-text mb-3">Analysis Failed</h2>
                <p class="text-gray-600 dark:text-dark-muted mb-8 leading-relaxed">Something went wrong during the analysis. Review the error
                    details below to troubleshoot the issue.</p>

                @if (!empty($analysis?->error))
                    <div class="mb-8 text-left">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-dark-text mb-3 uppercase tracking-wider">Error Message</h3>
                        <div
                            class="bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-400 p-6 rounded-xl border border-red-100 dark:border-red-900/30 text-sm font-mono leading-relaxed shadow-inner">
                            {{ $analysis->error }}
                        </div>
                    </div>
                @endif

                @if (!empty($logTail))
                    <div class="mb-8 text-left">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-dark-text mb-3 uppercase tracking-wider">Recent Logs</h3>
                        <pre id="log-container"
                            class="whitespace-pre-wrap bg-gray-900 text-gray-100 p-6 rounded-xl text-xs overflow-auto max-h-80 shadow-xl leading-relaxed">{{ $logTail }}</pre>
                    </div>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const container = document.getElementById('log-container');
                            if (container) {
                                container.scrollTop = container.scrollHeight;
                            }
                        });
                    </script>
                @endif

                <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mt-10">
                    <a href="{{ route('projects.index') }}"
                        class="w-full sm:w-auto bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-dark-text font-bold py-3 px-8 rounded-xl transition-all">
                        Back to Dashboard
                    </a>
                    @if ($project?->repo_path || $analysis?->zip_path)
                        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                            <form action="{{ route('projects.resume', $project) }}" method="POST" class="w-full">
                                @csrf
                                <button type="submit"
                                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-xl transition-all shadow-md hover:shadow-lg flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                    </svg>
                                    Resume
                                </button>
                            </form>
                            <form action="{{ route('projects.retry', $project) }}" method="POST" class="w-full">
                                @csrf
                                <button type="submit"
                                    class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-xl transition-all shadow-md hover:shadow-lg flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    Full Retry
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        @elseif($analysis?->status === 'completed')
            @if (str_starts_with($analysis->llm_output, 'NO_DATA_FOUND:'))
                <div class="bg-white dark:bg-dark-card rounded-2xl shadow-xl p-16 text-center border border-yellow-100 dark:border-yellow-900/30 max-w-2xl mx-auto transition-colors duration-200">
                    <div class="bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400 p-5 rounded-full inline-block mb-6">
                        <svg class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-dark-text mb-4">No Code Found</h2>
                    <p class="text-gray-600 dark:text-dark-muted mb-10 text-lg leading-relaxed">
                        {{ str_replace('NO_DATA_FOUND: ', '', $analysis->llm_output) }}</p>
                    <a href="{{ route('projects.index') }}"
                        class="bg-green-600 hover:bg-green-700 text-white font-bold py-4 px-10 rounded-xl transition-all shadow-lg hover:shadow-xl inline-flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Try Another Project
                    </a>
                </div>
            @else
                @php
                    $features = $analysis->features_content;
                    $ui = $analysis->ui_content;
                    $flow = $analysis->flow_content;
                    $mermaid = $analysis->mermaid_content;

                    // 1. Fallback for older analyses that don't have split content yet
if (empty($features) && empty($ui) && empty($flow) && empty($mermaid)) {
    $output = $analysis->llm_output;

    $extractSection = function ($content, $keywords, $stopKeywords) {
        $pattern =
            '/(?:' .
            implode('|', $keywords) .
            ')[:\s#\*\[]*(.*?)(?=\s*(?:' .
            implode('|', $stopKeywords) .
            ')|$)/si';
        preg_match($pattern, $content, $match);
        return isset($match[1]) ? trim($match[1]) : '';
    };

    $featuresKeywords = ['\[FEATURES\]', 'FEATURES', 'Features', '### Features'];
    $uiKeywords = [
        '\[UI\]',
        '\[WHAT USER SEES\]',
        'WHAT USER SEES',
        'User Interface',
        'UI Description',
        '### User Interface',
    ];
    $flowKeywords = [
        '\[FLOW\]',
        '\[USER FLOW\]',
        'USER FLOW',
        'User Journey',
        'User Flow',
        '### User Journey',
        '### User Flow',
    ];
    $diagramKeywords = ['\[DIAGRAM\]', 'MERMAID DIAGRAM', 'DIAGRAM', '### Diagram', 'Mermaid Code'];

    $features = $extractSection(
        $output,
        $featuresKeywords,
        array_merge($uiKeywords, $flowKeywords, $diagramKeywords),
    );
    $ui = $extractSection($output, $uiKeywords, array_merge($flowKeywords, $diagramKeywords));
    $flow = $extractSection($output, $flowKeywords, $diagramKeywords);

    preg_match('/((?:graph|flowchart)\s+(?:TD|LR|TB|BT)[\s\S]*)/si', $output, $mermaidMatch);
    $mermaid = isset($mermaidMatch[1]) ? trim($mermaidMatch[1]) : '';
}

// 2. Mermaid Cleanup (for both new and old content)
if ($mermaid) {
    if (preg_match('/```(?:mermaid)?\s*([\s\S]*?)\s*```/i', $mermaid, $codeBlockMatch)) {
        $mermaid = trim($codeBlockMatch[1]);
    }

    $mermaid = preg_replace('/```(mermaid|plaintext)?\s*/i', '', $mermaid);
    $mermaid = preg_replace('/\s*```$/i', '', $mermaid);
    $mermaid = str_replace(['(', ')'], ['[', ']'], $mermaid);
    $mermaid = preg_replace('/([a-zA-Z0-9]+)\[(.*?)\]\}/', '$1["$2"]', $mermaid);
    $mermaid = preg_replace('/([a-zA-Z0-9]+)\{(.*?)\]/', '$1["$2"]', $mermaid);
    $mermaid = trim($mermaid);
}

// 3. Instruction/Data Cleanup
$cleanOutput = function ($text) {
    if (empty($text)) {
        return '';
    }
    $text = preg_replace(
        '/(?:STRICT|RULES|Task:|Objective:|Role:|DATASET|Instructions:).*?\n/si',
        '',
                            $text,
                        );
                        return trim($text);
                    };

                    $features = $cleanOutput($features);
                    $ui = $cleanOutput($ui);
                    $flow = $cleanOutput($flow);

                    if (empty($features) && empty($ui) && empty($flow)) {
                        $features = $cleanOutput($analysis->llm_output);
                    }
                @endphp

                <div class="bg-white dark:bg-dark-card rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-dark-border transition-colors duration-200">
                    <div class="bg-gray-50/50 dark:bg-dark-bg/50 border-b border-gray-200 dark:border-dark-border px-4">
                        <nav class="flex -mb-px overflow-x-auto no-scrollbar" aria-label="Tabs">
                            <button onclick="switchAnalysisTab('features')" id="tab-features"
                                class="border-green-600 dark:border-green-500 text-green-700 dark:text-green-400 whitespace-nowrap py-5 px-6 border-b-2 font-bold text-sm transition-all flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Core Features
                            </button>
                            <button onclick="switchAnalysisTab('ui')" id="tab-ui"
                                class="border-transparent text-gray-500 dark:text-dark-muted hover:text-gray-700 dark:hover:text-dark-text hover:border-gray-300 dark:hover:border-dark-border whitespace-nowrap py-5 px-6 border-b-2 font-semibold text-sm transition-all flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                User Interface
                            </button>
                            <button onclick="switchAnalysisTab('flow')" id="tab-flow"
                                class="border-transparent text-gray-500 dark:text-dark-muted hover:text-gray-700 dark:hover:text-dark-text hover:border-gray-300 dark:hover:border-dark-border whitespace-nowrap py-5 px-6 border-b-2 font-semibold text-sm transition-all flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                                User Journey
                            </button>
                            @if ($mermaid)
                                <button onclick="switchAnalysisTab('diagram')" id="tab-diagram"
                                    class="border-transparent text-gray-500 dark:text-dark-muted hover:text-gray-700 dark:hover:text-dark-text hover:border-gray-300 dark:hover:border-dark-border whitespace-nowrap py-5 px-6 border-b-2 font-semibold text-sm transition-all flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    Flowchart
                                </button>
                            @endif
                            <button onclick="switchAnalysisTab('raw')" id="tab-raw"
                                class="border-transparent text-gray-500 dark:text-dark-muted hover:text-gray-700 dark:hover:text-dark-text hover:border-gray-300 dark:hover:border-dark-border whitespace-nowrap py-5 px-6 border-b-2 font-semibold text-sm transition-all flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                </svg>
                                Raw Data
                            </button>
                            <button onclick="switchAnalysisTab('logs-completed')" id="tab-logs-completed"
                                class="border-transparent text-gray-500 dark:text-dark-muted hover:text-gray-700 dark:hover:text-dark-text hover:border-gray-300 dark:hover:border-dark-border whitespace-nowrap py-5 px-6 border-b-2 font-semibold text-sm transition-all flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Logs
                            </button>
                        </nav>
                    </div>

                    <div class="p-10">
                        <!-- Features Content -->
                        <div id="content-features" class="analysis-tab-content">
                            <div class="flex items-center gap-3 mb-8">
                                <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg text-green-700 dark:text-green-400">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h2 class="text-2xl font-bold text-gray-900 dark:text-dark-text">Core Features</h2>
                            </div>
                            <div class="prose prose-green dark:prose-invert max-w-none text-gray-700 dark:text-dark-muted leading-relaxed text-lg">
                                {!! nl2br(e($features)) !!}
                            </div>
                        </div>

                        <!-- What User Sees Content -->
                        <div id="content-ui" class="analysis-tab-content hidden">
                            <div class="flex items-center gap-3 mb-8">
                                <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg text-blue-700 dark:text-blue-400">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </div>
                                <h2 class="text-2xl font-bold text-gray-900 dark:text-dark-text">What Your Users Will See</h2>
                            </div>
                            <div class="prose prose-blue dark:prose-invert max-w-none text-gray-700 dark:text-dark-muted leading-relaxed text-lg">
                                {!! nl2br(e($ui)) !!}
                            </div>
                        </div>

                        <!-- User Flow Content -->
                        <div id="content-flow" class="analysis-tab-content hidden">
                            <div class="flex items-center gap-3 mb-8">
                                <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg text-purple-700 dark:text-purple-400">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                    </svg>
                                </div>
                                <h2 class="text-2xl font-bold text-gray-900 dark:text-dark-text">The User Journey</h2>
                            </div>
                            <div class="prose prose-purple dark:prose-invert max-w-none text-gray-700 dark:text-dark-muted leading-relaxed text-lg">
                                {!! nl2br(e($flow)) !!}
                            </div>
                        </div>

                        <!-- Mermaid Diagram Content -->
                        @if ($mermaid)
                            <div id="content-diagram" class="analysis-tab-content hidden">
                                <div class="flex items-center justify-between mb-8">
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 bg-orange-100 dark:bg-orange-900/30 rounded-lg text-orange-700 dark:text-orange-400">
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </div>
                                        <h2 class="text-2xl font-bold text-gray-900 dark:text-dark-text">Process Flowchart</h2>
                                    </div>
                                    <div
                                        class="flex items-center p-1 bg-gray-100 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-dark-border shadow-sm">
                                        <button onclick="zoomIn()"
                                            class="p-2 hover:bg-white dark:hover:bg-gray-700 hover:text-green-600 dark:hover:text-green-400 rounded-lg transition-all text-gray-500 dark:text-dark-muted hover:shadow-sm"
                                            title="Zoom In">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 4v16m8-8H4" />
                                            </svg>
                                        </button>
                                        <button onclick="zoomOut()"
                                            class="p-2 hover:bg-white dark:hover:bg-gray-700 hover:text-green-600 dark:hover:text-green-400 rounded-lg transition-all text-gray-500 dark:text-dark-muted hover:shadow-sm"
                                            title="Zoom Out">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M20 12H4" />
                                            </svg>
                                        </button>
                                        <div class="w-px h-4 bg-gray-300 dark:bg-dark-border mx-1"></div>
                                        <button onclick="resetZoom()"
                                            class="p-2 hover:bg-white dark:hover:bg-gray-700 hover:text-green-600 dark:hover:text-green-400 rounded-lg transition-all text-gray-500 dark:text-dark-muted hover:shadow-sm"
                                            title="Reset Zoom">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </button>
                                        <button onclick="toggleFullscreen()"
                                            class="p-2 hover:bg-white dark:hover:bg-gray-700 hover:text-green-600 dark:hover:text-green-400 rounded-lg transition-all text-gray-500 dark:text-dark-muted hover:shadow-sm"
                                            title="Toggle Fullscreen">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <div id="diagram-container"
                                    class="bg-white dark:bg-dark-card rounded-2xl overflow-hidden border border-gray-200 dark:border-dark-border relative shadow-inner transition-colors duration-200"
                                    style="height: 600px; background-image: radial-gradient(#e2e8f0 1px, transparent 1px); background-size: 24px 24px;">
                                    <!-- Dark mode background overlay -->
                                    <div class="absolute inset-0 pointer-events-none opacity-0 dark:opacity-100 transition-opacity duration-200" 
                                         style="background-image: radial-gradient(#334155 1px, transparent 1px); background-size: 24px 24px;"></div>
                                    
                                    <div id="mermaid-wrapper" class="w-full h-full flex items-center justify-center relative z-10">
                                        <div id="mermaid-graph-source" class="hidden">{{ $mermaid }}</div>
                                        <div id="mermaid-output" class="w-full h-full flex items-center justify-center">
                                            <!-- SVG will be injected here -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Raw Data Content -->
                        <div id="content-raw" class="analysis-tab-content hidden">
                            <div class="space-y-10">
                                <div>
                                    <div class="flex justify-between items-center mb-4">
                                        <div class="flex items-center gap-3">
                                            <div class="p-2 bg-gray-100 dark:bg-gray-800 rounded-lg text-gray-700 dark:text-dark-text">
                                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                            </div>
                                            <h3 class="text-xl font-bold text-gray-900 dark:text-dark-text">Full AI Output</h3>
                                        </div>
                                        <button onclick="copyRawData()"
                                            class="bg-white dark:bg-dark-card hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-700 dark:text-dark-text text-sm px-4 py-2 rounded-xl border border-gray-300 dark:border-dark-border transition flex items-center gap-2 shadow-sm font-bold">
                                            <svg class="w-4 h-4 text-gray-400 dark:text-dark-muted" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                            </svg>
                                            <span>Copy Raw Output</span>
                                        </button>
                                    </div>
                                    <div
                                        class="bg-gray-50 dark:bg-dark-bg rounded-2xl p-8 border border-gray-200 dark:border-dark-border overflow-auto max-h-[600px] shadow-inner transition-colors">
                                        <pre id="raw-llm-output" class="text-sm text-gray-700 dark:text-dark-muted whitespace-pre-wrap font-mono leading-relaxed">{{ $analysis->llm_output }}</pre>
                                    </div>
                                </div>

                                @if ($mermaid)
                                    <div>
                                        <div class="flex justify-between items-center mb-4">
                                            <div class="flex items-center gap-3">
                                                <div class="p-2 bg-gray-100 dark:bg-gray-800 rounded-lg text-gray-700 dark:text-dark-text">
                                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                                        stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                                <h3 class="text-xl font-bold text-gray-900 dark:text-dark-text">Extracted Mermaid Source</h3>
                                            </div>
                                            <button onclick="copyMermaidSource()"
                                                class="bg-white dark:bg-dark-card hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-700 dark:text-dark-text text-sm px-4 py-2 rounded-xl border border-gray-300 dark:border-dark-border transition flex items-center gap-2 shadow-sm font-bold">
                                                <svg class="w-4 h-4 text-gray-400 dark:text-dark-muted" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                                </svg>
                                                <span>Copy Source</span>
                                            </button>
                                        </div>
                                        <div class="bg-gray-900 rounded-2xl p-8 border border-gray-800 shadow-xl">
                                            <pre id="mermaid-source-display" class="text-sm text-green-400 whitespace-pre-wrap font-mono leading-relaxed">{{ $mermaid }}</pre>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Detailed Logs Content -->
                        <div id="content-logs-completed" class="analysis-tab-content hidden">
                            <div class="flex justify-between items-center mb-8">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-gray-100 dark:bg-gray-800 rounded-lg text-gray-700 dark:text-dark-text">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <h2 class="text-2xl font-bold text-gray-900 dark:text-dark-text">Analysis Logs</h2>
                                </div>
                                <button onclick="copyLogsFromCompleted()"
                                    class="bg-white dark:bg-dark-card hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-700 dark:text-dark-text text-sm px-4 py-2 rounded-xl border border-gray-300 dark:border-dark-border transition flex items-center gap-2 shadow-sm font-bold">
                                    <svg class="w-4 h-4 text-gray-400 dark:text-dark-muted" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                    </svg>
                                    <span>Copy Logs</span>
                                </button>
                            </div>
                            <div class="bg-gray-900 rounded-2xl p-8 border border-gray-800 shadow-xl">
                                <pre id="completed-logs"
                                    class="text-xs text-green-400 font-mono whitespace-pre-wrap overflow-auto max-h-[600px] leading-relaxed">{{ $analysis->logs }}</pre>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    let panZoomInstance = null;
                    let mermaidRendered = false;

                    function copyRawData() {
                        const raw = document.getElementById('raw-llm-output').innerText;
                        navigator.clipboard.writeText(raw).then(() => {
                            const btn = document.querySelector('button[onclick="copyRawData()"]');
                            const span = btn.querySelector('span');
                            const originalText = span.innerText;
                            span.innerText = 'Copied!';
                            btn.classList.add('bg-green-50', 'text-green-700', 'border-green-200');
                            setTimeout(() => {
                                span.innerText = originalText;
                                btn.classList.remove('bg-green-50', 'text-green-700', 'border-green-200');
                            }, 2000);
                        });
                    }

                    function copyMermaidSource() {
                        const source = document.getElementById('mermaid-source-display').innerText;
                        navigator.clipboard.writeText(source).then(() => {
                            const btns = document.querySelectorAll('button[onclick="copyMermaidSource()"]');
                            btns.forEach(btn => {
                                const span = btn.querySelector('span');
                                const originalText = span ? span.innerText : '';
                                if (span) span.innerText = 'Copied!';
                                btn.classList.add('bg-green-50', 'text-green-700', 'border-green-200');
                                setTimeout(() => {
                                    if (span) span.innerText = originalText;
                                    btn.classList.remove('bg-green-50', 'text-green-700',
                                        'border-green-200');
                                }, 2000);
                            });
                        });
                    }

                    function switchAnalysisTab(tab) {
                        // Hide all content
                        document.querySelectorAll('.analysis-tab-content').forEach(el => el.classList.add('hidden'));
                        // Reset all tab styles
                        document.querySelectorAll('[id^="tab-"]').forEach(el => {
                            el.className =
                                'border-transparent text-gray-500 dark:text-dark-muted hover:text-gray-700 dark:hover:text-dark-text hover:border-gray-300 dark:hover:border-dark-border whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors';
                        });

                        // Show selected content
                        document.getElementById('content-' + tab).classList.remove('hidden');
                        // Set active tab style
                        const activeTab = document.getElementById('tab-' + tab);
                        if (activeTab) {
                            activeTab.className =
                                'border-green-600 dark:border-green-500 text-green-700 dark:text-green-400 whitespace-nowrap py-4 px-6 border-b-2 font-bold text-sm transition-all';
                        }

                        // Initialize or refresh diagram if diagram tab is selected
                        if (tab === 'diagram') {
                            if (!mermaidRendered) {
                                setTimeout(renderMermaid, 100);
                            } else {
                                setTimeout(refreshPanZoom, 100);
                            }
                        }
                    }

                    async function renderMermaid() {
                        const source = document.getElementById('mermaid-graph-source');
                        const output = document.getElementById('mermaid-output');

                        if (source && output) {
                            try {
                                let graphText = source.innerText.trim();

                                // 1. Strip Markdown code blocks
                                graphText = graphText.replace(/```mermaid/g, '').replace(/```/g, '').trim();

                                // 2. Fix the specific Master project syntax errors manually before the regex
                                // Fix: H{Navigate back home page with timestamp and 'Todos' link}] -> H["Navigate back..."]
                                // Fix: I{User logs out or closes application]} -> I["User logs out..."]
                                graphText = graphText.replace(/([A-Z])\{([^}]+)\}\]/g, '$1["$2"]');
                                graphText = graphText.replace(/([A-Z])\{([^\]]+)\]\}/g, '$1["$2"]');

                                // 3. Robust regex to quote all node labels and fix common punctuation
                                // Handles [label], (label), {label}, ((label)), [[label]], etc.
                                graphText = graphText.replace(/([A-Z0-9_-]+)?\s*(\[+|\{+|\(+)([\s\S]+?)(\]+|\}+|\)+)/g, function(
                                    match, id,
                                    start, label, end) {
                                    let cleanLabel = label.trim()
                                        .replace(/\.+$/g, '')
                                        .replace(/"/g, '#quot;')
                                        .replace(/[\[\](){}]/g, ''); // Remove nested brackets
                                    let nodeId = id || '';
                                    return nodeId + '["' + cleanLabel + '"]';
                                });

                                // 4. Final cleanup of trailing garbage on lines
                                graphText = graphText.split('\n')
                                    .map(line => line.trim().replace(/[;.\s]+$/g, ''))
                                    .filter(line => line.length > 0)
                                    .join('\n');

                                console.log('Final Mermaid Text:', graphText);

                                const {
                                    svg
                                } = await mermaid.render('mermaid-svg-rendered', graphText);

                                // Add modern styling to the SVG
                                const isDark = document.documentElement.classList.contains('dark');
                                const nodeColor = isDark ? '#1e293b' : '#ffffff';
                                const textColor = isDark ? '#f8fafc' : '#334155';
                                const strokeColor = isDark ? '#334155' : '#e2e8f0';
                                const edgeColor = isDark ? '#475569' : '#94a3b8';

                                let styledSvg = svg.replace('<style>', '<style>' +
                                    '.node rect, .node circle, .node polygon, .node path { ' +
                                    '   stroke-width: 2px !important; ' +
                                    '   fill: ' + nodeColor + ' !important; ' +
                                    '   stroke: ' + strokeColor + ' !important; ' +
                                    '   filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.05)); ' +
                                    '   rx: 8px; ry: 8px; ' +
                                    '} ' +
                                    '.edgePath path { stroke-width: 1.5px !important; stroke: ' + edgeColor + ' !important; } ' +
                                    '.edgeLabel { background-color: ' + (isDark ? 'rgba(15, 23, 42, 0.8)' : 'rgba(255, 255, 255, 0.8)') + ' !important; padding: 2px 4px !important; border-radius: 4px !important; color: ' + textColor + ' !important; } ' +
                                    '.label { font-weight: 600 !important; color: ' + textColor + ' !important; } ' +
                                    '.cluster rect { fill: ' + (isDark ? '#0f172a' : '#f8fafc') + ' !important; stroke: ' + strokeColor + ' !important; rx: 12px; ry: 12px; }'
                                );

                                output.innerHTML = styledSvg;
                                mermaidRendered = true;
                                setTimeout(initPanZoom, 100);
                            } catch (error) {
                                console.error('Mermaid render error:', error);
                                output.innerHTML = '<div class="text-red-500 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-100 dark:border-red-900/30">' +
                                    '<p class="font-bold">Failed to render flowchart.</p>' +
                                    '<p class="text-sm mt-1">The AI generated an invalid diagram format. Try clicking "Re-generate" to fix it.</p>' +
                                    '</div>';
                            }
                        }
                    }

                    function initPanZoom() {
                        const svg = document.querySelector('#mermaid-output svg');
                        if (svg) {
                            // Strip Mermaid's restrictive styles
                            svg.removeAttribute('width');
                            svg.removeAttribute('height');
                            svg.removeAttribute('style');
                            svg.style.width = '100%';
                            svg.style.height = '100%';
                            svg.style.display = 'block';
                            svg.style.maxWidth = 'none';

                            panZoomInstance = svgPanZoom(svg, {
                                zoomEnabled: true,
                                controlIconsEnabled: false,
                                fit: true,
                                center: true,
                                minZoom: 0.1,
                                maxZoom: 10,
                                refreshRate: 'auto'
                            });
                        }
                    }

                    function refreshPanZoom() {
                        if (panZoomInstance) {
                            panZoomInstance.resize();
                            panZoomInstance.fit();
                            panZoomInstance.center();
                        }
                    }

                    window.addEventListener('resize', refreshPanZoom);

                    function zoomIn() {
                        if (panZoomInstance) panZoomInstance.zoomIn();
                    }

                    function zoomOut() {
                        if (panZoomInstance) panZoomInstance.zoomOut();
                    }

                    function resetZoom() {
                        if (panZoomInstance) {
                            panZoomInstance.reset();
                            panZoomInstance.fit();
                            panZoomInstance.center();
                        }
                    }

                    function toggleFullscreen() {
                        const container = document.getElementById('diagram-container');
                        if (!document.fullscreenElement) {
                            container.requestFullscreen().catch(err => {
                                console.error(`Error attempting to enable full-screen mode: ${err.message}`);
                            });
                        } else {
                            document.exitFullscreen();
                        }
                    }

                    // Handle fullscreen change
                    document.addEventListener('fullscreenchange', () => {
                        const container = document.getElementById('diagram-container');
                        if (document.fullscreenElement) {
                            container.style.height = '100vh';
                            container.classList.add('bg-white', 'dark:bg-dark-bg');
                        } else {
                            container.style.height = '600px';
                            container.classList.remove('bg-white', 'dark:bg-dark-bg');
                        }
                        if (panZoomInstance) {
                            panZoomInstance.resize();
                            panZoomInstance.fit();
                            panZoomInstance.center();
                        }
                    });

                    // Handle Re-generate dropdown toggle
                    document.addEventListener('DOMContentLoaded', function() {
                        const regenBtn = document.getElementById('regen-menu-button');
                        const regenDropdown = document.getElementById('regen-dropdown');

                        if (regenBtn && regenDropdown) {
                            regenBtn.addEventListener('click', function(e) {
                                e.stopPropagation();
                                const isExpanded = regenBtn.getAttribute('aria-expanded') === 'true';
                                regenBtn.setAttribute('aria-expanded', !isExpanded);
                                regenDropdown.classList.toggle('hidden');
                            });

                            document.addEventListener('click', function(e) {
                                if (!regenBtn.contains(e.target) && !regenDropdown.contains(e.target)) {
                                    regenBtn.setAttribute('aria-expanded', 'false');
                                    regenDropdown.classList.add('hidden');
                                }
                            });
                        }
                    });
                </script>
            @endif
        @endif
    </div>
@endsection
