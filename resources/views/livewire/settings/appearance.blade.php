<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Appearance')" :subheading="__('Customize how the application looks for you')">
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <p class="text-sm text-secondary mb-4">{{ __('Theme preference') }}</p>

            <div class="grid grid-cols-3 gap-4" x-data="{ theme: localStorage.getItem('theme') || 'light' }">
                <button @click="theme = 'light'; document.documentElement.classList.remove('dark'); localStorage.setItem('theme', 'light')"
                    :class="theme === 'light' ? 'ring-2 ring-blue-500 bg-blue-50' : 'bg-slate-50 hover:bg-slate-100'"
                    class="p-4 rounded-xl border border-slate-200 text-center transition-all">
                    <span class="text-2xl block mb-2">☀️</span>
                    <span class="text-sm font-medium text-primary">Light</span>
                </button>

                <button @click="theme = 'dark'; document.documentElement.classList.add('dark'); localStorage.setItem('theme', 'dark')"
                    :class="theme === 'dark' ? 'ring-2 ring-blue-500 bg-blue-50' : 'bg-slate-50 hover:bg-slate-100'"
                    class="p-4 rounded-xl border border-slate-200 text-center transition-all">
                    <span class="text-2xl block mb-2">🌙</span>
                    <span class="text-sm font-medium text-primary">Dark</span>
                </button>

                <button @click="theme = 'system'; localStorage.removeItem('theme'); if(window.matchMedia('(prefers-color-scheme: dark)').matches){document.documentElement.classList.add('dark')}else{document.documentElement.classList.remove('dark')}"
                    :class="theme === 'system' ? 'ring-2 ring-blue-500 bg-blue-50' : 'bg-slate-50 hover:bg-slate-100'"
                    class="p-4 rounded-xl border border-slate-200 text-center transition-all">
                    <span class="text-2xl block mb-2">💻</span>
                    <span class="text-sm font-medium text-primary">System</span>
                </button>
            </div>
        </div>
    </x-settings.layout>
</section>
