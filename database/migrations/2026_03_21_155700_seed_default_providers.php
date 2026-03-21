<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $providers = [
            // Hosting
            ['name' => 'AWS', 'website' => 'https://aws.amazon.com', 'support_email' => null],
            ['name' => 'DigitalOcean', 'website' => 'https://www.digitalocean.com', 'support_email' => null],
            ['name' => 'Google Cloud', 'website' => 'https://cloud.google.com', 'support_email' => null],
            ['name' => 'Microsoft Azure', 'website' => 'https://azure.microsoft.com', 'support_email' => null],
            ['name' => 'Hetzner', 'website' => 'https://www.hetzner.com', 'support_email' => null],
            ['name' => 'Linode', 'website' => 'https://www.linode.com', 'support_email' => null],
            ['name' => 'Vultr', 'website' => 'https://www.vultr.com', 'support_email' => null],
            ['name' => 'Hostinger', 'website' => 'https://www.hostinger.com', 'support_email' => null],
            ['name' => 'Bluehost', 'website' => 'https://www.bluehost.com', 'support_email' => null],
            ['name' => 'SiteGround', 'website' => 'https://www.siteground.com', 'support_email' => null],
            ['name' => 'GoDaddy', 'website' => 'https://www.godaddy.com', 'support_email' => null],
            ['name' => 'Namecheap', 'website' => 'https://www.namecheap.com', 'support_email' => null],

            // Domain Registrars
            ['name' => 'Cloudflare', 'website' => 'https://www.cloudflare.com', 'support_email' => null],
            ['name' => 'Google Domains', 'website' => 'https://domains.google', 'support_email' => null],
            ['name' => 'Porkbun', 'website' => 'https://porkbun.com', 'support_email' => null],

            // SSL
            ['name' => "Let's Encrypt", 'website' => 'https://letsencrypt.org', 'support_email' => null],
            ['name' => 'DigiCert', 'website' => 'https://www.digicert.com', 'support_email' => null],
            ['name' => 'Comodo SSL', 'website' => 'https://www.comodo.com', 'support_email' => null],

            // Email
            ['name' => 'Zoho Mail', 'website' => 'https://www.zoho.com/mail/', 'support_email' => null],
            ['name' => 'Google Workspace', 'website' => 'https://workspace.google.com', 'support_email' => null],
            ['name' => 'Microsoft 365', 'website' => 'https://www.microsoft.com/microsoft-365', 'support_email' => null],

            // CDN / Performance
            ['name' => 'Fastly', 'website' => 'https://www.fastly.com', 'support_email' => null],
            ['name' => 'KeyCDN', 'website' => 'https://www.keycdn.com', 'support_email' => null],

            // Managed Platforms
            ['name' => 'Vercel', 'website' => 'https://vercel.com', 'support_email' => null],
            ['name' => 'Netlify', 'website' => 'https://www.netlify.com', 'support_email' => null],
            ['name' => 'Laravel Forge', 'website' => 'https://forge.laravel.com', 'support_email' => null],
            ['name' => 'Laravel Vapor', 'website' => 'https://vapor.laravel.com', 'support_email' => null],
            ['name' => 'Ploi', 'website' => 'https://ploi.io', 'support_email' => null],
            ['name' => 'RunCloud', 'website' => 'https://runcloud.io', 'support_email' => null],

            // Monitoring
            ['name' => 'UptimeRobot', 'website' => 'https://uptimerobot.com', 'support_email' => null],
        ];

        $now = now();

        foreach ($providers as $provider) {
            DB::table('providers')->insertOrIgnore(array_merge($provider, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('providers')->whereIn('name', [
            'AWS', 'DigitalOcean', 'Google Cloud', 'Microsoft Azure', 'Hetzner',
            'Linode', 'Vultr', 'Hostinger', 'Bluehost', 'SiteGround', 'GoDaddy',
            'Namecheap', 'Cloudflare', 'Google Domains', 'Porkbun',
            "Let's Encrypt", 'DigiCert', 'Comodo SSL',
            'Zoho Mail', 'Google Workspace', 'Microsoft 365',
            'Fastly', 'KeyCDN',
            'Vercel', 'Netlify', 'Laravel Forge', 'Laravel Vapor', 'Ploi', 'RunCloud',
            'UptimeRobot',
        ])->delete();
    }
};
