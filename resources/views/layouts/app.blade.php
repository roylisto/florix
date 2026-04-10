<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Florix - Code to Business Explanation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/mermaid/dist/mermaid.min.js"></script>
    <script>
        mermaid.initialize({
            startOnLoad: true
        });
    </script>
</head>

<body class="bg-gray-50 min-h-screen">
    <nav class="bg-white shadow-sm mb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <a href="{{ route('projects.index') }}"
                        class="flex-shrink-0 flex items-center text-xl font-bold text-green-600">
                        🌿 Florix
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
        @yield('content')
    </main>

    <footer class="text-center text-gray-500 text-sm mt-12 pb-8">
        &copy; {{ date('Y') }} Florix - Built with AI
    </footer>
</body>

</html>
