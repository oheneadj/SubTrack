<x-layouts.auth :title="__('Register')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Create an account')" :description="__('Join SubTrack to manage your client relationships')" />

        @if (session('status'))
            <div class="alert alert-success text-xs py-2 rounded-xl">
                <x-icon-check class="w-4 h-4" />
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-5">
            @csrf
            
            <!-- Name -->
            <div class="form-control w-full">
                <label class="label py-1">
                    <span class="label-text font-bold text-slate-700 text-xs uppercase tracking-wider">{{ __('Full Name') }}</span>
                </label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-blue-500 transition-colors">
                        <x-icon-user class="w-4 h-4" />
                    </div>
                    <input 
                        name="name" 
                        type="text" 
                        value="{{ old('name') }}" 
                        required 
                        autofocus 
                        autocomplete="name" 
                        placeholder="John Doe"
                        class="input input-bordered w-full !pl-10 h-11 bg-slate-50/50 focus:bg-white transition-all rounded-xl border-slate-200 @error('name') input-error @enderror"
                    />
                </div>
                @error('name')
                    <label class="label p-1">
                        <span class="label-text-alt text-error font-medium">{{ $message }}</span>
                    </label>
                @enderror
            </div>

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

            <div class="grid grid-cols-1 gap-5">
                <!-- Password -->
                <div class="form-control w-full">
                    <label class="label py-1">
                        <span class="label-text font-bold text-slate-700 text-xs uppercase tracking-wider">{{ __('Password') }}</span>
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
                            class="input input-bordered w-full !pl-10 h-11 bg-slate-50/50 focus:bg-white transition-all rounded-xl border-slate-200 @error('password') input-error @enderror"
                        />
                    </div>
                </div>

                <!-- Confirm Password -->
                <div class="form-control w-full">
                    <label class="label py-1">
                        <span class="label-text font-bold text-slate-700 text-xs uppercase tracking-wider">{{ __('Confirm Password') }}</span>
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
                            class="input input-bordered w-full !pl-10 h-11 bg-slate-50/50 focus:bg-white transition-all rounded-xl border-slate-200"
                        />
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary w-full h-11 rounded-xl shadow-lg shadow-blue-200 font-bold tracking-wide">
                    {{ __('Create Account') }}
                </button>
            </div>
        </form>

        <div class="text-sm text-center text-slate-500 pt-2">
            <span>{{ __('Already have an account?') }}</span>
            <a href="{{ route('login') }}" class="text-blue-600 font-bold hover:underline" wire:navigate>{{ __('Sign In') }}</a>
        </div>
    </div>
</x-layouts.auth>
