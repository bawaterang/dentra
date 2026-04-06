<?php

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    public $username = '';
    public $password = '';
    public $remember = false;

    public function login()
    {
        $this->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        if (Auth::attempt(['username' => $this->username, 'password' => $this->password], $this->remember)) {
            $user = Auth::user();
            $user->login_terakhir = now();
            $user->save();

            session()->regenerate();
            return redirect()->route('dashboard.index');
        }

        session()->flash('error', 'Username atau password salah.');
    }
};
?>

<style>
    .global-loader {
        position: fixed;
        inset: 0;
        z-index: 99999;
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(4px);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .loader-content {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12px;
    }
    .tooth-spinner {
        width: 48px;
        height: 48px;
        color: #6691e7;
        animation: pulseTooth 1s ease-in-out infinite;
    }
    @keyframes pulseTooth {
        0% { transform: scale(0.9); opacity: 0.7; }
        50% { transform: scale(1.15); opacity: 1; }
        100% { transform: scale(0.9); opacity: 0.7; }
    }
    .loader-text {
        font-size: 14px;
        font-weight: 600;
        color: #6691e7;
        letter-spacing: 1px;
        animation: pulseText 1s ease-in-out infinite;
    }
    @keyframes pulseText {
        0% { opacity: 0.5; }
        50% { opacity: 1; }
        100% { opacity: 0.5; }
    }
</style>

<div class="min-h-screen flex bg-white font-sans antialiased text-gray-900 relative">
    <!-- Global Loader Overlay (Existing App Loader Styles) -->
    <div wire:loading.flex wire:target="login" class="global-loader" style="display:none;">
        <div class="loader-content">
            <svg class="tooth-spinner" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M2.26 10c.85-6.79 5-8 9.74-8s8.89 1.21 9.74 8c.55 4.39-1.32 8.52-4.14 11.2a2 2 0 0 1-2.82-.12l-2-2.13a1 1 0 0 0-1.46 0l-2 2.13a2 2 0 0 1-2.82.12C3.58 18.52 1.71 14.39 2.26 10Z" />
                <path d="M12 11v11" />
            </svg>
            <div class="loader-text text-center tracking-[4px]">Memuat...</div>
        </div>
    </div>

    <!-- Left Side - Dental Info & Background -->
    <div class="hidden lg:flex lg:w-1/2 relative bg-indigo-900 overflow-hidden">
        <img class="absolute inset-0 h-full w-full object-cover opacity-40 mix-blend-overlay"
            src="{{ asset('images/dental_clinic_bg.png') }}" alt="Dental Clinic Ambient Background">

        <div class="relative z-10 w-full flex flex-col justify-between p-16 xl:p-24 text-white">
            <div>
                <div class="flex items-center">
                    <img src="{{ asset('images/sigi-logo-white.svg') }}" alt="SIGI Dental EMR"
                        class="h-12 w-auto drop-shadow-lg">
                </div>
            </div>

            <div>
                <h1 class="text-4xl lg:text-5xl font-extrabold tracking-tight mb-6">
                    Welcome to the Future <br> of Dental Care
                </h1>
                <p class="text-lg text-indigo-100 max-w-lg leading-relaxed">
                    Streamline your clinic operations seamlessly. From patient registration to comprehensive reporting,
                    experience a unified, modern platform tailored for dental professionals.
                </p>

                <div class="mt-12 flex -space-x-2">
                    <!-- Decorational user avatars to mimic "trusted by" -->
                    <img class="inline-block h-10 w-10 rounded-full ring-2 ring-indigo-900"
                        src="https://ui-avatars.com/api/?name=Dr.+Smith&background=E0E7FF&color=3730A3" alt="" />
                    <img class="inline-block h-10 w-10 rounded-full ring-2 ring-indigo-900"
                        src="https://ui-avatars.com/api/?name=Nurse+Jane&background=DBEAFE&color=1E40AF" alt="" />
                    <img class="inline-block h-10 w-10 rounded-full ring-2 ring-indigo-900"
                        src="https://ui-avatars.com/api/?name=Admin+Tom&background=BFDBFE&color=1E3A8A" alt="" />
                    <div
                        class="inline-flex items-center justify-center w-10 h-10 rounded-full ring-2 ring-indigo-900 bg-white/20 backdrop-blur-md text-sm font-medium">
                        +5k
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Side - Login Form -->
    <div
        class="flex-1 flex flex-col justify-center py-12 px-4 sm:px-6 lg:flex-none lg:w-1/2 lg:px-20 xl:px-24 bg-white/95">
        <div class="mx-auto w-full max-w-sm lg:w-96">

            {{-- Mobile logo --}}
            <div class="lg:hidden flex justify-center mb-8">
                <img src="{{ asset('images/sigi-logo.svg') }}" alt="SIGI Dental EMR"
                    class="h-10 w-auto drop-shadow-[0_4px_12px_rgba(49,46,129,0.45)]"
                    style="filter: drop-shadow(0 4px 12px rgba(49,46,129,0.4));">
            </div>

            {{-- Desktop logo above form --}}
            <div class="hidden lg:flex items-center mb-8">
                <img src="{{ asset('images/sigi-logo.svg') }}" alt="SIGI Dental EMR" class="h-10 w-auto"
                    style="filter: drop-shadow(0 6px 20px rgba(30,27,75,0.55));">
            </div>

            <div>
                <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight lg:text-left text-center">
                    Welcome Back !
                </h2>
                <p class="mt-2 text-sm text-gray-600 lg:text-left text-center">
                    Sign in to continue your session.
                </p>
            </div>

            <div class="mt-8">
                @if (session()->has('message'))
                <div
                    class="mb-6 bg-green-50 text-green-700 p-4 rounded-xl flex items-center gap-3 border border-green-200">
                    <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span class="text-sm font-medium">{{ session('message') }}</span>
                </div>
                @endif

                @if (session()->has('error'))
                <div class="mb-6 bg-red-50 text-red-700 p-4 rounded-xl flex items-center gap-3 border border-red-200">
                    <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span class="text-sm font-medium">{{ session('error') }}</span>
                </div>
                @endif

                <form wire:submit="login" class="space-y-6">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-1.5">
                            Username
                        </label>
                        <div class="relative">
                            <div
                                class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <input wire:model="username" id="username" name="username" type="text"
                                autocomplete="username" required placeholder="Enter your username"
                                class="appearance-none block w-full pl-11 px-4 py-3 bg-gray-50/50 border border-gray-200 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-all focus:bg-white text-gray-900">
                        </div>
                        @error('username') <span class="text-red-500 text-xs mt-1.5 block font-medium">{{ $message
                            }}</span> @enderror
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="password" class="block text-sm font-medium text-gray-700">
                                Password
                            </label>
                            <!-- <div class="text-sm">
                                <a href="#"
                                    class="font-medium text-indigo-600 hover:text-indigo-500 hover:underline transition-colors">
                                    Forgot password?
                                </a>
                            </div> -->
                        </div>
                        <div class="relative" x-data="{ show: false }">
                            <div
                                class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input wire:model="password" id="password" name="password"
                                :type="show ? 'text' : 'password'" autocomplete="current-password" required
                                placeholder="Enter your password"
                                class="appearance-none block w-full pl-11 pr-12 py-3 bg-gray-50/50 border border-gray-200 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-all focus:bg-white text-gray-900">

                            <button type="button" @click="show = !show"
                                class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none transition-colors">
                                <svg x-show="!show" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg x-show="show" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" style="display: none;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>
                        @error('password') <span class="text-red-500 text-xs mt-1.5 block font-medium">{{ $message
                            }}</span> @enderror
                    </div>

                    <div class="flex items-center">
                        <input wire:model="remember" id="remember-me" name="remember-me" type="checkbox"
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded transition-colors cursor-pointer">
                        <label for="remember-me" class="ml-2 block text-sm text-gray-900 cursor-pointer">
                            Remember me
                        </label>
                    </div>

                        <button type="submit" 
                            wire:loading.attr="disabled"
                            class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-xl shadow-md text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all transform active:scale-[0.98] disabled:opacity-70 disabled:cursor-not-allowed gap-2">
                            <span wire:loading.remove wire:target="login">Sign In</span>
                            <span wire:loading wire:target="login" class="flex items-center gap-2">
                                <i class="ri-loader-4-line animate-spin text-lg"></i>
                                Memproses...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>