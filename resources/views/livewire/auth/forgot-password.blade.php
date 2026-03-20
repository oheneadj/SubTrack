<x-layouts.auth :title="__('Forgot password')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Forgot password?')" :description="__('No worries, enter your email and we\'ll send you instructions')" />

        @if (session('status'))
            <div class="alert alert-success text-xs py-3 rounded-xl">
                <x-icon-check class="w-4 h-4" />
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-5">
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
                        placeholder="name@company.com"
                        class="input input-bordered w-full pl-10 h-11 bg-slate-50/50 focus:bg-white transition-all rounded-xl border-slate-200 @error('email') input-error @enderror"
                    />
                </div>
                @error('email')
                    <label class="label p-1">
                        <span class="label-text-alt text-error font-medium">{{ $message }}</span>
                    </label>
                @enderror
            </div>

            <div class="mt-2">
                <button type="submit" class="btn btn-primary w-full h-11 rounded-xl shadow-lg shadow-blue-200 font-bold tracking-wide">
                    {{ __('Send Reset Link') }}
                </button>
            </div>
        </form>

        <div class="text-sm text-center text-slate-500 pt-2">
            <span>{{ __('Remember your password?') }}</span>
            <a href="{{ route('login') }}" class="text-blue-600 font-bold hover:underline" wire:navigate>{{ __('Return to Sign In') }}</a>
        </div>
    </div>
</x-layouts.auth>
