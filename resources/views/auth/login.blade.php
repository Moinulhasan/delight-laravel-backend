<!doctype html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Sign in'])
</head>
<body>
    <main class="grid min-h-dvh lg:grid-cols-[1.2fr_1fr]">
        <section class="relative hidden overflow-hidden lg:block">
            <img src="{{ asset('brand/login-hero.webp') }}" alt="" class="absolute inset-0 size-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-ink via-ink/45 to-ink/30"></div>

            <div class="relative flex h-full flex-col justify-between p-12">
                <img src="{{ $settings->logo('logo_header', '/brand/delight-logo-horizontal-white.svg') }}" alt="{{ $settings->company_name }}" class="h-11 w-fit">

                <div class="max-w-xl">
                    <p class="eyebrow">Admin portal</p>
                    <p class="mt-4 text-4xl leading-tight font-light text-bone xl:text-5xl">
                        Modern living<br><span class="font-bold">starts here</span>
                    </p>
                    <div class="mt-6 h-px w-20 bg-brass"></div>
                    <p class="mt-6 max-w-md text-sm leading-relaxed text-sand/80">
                        Manage the hero slider, projects, videos, leadership and contact details shown on delightre.com.
                    </p>
                </div>
            </div>
        </section>

        <section class="flex items-center justify-center px-5 py-12 sm:px-10">
            <div class="w-full max-w-sm">
                <img src="{{ $settings->logo('logo_header', '/brand/delight-logo-horizontal-white.svg') }}" alt="{{ $settings->company_name }}" class="mb-12 h-10 lg:hidden">

                <p class="eyebrow">Welcome back</p>
                <h1 class="mt-3 text-3xl font-bold text-bone">Sign in</h1>
                <p class="mt-2 text-sm text-muted">Use your admin account to continue.</p>

                <form method="POST" action="{{ route('login.store') }}" class="mt-9 flex flex-col gap-5">
                    @csrf

                    <div class="flex flex-col gap-2">
                        <label for="email" class="text-xs font-medium text-sand">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus
                            class="field @error('email') border-danger @enderror"
                            @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                        @error('email')
                            <p id="email-error" role="alert" class="text-xs text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col gap-2">
                        <label for="password" class="text-xs font-medium text-sand">Password</label>
                        <input id="password" name="password" type="password" autocomplete="current-password" required
                            class="field @error('password') border-danger @enderror">
                        @error('password')
                            <p role="alert" class="text-xs text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <label class="flex w-fit items-center gap-2.5 text-xs text-muted">
                        <input type="checkbox" name="remember" value="1" @checked(old('remember')) class="size-4 accent-brass">
                        Keep me signed in
                    </label>

                    <button type="submit" class="mt-2 cursor-pointer rounded-lg bg-brass px-4 py-3.5 text-sm font-semibold text-ink transition-colors hover:bg-brass-hover">
                        Sign in
                    </button>
                </form>

                <p class="mt-10 border-t border-line pt-5 text-xs text-muted">Authorised {{ $settings->company_name }} staff only.</p>
            </div>
        </section>
    </main>
</body>
</html>
