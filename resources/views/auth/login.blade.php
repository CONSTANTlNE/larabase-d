@extends('layouts.app')
@section('title', 'Sign In — LaraBase-D')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-white">LaraBase-D</h1>
            <p class="text-gray-400 mt-1 text-sm">PostgreSQL Database Browser</p>
        </div>

        <div class="bg-gray-900 border border-gray-800 rounded-xl p-8">
            @if (session('status'))
                <div class="mb-4 text-green-400 text-sm bg-green-500/10 border border-green-500/20 rounded-lg px-4 py-3">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="space-y-5">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-300 mb-1.5">Email</label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="email"
                            class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3.5 py-2.5 text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                            placeholder="you@example.com"
                        >
                        @error('email')
                            <p class="mt-1.5 text-red-400 text-xs">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-300 mb-1.5">Password</label>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3.5 py-2.5 text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                            placeholder="••••••••"
                        >
                        @error('password')
                            <p class="mt-1.5 text-red-400 text-xs">{{ $message }}</p>
                        @enderror
                    </div>

                    <button
                        type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-500 text-white font-medium py-2.5 px-4 rounded-lg transition-colors text-sm"
                    >
                        Sign In
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
