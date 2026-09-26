<?php

namespace Database\Seeders;

use App\Enums\UserRole;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password'),
                'role' => UserRole::SuperAdmin,
            ]
        );

        $defaults = [
            'business_name' => '',
            'business_email' => '',
            'business_phone' => '',
            'business_website' => '',
            'logo_url' => '',
            'bank_name' => '',
            'bank_account_name' => '',
            'bank_account_number' => '',
            'paypal_email' => '',
            'invoice_due_days' => '14',
            'sender_name' => '',
            'sender_title' => '',
            'app_name' => 'SubTrack',
            'reminder_days' => '30,14,7',
            'penalty_percentage' => '5',
            'grace_period_days' => '14',
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }

        $this->call([
            RealisticDataSeeder::class,
            ExtraDataSeeder::class,
            MassiveDataSeeder::class,
            MailTemplateSeeder::class,
            MonthlyRenewalTestDataSeeder::class,
        ]);
    }
}
