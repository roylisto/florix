@extends('layouts.app')

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="mb-8 flex items-center justify-between">
            <h1 class="text-3xl font-bold text-gray-900">{{ $project->name }}</h1>
            <div class="flex items-center space-x-4">
                <span
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
            <div class="bg-white rounded-xl shadow-md p-12 text-center">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-green-600 mb-4">
                </div>
                <h2 class="text-xl font-bold text-gray-800 mb-2">
                    @if ($analysis?->status === 'generating_explanation')
                        Generating AI Explanation...
                    @else
                        Analyzing Repository...
                    @endif
                </h2>
                <p class="text-gray-500">
                    @if ($analysis?->status === 'generating_explanation')
                        The AI is now processing the parsed data to generate a business-friendly explanation. This step can
                        take a few minutes depending on the repository size.
                    @else
                        We are parsing your code and preparing it for AI analysis. This may take a minute.
                    @endif
                </p>
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
                    setTimeout(function() {
                        window.location.reload();
                    }, 5000);
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
                        <pre class="whitespace-pre-wrap bg-gray-900 text-gray-100 p-4 rounded-lg text-xs overflow-x-auto max-h-96">{{ $logTail }}</pre>
                    </div>
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
    </div>
@endsection
