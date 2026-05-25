@extends('layouts.app')
@section('title', 'Setup — LaraBase-D')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-white">LaraBase-D</h1>
            <p class="text-gray-400 mt-1 text-sm">Create your admin account to get started</p>
        </div>

        <div class="bg-gray-900 border border-gray-800 rounded-xl p-8">
            <h2 class="text-lg font-semibold text-white mb-6">Initial Setup</h2>

            <form method="POST" action="{{ route('setup.store') }}">
                @csrf

                <div class="space-y-5">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-300 mb-1.5">Name</label>
                        <input
                            id="name"
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            required
                            autofocus
                            class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3.5 py-2.5 text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                            placeholder="Your name"
                        >
                        @error('name')
                            <p class="mt-1.5 text-red-400 text-xs">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-300 mb-1.5">Email</label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
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
                            class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3.5 py-2.5 text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                            placeholder="Min 8 characters"
                        >
                        @error('password')
                            <p class="mt-1.5 text-red-400 text-xs">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-300 mb-1.5">Confirm Password</label>
                        <input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            required
                            class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3.5 py-2.5 text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                            placeholder="••••••••"
                        >
                    </div>

                    <button
                        type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-500 text-white font-medium py-2.5 px-4 rounded-lg transition-colors text-sm"
                    >
                        Create Admin Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
