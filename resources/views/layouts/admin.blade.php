<!doctype html>
<html lang="en">
<head>
    @include('partials.head', ['title' => $title])
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite('resources/js/swal.js')
    @stack('scripts')
</head>
<body data-flash="{{ session('status') }}">
    @php
        $signOut = 'cursor-pointer text-xs font-medium text-muted transition-colors hover:text-brass';
        $logo = $settings->logo('logo_header', '/brand/delight-logo-horizontal-white.svg');
        $navLink = 'rounded-lg border-l-2 px-3 py-2.5 transition-colors';
        $navActive = 'border-brass bg-navy/60 font-semibold text-bone';
        $navIdle = 'border-transparent text-sand hover:bg-navy/40 hover:text-bone';
        $content = [
            ['Hero slides', 'hero-slides.index', 'hero-slides.*'],
            ['Projects', 'projects.index', 'projects.*'],
            ['Videos', 'videos.index', 'videos.*'],
            ['Leadership', 'leaders.index', 'leaders.*'],
            ['Contact details', 'contact-details.edit', 'contact-details.*'],
        ];
        $mobile = [['Dashboard', 'dashboard', 'dashboard'], ...$content, ['Settings', 'settings.edit', 'settings.*']];
    @endphp

    <div class="lg:grid lg:min-h-dvh lg:grid-cols-[256px_1fr]">
        <aside class="sticky top-0 hidden h-dvh flex-col border-r border-line bg-ink-2 p-6 lg:flex">
            <img src="{{ $logo }}" alt="{{ $settings->company_name }}" class="h-9 w-fit max-w-full object-contain object-left">

            <nav aria-label="Main" class="mt-10 flex flex-1 flex-col gap-1 text-sm">
                <a href="{{ route('dashboard') }}" @if (request()->routeIs('dashboard')) aria-current="page" @endif
                    class="{{ $navLink }} {{ request()->routeIs('dashboard') ? $navActive : $navIdle }}">Dashboard</a>

                <p class="eyebrow mt-7 mb-2 px-3">Content</p>
                @foreach ($content as [$label, $route, $pattern])
                    <a href="{{ route($route) }}" @if (request()->routeIs($pattern)) aria-current="page" @endif
                        class="{{ $navLink }} {{ request()->routeIs($pattern) ? $navActive : $navIdle }}">{{ $label }}</a>
                @endforeach

                <p class="eyebrow mt-7 mb-2 px-3">Administration</p>
                <a href="{{ route('settings.edit') }}" @if (request()->routeIs('settings.*')) aria-current="page" @endif
                    class="{{ $navLink }} {{ request()->routeIs('settings.*') ? $navActive : $navIdle }}">Settings</a>
            </nav>

            <div class="border-t border-line pt-5">
                <p class="truncate text-sm font-medium">{{ auth()->user()->name }}</p>
                <p class="mt-0.5 truncate text-xs text-muted">{{ auth()->user()->email }}</p>
                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button class="{{ $signOut }}">Sign out</button>
                </form>
            </div>
        </aside>

        <div class="min-w-0">
            <header class="border-b border-line bg-ink-2 px-5 py-4 lg:hidden">
                <div class="flex items-center justify-between">
                    <img src="{{ $logo }}" alt="{{ $settings->company_name }}" class="h-8 max-w-[60%] object-contain object-left">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="{{ $signOut }}">Sign out</button>
                    </form>
                </div>
                <nav aria-label="Main" class="mt-3 flex flex-wrap gap-x-5 text-xs font-medium">
                    @foreach ($mobile as [$label, $route, $pattern])
                        <a href="{{ route($route) }}" class="py-2 {{ request()->routeIs($pattern) ? 'text-brass' : 'text-sand' }}">{{ $label }}</a>
                    @endforeach
                </nav>
            </header>

            <main class="mx-auto max-w-[1600px] px-5 py-8 lg:px-10 lg:py-10">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
