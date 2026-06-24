<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Catat.In') }}</title>
        @fonts
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <style>
                /*! tailwindcss v4.0.7 | MIT License | https://tailwindcss.com */ @layer properties{@supports (((-webkit-hyphens:none)) and (not (margin-trim:inline))) or ((-moz-orient:inline) and (not (color:rgb(from red r g b)))){*,:before,:after,::backdrop{--tw-translate-x:0;--tw-translate-y:0;--tw-translate-z:0;--tw-rotate-x:initial;--tw-rotate-y:initial;--tw-rotate-z:initial;--tw-skew-x:initial;--tw-skew-y:initial;--tw-space-x-reverse:0;--tw-border-style:solid;--tw-leading:initial;--tw-font-weight:initial;--tw-tracking:initial;--tw-shadow:0 0 #0000;--tw-shadow-color:initial;--tw-shadow-alpha:100%;--tw-inset-shadow:0 0 #0000;--tw-inset-shadow-color:initial;--tw-inset-shadow-alpha:100%;--tw-ring-color:initial;--tw-ring-shadow:0 0 #0000;--tw-inset-ring-color:initial;--tw-inset-ring-shadow:0 0 #0000;--tw-ring-inset:initial;--tw-ring-offset-width:0px;--tw-ring-offset-color:#fff;--tw-ring-offset-shadow:0 0 #0000;--tw-blur:initial;--tw-brightness:initial;--tw-contrast:initial;--tw-grayscale:initial;--tw-hue-rotate:initial;--tw-invert:initial;--tw-opacity:initial;--tw-saturate:initial;--tw-sepia:initial;--tw-drop-shadow:initial;--tw-drop-shadow-color:initial;--tw-drop-shadow-alpha:100%;--tw-drop-shadow-size:initial;--tw-duration:initial;--tw-ease:initial;--tw-content:""}}}@layer theme{:root,:host{--font-sans:"Instrument Sans", ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";--font-serif:ui-serif, Georgia, Cambria, "Times New Roman", Times, serif;--font-mono:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;--color-red-50:oklch(97.1% .013 17.38);--color-red-100:oklch(93.6% .032 17.717);--color-red-200:oklch(88.5% .062 18.334);--color-red-300:oklch(80.8% .114 19.571);--color-red-400:oklch(70.4% .191 22.216);--color-red-500:oklch(63.7% .237 25.331);--color-red-600:oklch(57.7% .245 27.325);--color-red-700:oklch(50.5% .213 27.518);--color-red-800:oklch(44.4% .... (line truncated to 2000 chars)
                *, :before, :after, ::backdrop { --tw-border-style: solid; box-sizing: border-box; border: 0 solid #e5e7eb; }
                html { -webkit-text-size-adjust: 100%; font-variation-settings: normal; tab-size: 4; font-family: var(--font-sans, ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji"); line-height: 1.5; }
                body { line-height: inherit; margin: 0; }
                h1, h2, p { margin: 0; font-size: inherit; font-weight: inherit; }
                a { color: inherit; -webkit-text-decoration: inherit; text-decoration: inherit; }
                nav { display: flex; }
                svg { display: block; vertical-align: middle; }
                *, :before, :after, ::backdrop { --tw-blur: initial; --tw-brightness: initial; --tw-contrast: initial; --tw-grayscale: initial; --tw-hue-rotate: initial; --tw-invert: initial; --tw-opacity: initial; --tw-saturate: initial; --tw-sepia: initial; --tw-drop-shadow: initial; --tw-drop-shadow-color: initial; --tw-drop-shadow-alpha: 100%; --tw-drop-shadow-size: initial; }
            </style>
        @endif
        <style>
            body {
                margin: 0;
                font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
            }
            .gradient-bg {
                background: linear-gradient(135deg, #065f46 0%, #059669 50%, #34d399 100%);
            }
        </style>
    </head>
    <body class="min-h-screen flex flex-col gradient-bg text-white">
        <header class="w-full px-6 py-5 lg:px-10">
            <nav class="flex items-center justify-end gap-3">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/home') }}" class="inline-flex items-center px-5 py-2 text-sm font-medium bg-white/20 hover:bg-white/30 rounded-xl transition-all duration-200">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="inline-flex items-center px-5 py-2 text-sm font-medium text-white/80 hover:text-white transition-all duration-200">
                            Log in
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="inline-flex items-center px-5 py-2 text-sm font-medium bg-white text-emerald-700 hover:bg-white/90 rounded-xl transition-all duration-200 shadow-lg shadow-emerald-900/20">
                                Register
                            </a>
                        @endif
                    @endauth
                @endif
            </nav>
        </header>

        <div class="flex-1 flex items-center justify-center px-6 pb-20 lg:pb-0">
            <main class="flex flex-col items-center text-center">
                <svg class="w-32 h-32 lg:w-40 lg:h-40 mb-8" viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="60" cy="60" r="54" fill="none" stroke="white" stroke-width="2" opacity="0.3"/>
                    <text x="60" y="60" text-anchor="middle" dominant-baseline="central" font-size="56" font-weight="900" fill="white" font-family="system-ui, sans-serif">$</text>
                </svg>

                <h1 class="text-5xl lg:text-7xl font-extrabold tracking-tight mb-4 leading-tight">
                    Catat <span class="text-yellow-300">Setiap</span> Rupiah
                </h1>

                <p class="text-base lg:text-lg text-emerald-100/70 max-w-md mb-12 leading-relaxed">
                    Catat pemasukan & pengeluaran, atur anggaran,<br class="hidden sm:block"> dan kendalikan keuanganmu dalam satu aplikasi.
                </p>

                @if (!Auth::check())
                    <div class="flex flex-col sm:flex-row items-center gap-4">
                        <a href="{{ route('register') }}" class="inline-flex items-center px-8 py-3.5 text-base font-semibold bg-white text-emerald-700 hover:bg-emerald-50 rounded-2xl transition-all duration-200 shadow-2xl shadow-emerald-900/30 hover:shadow-emerald-900/40 hover:-translate-y-0.5">
                            Mulai Sekarang
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                            </svg>
                        </a>
                        <a href="{{ route('login') }}" class="inline-flex items-center px-6 py-3.5 text-base font-medium text-white/80 hover:text-white border border-white/20 hover:border-white/40 rounded-2xl transition-all duration-200">
                            Sudah punya akun?
                        </a>
                    </div>
                @endif

                <div class="mt-16 flex items-center justify-center gap-10 lg:gap-14 text-emerald-100/60 text-sm">
                    <div class="flex flex-col items-center gap-2">
                        <svg class="w-6 h-6 text-emerald-200/70" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Pemasukan</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <svg class="w-6 h-6 text-emerald-200/70" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941"/>
                        </svg>
                        <span>Pengeluaran</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <svg class="w-6 h-6 text-emerald-200/70" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
                        </svg>
                        <span>Laporan</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <svg class="w-6 h-6 text-emerald-200/70" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z"/>
                        </svg>
                        <span>Anggaran</span>
                    </div>
                </div>
            </main>
        </div>
    </body>
</html>
