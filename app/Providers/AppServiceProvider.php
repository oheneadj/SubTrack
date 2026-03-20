<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use App\Services\ActivityLogService;
use App\Models\Client;
use App\Models\Invoice;
use App\Observers\ClientObserver;
use App\Observers\InvoiceObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerActivityListeners();

        Client::observe(ClientObserver::class);
        Invoice::observe(InvoiceObserver::class);
    }

    /**
     * Register listeners for the Activity Log.
     */
    protected function registerActivityListeners(): void
    {
        Event::listen(Login::class, function (Login $event) {
            /** @var \App\Models\User $user */
            $user = $event->user;
            $now = now();

            $user->update([
                'last_login_at' => $now,
                'invitation_accepted_at' => $user->invitation_accepted_at ?? $now,
            ]);

            app(ActivityLogService::class)->logAuth('login', "User {$user->email} logged in");
        });

        Event::listen(Logout::class, function (Logout $event) {
            if ($event->user) {
                app(ActivityLogService::class)->logAuth('logout', "User {$event->user->email} logged out");
            }
        });

        Event::listen(PasswordReset::class, function (PasswordReset $event) {
            app(ActivityLogService::class)->logAuth('password_reset', "User {$event->user->email} reset their password");
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
