<?php

namespace Database\Seeders;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\ServiceType;
use App\Enums\SubscriptionStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Project;
use App\Models\Provider;
use App\Models\Renewal;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class RealisticDataSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Providers ──────────────────────────────────────────────
        $providers = collect([
            ['name' => 'Namecheap',        'website' => 'https://www.namecheap.com',        'support_email' => 'support@namecheap.com'],
            ['name' => 'Cloudflare',       'website' => 'https://www.cloudflare.com',       'support_email' => 'support@cloudflare.com'],
            ['name' => 'DigitalOcean',     'website' => 'https://www.digitalocean.com',     'support_email' => 'support@digitalocean.com'],
            ['name' => 'AWS',              'website' => 'https://aws.amazon.com',           'support_email' => 'aws-support@amazon.com'],
            ['name' => 'Hetzner',          'website' => 'https://www.hetzner.com',          'support_email' => 'support@hetzner.com'],
            ['name' => 'GoDaddy',          'website' => 'https://www.godaddy.com',          'support_email' => 'support@godaddy.com'],
            ['name' => 'Google Workspace', 'website' => 'https://workspace.google.com',     'support_email' => 'workspace-support@google.com'],
            ['name' => "Let's Encrypt",    'website' => 'https://letsencrypt.org',          'support_email' => null],
            ['name' => 'Vercel',           'website' => 'https://vercel.com',               'support_email' => 'support@vercel.com'],
            ['name' => 'Hostinger',        'website' => 'https://www.hostinger.com',        'support_email' => 'support@hostinger.com'],
        ])->map(fn ($p) => Provider::create($p));

        $namecheap    = $providers[0];
        $cloudflare   = $providers[1];
        $digitalOcean = $providers[2];
        $aws          = $providers[3];
        $hetzner      = $providers[4];
        $godaddy      = $providers[5];
        $google       = $providers[6];
        $letsEncrypt  = $providers[7];
        $vercel       = $providers[8];
        $hostinger    = $providers[9];

        // ─── Clients ────────────────────────────────────────────────
        $clientsData = [
            ['name' => 'James Mensah',      'email' => 'james@mensahlaw.com',         'phone' => '+233 24 123 4567', 'company_name' => 'Mensah & Associates Law Firm'],
            ['name' => 'Ama Serwaa',        'email' => 'ama@goldcoastlogistics.com',   'phone' => '+233 50 987 6543', 'company_name' => 'Gold Coast Logistics Ltd'],
            ['name' => 'Dr. Kwame Asante',  'email' => 'kwame@asanteclinic.com',       'phone' => '+233 20 555 1234', 'company_name' => 'Asante Medical Centre'],
            ['name' => 'Efua Owusu',        'email' => 'efua@cantonments.edu.gh',      'phone' => '+233 27 333 7890', 'company_name' => 'Cantonments International School'],
            ['name' => 'Michael Thompson',  'email' => 'michael@brightondev.co.uk',    'phone' => '+44 7700 900123',  'company_name' => 'Brighton Development Group'],
            ['name' => 'Fatima Al-Hassan',  'email' => 'fatima@habesha-foods.com',     'phone' => '+233 54 222 8910', 'company_name' => 'Habesha Foods Ghana'],
            ['name' => 'Sarah Okonkwo',     'email' => 'sarah@lagoscreatives.ng',      'phone' => '+234 802 345 6789','company_name' => 'Lagos Creatives Studio'],
            ['name' => 'Daniel Quaye',      'email' => 'daniel@accraproptech.com',     'phone' => '+233 26 444 5678', 'company_name' => 'Accra PropTech Solutions'],
        ];

        $clients = collect($clientsData)->map(fn ($c) => Client::create($c));

        // ─── Projects ───────────────────────────────────────────────
        $projects = collect([
            // Client 0 — James Mensah (Law Firm)
            ['client_id' => $clients[0]->id, 'project_name' => 'Mensah Law Corporate Website',       'description' => 'Full redesign and rebuild of the firm\'s corporate website with case study portfolio and contact forms.'],
            ['client_id' => $clients[0]->id, 'project_name' => 'Client Portal App',                  'description' => 'Secure web portal for clients to track case progress, upload documents, and communicate with lawyers.'],

            // Client 1 — Ama Serwaa (Logistics)
            ['client_id' => $clients[1]->id, 'project_name' => 'Gold Coast Logistics Website',       'description' => 'Marketing site with real-time shipment tracking widget, service pages, and fleet overview.'],
            ['client_id' => $clients[1]->id, 'project_name' => 'Fleet Management Dashboard',         'description' => 'Internal dashboard to monitor vehicle locations, maintenance schedules, and delivery metrics.'],

            // Client 2 — Dr. Kwame Asante (Medical)
            ['client_id' => $clients[2]->id, 'project_name' => 'Asante Clinic Online Booking',       'description' => 'Patient-facing booking system with doctor schedules, appointment reminders, and payment integration.'],

            // Client 3 — Efua Owusu (School)
            ['client_id' => $clients[3]->id, 'project_name' => 'Cantonments School Website',         'description' => 'School website with admissions portal, news section, events calendar, and parent dashboard.'],
            ['client_id' => $clients[3]->id, 'project_name' => 'E-Learning Platform',                'description' => 'Online learning management system for remote and hybrid classes with video streaming support.'],

            // Client 4 — Michael Thompson (UK Developer)
            ['client_id' => $clients[4]->id, 'project_name' => 'Brighton Properties Marketplace',    'description' => 'Property listing platform with search filters, virtual tours, mortgage calculators, and agent profiles.'],

            // Client 5 — Fatima Al-Hassan (Foods)
            ['client_id' => $clients[5]->id, 'project_name' => 'Habesha Foods E-Commerce',           'description' => 'Online store for Ethiopian and Ghanaian specialty foods with delivery tracking and mobile checkout.'],

            // Client 6 — Sarah Okonkwo (Creatives)
            ['client_id' => $clients[6]->id, 'project_name' => 'Lagos Creatives Portfolio',          'description' => 'Portfolio showcase with project galleries, video reels, client testimonials, and booking form.'],

            // Client 7 — Daniel Quaye (PropTech)
            ['client_id' => $clients[7]->id, 'project_name' => 'Accra PropTech Platform',            'description' => 'SaaS property management platform with tenant screening, rent collection, and maintenance requests.'],
            ['client_id' => $clients[7]->id, 'project_name' => 'PropTech Marketing Site',            'description' => 'Landing page with feature highlights, pricing tiers, and demo booking for the SaaS platform.'],
        ])->map(fn ($p) => Project::create($p));

        // ─── Subscriptions ──────────────────────────────────────────
        $now = Carbon::now();

        $subscriptionsData = [
            // Mensah Law — Corporate Website
            ['project_id' => $projects[0]->id, 'service_type' => ServiceType::Domain,      'provider_id' => $namecheap->id,    'domain_name' => 'mensahlaw.com',            'purchase_date' => '2023-03-15', 'expiry_date' => $now->copy()->addDays(45)->toDateString(),  'purchase_cost_usd' => 12.98,   'renewal_cost_usd' => 14.98,   'status' => SubscriptionStatus::Active],
            ['project_id' => $projects[0]->id, 'service_type' => ServiceType::Hosting,     'provider_id' => $digitalOcean->id, 'domain_name' => null,                       'purchase_date' => '2023-03-15', 'expiry_date' => $now->copy()->addMonths(6)->toDateString(), 'purchase_cost_usd' => 144.00,  'renewal_cost_usd' => 144.00,  'status' => SubscriptionStatus::Active],
            ['project_id' => $projects[0]->id, 'service_type' => ServiceType::SSL,         'provider_id' => $letsEncrypt->id,  'domain_name' => 'mensahlaw.com',            'purchase_date' => '2024-01-10', 'expiry_date' => $now->copy()->addDays(12)->toDateString(),  'purchase_cost_usd' => 0.00,    'renewal_cost_usd' => 0.00,    'status' => SubscriptionStatus::Expiring],

            // Mensah Law — Client Portal
            ['project_id' => $projects[1]->id, 'service_type' => ServiceType::Domain,      'provider_id' => $namecheap->id,    'domain_name' => 'portal.mensahlaw.com',     'purchase_date' => '2024-06-01', 'expiry_date' => $now->copy()->addDays(190)->toDateString(), 'purchase_cost_usd' => 12.98,   'renewal_cost_usd' => 14.98,   'status' => SubscriptionStatus::Active],
            ['project_id' => $projects[1]->id, 'service_type' => ServiceType::Hosting,     'provider_id' => $aws->id,          'domain_name' => null,                       'purchase_date' => '2024-06-01', 'expiry_date' => $now->copy()->addMonths(8)->toDateString(), 'purchase_cost_usd' => 240.00,  'renewal_cost_usd' => 240.00,  'status' => SubscriptionStatus::Active],

            // Gold Coast Logistics — Website
            ['project_id' => $projects[2]->id, 'service_type' => ServiceType::Domain,      'provider_id' => $godaddy->id,      'domain_name' => 'goldcoastlogistics.com',   'purchase_date' => '2022-11-01', 'expiry_date' => $now->copy()->addDays(5)->toDateString(),   'purchase_cost_usd' => 11.99,   'renewal_cost_usd' => 18.99,   'status' => SubscriptionStatus::Expiring],
            ['project_id' => $projects[2]->id, 'service_type' => ServiceType::Hosting,     'provider_id' => $hostinger->id,    'domain_name' => null,                       'purchase_date' => '2023-01-15', 'expiry_date' => $now->copy()->addDays(65)->toDateString(),  'purchase_cost_usd' => 47.88,   'renewal_cost_usd' => 95.88,   'status' => SubscriptionStatus::Active],
            ['project_id' => $projects[2]->id, 'service_type' => ServiceType::SSL,         'provider_id' => $cloudflare->id,   'domain_name' => 'goldcoastlogistics.com',   'purchase_date' => '2023-01-15', 'expiry_date' => $now->copy()->addDays(65)->toDateString(),  'purchase_cost_usd' => 0.00,    'renewal_cost_usd' => 0.00,    'status' => SubscriptionStatus::Active],

            // Gold Coast Logistics — Fleet Dashboard
            ['project_id' => $projects[3]->id, 'service_type' => ServiceType::Hosting,     'provider_id' => $digitalOcean->id, 'domain_name' => null,                       'purchase_date' => '2024-02-01', 'expiry_date' => $now->copy()->addDays(120)->toDateString(), 'purchase_cost_usd' => 288.00,  'renewal_cost_usd' => 288.00,  'status' => SubscriptionStatus::Active],
            ['project_id' => $projects[3]->id, 'service_type' => ServiceType::Domain,      'provider_id' => $namecheap->id,    'domain_name' => 'fleet.goldcoastlogistics.com', 'purchase_date' => '2024-02-01', 'expiry_date' => $now->copy()->addDays(120)->toDateString(), 'purchase_cost_usd' => 12.98, 'renewal_cost_usd' => 14.98, 'status' => SubscriptionStatus::Active],

            // Asante Clinic — Online Booking
            ['project_id' => $projects[4]->id, 'service_type' => ServiceType::Domain,      'provider_id' => $namecheap->id,    'domain_name' => 'asanteclinic.com',         'purchase_date' => '2023-06-20', 'expiry_date' => $now->copy()->subDays(3)->toDateString(),   'purchase_cost_usd' => 12.98,   'renewal_cost_usd' => 14.98,   'status' => SubscriptionStatus::Expired],
            ['project_id' => $projects[4]->id, 'service_type' => ServiceType::Hosting,     'provider_id' => $hetzner->id,      'domain_name' => null,                       'purchase_date' => '2023-06-20', 'expiry_date' => $now->copy()->addDays(28)->toDateString(),  'purchase_cost_usd' => 59.88,   'renewal_cost_usd' => 59.88,   'status' => SubscriptionStatus::Expiring],
            ['project_id' => $projects[4]->id, 'service_type' => ServiceType::Maintenance, 'provider_id' => null,              'domain_name' => null,                       'purchase_date' => '2024-01-01', 'expiry_date' => $now->copy()->addDays(90)->toDateString(),  'purchase_cost_usd' => 600.00,  'renewal_cost_usd' => 600.00,  'status' => SubscriptionStatus::Active],

            // Cantonments School — Website
            ['project_id' => $projects[5]->id, 'service_type' => ServiceType::Domain,      'provider_id' => $cloudflare->id,   'domain_name' => 'cantonments.edu.gh',       'purchase_date' => '2022-09-01', 'expiry_date' => $now->copy()->addDays(200)->toDateString(), 'purchase_cost_usd' => 25.00,   'renewal_cost_usd' => 25.00,   'status' => SubscriptionStatus::Active],
            ['project_id' => $projects[5]->id, 'service_type' => ServiceType::Hosting,     'provider_id' => $digitalOcean->id, 'domain_name' => null,                       'purchase_date' => '2022-09-01', 'expiry_date' => $now->copy()->addDays(200)->toDateString(), 'purchase_cost_usd' => 168.00,  'renewal_cost_usd' => 168.00,  'status' => SubscriptionStatus::Active],

            // Cantonments School — E-Learning
            ['project_id' => $projects[6]->id, 'service_type' => ServiceType::Hosting,     'provider_id' => $aws->id,          'domain_name' => null,                       'purchase_date' => '2024-01-15', 'expiry_date' => $now->copy()->addDays(300)->toDateString(), 'purchase_cost_usd' => 480.00,  'renewal_cost_usd' => 480.00,  'status' => SubscriptionStatus::Active],
            ['project_id' => $projects[6]->id, 'service_type' => ServiceType::Domain,      'provider_id' => $namecheap->id,    'domain_name' => 'learn.cantonments.edu.gh', 'purchase_date' => '2024-01-15', 'expiry_date' => $now->copy()->addDays(300)->toDateString(), 'purchase_cost_usd' => 12.98,   'renewal_cost_usd' => 14.98,   'status' => SubscriptionStatus::Active],

            // Brighton Properties
            ['project_id' => $projects[7]->id, 'service_type' => ServiceType::Domain,      'provider_id' => $namecheap->id,    'domain_name' => 'brightonproperties.co.uk', 'purchase_date' => '2023-04-01', 'expiry_date' => $now->copy()->addDays(15)->toDateString(),  'purchase_cost_usd' => 9.98,    'renewal_cost_usd' => 12.98,   'status' => SubscriptionStatus::Expiring],
            ['project_id' => $projects[7]->id, 'service_type' => ServiceType::Hosting,     'provider_id' => $vercel->id,       'domain_name' => null,                       'purchase_date' => '2023-04-01', 'expiry_date' => $now->copy()->addDays(180)->toDateString(), 'purchase_cost_usd' => 240.00,  'renewal_cost_usd' => 240.00,  'status' => SubscriptionStatus::Active],
            ['project_id' => $projects[7]->id, 'service_type' => ServiceType::SSL,         'provider_id' => $cloudflare->id,   'domain_name' => 'brightonproperties.co.uk', 'purchase_date' => '2023-04-01', 'expiry_date' => $now->copy()->addDays(180)->toDateString(), 'purchase_cost_usd' => 0.00,    'renewal_cost_usd' => 0.00,    'status' => SubscriptionStatus::Active],

            // Habesha Foods — E-Commerce
            ['project_id' => $projects[8]->id, 'service_type' => ServiceType::Domain,      'provider_id' => $godaddy->id,      'domain_name' => 'habesha-foods.com',        'purchase_date' => '2023-08-10', 'expiry_date' => $now->copy()->addDays(140)->toDateString(), 'purchase_cost_usd' => 12.99,   'renewal_cost_usd' => 19.99,   'status' => SubscriptionStatus::Active],
            ['project_id' => $projects[8]->id, 'service_type' => ServiceType::Hosting,     'provider_id' => $digitalOcean->id, 'domain_name' => null,                       'purchase_date' => '2023-08-10', 'expiry_date' => $now->copy()->addDays(140)->toDateString(), 'purchase_cost_usd' => 168.00,  'renewal_cost_usd' => 168.00,  'status' => SubscriptionStatus::Active],
            ['project_id' => $projects[8]->id, 'service_type' => ServiceType::Maintenance, 'provider_id' => null,              'domain_name' => null,                       'purchase_date' => '2024-01-01', 'expiry_date' => $now->copy()->addDays(60)->toDateString(),  'purchase_cost_usd' => 1200.00, 'renewal_cost_usd' => 1200.00, 'status' => SubscriptionStatus::Active],

            // Lagos Creatives — Portfolio
            ['project_id' => $projects[9]->id, 'service_type' => ServiceType::Domain,      'provider_id' => $namecheap->id,    'domain_name' => 'lagoscreatives.ng',        'purchase_date' => '2024-03-01', 'expiry_date' => $now->copy()->addDays(340)->toDateString(), 'purchase_cost_usd' => 18.98,   'renewal_cost_usd' => 18.98,   'status' => SubscriptionStatus::Active],
            ['project_id' => $projects[9]->id, 'service_type' => ServiceType::Hosting,     'provider_id' => $vercel->id,       'domain_name' => null,                       'purchase_date' => '2024-03-01', 'expiry_date' => $now->copy()->addDays(340)->toDateString(), 'purchase_cost_usd' => 240.00,  'renewal_cost_usd' => 240.00,  'status' => SubscriptionStatus::Active],

            // Accra PropTech — Platform
            ['project_id' => $projects[10]->id, 'service_type' => ServiceType::Domain,     'provider_id' => $cloudflare->id,   'domain_name' => 'accraproptech.com',        'purchase_date' => '2023-09-01', 'expiry_date' => $now->copy()->addDays(160)->toDateString(), 'purchase_cost_usd' => 10.98,   'renewal_cost_usd' => 10.98,   'status' => SubscriptionStatus::Active],
            ['project_id' => $projects[10]->id, 'service_type' => ServiceType::Hosting,    'provider_id' => $aws->id,          'domain_name' => null,                       'purchase_date' => '2023-09-01', 'expiry_date' => $now->copy()->addDays(160)->toDateString(), 'purchase_cost_usd' => 720.00,  'renewal_cost_usd' => 720.00,  'status' => SubscriptionStatus::Active],
            ['project_id' => $projects[10]->id, 'service_type' => ServiceType::SSL,        'provider_id' => $cloudflare->id,   'domain_name' => 'accraproptech.com',        'purchase_date' => '2024-01-01', 'expiry_date' => $now->copy()->addDays(160)->toDateString(), 'purchase_cost_usd' => 0.00,    'renewal_cost_usd' => 0.00,    'status' => SubscriptionStatus::Active],
            ['project_id' => $projects[10]->id, 'service_type' => ServiceType::Maintenance,'provider_id' => null,              'domain_name' => null,                       'purchase_date' => '2024-01-01', 'expiry_date' => $now->copy()->addDays(30)->toDateString(),  'purchase_cost_usd' => 2400.00, 'renewal_cost_usd' => 2400.00, 'status' => SubscriptionStatus::Expiring],

            // Accra PropTech — Marketing Site
            ['project_id' => $projects[11]->id, 'service_type' => ServiceType::Domain,     'provider_id' => $namecheap->id,    'domain_name' => 'getproptech.com',          'purchase_date' => '2024-05-01', 'expiry_date' => $now->copy()->addDays(400)->toDateString(), 'purchase_cost_usd' => 12.98,   'renewal_cost_usd' => 14.98,   'status' => SubscriptionStatus::Active],
            ['project_id' => $projects[11]->id, 'service_type' => ServiceType::Hosting,    'provider_id' => $vercel->id,       'domain_name' => null,                       'purchase_date' => '2024-05-01', 'expiry_date' => $now->copy()->addDays(400)->toDateString(), 'purchase_cost_usd' => 0.00,    'renewal_cost_usd' => 0.00,    'status' => SubscriptionStatus::Active],
        ];

        $subs = collect($subscriptionsData)->map(fn ($s) => Subscription::create($s));

        // ─── Renewals (historical records) ──────────────────────────
        $renewalsData = [
            ['subscription_id' => $subs[0]->id, 'due_date' => '2024-03-15', 'provider_cost_usd' => 14.98, 'client_cost_usd' => 25.00, 'payment_status' => PaymentStatus::Paid, 'payment_received_date' => '2024-03-10', 'renewal_confirmed_date' => '2024-03-12', 'notes' => 'Domain renewed for 1 year'],
            ['subscription_id' => $subs[1]->id, 'due_date' => '2024-03-15', 'provider_cost_usd' => 144.00, 'client_cost_usd' => 200.00, 'payment_status' => PaymentStatus::Paid, 'payment_received_date' => '2024-03-12', 'renewal_confirmed_date' => '2024-03-13', 'notes' => 'DigitalOcean droplet annual renewal'],
            ['subscription_id' => $subs[5]->id, 'due_date' => '2024-11-01', 'provider_cost_usd' => 18.99, 'client_cost_usd' => 30.00, 'payment_status' => PaymentStatus::Pending, 'payment_received_date' => null, 'renewal_confirmed_date' => null, 'notes' => 'Domain expiring soon — awaiting client payment'],
            ['subscription_id' => $subs[10]->id, 'due_date' => '2025-06-17', 'provider_cost_usd' => 14.98, 'client_cost_usd' => 25.00, 'payment_status' => PaymentStatus::Lapsed, 'payment_received_date' => null, 'renewal_confirmed_date' => null, 'notes' => 'Domain expired — client unresponsive'],
            ['subscription_id' => $subs[11]->id, 'due_date' => '2025-07-15', 'provider_cost_usd' => 59.88, 'client_cost_usd' => 100.00, 'payment_status' => PaymentStatus::Invoiced, 'payment_received_date' => null, 'renewal_confirmed_date' => null, 'notes' => 'Invoice sent to Dr. Asante'],
            ['subscription_id' => $subs[18]->id, 'due_date' => '2024-04-01', 'provider_cost_usd' => 12.98, 'client_cost_usd' => 20.00, 'payment_status' => PaymentStatus::Paid, 'payment_received_date' => '2024-03-28', 'renewal_confirmed_date' => '2024-03-29', 'notes' => 'Renewed - Brighton Properties domain'],
            ['subscription_id' => $subs[22]->id, 'due_date' => '2024-08-10', 'provider_cost_usd' => 19.99, 'client_cost_usd' => 35.00, 'payment_status' => PaymentStatus::Paid, 'payment_received_date' => '2024-08-05', 'renewal_confirmed_date' => '2024-08-06', 'notes' => 'Habesha Foods domain renewal'],
            ['subscription_id' => $subs[30]->id, 'due_date' => '2025-07-01', 'provider_cost_usd' => 2400.00, 'client_cost_usd' => 3600.00, 'payment_status' => PaymentStatus::Invoiced, 'payment_received_date' => null, 'renewal_confirmed_date' => null, 'notes' => 'Annual maintenance contract renewal pending'],
        ];

        $renewals = collect($renewalsData)->map(fn ($r) => Renewal::create($r));

        // ─── Invoices ───────────────────────────────────────────────
        $invoicesData = [
            // Mensah Law — paid invoice
            [
                'client_id' => $clients[0]->id,
                'project_id' => $projects[0]->id,
                'invoice_number' => 'INV-2024-001',
                'issued_date' => '2024-03-01',
                'due_date' => '2024-03-15',
                'tax_rate' => 0,
                'tax_amount' => 0,
                'subtotal' => 225.00,
                'total_amount' => 225.00,
                'status' => InvoiceStatus::Paid,
            ],
            // Gold Coast Logistics — sent (pending payment)
            [
                'client_id' => $clients[1]->id,
                'project_id' => $projects[2]->id,
                'invoice_number' => 'INV-2025-002',
                'issued_date' => $now->copy()->subDays(5)->toDateString(),
                'due_date' => $now->copy()->addDays(9)->toDateString(),
                'tax_rate' => 0,
                'tax_amount' => 0,
                'subtotal' => 30.00,
                'total_amount' => 30.00,
                'status' => InvoiceStatus::Sent,
            ],
            // Asante Clinic — overdue
            [
                'client_id' => $clients[2]->id,
                'project_id' => $projects[4]->id,
                'invoice_number' => 'INV-2025-003',
                'issued_date' => $now->copy()->subDays(20)->toDateString(),
                'due_date' => $now->copy()->subDays(6)->toDateString(),
                'tax_rate' => 0,
                'tax_amount' => 0,
                'subtotal' => 125.00,
                'total_amount' => 125.00,
                'status' => InvoiceStatus::Overdue,
            ],
            // Brighton Dev — draft
            [
                'client_id' => $clients[4]->id,
                'project_id' => $projects[7]->id,
                'invoice_number' => 'INV-2025-004',
                'issued_date' => $now->copy()->toDateString(),
                'due_date' => $now->copy()->addDays(14)->toDateString(),
                'tax_rate' => 20.00,
                'tax_amount' => 4.00,
                'subtotal' => 20.00,
                'total_amount' => 24.00,
                'status' => InvoiceStatus::Draft,
            ],
            // Accra PropTech — paid
            [
                'client_id' => $clients[7]->id,
                'project_id' => $projects[10]->id,
                'invoice_number' => 'INV-2024-005',
                'issued_date' => '2024-09-01',
                'due_date' => '2024-09-15',
                'tax_rate' => 0,
                'tax_amount' => 0,
                'subtotal' => 750.00,
                'total_amount' => 750.00,
                'status' => InvoiceStatus::Paid,
            ],
            // Accra PropTech — maintenance renewal sent
            [
                'client_id' => $clients[7]->id,
                'project_id' => $projects[10]->id,
                'invoice_number' => 'INV-2025-006',
                'issued_date' => $now->copy()->subDays(10)->toDateString(),
                'due_date' => $now->copy()->addDays(4)->toDateString(),
                'tax_rate' => 0,
                'tax_amount' => 0,
                'subtotal' => 3600.00,
                'total_amount' => 3600.00,
                'status' => InvoiceStatus::Sent,
            ],
        ];

        $invoices = collect($invoicesData)->map(fn ($i) => Invoice::create($i));

        // ─── Invoice Items ──────────────────────────────────────────
        $invoiceItemsData = [
            // INV-2024-001 items
            ['invoice_id' => $invoices[0]->id, 'description' => 'Domain Renewal — mensahlaw.com',          'period' => 'Mar 2024 – Mar 2025', 'quantity' => 1, 'unit_price' => 25.00,   'total' => 25.00],
            ['invoice_id' => $invoices[0]->id, 'description' => 'VPS Hosting — DigitalOcean Droplet',      'period' => 'Mar 2024 – Mar 2025', 'quantity' => 1, 'unit_price' => 200.00,  'total' => 200.00],

            // INV-2025-002 items
            ['invoice_id' => $invoices[1]->id, 'description' => 'Domain Renewal — goldcoastlogistics.com', 'period' => 'Urgent Renewal',       'quantity' => 1, 'unit_price' => 30.00,   'total' => 30.00],

            // INV-2025-003 items
            ['invoice_id' => $invoices[2]->id, 'description' => 'Domain Renewal — asanteclinic.com',       'period' => 'Jun 2025 – Jun 2026',  'quantity' => 1, 'unit_price' => 25.00,   'total' => 25.00],
            ['invoice_id' => $invoices[2]->id, 'description' => 'Hosting Renewal — Hetzner Cloud',         'period' => 'Jul 2025 – Jul 2026',  'quantity' => 1, 'unit_price' => 100.00,  'total' => 100.00],

            // INV-2025-004 items
            ['invoice_id' => $invoices[3]->id, 'description' => 'Domain Renewal — brightonproperties.co.uk','period' => 'Apr 2025 – Apr 2026', 'quantity' => 1, 'unit_price' => 20.00,   'total' => 20.00],

            // INV-2024-005 items
            ['invoice_id' => $invoices[4]->id, 'description' => 'Domain Registration — accraproptech.com', 'period' => 'Sep 2024 – Sep 2025',  'quantity' => 1, 'unit_price' => 15.00,   'total' => 15.00],
            ['invoice_id' => $invoices[4]->id, 'description' => 'AWS Hosting Setup & First Year',           'period' => 'Sep 2024 – Sep 2025',  'quantity' => 1, 'unit_price' => 720.00,  'total' => 720.00],
            ['invoice_id' => $invoices[4]->id, 'description' => 'Cloudflare SSL Certificate',               'period' => 'Sep 2024 – Sep 2025',  'quantity' => 1, 'unit_price' => 15.00,   'total' => 15.00],

            // INV-2025-006 items
            ['invoice_id' => $invoices[5]->id, 'description' => 'Annual Maintenance Contract — PropTech Platform', 'period' => 'Jul 2025 – Jul 2026', 'quantity' => 1, 'unit_price' => 3600.00, 'total' => 3600.00],
        ];

        foreach ($invoiceItemsData as $item) {
            InvoiceItem::create($item);
        }

        $this->command->info('✅ Seeded: 10 Providers, 8 Clients, 12 Projects, ' . $subs->count() . ' Subscriptions, ' . count($renewalsData) . ' Renewals, ' . count($invoicesData) . ' Invoices, ' . count($invoiceItemsData) . ' Invoice Items');
    }
}
