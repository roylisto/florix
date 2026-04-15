@extends('layouts.app')

@section('content')
    <div class="space-y-8 transition-colors duration-200">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-dark-text">Project Analyses</h1>
                <p class="text-sm text-gray-500 dark:text-dark-muted mt-1">Manage and view your repository business explanations.</p>
            </div>
            <button onclick="document.getElementById('upload-section').scrollIntoView({behavior: 'smooth'})"
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm">
                Analyze New Repo
            </button>
        </div>

        @if ($projects->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach ($projects as $project)
                    <div class="bg-white dark:bg-dark-card rounded-xl shadow-sm border border-gray-200 dark:border-dark-border hover:shadow-md transition-all">
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-4">
                                <div class="min-w-0">
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-dark-text truncate pr-4">{{ $project->name }}</h3>
                                    <p class="text-xs text-gray-400 dark:text-dark-muted mt-1 flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        {{ $project->created_at->diffForHumans() }}
                                    </p>
                                </div>
                                @php
                                    $status = $project->latestAnalysis?->status;
                                    $statusClasses = match ($status) {
                                        'completed' => 'bg-green-50 text-green-700 border-green-100 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800',
                                        'processing', 'generating_explanation' => 'bg-blue-50 text-blue-700 border-blue-100 animate-pulse dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-800',
                                        'failed' => 'bg-red-50 text-red-700 border-red-100 dark:bg-red-900/20 dark:text-red-400 dark:border-red-800',
                                        default => 'bg-gray-50 text-gray-600 border-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700',
                                    };
                                @endphp
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $statusClasses }}">
                                    {{ ucfirst(str_replace('_', ' ', $status ?? 'pending')) }}
                                </span>
                            </div>

                            <div class="flex items-center gap-3 mt-6">
                                <a href="{{ route('projects.show', $project) }}"
                                    class="flex-1 bg-gray-50 dark:bg-gray-800 hover:bg-green-50 dark:hover:bg-green-900/20 text-gray-700 dark:text-dark-muted hover:text-green-700 dark:hover:text-green-400 text-center py-2 px-4 rounded-lg text-sm font-medium border border-gray-200 dark:border-dark-border hover:border-green-200 dark:hover:border-green-800 transition-all">
                                    View Analysis
                                </a>
                                <form action="{{ route('projects.destroy', $project) }}" method="POST"
                                    onsubmit="return confirm('Are you sure you want to delete this project?')"
                                    class="shrink-0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg border border-gray-200 dark:border-dark-border hover:border-red-100 dark:hover:border-red-900 transition-all"
                                        title="Delete Project">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-white dark:bg-dark-card rounded-xl border-2 border-dashed border-gray-200 dark:border-dark-border p-12 text-center transition-colors">
                <div class="bg-gray-50 dark:bg-gray-800 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-dark-text">No projects yet</h3>
                <p class="text-gray-500 dark:text-dark-muted mt-1">Upload a repository below to get started.</p>
            </div>
        @endif

        <div id="upload-section" class="bg-white dark:bg-dark-card rounded-xl shadow-sm border border-gray-200 dark:border-dark-border p-8 mt-12 transition-colors">
            <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-dark-text">Analyze New Repository</h2>

            <form action="{{ route('projects.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-6">
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-2">Project Name</label>
                    <input type="text" name="name" id="name" required placeholder="e.g. My Awesome CRM"
                        class="w-full px-4 py-2 bg-white dark:bg-dark-bg border border-gray-300 dark:border-dark-border text-gray-900 dark:text-dark-text rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition">
                    @error('name')
                        <p class="mt-1 text-red-500 text-sm">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8 border-t border-gray-100 dark:border-dark-border pt-8">
                    <div>
                        <label for="zip_file" class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-2">Option 1: Upload ZIP</label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 dark:border-dark-border border-dashed rounded-xl hover:border-green-400 dark:hover:border-green-600 transition-colors group">
                            <div class="space-y-1 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-dark-muted group-hover:text-green-500 transition-colors"
                                    stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                    <path
                                        d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="flex text-sm text-gray-600 dark:text-dark-muted">
                                    <label for="zip_file"
                                        class="relative cursor-pointer bg-white dark:bg-dark-card rounded-md font-bold text-green-600 dark:text-green-500 hover:text-green-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-green-500">
                                        <span>Upload a file</span>
                                        <input id="zip_file" name="zip_file" type="file" class="sr-only"
                                            accept=".zip">
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-dark-muted">ZIP up to 50MB</p>
                            </div>
                        </div>
                        @error('zip_file')
                            <p class="mt-1 text-red-500 text-sm">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="repo_path" class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-2">Option 2: Local Absolute Path</label>
                        <input type="text" name="repo_path" id="repo_path" placeholder="/Users/me/projects/awesome-app"
                            class="w-full px-4 py-2 bg-white dark:bg-dark-bg border border-gray-300 dark:border-dark-border text-gray-900 dark:text-dark-text rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition h-[108px]">
                        <p class="mt-2 text-xs text-gray-500 dark:text-dark-muted italic leading-relaxed">The server will analyze files directly from this local path. Make sure the application has read access.</p>
                        @error('repo_path')
                            <p class="mt-1 text-red-500 text-sm">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex justify-end pt-6 border-t border-gray-100 dark:border-dark-border">
                    <button type="submit"
                        class="bg-green-600 hover:bg-green-700 text-white px-8 py-3 rounded-xl font-bold transition shadow-lg hover:shadow-xl flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                        Start Analysis
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
