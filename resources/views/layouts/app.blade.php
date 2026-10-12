<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name'))</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-gray-50 font-sans text-gray-900 antialiased">
        <header class="border-b border-gray-200 bg-white">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3">
                <a href="{{ url('/') }}" class="text-lg font-semibold">{{ config('app.name') }}</a>

                <nav class="flex items-center gap-4 text-sm">
                    @auth
                        <span class="text-gray-500">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-gray-600 hover:text-gray-900">
                                {{ __('Log out') }}
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-gray-600 hover:text-gray-900">
                            {{ __('Sign in') }}
                        </a>
                    @endauth
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-4 py-8">
            @yield('content')
        </main>

        @stack('scripts')
    </body>
</html>
