<x-layouts.auth :title="__('Email verification')">
    <div class="flex flex-col gap-6 py-4">
        <div class="flex justify-center">
            <div class="w-16 h-16 bg-blue-50 rounded-full flex items-center justify-center text-blue-500 shadow-inner">
                <x-icon-mail-check class="w-8 h-8" />
            </div>
        </div>

        <div class="text-center flex flex-col gap-2 px-2">
            <h2 class="text-xl font-bold text-slate-900 tracking-tight">{{ __('Verify your email') }}</h2>
            <p class="text-sm text-slate-500 font-medium">
                {{ __('Almost there! We\'ve sent a verification link to your email. Please click it to activate your account.') }}
            </p>
        </div>

        @if (session('status') == 'verification-link-sent')
            <div class="alert alert-success text-xs py-3 rounded-xl border-green-100 bg-green-50 text-green-700">
                <x-icon-check class="w-4 h-4" />
                <span>{{ __('A new link has been sent to your email.') }}</span>
            </div>
        @endif

        <div class="flex flex-col gap-3 pt-2">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="btn btn-primary w-full h-11 rounded-xl shadow-lg shadow-blue-200 font-bold tracking-wide">
                    {{ __('Resend Verification Email') }}
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-ghost w-full h-11 rounded-xl text-slate-500 font-semibold hover:bg-slate-100">
                    {{ __('Sign Out') }}
                </button>
            </form>
        </div>
    </div>
</x-layouts.auth>
