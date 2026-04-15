<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Florix - Code to Business Explanation</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🌿</text></svg>">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: {
                            bg: '#0f172a',
                            card: '#1e293b',
                            border: '#334155',
                            text: '#f8fafc',
                            muted: '#94a3b8'
                        }
                    }
                }
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/mermaid/dist/mermaid.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/svg-pan-zoom@3.6.1/dist/svg-pan-zoom.min.js"></script>
    <script>
        function initMermaid(isDark) {
            mermaid.initialize({
                startOnLoad: false,
                securityLevel: 'loose',
                theme: isDark ? 'dark' : 'base',
                themeVariables: {
                    primaryColor: isDark ? '#1e293b' : '#ffffff',
                    primaryTextColor: isDark ? '#f8fafc' : '#1f2937',
                    primaryBorderColor: '#3b82f6',
                    lineColor: isDark ? '#94a3b8' : '#64748b',
                    secondaryColor: isDark ? '#0f172a' : '#f8fafc',
                    tertiaryColor: isDark ? '#1e293b' : '#ffffff',
                    clusterBkg: isDark ? '#0f172a' : '#f1f5f9',
                    clusterBorder: isDark ? '#334155' : '#cbd5e1',
                    fontSize: '14px',
                    fontFamily: 'ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif'
                },
                flowchart: {
                    htmlLabels: true,
                    curve: 'basis',
                    padding: 20
                }
            });
        }

        // Initialize with system preference or stored preference
        const isDark = localStorage.getItem('theme') === 'dark' || 
                      (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches);
        
        if (isDark) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
        
        initMermaid(isDark);
    </script>
</head>

<body class="bg-gray-50 dark:bg-dark-bg text-gray-900 dark:text-dark-text min-h-screen flex flex-col transition-colors duration-200">
    <!-- Top Navbar -->
    <nav class="bg-white dark:bg-dark-card border-b border-gray-200 dark:border-dark-border fixed w-full z-30 top-0 h-16 flex items-center shrink-0 transition-colors duration-200">
        <div class="px-4 sm:px-6 lg:px-8 w-full flex justify-between items-center">
            <div class="flex items-center">
                <a href="{{ route('projects.index') }}" class="flex items-center text-xl font-bold text-green-600 dark:text-green-500">
                    🌿 Florix
                </a>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider hidden sm:inline">Local AI Engine: Ollama</span>
                
                <!-- Theme Toggle Button -->
                <button onclick="toggleTheme()" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 transition-all border border-gray-200 dark:border-gray-700" title="Toggle Theme">
                    <svg id="sun-icon" class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707.707m12.728 12.728L5.121 5.121M19.071 19.071L4.929 4.929" />
                    </svg>
                    <svg id="moon-icon" class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                </button>
            </div>
        </div>
    </nav>

    <div class="flex pt-16 h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside class="w-64 bg-white dark:bg-dark-card border-r border-gray-200 dark:border-dark-border hidden md:flex flex-col shrink-0 transition-colors duration-200">
            <div class="flex-1 flex flex-col pt-5 pb-4 overflow-y-auto">
                <nav class="mt-2 flex-1 px-2 space-y-1">
                    <a href="{{ route('projects.index') }}"
                        class="{{ request()->routeIs('projects.index') ? 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-gray-200' }} group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                        <svg class="{{ request()->routeIs('projects.index') ? 'text-green-500' : 'text-gray-400 dark:text-gray-500 group-hover:text-gray-500 dark:group-hover:text-gray-300' }} mr-3 flex-shrink-0 h-5 w-5"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Dashboard
                    </a>

                    <div class="pt-4 pb-2 px-3 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                        Quick Links
                    </div>

                    <a href="{{ route('projects.index') }}?action=upload"
                        class="text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-gray-200 group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors">
                        <svg class="text-gray-400 dark:text-gray-500 group-hover:text-gray-500 dark:group-hover:text-gray-300 mr-3 flex-shrink-0 h-5 w-5" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        New Analysis
                    </a>
                </nav>
            </div>
            <div class="p-4 border-t border-gray-100 dark:border-dark-border">
                <div class="text-[10px] text-gray-400 dark:text-gray-500 text-center uppercase tracking-widest font-bold">
                    &copy; {{ date('Y') }} Florix AI
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto bg-gray-50 dark:bg-dark-bg transition-colors duration-200">
            <div class="py-8 px-4 sm:px-8 lg:px-12 w-full">
                @yield('content')
            </div>
        </main>
    </div>

    <script>
        function toggleTheme() {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            
            // Re-initialize mermaid with new theme
            initMermaid(isDark);
            
            // If we are on the analysis page, re-render the diagram if it exists
            if (typeof renderMermaid === 'function') {
                renderMermaid();
            }
        }
    </script>
</body>

</html>
