# SubTrack — Client Subscription & Asset Manager

SubTrack is a private internal tool for web agencies to track client subscriptions (domains, hosting, SSL, maintenance), automate renewal reminders, and generate professional USD invoices.

## 🚀 Installation

1. Clone the repository
2. Install dependencies: `composer install && npm install`
3. Copy environment file: `cp .env.example .env`
4. Generate key: `php artisan key:generate`
5. Configure database in `.env`
6. Run migrations: `php artisan migrate`
7. Link storage: `php artisan storage:link`
8. Build assets: `npm run build`

## 📅 Automation (CRON)

To enable automatic subscription expiry checks and email notifications, you must add the following entry to your server's crontab:

```cron
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

This will trigger the `subtrack:check-expiries` command daily as configured in `routes/console.php`.

## 🛠️ Features

- **Dashboard**: High-level overview of critical and warning expiries.
- **Client & Project Management**: Organize assets by client.
- **Subscription Tracking**: Monitor domains, hosting, and SSL with traffic-light status.
- **Renewal Tracker**: Track provider vs client costs and payment status.
- **Invoicing**: Generate professional PDF invoices and send them via email.
- **Settings**: Configure agency details, logos, and invoice numbering.

## 🏗️ Tech Stack

- Laravel 13
- Livewire 4
- FlyonUI (Tailwind CSS v4)
- DomPDF
