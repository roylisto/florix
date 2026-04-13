@extends('layouts.app')

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="mb-8 flex flex-col lg:flex-row lg:items-start justify-between gap-6">
            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-3 mb-3">
                    <h1 class="text-3xl font-bold text-gray-900 break-words">{{ $project->name }}</h1>
                    @php
                        $statusClasses = match ($analysis?->status) {
                            'completed' => 'bg-green-100 text-green-800',
                            'processing', 'generating_explanation' => 'bg-blue-100 text-blue-800',
                            'failed' => 'bg-red-100 text-red-800',
                            default => 'bg-gray-100 text-gray-800',
                        };
                    @endphp
                    <span id="status-badge"
                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium shrink-0 {{ $statusClasses }}">
                        {{ ucfirst(str_replace('_', ' ', $analysis?->status ?? 'pending')) }}
                    </span>
                </div>
                <a href="{{ route('projects.index') }}"
                    class="text-sm text-gray-500 hover:text-green-600 font-medium inline-flex items-center transition py-1 px-2 -ml-2 rounded-lg hover:bg-gray-100">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Dashboard
                </a>
            </div>

            <div class="flex flex-wrap items-center gap-3 shrink-0 lg:mt-1">
                @if ($analysis?->extracted_path)
                    <a href="{{ route('projects.browse', $project) }}"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition flex items-center shadow-sm whitespace-nowrap">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                        </svg>
                        Browse Source Code
                    </a>
                @endif

                @if ($analysis?->status === 'completed')
                    <form action="{{ route('projects.regenerate', $project) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition flex items-center shadow-sm whitespace-nowrap">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Re-generate
                        </button>
                    </form>
                @endif

                <form action="{{ route('projects.destroy', $project) }}" method="POST" class="inline"
                    onsubmit="return confirm('Are you sure you want to delete this project and all its analysis data?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="text-red-600 hover:text-red-800 p-2 rounded-lg border border-red-200 hover:border-red-400 transition bg-white shadow-sm"
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
            <div id="processing-view" class="bg-white rounded-xl shadow-md p-12 text-center">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-green-600 mb-4">
                </div>
                <h2 id="processing-title" class="text-xl font-bold text-gray-800 mb-2">
                    @if ($analysis?->status === 'generating_explanation')
                        Generating AI Explanation...
                    @else
                        Analyzing Repository...
                    @endif
                </h2>
                <p id="processing-description" class="text-gray-500">
                    @if ($analysis?->status === 'generating_explanation')
                        The AI is now processing the parsed data to generate a business-friendly explanation. This step can
                        take a few minutes depending on the repository size.
                    @else
                        We are parsing your code and preparing it for AI analysis. This may take a minute.
                    @endif
                </p>

                <div id="progress-container"
                    class="mt-4 {{ $analysis?->progress_message ? '' : 'hidden' }} flex items-center justify-center space-x-2">
                    <div class="flex space-x-1">
                        <div class="h-1.5 w-1.5 bg-green-600 rounded-full animate-bounce" style="animation-delay: 0s">
                        </div>
                        <div class="h-1.5 w-1.5 bg-green-600 rounded-full animate-bounce" style="animation-delay: 0.2s">
                        </div>
                        <div class="h-1.5 w-1.5 bg-green-600 rounded-full animate-bounce" style="animation-delay: 0.4s">
                        </div>
                    </div>
                    <span id="progress-message"
                        class="text-sm font-medium text-green-700 italic">{{ $analysis?->progress_message }}</span>
                </div>

                <!-- Debugging Tabs -->
                <div class="mt-8 text-left">
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                            <button onclick="switchTab('logs')" id="tab-logs"
                                class="border-green-500 text-green-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                Detailed Logs
                            </button>
                            <button onclick="switchTab('prompt')" id="tab-prompt"
                                class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                AI Prompt
                            </button>
                        </nav>
                    </div>

                    <div id="content-logs" class="mt-4">
                        <pre id="realtime-logs"
                            class="bg-gray-900 text-gray-300 p-4 rounded-lg text-xs font-mono overflow-auto max-h-64 border border-gray-800">{{ $analysis?->logs ?? 'Waiting for logs...' }}</pre>
                    </div>

                    <div id="content-prompt" class="mt-4 hidden">
                        <pre id="realtime-prompt"
                            class="bg-gray-900 text-blue-300 p-4 rounded-lg text-xs font-mono overflow-auto max-h-64 border border-gray-800">{{ $analysis?->prompt ?? 'Prompt will appear here...' }}</pre>
                    </div>
                </div>

                <div class="mt-8">
                    <form action="{{ route('projects.cancel', $project) }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="text-red-600 hover:text-red-800 text-sm font-medium border border-red-200 hover:border-red-400 px-4 py-2 rounded-lg transition"
                            onclick="return confirm('Are you sure you want to cancel the current analysis?')">
                            Cancel Processing
                        </button>
                    </form>
                </div>

                <script>
                    function switchTab(tab) {
                        const logsBtn = document.getElementById('tab-logs');
                        const promptBtn = document.getElementById('tab-prompt');
                        const logsContent = document.getElementById('content-logs');
                        const promptContent = document.getElementById('content-prompt');

                        if (tab === 'logs') {
                            logsBtn.className =
                                'border-green-500 text-green-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm';
                            promptBtn.className =
                                'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm';
                            logsContent.classList.remove('hidden');
                            promptContent.classList.add('hidden');
                        } else {
                            promptBtn.className =
                                'border-green-500 text-green-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm';
                            logsBtn.className =
                                'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm';
                            promptContent.classList.remove('hidden');
                            logsContent.classList.add('hidden');
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
                                badge.className = 'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ';
                                if (data.status === 'completed') badge.classList.add('bg-green-100', 'text-green-800');
                                else if (data.status === 'processing' || data.status === 'generating_explanation') badge.classList
                                    .add('bg-blue-100', 'text-blue-800');
                                else if (data.status === 'failed') badge.classList.add('bg-red-100', 'text-red-800');
                                else badge.classList.add('bg-gray-100', 'text-gray-800');

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
            <div class="bg-white rounded-xl shadow-md p-8">
                <div class="bg-red-100 text-red-600 p-4 rounded-full inline-block mb-4 mx-auto">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-gray-800 mb-4 text-center">Analysis Failed</h2>
                <p class="text-gray-600 mb-6 text-center">Something went wrong during the analysis. Review the error
                    details
                    below.</p>
                @if (!empty($analysis?->error))
                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-gray-700 mb-2">Error Message</h3>
                        <pre class="whitespace-pre-wrap bg-red-50 text-red-800 p-4 rounded-lg border border-red-200 text-sm">{{ $analysis->error }}</pre>
                    </div>
                @endif
                @if (!empty($logTail))
                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-gray-700 mb-2">Recent Logs (tail)</h3>
                        <pre id="log-container"
                            class="whitespace-pre-wrap bg-gray-900 text-gray-100 p-4 rounded-lg text-xs overflow-auto max-h-96">{{ $logTail }}</pre>
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
                <div class="flex items-center justify-between">
                    <a href="{{ route('projects.index') }}"
                        class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg transition">
                        Back to Dashboard
                    </a>
                    @if ($project?->repo_path || $analysis?->zip_path)
                        <form action="{{ route('projects.retry', $project) }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg transition">
                                Retry Analysis
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @elseif($analysis?->status === 'completed')
            @if (str_starts_with($analysis->llm_output, 'NO_DATA_FOUND:'))
                <div class="bg-white rounded-xl shadow-md p-12 text-center">
                    <div class="bg-yellow-100 text-yellow-600 p-4 rounded-full inline-block mb-4 mx-auto">
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-gray-800 mb-4">No Code Found</h2>
                    <p class="text-gray-600 mb-6">{{ str_replace('NO_DATA_FOUND: ', '', $analysis->llm_output) }}</p>
                    <a href="{{ route('projects.index') }}"
                        class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg transition">
                        Try Again
                    </a>
                </div>
            @else
                @php
                    $output = $analysis->llm_output;

                    // Extract features
                    preg_match('/FEATURES[:\s]*(.*?)(?=\s*WHAT USER SEES|$)/si', $output, $featuresMatch);
                    $features = isset($featuresMatch[1]) ? trim($featuresMatch[1]) : '';

                    // Extract what user sees
                    preg_match('/WHAT USER SEES[:\s]*(.*?)(?=\s*USER FLOW|$)/si', $output, $uiMatch);
                    $ui = isset($uiMatch[1]) ? trim($uiMatch[1]) : '';

                    // Extract user flow
                    preg_match('/USER FLOW[:\s]*(.*?)(?=\s*MERMAID DIAGRAM|$)/si', $output, $flowMatch);
                    $flow = isset($flowMatch[1]) ? trim($flowMatch[1]) : '';

                    // Extract mermaid diagram - more robust matching for larger projects
                    preg_match(
                        '/MERMAID DIAGRAM[:\s]*.*?((?:graph|flowchart)\s+(TD|LR|TB|BT).*)/si',
                        $output,
                        $mermaidMatch,
                    );
                    $mermaid = isset($mermaidMatch[1]) ? trim($mermaidMatch[1]) : '';

                    // Clean up potential markdown code blocks that AI might still include
                    if ($mermaid) {
                        $mermaid = preg_replace('/```(mermaid|plaintext)?\s*/i', '', $mermaid);
                        $mermaid = preg_replace('/\s*```$/i', '', $mermaid);
                        $mermaid = trim($mermaid);
                    }
                @endphp

                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="border-b border-gray-200">
                        <nav class="flex -mb-px overflow-x-auto" aria-label="Tabs">
                            <button onclick="switchAnalysisTab('features')" id="tab-features"
                                class="border-green-500 text-green-600 whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors">
                                Core Features
                            </button>
                            <button onclick="switchAnalysisTab('ui')" id="tab-ui"
                                class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors">
                                What Your Users Will See
                            </button>
                            <button onclick="switchAnalysisTab('flow')" id="tab-flow"
                                class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors">
                                The User Journey
                            </button>
                            @if ($mermaid)
                                <button onclick="switchAnalysisTab('diagram')" id="tab-diagram"
                                    class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors">
                                    Process Flowchart
                                </button>
                            @endif
                            <button onclick="switchAnalysisTab('raw')" id="tab-raw"
                                class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors">
                                Raw Data
                            </button>
                        </nav>
                    </div>

                    <div class="p-8">
                        <!-- Features Content -->
                        <div id="content-features" class="analysis-tab-content">
                            <h2 class="text-xl font-bold mb-6 text-green-700 flex items-center">
                                <svg class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Core Features
                            </h2>
                            <div class="prose max-w-none text-gray-700">
                                {!! nl2br(e($features)) !!}
                            </div>
                        </div>

                        <!-- What User Sees Content -->
                        <div id="content-ui" class="analysis-tab-content hidden">
                            <h2 class="text-xl font-bold mb-6 text-blue-700 flex items-center">
                                <svg class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                What Your Users Will See
                            </h2>
                            <div class="prose max-w-none text-gray-700">
                                {!! nl2br(e($ui)) !!}
                            </div>
                        </div>

                        <!-- User Flow Content -->
                        <div id="content-flow" class="analysis-tab-content hidden">
                            <h2 class="text-xl font-bold mb-6 text-purple-700 flex items-center">
                                <svg class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                                The User Journey
                            </h2>
                            <div class="prose max-w-none text-gray-700">
                                {!! nl2br(e($flow)) !!}
                            </div>
                        </div>

                        <!-- Mermaid Diagram Content -->
                        @if ($mermaid)
                            <div id="content-diagram" class="analysis-tab-content hidden">
                                <div class="flex items-center justify-between mb-6">
                                    <h2 class="text-xl font-bold text-orange-700 flex items-center">
                                        <svg class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        Process Flowchart
                                    </h2>
                                    <div class="flex space-x-2">
                                        <button onclick="zoomIn()"
                                            class="p-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition"
                                            title="Zoom In">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                                            </svg>
                                        </button>
                                        <button onclick="zoomOut()"
                                            class="p-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition"
                                            title="Zoom Out">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7" />
                                            </svg>
                                        </button>
                                        <button onclick="resetZoom()"
                                            class="p-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition"
                                            title="Reset Zoom">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </button>
                                        <button onclick="toggleFullscreen()"
                                            class="p-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition"
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
                                    class="bg-gray-50 rounded-lg overflow-hidden border border-gray-100 relative"
                                    style="height: 500px;">
                                    <div id="mermaid-wrapper" class="w-full h-full p-4 flex items-center justify-center">
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
                            <div class="space-y-6">
                                <div>
                                    <h3 class="text-lg font-bold mb-3 text-gray-800 flex items-center">
                                        <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        Full AI Output
                                    </h3>
                                    <div
                                        class="bg-gray-50 rounded-lg p-6 border border-gray-200 overflow-auto max-h-[500px]">
                                        <pre class="text-sm text-gray-700 whitespace-pre-wrap font-mono">{{ $analysis->llm_output }}</pre>
                                    </div>
                                </div>

                                @if ($mermaid)
                                    <div>
                                        <h3 class="text-lg font-bold mb-3 text-gray-800 flex items-center">
                                            <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                            </svg>
                                            Extracted Mermaid Source
                                        </h3>
                                        <div class="bg-gray-900 rounded-lg p-6 border border-gray-700">
                                            <pre class="text-sm text-green-400 whitespace-pre-wrap font-mono">{{ $mermaid }}</pre>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    let panZoomInstance = null;
                    let mermaidRendered = false;

                    function switchAnalysisTab(tab) {
                        // Hide all content
                        document.querySelectorAll('.analysis-tab-content').forEach(el => el.classList.add('hidden'));
                        // Reset all tab styles
                        document.querySelectorAll('[id^="tab-"]').forEach(el => {
                            if (el.id.startsWith('tab-logs') || el.id.startsWith('tab-prompt')) return; // Ignore debug tabs
                            el.className =
                                'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors';
                        });

                        // Show selected content
                        document.getElementById('content-' + tab).classList.remove('hidden');
                        // Set active tab style
                        document.getElementById('tab-' + tab).className =
                            'border-green-500 text-green-600 whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors';

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
                                graphText = graphText.replace(/([A-Z0-9_-]+)?(\[+|\{+|\(+)(.+?)(\]+|\}+|\)+)/g, function(match, id,
                                    start, label, end) {
                                    let cleanLabel = label.trim().replace(/\.+$/g, '').replace(/"/g, '#quot;');
                                    let nodeId = id || '';
                                    // Use standard [ ] for the cleaned output to ensure compatibility
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
                                output.innerHTML = svg;
                                mermaidRendered = true;
                                setTimeout(initPanZoom, 100);
                            } catch (error) {
                                console.error('Mermaid render error:', error);
                                output.innerHTML = '<div class="text-red-500 p-4 bg-red-50 rounded-lg border border-red-100">' +
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
                            container.classList.add('bg-white');
                        } else {
                            container.style.height = '500px';
                            container.classList.remove('bg-white');
                        }
                        if (panZoomInstance) {
                            panZoomInstance.resize();
                            panZoomInstance.fit();
                            panZoomInstance.center();
                        }
                    });
                </script>
            @endif
        @endif
    </div>
@endsection
