@extends('layouts.app')

@section('content')
    <div class="space-y-8">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-dark-text">Project Dashboard</h1>
                <p class="text-gray-500 dark:text-dark-muted mt-1">Manage and analyze your software repositories.</p>
            </div>
            <button onclick="document.getElementById('upload-modal').classList.remove('hidden')"
                class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-xl font-bold transition-all shadow-lg hover:shadow-xl flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                New Project
            </button>
        </div>

        @if (session('success'))
            <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 p-4 rounded-r-xl">
                <div class="flex items-center">
                    <div class="flex-shrink-0 text-green-500">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800 dark:text-green-400">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @forelse($projects as $project)
                <div
                    class="bg-white dark:bg-dark-card rounded-2xl border border-gray-100 dark:border-dark-border shadow-sm hover:shadow-xl transition-all duration-300 group overflow-hidden">
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-4">
                            <div class="bg-green-100 dark:bg-green-900/30 p-3 rounded-xl text-green-600 dark:text-green-400 group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                </svg>
                            </div>
                            @php
                                $latestAnalysis = $project->analyses()->latest()->first();
                                $status = $latestAnalysis?->status ?? 'no_analysis';
                                $statusColor = match ($status) {
                                    'completed' => 'text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20',
                                    'processing', 'generating_explanation' => 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20',
                                    'failed' => 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20',
                                    default => 'text-gray-500 dark:text-dark-muted bg-gray-50 dark:bg-dark-bg',
                                };
                            @endphp
                            <span
                                class="text-[10px] font-bold uppercase tracking-widest px-2.5 py-1 rounded-full {{ $statusColor }}">
                                {{ str_replace('_', ' ', $status) }}
                            </span>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-dark-text mb-2 group-hover:text-green-600 dark:group-hover:text-green-500 transition-colors">
                            {{ $project->name }}</h3>
                        <p class="text-sm text-gray-500 dark:text-dark-muted line-clamp-2 mb-6 h-10">
                            {{ $latestAnalysis?->progress_message ?? 'Last analyzed ' . ($latestAnalysis?->updated_at?->diffForHumans() ?? 'never') }}
                        </p>
                        <div class="flex items-center justify-between pt-4 border-t border-gray-50 dark:border-dark-border">
                            <span class="text-xs text-gray-400 dark:text-dark-muted font-medium">
                                {{ $project->analyses_count }} Analyses
                            </span>
                            <a href="{{ route('projects.show', $project) }}"
                                class="text-sm font-bold text-green-600 dark:text-green-500 hover:text-green-700 dark:hover:text-green-400 flex items-center gap-1 group/link transition-colors">
                                View Details
                                <svg class="w-4 h-4 group-hover/link:translate-x-1 transition-transform" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-20 text-center bg-white dark:bg-dark-card rounded-3xl border-2 border-dashed border-gray-200 dark:border-dark-border transition-colors">
                    <div class="bg-gray-50 dark:bg-dark-bg w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6 transition-colors">
                        <svg class="w-10 h-10 text-gray-300 dark:text-dark-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-dark-text mb-2">No projects yet</h3>
                    <p class="text-gray-500 dark:text-dark-muted mb-8">Upload a repository to get your first business explanation.</p>
                    <button onclick="document.getElementById('upload-modal').classList.remove('hidden')"
                        class="bg-green-600 hover:bg-green-700 text-white px-8 py-3 rounded-xl font-bold transition-all shadow-lg hover:shadow-xl inline-flex items-center gap-2">
                        Create Your First Project
                    </button>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Upload Modal -->
    <div id="upload-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog"
        aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75 dark:bg-dark-bg/80 backdrop-blur-sm transition-opacity" aria-hidden="true"
                onclick="document.getElementById('upload-modal').classList.add('hidden')"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div
                class="inline-block align-middle bg-white dark:bg-dark-card rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100 dark:border-dark-border transition-colors">
                <form action="{{ route('projects.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="px-8 pt-8 pb-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-dark-text" id="modal-title">New Analysis</h3>
                            <button type="button" onclick="document.getElementById('upload-modal').classList.add('hidden')"
                                class="text-gray-400 dark:text-dark-muted hover:text-gray-500 dark:hover:text-dark-text transition-colors">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="space-y-6">
                            <div>
                                <label for="name" class="block text-sm font-bold text-gray-700 dark:text-dark-text mb-2">Project
                                    Name</label>
                                <input type="text" name="name" id="name" required
                                    class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-dark-border bg-white dark:bg-dark-bg text-gray-900 dark:text-dark-text focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all outline-none"
                                    placeholder="e.g. E-commerce Platform">
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-dark-text mb-2">Repository Files
                                    (.zip)</label>
                                <div id="drop-zone"
                                    class="mt-1 flex justify-center px-6 pt-10 pb-10 border-2 border-gray-200 dark:border-dark-border border-dashed rounded-2xl hover:border-green-400 dark:hover:border-green-500 transition-all bg-gray-50/50 dark:bg-dark-bg/50 group cursor-pointer relative">
                                    <div class="space-y-2 text-center">
                                        <div
                                            class="mx-auto h-16 w-16 text-gray-400 dark:text-dark-muted group-hover:text-green-500 dark:group-hover:text-green-400 transition-colors bg-white dark:bg-dark-card rounded-full flex items-center justify-center shadow-sm">
                                            <svg class="h-8 w-8" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                                <path
                                                    d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </div>
                                        <div class="flex text-sm text-gray-600 dark:text-dark-muted pt-2">
                                            <label for="zip_file"
                                                class="relative cursor-pointer rounded-md font-bold text-green-600 dark:text-green-500 hover:text-green-500 dark:hover:text-green-400 transition-colors">
                                                <span>Upload a file</span>
                                                <input id="zip_file" name="zip_file" type="file" class="sr-only"
                                                    accept=".zip" required onchange="handleFileSelect(this)">
                                            </label>
                                            <p class="pl-1">or drag and drop</p>
                                        </div>
                                        <p class="text-xs text-gray-400 dark:text-dark-muted">ZIP files up to 50MB</p>
                                        <p id="file-name-display" class="text-sm font-bold text-green-600 dark:text-green-400 mt-2 hidden"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-8 py-6 bg-gray-50 dark:bg-dark-bg/50 border-t border-gray-100 dark:border-dark-border flex flex-col sm:flex-row gap-3 transition-colors">
                        <button type="submit"
                            class="w-full sm:flex-1 bg-green-600 hover:bg-green-700 text-white font-bold py-3.5 px-6 rounded-xl transition-all shadow-lg hover:shadow-xl">
                            Start Analysis
                        </button>
                        <button type="button" onclick="document.getElementById('upload-modal').classList.add('hidden')"
                            class="w-full sm:w-auto px-6 py-3.5 text-sm font-bold text-gray-700 dark:text-dark-text hover:bg-gray-100 dark:hover:bg-gray-800 rounded-xl transition-all border border-gray-200 dark:border-dark-border">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function handleFileSelect(input) {
            const fileNameDisplay = document.getElementById('file-name-display');
            if (input.files && input.files[0]) {
                fileNameDisplay.innerText = "Selected: " + input.files[0].name;
                fileNameDisplay.classList.remove('hidden');
            } else {
                fileNameDisplay.classList.add('hidden');
            }
        }

        const dropZone = document.getElementById('drop-zone');
        const fileInput = document.getElementById('zip_file');

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, e => {
                e.preventDefault();
                e.stopPropagation();
            }, false);
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.add('border-green-400', 'bg-green-50/50', 'dark:bg-green-900/10');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.remove('border-green-400', 'bg-green-50/50', 'dark:bg-green-900/10');
            }, false);
        });

        dropZone.addEventListener('drop', e => {
            const dt = e.dataTransfer;
            const files = dt.files;
            fileInput.files = files;
            handleFileSelect(fileInput);
        }, false);
    </script>
@endsection
