@extends('layouts.app')

@section('content')
    <div class="space-y-8">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Project Analyses</h1>
                <p class="text-sm text-gray-500 mt-1">Manage and view your repository business explanations.</p>
            </div>
            <button onclick="document.getElementById('upload-section').scrollIntoView({behavior: 'smooth'})"
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm">
                Analyze New Repo
            </button>
        </div>

        @if ($projects->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach ($projects as $project)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-4">
                                <div class="min-w-0">
                                    <h3 class="text-lg font-bold text-gray-900 truncate pr-4">{{ $project->name }}</h3>
                                    <p class="text-xs text-gray-400 mt-1 flex items-center">
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
                                        'completed' => 'bg-green-50 text-green-700 border-green-100',
                                        'processing', 'generating_explanation' => 'bg-blue-50 text-blue-700 border-blue-100 animate-pulse',
                                        'failed' => 'bg-red-50 text-red-700 border-red-100',
                                        default => 'bg-gray-50 text-gray-600 border-gray-100',
                                    };
                                @endphp
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $statusClasses }}">
                                    {{ ucfirst(str_replace('_', ' ', $status ?? 'pending')) }}
                                </span>
                            </div>

                            <div class="flex items-center gap-3 mt-6">
                                <a href="{{ route('projects.show', $project) }}"
                                    class="flex-1 bg-gray-50 hover:bg-green-50 text-gray-700 hover:text-green-700 text-center py-2 px-4 rounded-lg text-sm font-medium border border-gray-200 hover:border-green-200 transition-all">
                                    View Analysis
                                </a>
                                <form action="{{ route('projects.destroy', $project) }}" method="POST"
                                    onsubmit="return confirm('Are you sure you want to delete this project?')"
                                    class="shrink-0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg border border-gray-200 hover:border-red-100 transition-all"
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
            <div class="bg-white rounded-xl border-2 border-dashed border-gray-200 p-12 text-center">
                <div class="bg-gray-50 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900">No projects yet</h3>
                <p class="text-gray-500 mt-1">Upload a repository below to get started.</p>
            </div>
        @endif

        <div id="upload-section" class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 mt-12">
            <h2 class="text-xl font-bold mb-6 text-gray-900">Analyze New Repository</h2>

            <form action="{{ route('projects.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-6">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Project Name</label>
                    <input type="text" name="name" id="name" required placeholder="e.g. My Awesome CRM"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition">
                    @error('name')
                        <p class="mt-1 text-red-500 text-sm">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8 border-t border-gray-100 pt-8">
                    <div>
                        <label for="zip_file" class="block text-sm font-medium text-gray-700 mb-2">Option 1: Upload ZIP
                            file</label>
                        <div
                            class="relative border-2 border-dashed border-gray-300 rounded-xl px-6 py-10 text-center hover:border-green-400 transition-colors group">
                            <input type="file" name="zip_file" id="zip_file" accept=".zip"
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                            <div class="space-y-1 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400 group-hover:text-green-500 transition-colors"
                                    stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                    <path
                                        d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="flex text-sm text-gray-600 justify-center">
                                    <span class="relative rounded-md font-medium text-green-600">Upload a file</span>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-500">ZIP up to 50MB</p>
                            </div>
                        </div>
                        @error('zip_file')
                            <p class="mt-1 text-red-500 text-sm">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col">
                        <label for="local_path" class="block text-sm font-medium text-gray-700 mb-2">Option 2: Local
                            Path</label>
                        <div class="flex-1 flex flex-col justify-center bg-gray-50 rounded-xl p-6 border border-gray-200">
                            <input type="text" name="local_path" id="local_path"
                                placeholder="e.g. /Users/roy/Projects/my-app"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition bg-white">
                            <p class="mt-2 text-xs text-gray-400 italic">Useful for analyzing local directories without
                                zipping.</p>
                        </div>
                        @error('local_path')
                            <p class="mt-1 text-red-500 text-sm">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <button type="submit"
                    class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-xl shadow-lg hover:shadow-xl transition duration-200 transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    Start Business Analysis
                </button>
            </form>
        </div>
    </div>
@endsection
