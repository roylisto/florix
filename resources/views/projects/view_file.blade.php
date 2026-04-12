@extends('layouts.app')

@section('content')
<link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css" rel="stylesheet" />
<style>
    pre[class*="language-"] {
        margin: 0;
        border-radius: 0;
        max-height: 70vh;
    }
    .line-numbers .line-numbers-rows {
        border-right: 1px solid #444;
    }
</style>

<div class="max-w-6xl mx-auto">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">{{ $project->name }}</h1>
            <nav class="flex mt-2 text-sm text-gray-500 font-medium" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="{{ route('projects.browse', $project) }}" class="hover:text-green-600">Root</a>
                    </li>
                    @foreach($breadcrumbs as $index => $breadcrumb)
                        <li>
                            <div class="flex items-center">
                                <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                                </svg>
                                @if($index === count($breadcrumbs) - 1)
                                    <span class="ml-1 text-gray-700 md:ml-2">{{ $breadcrumb['name'] }}</span>
                                @else
                                    <a href="{{ route('projects.browse.path', [$project, 'path' => $breadcrumb['path']]) }}" class="ml-1 hover:text-green-600 md:ml-2">{{ $breadcrumb['name'] }}</a>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </nav>
        </div>
        <a href="{{ dirname($path) === '.' ? route('projects.browse', $project) : route('projects.browse.path', [$project, 'path' => dirname($path)]) }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg font-medium transition">
            &larr; Back to Folder
        </a>
    </div>

    <div class="bg-gray-900 rounded-xl shadow-xl overflow-hidden border border-gray-800">
        <div class="p-4 bg-gray-800 border-b border-gray-700 flex items-center justify-between">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                <span class="text-sm font-mono text-gray-300">{{ basename($path) }}</span>
            </div>
            <div class="flex space-x-2">
                <div class="w-3 h-3 rounded-full bg-red-500"></div>
                <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                <div class="w-3 h-3 rounded-full bg-green-500"></div>
            </div>
        </div>
        
        <div class="relative">
            <pre class="line-numbers language-{{ $extension }}"><code class="language-{{ $extension }}">{{ $content }}</code></pre>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/line-numbers/prism-line-numbers.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-php.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-typescript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-css.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-json.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-markdown.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-python.min.js"></script>

<link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/line-numbers/prism-line-numbers.min.css" rel="stylesheet" />
@endsection
