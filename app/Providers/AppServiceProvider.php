<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\Renewal;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use App\Observers\ClientObserver;
use App\Observers\InvoiceObserver;
use App\Observers\ReceiptObserver;
use App\Observers\RenewalObserver;
use App\Observers\SubscriptionObserver;
use App\Services\ActivityLogService;
use App\Services\Payment\GatewayRegistry;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Built once per request — all gateways resolved from config on first use.
        $this->app->singleton(GatewayRegistry::class);
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
        Subscription::observe(SubscriptionObserver::class);
        Renewal::observe(RenewalObserver::class);
        Receipt::observe(ReceiptObserver::class);

        $this->configureDynamicMail();
    }

    /**
     * Dynamically override mail configuration with App Settings.
     */
    protected function configureDynamicMail(): void
    {
        try {
            if (Schema::hasTable('settings')) {
                $fromEmail = Setting::get('contact_email') ?: Setting::get('business_email');
                $fromName = Setting::get('sender_name') ?: Setting::get('business_name') ?: Setting::get('app_name');

                if ($fromEmail) {
                    Config::set('mail.from.address', $fromEmail);
                }
                if ($fromName) {
                    Config::set('mail.from.name', $fromName);
                }
            }
        } catch (\Exception $e) {
            // Silently fail if database is offline or migrating
        }
    }

    /**
     * Register listeners for the Activity Log.
     */
    protected function registerActivityListeners(): void
    {
        Event::listen(Login::class, function (Login $event) {
            /** @var User $user */
            $user = $event->user;
            $now = now();

            $user->update([
                'last_login_at' => $now,
                'invitation_accepted_at' => $user->invitation_accepted_at ?? $now,
            ]);

            app(ActivityLogService::class)->logAuth('login', "User {$user->email} logged in");
        });

        Event::listen(Logout::class, function (Logout $event) {
            /** @var User|null $logoutUser */
            $logoutUser = $event->user;
            if ($logoutUser) {
                app(ActivityLogService::class)->logAuth('logout', "User {$logoutUser->email} logged out");
            }
        });

        Event::listen(PasswordReset::class, function (PasswordReset $event) {
            /** @var User $resetUser */
            $resetUser = $event->user;
            app(ActivityLogService::class)->logAuth('password_reset', "User {$resetUser->email} reset their password");
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        Model::preventLazyLoading(! app()->isProduction());
        Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation): void {
            Log::warning("Lazy loading violation: [{$relation}] on ".$model::class);
        });

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
