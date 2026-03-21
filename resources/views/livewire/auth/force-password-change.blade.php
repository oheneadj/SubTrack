<div class="sm:mx-auto sm:w-full sm:max-w-md">
          <div class="sm:mx-auto sm:w-full sm:max-w-md mb-8">
            <h2 class="text-center text-2xl font-extrabold text-slate-900 font-outfit tracking-tight">
                Secure Your Account
            </h2>
            <p class="mt-3 text-center text-sm text-slate-500 font-medium">
                You are logging in with a temporary password. Please set a new secure personal password to continue.
            </p>
        </div>

        @if (session('error'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg text-sm font-medium">
                {{ session('error') }}
            </div>
        @endif

        <form wire:submit="save" class="space-y-6">
            <div>
                <label for="password" class="block text-sm font-semibold text-slate-700">New Password (Min 8 chars)</label>
                <div class="mt-2 text-slate-900">
                    <input wire:model="password" id="password" type="password" required class="appearance-none block w-full px-4 py-3 border border-slate-300 rounded-lg shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-all duration-200">
                </div>
                @error('password') <span class="text-xs text-red-500 mt-2 block font-medium">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-semibold text-slate-700">Confirm New Password</label>
                <div class="mt-2 text-slate-900">
                    <input wire:model="password_confirmation" id="password_confirmation" type="password" required class="appearance-none block w-full px-4 py-3 border border-slate-300 rounded-lg shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-all duration-200">
                </div>
                @error('password_confirmation') <span class="text-xs text-red-500 mt-2 block font-medium">{{ $message }}</span> @enderror
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                    <span wire:loading.remove>Update Password</span>
                    <span wire:loading>
                        <span class="loading loading-spinner loading-sm mr-2"></span> Updating...
                    </span>
                </button>
            </div>
        </form>

</div>
