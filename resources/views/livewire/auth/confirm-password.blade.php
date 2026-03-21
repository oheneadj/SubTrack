<x-layouts.auth :title="__('Confirm password')">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('Confirm password')"
            :description="__('This is a secure area of the application. Please confirm your password before continuing.')"
        />

        @if (session('status'))
            <div class="alert alert-info text-xs py-3 rounded-xl">
                <x-icon-info-circle class="w-4 h-4" />
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('password.confirm.store') }}" class="flex flex-col gap-5">
            @csrf

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
                        autofocus
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

            <div class="mt-2">
                <button type="submit" class="btn btn-primary w-full h-11 rounded-xl shadow-lg shadow-blue-200 font-bold tracking-wide">
                    {{ __('Confirm') }}
                </button>
            </div>
        </form>
    </div>
</x-layouts.auth>
