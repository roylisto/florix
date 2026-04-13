@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-md p-8">
        <h1 class="text-2xl font-bold mb-6 text-gray-800">Analyze New Repository</h1>
        
        <form action="{{ route('projects.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <div class="mb-6">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Project Name</label>
                <input type="text" name="name" id="name" required placeholder="e.g. My Awesome CRM"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition">
                @error('name') <p class="mt-1 text-red-500 text-sm">{{ $message }}</p> @enderror
            </div>

            <div class="mb-8 border-t pt-6">
                <div class="mb-6">
                    <label for="zip_file" class="block text-sm font-medium text-gray-700 mb-2">Option 1: Upload ZIP file</label>
                    <input type="file" name="zip_file" id="zip_file" accept=".zip"
                        class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100 cursor-pointer">
                    @error('zip_file') <p class="mt-1 text-red-500 text-sm">{{ $message }}</p> @enderror
                </div>

                <div class="relative flex items-center justify-center mb-6">
                    <div class="flex-grow border-t border-gray-200"></div>
                    <span class="flex-shrink mx-4 text-gray-400 text-sm font-medium uppercase tracking-wider">OR</span>
                    <div class="flex-grow border-t border-gray-200"></div>
                </div>

                <div class="mb-6">
                    <label for="local_path" class="block text-sm font-medium text-gray-700 mb-2">Option 2: Local path to repository</label>
                    <input type="text" name="local_path" id="local_path" placeholder="/Users/roy/Projects/my-app"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition">
                    @error('local_path') <p class="mt-1 text-red-500 text-sm">{{ $message }}</p> @enderror
                </div>
            </div>

            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg shadow-lg hover:shadow-xl transition duration-200 transform hover:-translate-y-0.5">
                Analyze Repository
            </button>
        </form>
    </div>

    @if($projects->count() > 0)
    <div class="mt-12">
        <h2 class="text-xl font-bold mb-6 text-gray-800">Previous Analyses</h2>
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <ul class="divide-y divide-gray-200">
                @foreach($projects as $project)
                <li class="hover:bg-gray-50 transition">
                    <div class="px-6 py-4">
                        <div class="flex items-center justify-between">
                            <a href="{{ route('projects.show', $project) }}" class="flex-grow">
                                <h3 class="font-bold text-gray-900">{{ $project->name }}</h3>
                                <p class="text-sm text-gray-500">Created {{ $project->created_at->diffForHumans() }}</p>
                            </a>
                            <div class="flex items-center space-x-4">
                                @if ($project->latestAnalysis?->status === 'completed')
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Completed
                                    </span>
                                @elseif($project->latestAnalysis?->status === 'processing')
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 animate-pulse">
                                        Processing...
                                    </span>
                                @elseif($project->latestAnalysis?->status === 'failed')
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Failed
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        Pending
                                    </span>
                                @endif

                                <div class="flex items-center space-x-2">
                                    <form action="{{ route('projects.destroy', $project) }}" method="POST"
                                        onsubmit="return confirm('Are you sure you want to delete this project and all its analysis data?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1 text-gray-400 hover:text-red-600 transition"
                                            title="Delete Project">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                    <a href="{{ route('projects.show', $project) }}" class="text-gray-400 hover:text-green-600 transition">
                                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                            fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif
</div>
@endsection
