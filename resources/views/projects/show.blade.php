@extends('layouts.app')

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="mb-8 flex items-center justify-between">
            <h1 class="text-3xl font-bold text-gray-900">{{ $project->name }}</h1>
            <div class="flex items-center space-x-4">
                <span id="status-badge"
                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                @if ($analysis?->status === 'completed') bg-green-100 text-green-800
                @elseif($analysis?->status === 'processing' || $analysis?->status === 'generating_explanation') bg-blue-100 text-blue-800
                @elseif($analysis?->status === 'failed') bg-red-100 text-red-800
                @else bg-gray-100 text-gray-800 @endif">
                    {{ ucfirst(str_replace('_', ' ', $analysis?->status ?? 'pending')) }}
                </span>
                <a href="{{ route('projects.index') }}" class="text-sm text-gray-500 hover:text-green-600 font-medium">
                    &larr; Back to Dashboard
                </a>
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
                <p class="text-gray-600 mb-6 text-center">Something went wrong during the analysis. Review the error details
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
                <div class="space-y-8">
                    @php
                        $output = $analysis->llm_output;

                        // Extract features
                        preg_match('/FEATURES\s*(.*?)(?=\s*WHAT USER SEES|$)/s', $output, $featuresMatch);
                        $features = isset($featuresMatch[1]) ? trim($featuresMatch[1]) : '';

                        // Extract what user sees
                        preg_match('/WHAT USER SEES\s*(.*?)(?=\s*USER FLOW|$)/s', $output, $uiMatch);
                        $ui = isset($uiMatch[1]) ? trim($uiMatch[1]) : '';

                        // Extract user flow
                        preg_match('/USER FLOW\s*(.*?)(?=\s*MERMAID DIAGRAM|$)/s', $output, $flowMatch);
                        $flow = isset($flowMatch[1]) ? trim($flowMatch[1]) : '';

                        // Extract mermaid diagram
                        preg_match('/MERMAID DIAGRAM\s*(graph TD.*)/s', $output, $mermaidMatch);
                        $mermaid = isset($mermaidMatch[1]) ? trim($mermaidMatch[1]) : '';
                    @endphp

                    <!-- Features -->
                    <div class="bg-white rounded-xl shadow-md p-8">
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

                    <!-- What User Sees -->
                    <div class="bg-white rounded-xl shadow-md p-8">
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

                    <!-- User Flow -->
                    <div class="bg-white rounded-xl shadow-md p-8">
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

                    <!-- Mermaid Diagram -->
                    @if ($mermaid)
                        <div class="bg-white rounded-xl shadow-md p-8">
                            <h2 class="text-xl font-bold mb-6 text-orange-700 flex items-center">
                                <svg class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Process Flowchart
                            </h2>
                            <div class="mermaid bg-gray-50 rounded-lg p-4 flex justify-center">
                                {{ $mermaid }}
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        @endif
    </div>
@endsection
