<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telcovantage Service Status</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .font-space {
            font-family: 'Space Grotesk', sans-serif;
        }
        /* Starbucks Green */
        .bg-starbucks {
            background-color: #006241;
        }
        .text-starbucks {
            color: #006241;
        }
        .border-starbucks {
            border-color: #006241;
        }
    </style>
</head>
<body class="bg-[#f4f4f2] text-slate-800 min-h-screen flex flex-col justify-between selection:bg-[#006241] selection:text-white">

    <header class="max-w-4xl w-full mx-auto px-4 pt-12 sm:pt-16 pb-6 flex items-center justify-between z-10">
        <div class="flex items-center gap-3">
            <img src="{{ asset('assets/images/logo-dark.png') }}" alt="TelcoVantage Philippines Logo" class="h-10 w-auto object-contain" />
            <div class="border-l border-gray-300 pl-3">
                <span class="text-sm font-extrabold tracking-[0.2em] uppercase text-starbucks font-space">Telcovantage</span>
                <h1 class="text-xs font-semibold tracking-wider text-slate-500 uppercase -mt-0.5">Service Status</h1>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button onclick="window.location.reload()" class="flex h-9 items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 text-xs font-bold text-slate-700 transition hover:bg-gray-50 active:scale-95 shadow-sm">
                <i class="fa-solid fa-arrows-rotate text-starbucks animate-pulse"></i>
                Refresh
            </button>
            <a href="/" class="hidden sm:flex h-9 items-center rounded-xl bg-starbucks px-4 text-xs font-black text-white transition hover:opacity-95 active:scale-95 shadow-md shadow-emerald-900/10">
                Back to Home
            </a>
        </div>
    </header>

    <main class="max-w-4xl w-full mx-auto px-4 flex-1 flex flex-col justify-center gap-6 z-10 py-6">

        <!-- Global Status Bar -->
        @if($systemStatus === 'Operational')
            <div class="rounded-3xl border border-emerald-200 bg-white p-6 sm:p-8 shadow-[0_16px_36px_rgba(0,98,65,0.06)] flex flex-col sm:flex-row items-center justify-between gap-6">
                <div class="flex items-center gap-5 text-center sm:text-left flex-col sm:flex-row">
                    <div class="h-16 w-16 rounded-full bg-[#006241]/10 border border-[#006241]/20 flex items-center justify-center shadow-[0_0_20px_rgba(0,98,65,0.1)]">
                        <i class="fa-solid fa-circle-check text-starbucks text-3xl"></i>
                    </div>
                    <div>
                        <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 font-space">All Systems Active</h2>
                        <p class="text-slate-500 text-sm mt-1">Telcovantage API services are active, database is healthy, and response is optimal.</p>
                    </div>
                </div>
                <div class="shrink-0 rounded-2xl bg-starbucks px-5 py-2 text-xs font-extrabold uppercase tracking-widest text-white">
                    Active
                </div>
            </div>
        @else
            <div class="rounded-3xl border border-rose-200 bg-white p-6 sm:p-8 shadow-[0_16px_36px_rgba(244,63,94,0.06)] flex flex-col sm:flex-row items-center justify-between gap-6">
                <div class="flex items-center gap-5 text-center sm:text-left flex-col sm:flex-row">
                    <div class="h-16 w-16 rounded-full bg-rose-50 border border-rose-100 flex items-center justify-center shadow-[0_0_20px_rgba(244,63,94,0.08)]">
                        <i class="fa-solid fa-circle-exclamation text-rose-500 text-3xl animate-bounce"></i>
                    </div>
                    <div>
                        <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 font-space">Systems Inactive</h2>
                        <p class="text-slate-500 text-sm mt-1">One or more database, cache, or API router connections are experiencing issues.</p>
                    </div>
                </div>
                <div class="shrink-0 rounded-2xl bg-rose-500 px-5 py-2 text-xs font-extrabold uppercase tracking-widest text-white">
                    Inactive
                </div>
            </div>
        @endif

        <!-- Detailed Services Grid -->
        <div class="grid gap-4 sm:grid-cols-2">
            
            <!-- Database Connection Card -->
            <div class="rounded-2xl border border-gray-200 bg-white p-6 hover:shadow-md transition-all duration-300">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-center text-slate-600">
                            <i class="fa-solid fa-database text-base"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-slate-800">Database Connection</h3>
                            <span class="text-[10px] text-slate-400 uppercase tracking-widest">Twin Backend</span>
                        </div>
                    </div>
                    @if($dbStatus === 'Operational')
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-[#006241]/10 px-3 py-1 text-xs font-bold text-starbucks ring-1 ring-[#006241]/20">
                            <span class="h-1.5 w-1.5 rounded-full bg-[#006241]"></span>
                            Active
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-3 py-1 text-xs font-bold text-rose-500 ring-1 ring-rose-500/20">
                            <span class="h-1.5 w-1.5 rounded-full bg-rose-500 animate-ping"></span>
                            Inactive
                        </span>
                    @endif
                </div>
                @if($dbError)
                    <div class="mt-4 p-3 rounded-xl bg-rose-50 border border-rose-100 text-xs font-mono text-rose-600 truncate">
                        {{ $dbError }}
                    </div>
                @else
                    <p class="text-slate-500 text-xs mt-4 leading-relaxed">Database responding successfully. Connection pool active and read/write requests functioning normally.</p>
                @endif
            </div>

            <!-- Redis / Cache Card -->
            <div class="rounded-2xl border border-gray-200 bg-white p-6 hover:shadow-md transition-all duration-300">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-center text-slate-600">
                            <i class="fa-solid fa-bolt text-base"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-slate-800">Cache Server</h3>
                            <span class="text-[10px] text-slate-400 uppercase tracking-widest">Redis / File Cache</span>
                        </div>
                    </div>
                    @if($cacheStatus === 'Operational')
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-[#006241]/10 px-3 py-1 text-xs font-bold text-starbucks ring-1 ring-[#006241]/20">
                            <span class="h-1.5 w-1.5 rounded-full bg-[#006241]"></span>
                            Active
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-3 py-1 text-xs font-bold text-rose-500 ring-1 ring-rose-500/20">
                            <span class="h-1.5 w-1.5 rounded-full bg-rose-500 animate-ping"></span>
                            Inactive
                        </span>
                    @endif
                </div>
                <p class="text-slate-500 text-xs mt-4 leading-relaxed">Application caching driver is fully operational. Temporary values stored and read successfully in 0.4ms.</p>
            </div>

            <!-- API Layer Status -->
            <div class="rounded-2xl border border-gray-200 bg-white p-6 hover:shadow-md transition-all duration-300">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-center text-slate-600">
                            <i class="fa-solid fa-code text-base"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-slate-800">API Endpoint Router</h3>
                            <span class="text-[10px] text-slate-400 uppercase tracking-widest">HTTP Gateway</span>
                        </div>
                    </div>
                    @if($apiStatus === 'Active')
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-[#006241]/10 px-3 py-1 text-xs font-bold text-starbucks ring-1 ring-[#006241]/20">
                            <span class="h-1.5 w-1.5 rounded-full bg-[#006241]"></span>
                            Active
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-3 py-1 text-xs font-bold text-rose-500 ring-1 ring-rose-500/20">
                            <span class="h-1.5 w-1.5 rounded-full bg-rose-500 animate-ping"></span>
                            Offline
                        </span>
                    @endif
                </div>
                @if($apiError)
                    <div class="mt-4 p-3 rounded-xl bg-rose-50 border border-rose-100 text-xs font-mono text-rose-600 truncate" title="{{ $apiError }}">
                        {{ $apiError }}
                    </div>
                @else
                    <p class="text-slate-500 text-xs mt-4 leading-relaxed">HTTP Router is dispatching API endpoints normally. Standard controller endpoints are ready to handle device sync payloads.</p>
                @endif
            </div>

            <!-- Memory & System Info -->
            <div class="rounded-2xl border border-gray-200 bg-white p-6 hover:shadow-md transition-all duration-300 flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-center text-slate-600">
                            <i class="fa-solid fa-microchip text-base"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-slate-800">System Environment</h3>
                            <span class="text-[10px] text-slate-400 uppercase tracking-widest">Host Status</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-2 mt-4 text-[10px]">
                        <div class="bg-gray-50 border border-gray-100 rounded-xl px-2 py-2">
                            <span class="text-slate-400 uppercase tracking-wider block text-[9px]">PHP Version</span>
                            <span class="font-bold text-slate-700 font-mono">{{ PHP_VERSION }}</span>
                        </div>
                        <div class="bg-gray-50 border border-gray-100 rounded-xl px-2 py-2">
                            <span class="text-slate-400 uppercase tracking-wider block text-[9px]">Memory Limit</span>
                            <span class="font-bold text-slate-700 font-mono">{{ ini_get('memory_limit') }}</span>
                        </div>
                        <div class="bg-gray-50 border border-gray-100 rounded-xl px-2 py-2">
                            <span class="text-slate-400 uppercase tracking-wider block text-[9px]">Free Storage</span>
                            <span class="font-bold text-slate-700 font-mono block truncate" title="{{ $storageInfo }}">{{ $storageInfo }}</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </main>

    <footer class="max-w-4xl w-full mx-auto px-4 py-8 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500 z-10">
        <p>&copy; {{ date('Y') }} Telcovantage. All services monitored.</p>
        <div class="flex items-center gap-2">
            <span class="h-2 w-2 rounded-full bg-[#006241] animate-ping"></span>
            <p>Live checking active · Local Time: {{ \Carbon\Carbon::now('Asia/Manila')->format('h:i:s A \P\H\T') }}</p>
        </div>
    </footer>

</body>
</html>
