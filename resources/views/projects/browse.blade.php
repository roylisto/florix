@extends('layouts.app')

@section('content')
    <div class="space-y-8">
        <div class="mb-8 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="flex-1 min-w-0">
                <h1 class="text-3xl font-bold text-gray-900">{{ $project->name }}</h1>
                <nav class="flex mt-2 text-sm text-gray-500 font-medium" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li class="inline-flex items-center">
                            <a href="{{ route('projects.browse', $project) }}" class="hover:text-green-600">Root</a>
                        </li>
                        @foreach ($breadcrumbs as $breadcrumb)
                            <li>
                                <div class="flex items-center">
                                    <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                            stroke-width="2" d="m1 9 4-4-4-4" />
                                    </svg>
                                    <a href="{{ route('projects.browse.path', [$project, 'path' => $breadcrumb['path']]) }}"
                                        class="ml-1 hover:text-green-600 md:ml-2">{{ $breadcrumb['name'] }}</a>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </nav>
            </div>
            <a href="{{ route('projects.show', $project) }}"
                class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg font-medium transition">
                &larr; Back to Analysis
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-200">
            <div class="p-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                <span class="text-sm font-semibold text-gray-700">Files & Directories</span>
                <span class="text-xs text-gray-500">{{ count($directories) + count($files) }} items</span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @if ($path)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <a href="{{ dirname($path) === '.' ? route('projects.browse', $project) : route('projects.browse.path', [$project, 'path' => dirname($path)]) }}"
                                        class="flex items-center text-blue-600 hover:text-blue-800">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                        </svg>
                                        ..
                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">-</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">-</td>
                            </tr>
                        @endif

                        @foreach ($directories as $dir)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <a href="{{ route('projects.browse.path', [$project, 'path' => $dir['path']]) }}"
                                        class="flex items-center text-gray-700 hover:text-green-600">
                                        <svg class="w-5 h-5 mr-2 text-yellow-400 fill-current" viewBox="0 0 20 20">
                                            <path
                                                d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
                                        </svg>
                                        {{ $dir['name'] }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">-</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('projects.browse.path', [$project, 'path' => $dir['path']]) }}"
                                        class="text-green-600 hover:text-green-900">Open</a>
                                </td>
                            </tr>
                        @endforeach

                        @foreach ($files as $file)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <a href="{{ route('projects.view', [$project, 'path' => $file['path']]) }}"
                                        class="flex items-center text-gray-700 hover:text-green-600">
                                        <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                        </svg>
                                        {{ $file['name'] }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $file['size'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('projects.view', [$project, 'path' => $file['path']]) }}"
                                        class="text-green-600 hover:text-green-900">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
