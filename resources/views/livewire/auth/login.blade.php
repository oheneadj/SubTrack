<x-layouts.auth :title="__('Log in')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Welcome back!')" :description="__('Enter your credentials to access your account')" />

        @if (session('status'))
            <div class="alert alert-success text-xs py-2 rounded-xl">
                <x-icon-check class="w-4 h-4" />
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
            @csrf
            <!-- Email Address -->
            <div class="form-control w-full">
                <label class="label py-1">
                    <span class="label-text font-bold text-slate-700 text-xs uppercase tracking-wider">{{ __('Email Address') }}</span>
                </label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-blue-500 transition-colors">
                        <x-icon-mail class="w-4 h-4" />
                    </div>
                    <input 
                        name="email" 
                        type="email" 
                        value="{{ old('email') }}" 
                        required 
                        autofocus 
                        autocomplete="email" 
                        placeholder="name@company.com"
                        class="input input-bordered w-full !pl-10 h-11 bg-slate-50/50 focus:bg-white transition-all rounded-xl border-slate-200 @error('email') input-error @enderror"
                    />
                </div>
                @error('email')
                    <label class="label p-1">
                        <span class="label-text-alt text-error font-medium">{{ $message }}</span>
                    </label>
                @enderror
            </div>

            <!-- Password -->
            <div class="form-control w-full">
                <div class="flex justify-between items-center mb-1">
                    <label class="label py-0">
                        <span class="label-text font-bold text-slate-700 text-xs uppercase tracking-wider">{{ __('Password') }}</span>
                    </label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-700 transition-colors" wire:navigate>
                            {{ __('Forgot?') }}
                        </a>
                    @endif
                </div>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-blue-500 transition-colors">
                        <x-icon-lock class="w-4 h-4" />
                    </div>
                    <input 
                        name="password" 
                        type="password" 
                        required 
                        autocomplete="current-password" 
                        placeholder="••••••••"
                        class="input input-bordered w-full !pl-10 h-11 bg-slate-50/50 focus:bg-white transition-all rounded-xl border-slate-200 @error('password') input-error @enderror"
                    />
                </div>
                @error('password')
                    <label class="label p-1">
                        <span class="label-text-alt text-error font-medium">{{ $message }}</span>
                    </label>
                @enderror
            </div>

            <!-- Remember Me -->
            <div class="form-control">
                <label class="flex items-center label cursor-pointer justify-start gap-3 py-1">
                    <input type="checkbox" name="remember" class="checkbox checkbox-primary checkbox-sm rounded-md" {{ old('remember') ? 'checked' : '' }} />
                    <span class="label-text text-sm font-medium text-slate-600">{{ __('Stay logged in') }}</span>
                </label>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary w-full h-11 rounded-xl shadow-lg shadow-blue-200 font-bold tracking-wide">
                    {{ __('Sign In') }}
                </button>
            </div>
        </form>

        @if (Route::has('register'))
            <div class="text-sm text-center text-slate-500 pt-2">
                <span>{{ __('New to SubTrack?') }}</span>
                <a href="{{ route('register') }}" class="text-blue-600 font-bold hover:underline" wire:navigate>{{ __('Create an account') }}</a>
            </div>
        @endif
    </div>
</x-layouts.auth>
