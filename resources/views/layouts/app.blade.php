<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Florix - Code to Business Explanation</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🌿</text></svg>">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/mermaid/dist/mermaid.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/svg-pan-zoom@3.6.1/dist/svg-pan-zoom.min.js"></script>
    <script>
        mermaid.initialize({
            startOnLoad: false,
            securityLevel: 'loose',
            theme: 'base',
            themeVariables: {
                primaryColor: '#ffffff',
                primaryTextColor: '#1f2937',
                primaryBorderColor: '#3b82f6',
                lineColor: '#64748b',
                secondaryColor: '#f8fafc',
                tertiaryColor: '#ffffff',
                clusterBkg: '#f1f5f9',
                clusterBorder: '#cbd5e1',
                fontSize: '14px',
                fontFamily: 'ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif'
            },
            flowchart: {
                htmlLabels: true,
                curve: 'basis',
                padding: 20
            }
        });
    </script>
</head>

<body class="bg-gray-50 min-h-screen flex flex-col">
    <!-- Top Navbar -->
    <nav class="bg-white border-b border-gray-200 fixed w-full z-30 top-0 h-16 flex items-center shrink-0">
        <div class="px-4 sm:px-6 lg:px-8 w-full flex justify-between items-center">
            <div class="flex items-center">
                <a href="{{ route('projects.index') }}" class="flex items-center text-xl font-bold text-green-600">
                    🌿 Florix
                </a>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">Local AI Engine: Ollama</span>
            </div>
        </div>
    </nav>

    <div class="flex pt-16 h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside class="w-64 bg-white border-r border-gray-200 hidden md:flex flex-col shrink-0">
            <div class="flex-1 flex flex-col pt-5 pb-4 overflow-y-auto">
                <nav class="mt-2 flex-1 px-2 space-y-1">
                    <a href="{{ route('projects.index') }}"
                        class="{{ request()->routeIs('projects.index') ? 'bg-green-50 text-green-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                        <svg class="{{ request()->routeIs('projects.index') ? 'text-green-500' : 'text-gray-400 group-hover:text-gray-500' }} mr-3 flex-shrink-0 h-5 w-5"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Dashboard
                    </a>

                    <div class="pt-4 pb-2 px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                        Quick Links
                    </div>

                    <a href="{{ route('projects.index') }}?action=upload"
                        class="text-gray-600 hover:bg-gray-50 hover:text-gray-900 group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                        <svg class="text-gray-400 group-hover:text-gray-500 mr-3 flex-shrink-0 h-5 w-5" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        New Analysis
                    </a>
                </nav>
            </div>
            <div class="p-4 border-t border-gray-100">
                <div class="text-[10px] text-gray-400 text-center uppercase tracking-widest font-bold">
                    &copy; {{ date('Y') }} Florix AI
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto bg-gray-50">
            <div class="py-8 px-4 sm:px-8 lg:px-12 w-full">
                @yield('content')
            </div>
        </main>
    </div>
</body>

</html>
