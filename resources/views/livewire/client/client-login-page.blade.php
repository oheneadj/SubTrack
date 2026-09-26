<div class="max-w-sm mx-auto">
    <div class="bg-white rounded-2xl border border-slate-200 p-8">
        <div class="text-center mb-8">
            <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center mx-auto mb-4">
                <x-icon-mail class="w-6 h-6 text-blue-600" />
            </div>
            <h1 class="text-xl font-bold text-slate-800">Client Portal</h1>
            <p class="text-sm text-slate-500 mt-1">Enter your email to receive a login link.</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success mb-6">
                <x-icon-circle-check class="w-5 h-5" />
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if($errors->has('auth'))
            <div class="alert alert-error mb-6">
                <x-icon-alert-triangle class="w-5 h-5" />
                <span>{{ $errors->first('auth') }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('client.magic-link.send') }}" class="space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email Address</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="you@example.com"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('email') border-red-400 @enderror"
                    required
                    autofocus
                >
                @error('email')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="w-full bg-blue-600 text-white font-semibold rounded-lg py-2.5 text-sm hover:bg-blue-700 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1">
                Send Login Link
            </button>
        </form>
    </div>
</div>
