@extends('layouts.app')

@section('content')
    <div class="space-y-8">
        <div class="mb-8 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-3 mb-2">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-dark-text break-words">Browse Files</h1>
                </div>
                <div class="flex items-center text-sm text-gray-500 dark:text-dark-muted">
                    <a href="{{ route('projects.index') }}" class="hover:text-green-600 dark:hover:text-green-500 transition-colors">Projects</a>
                    <span class="mx-2">/</span>
                    <a href="{{ route('projects.show', $project) }}" class="hover:text-green-600 dark:hover:text-green-500 transition-colors">{{ $project->name }}</a>
                    <span class="mx-2">/</span>
                    <span class="text-gray-900 dark:text-dark-text font-medium">Browse Code</span>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-dark-card rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-dark-border transition-colors duration-200">
            <div class="p-6 border-b border-gray-100 dark:border-dark-border bg-gray-50/50 dark:bg-dark-bg/50 transition-colors">
                <h2 class="text-lg font-bold text-gray-900 dark:text-dark-text flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-400 dark:text-dark-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                    </svg>
                    Project Structure
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-dark-bg/50 transition-colors">
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 dark:text-dark-muted uppercase tracking-wider">Name</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 dark:text-dark-muted uppercase tracking-wider">Type</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 dark:text-dark-muted uppercase tracking-wider">Size</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 dark:text-dark-muted uppercase tracking-wider text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-dark-border transition-colors">
                        @foreach ($files as $file)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        @if ($file['type'] === 'directory')
                                            <svg class="w-5 h-5 text-yellow-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
                                            </svg>
                                        @else
                                            <svg class="w-5 h-5 text-gray-400 dark:text-dark-muted mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                            </svg>
                                        @endif
                                        <span class="text-sm font-medium text-gray-700 dark:text-dark-text">{{ $file['name'] }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-dark-muted uppercase tracking-widest text-[10px] font-bold">
                                    {{ $file['type'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-dark-muted">
                                    {{ $file['size'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    @if ($file['type'] === 'file')
                                        <a href="{{ route('projects.view_file', [$project, 'path' => $file['path']]) }}"
                                            class="inline-flex items-center px-4 py-2 bg-white dark:bg-dark-card border border-gray-200 dark:border-dark-border rounded-xl text-xs font-bold text-gray-700 dark:text-dark-text hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-green-600 dark:hover:text-green-500 transition-all shadow-sm hover:shadow-md">
                                            View Source
                                        </a>
                                    @else
                                        <a href="{{ route('projects.browse', [$project, 'path' => $file['path']]) }}"
                                            class="inline-flex items-center px-4 py-2 bg-white dark:bg-dark-card border border-gray-200 dark:border-dark-border rounded-xl text-xs font-bold text-gray-700 dark:text-dark-text hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-blue-600 dark:hover:text-blue-500 transition-all shadow-sm hover:shadow-md">
                                            Open Folder
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if(count($files) === 0)
                <div class="p-12 text-center">
                    <p class="text-gray-500 dark:text-dark-muted">This directory is empty.</p>
                </div>
            @endif
        </div>
    </div>
@endsection
