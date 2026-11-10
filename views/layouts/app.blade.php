<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - Enrollment Analytics</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100" x-data="{ sidebarOpen: false }">
    <div class="flex h-screen overflow-hidden">
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-800 text-white transform transition-transform lg:translate-x-0 lg:static lg:inset-auto">
            <div class="p-4 border-b border-slate-700">
                <h1 class="text-lg font-bold">Enrollment Analytics</h1>
                <p class="text-xs text-slate-400">Decision Support System</p>
            </div>
            <nav class="p-4 space-y-1 text-sm overflow-y-auto h-[calc(100vh-5rem)]">
                <a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded {{ request()->routeIs('dashboard') ? 'bg-indigo-600' : 'hover:bg-slate-700' }}">Dashboard</a>
                <p class="px-3 pt-3 text-xs uppercase text-slate-400">Enrollment</p>
                <a href="{{ route('enrollment-records.index') }}" class="block px-3 py-2 rounded {{ request()->routeIs('enrollment-records.*') ? 'bg-indigo-600' : 'hover:bg-slate-700' }}">Records</a>
                <a href="{{ route('enrollment-records.import.form') }}" class="block px-3 py-2 rounded hover:bg-slate-700">Import Data</a>
                <p class="px-3 pt-3 text-xs uppercase text-slate-400">Analytics</p>
                <a href="{{ route('analytics.summary') }}" class="block px-3 py-2 rounded {{ request()->routeIs('analytics.summary') ? 'bg-indigo-600' : 'hover:bg-slate-700' }}">Overview</a>
                <a href="{{ route('analytics.trends') }}" class="block px-3 py-2 rounded {{ request()->routeIs('analytics.trends') ? 'bg-indigo-600' : 'hover:bg-slate-700' }}">Trends</a>
                <a href="{{ route('analytics.programs') }}" class="block px-3 py-2 rounded {{ request()->routeIs('analytics.programs') ? 'bg-indigo-600' : 'hover:bg-slate-700' }}">Programs</a>
                <a href="{{ route('analytics.colleges') }}" class="block px-3 py-2 rounded {{ request()->routeIs('analytics.colleges') ? 'bg-indigo-600' : 'hover:bg-slate-700' }}">Colleges</a>
                <a href="{{ route('analytics.retention') }}" class="block px-3 py-2 rounded {{ request()->routeIs('analytics.retention') ? 'bg-indigo-600' : 'hover:bg-slate-700' }}">Retention</a>
                <p class="px-3 pt-3 text-xs uppercase text-slate-400">Support</p>
                <a href="{{ route('alerts.index') }}" class="block px-3 py-2 rounded {{ request()->routeIs('alerts.*') ? 'bg-indigo-600' : 'hover:bg-slate-700' }}">Alerts</a>
                <a href="{{ route('reports.index') }}" class="block px-3 py-2 rounded {{ request()->routeIs('reports.*') ? 'bg-indigo-600' : 'hover:bg-slate-700' }}">Reports</a>
                <p class="px-3 pt-3 text-xs uppercase text-slate-400">Data Management</p>
                <a href="{{ route('academic-years.index') }}" class="block px-3 py-2 rounded hover:bg-slate-700">Academic Years</a>
                <a href="{{ route('semesters.index') }}" class="block px-3 py-2 rounded hover:bg-slate-700">Semesters</a>
                <a href="{{ route('colleges.index') }}" class="block px-3 py-2 rounded hover:bg-slate-700">Colleges</a>
                <a href="{{ route('departments.index') }}" class="block px-3 py-2 rounded hover:bg-slate-700">Departments</a>
                <a href="{{ route('programs.index') }}" class="block px-3 py-2 rounded hover:bg-slate-700">Programs</a>
                @if(auth()->user()?->isAdmin())
                <p class="px-3 pt-3 text-xs uppercase text-slate-400">Administration</p>
                <a href="{{ route('users.index') }}" class="block px-3 py-2 rounded {{ request()->routeIs('users.*') ? 'bg-indigo-600' : 'hover:bg-slate-700' }}">User Management</a>
                @endif
            </nav>
        </aside>

        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="bg-white shadow-sm px-4 py-3 flex items-center justify-between">
                <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-gray-600">☰</button>
                <h2 class="text-lg font-semibold text-gray-800">@yield('title', 'Dashboard')</h2>
                <div class="flex items-center gap-4" x-data="{ open: false }">
                    <span class="text-sm text-gray-600">{{ auth()->user()->name ?? '' }}</span>
                    <div class="relative">
                        <button @click="open = !open" class="text-sm bg-gray-100 px-3 py-1 rounded">Account</button>
                        <div x-show="open" @click.outside="open = false" class="absolute right-0 mt-2 w-40 bg-white shadow rounded py-1 z-10">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="block w-full text-left px-4 py-2 text-sm hover:bg-gray-50">Logout</button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-6">
                @if(session('success'))
                    <div class="mb-4 p-3 bg-emerald-50 text-emerald-700 rounded-lg">{{ session('success') }}</div>
                @endif
                @if($errors->any())
                    <div class="mb-4 p-3 bg-red-50 text-red-700 rounded-lg">
                        <ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
