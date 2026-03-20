<x-layouts.auth :title="__('Reset password')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Reset password')" :description="__('Enter your new password below to secure your account')" />

        @if (session('status'))
            <div class="alert alert-success text-xs py-3 rounded-xl">
                <x-icon-check class="w-4 h-4" />
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-5">
            @csrf
            <!-- Token -->
            <input type="hidden" name="token" value="{{ request()->route('token') }}">

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
                        value="{{ old('email', request('email')) }}" 
                        required 
                        autocomplete="email" 
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

            <!-- Password -->
            <div class="form-control w-full">
                <label class="label py-1">
                    <span class="label-text font-bold text-slate-700 text-xs uppercase tracking-wider">{{ __('New Password') }}</span>
                </label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-blue-500 transition-colors">
                        <x-icon-lock class="w-4 h-4" />
                    </div>
                    <input 
                        name="password" 
                        type="password" 
                        required 
                        autocomplete="new-password" 
                        placeholder="••••••••"
                        class="input input-bordered w-full pl-10 h-11 bg-slate-50/50 focus:bg-white transition-all rounded-xl border-slate-200 @error('password') input-error @enderror"
                    />
                </div>
                @error('password')
                    <label class="label p-1">
                        <span class="label-text-alt text-error font-medium">{{ $message }}</span>
                    </label>
                @enderror
            </div>

            <!-- Confirm Password -->
            <div class="form-control w-full">
                <label class="label py-1">
                    <span class="label-text font-bold text-slate-700 text-xs uppercase tracking-wider">{{ __('Confirm New Password') }}</span>
                </label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-blue-500 transition-colors">
                        <x-icon-lock-check class="w-4 h-4" />
                    </div>
                    <input 
                        name="password_confirmation" 
                        type="password" 
                        required 
                        autocomplete="new-password" 
                        placeholder="••••••••"
                        class="input input-bordered w-full pl-10 h-11 bg-slate-50/50 focus:bg-white transition-all rounded-xl border-slate-200"
                    />
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary w-full h-11 rounded-xl shadow-lg shadow-blue-200 font-bold tracking-wide">
                    {{ __('Update Password') }}
                </button>
            </div>
        </form>
    </div>
</x-layouts.auth>
