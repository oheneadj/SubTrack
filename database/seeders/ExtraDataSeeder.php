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

class ExtraDataSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // Grab existing providers
        $namecheap = Provider::where('name', 'Namecheap')->first();
        $cloudflare = Provider::where('name', 'Cloudflare')->first();
        $digitalOcean = Provider::where('name', 'DigitalOcean')->first();
        $aws = Provider::where('name', 'AWS')->first();
        $hetzner = Provider::where('name', 'Hetzner')->first();
        $godaddy = Provider::where('name', 'GoDaddy')->first();
        $vercel = Provider::where('name', 'Vercel')->first();
        $hostinger = Provider::where('name', 'Hostinger')->first();
        $letsEncrypt = Provider::where('name', "Let's Encrypt")->first();

        // New Providers
        $linode = Provider::create(['name' => 'Linode (Akamai)', 'website' => 'https://www.linode.com', 'support_email' => 'support@linode.com']);
        $netlify = Provider::create(['name' => 'Netlify', 'website' => 'https://www.netlify.com', 'support_email' => 'support@netlify.com']);
        $siteground = Provider::create(['name' => 'SiteGround', 'website' => 'https://www.siteground.com', 'support_email' => 'support@siteground.com']);
        $ovh = Provider::create(['name' => 'OVHcloud', 'website' => 'https://www.ovhcloud.com', 'support_email' => 'support@ovhcloud.com']);
        $porkbun = Provider::create(['name' => 'Porkbun', 'website' => 'https://porkbun.com', 'support_email' => 'support@porkbun.com']);

        // ─── More Clients ───────────────────────────────────────────
        $c9  = Client::create(['name' => 'Kofi Adjei',         'email' => 'kofi@adjeitech.com',        'phone' => '+233 24 887 3210', 'company_name' => 'Adjei Tech Solutions']);
        $c10 = Client::create(['name' => 'Grace Nkrumah',      'email' => 'grace@goldenstitch.com',     'phone' => '+233 50 112 4455', 'company_name' => 'Golden Stitch Fashion House']);
        $c11 = Client::create(['name' => 'Emmanuel Tetteh',    'email' => 'emmanuel@tetraengr.com',     'phone' => '+233 20 667 8901', 'company_name' => 'Tetra Engineering Ltd']);
        $c12 = Client::create(['name' => 'Abigail Mensah',     'email' => 'abigail@kumasibrews.com',    'phone' => '+233 27 998 3344', 'company_name' => 'Kumasi Craft Brews']);
        $c13 = Client::create(['name' => 'Samuel Darko',       'email' => 'samuel@darkoauto.com',       'phone' => '+233 54 776 2288', 'company_name' => 'Darko Auto Group']);
        $c14 = Client::create(['name' => 'Priscilla Boateng',  'email' => 'priscilla@eduvault.com',     'phone' => '+233 26 334 5566', 'company_name' => 'EduVault Online Academy']);
        $c15 = Client::create(['name' => 'Nana Yaw Owusu',     'email' => 'nana@owusuagritech.com',     'phone' => '+233 24 556 7890', 'company_name' => 'Owusu AgriTech']);
        $c16 = Client::create(['name' => 'Akua Frimpong',      'email' => 'akua@healthplusgh.com',      'phone' => '+233 50 889 1122', 'company_name' => 'HealthPlus Pharmacy']);
        $c17 = Client::create(['name' => 'Yaw Boadu',          'email' => 'yaw@boadu-events.com',       'phone' => '+233 20 443 2211', 'company_name' => 'Boadu Events & Catering']);
        $c18 = Client::create(['name' => 'Linda Agyapong',     'email' => 'linda@sunrisehotels.com',    'phone' => '+233 27 667 9988', 'company_name' => 'Sunrise Hotels & Resorts']);
        $c19 = Client::create(['name' => 'Richard Ampofo',     'email' => 'richard@ampofolaw.com',      'phone' => '+233 54 223 4455', 'company_name' => 'Ampofo Legal Chambers']);
        $c20 = Client::create(['name' => 'Cecilia Antwi',      'email' => 'cecilia@pixelarts.com.gh',   'phone' => '+233 26 112 6677', 'company_name' => 'PixelArts Design Studio']);

        // ─── More Projects ──────────────────────────────────────────
        $p13 = Project::create(['client_id' => $c9->id,  'project_name' => 'Adjei Tech Corporate Site',       'description' => 'Technology consultancy website with services, blog, and client case studies.']);
        $p14 = Project::create(['client_id' => $c9->id,  'project_name' => 'IT Support Ticketing System',     'description' => 'Internal helpdesk for managing IT support tickets across client organizations.']);
        $p15 = Project::create(['client_id' => $c10->id, 'project_name' => 'Golden Stitch Online Store',      'description' => 'Full e-commerce store for bespoke African fashion with custom sizing and international shipping.']);
        $p16 = Project::create(['client_id' => $c11->id, 'project_name' => 'Tetra Engineering Website',       'description' => 'Project portfolio site for civil and structural engineering firm with 3D renders.']);
        $p17 = Project::create(['client_id' => $c11->id, 'project_name' => 'Project Management Portal',       'description' => 'Client-facing portal for tracking construction milestones and document sharing.']);
        $p18 = Project::create(['client_id' => $c12->id, 'project_name' => 'Kumasi Brews Online',             'description' => 'Craft beer e-commerce with age verification, tasting notes, and subscription boxes.']);
        $p19 = Project::create(['client_id' => $c13->id, 'project_name' => 'Darko Auto Dealership Website',   'description' => 'Vehicle inventory listing with financing calculator and test drive booking.']);
        $p20 = Project::create(['client_id' => $c13->id, 'project_name' => 'Service Center Booking App',      'description' => 'Online booking system for vehicle maintenance, oil changes, and inspections.']);
        $p21 = Project::create(['client_id' => $c14->id, 'project_name' => 'EduVault Learning Platform',      'description' => 'Online course platform with video streaming, quizzes, and certificate generation.']);
        $p22 = Project::create(['client_id' => $c14->id, 'project_name' => 'EduVault Marketing Website',      'description' => 'Landing page with course previews, testimonials, and instructor profiles.']);
        $p23 = Project::create(['client_id' => $c15->id, 'project_name' => 'Owusu AgriTech Platform',         'description' => 'Agricultural marketplace connecting farmers with buyers, with crop tracking dashboard.']);
        $p24 = Project::create(['client_id' => $c16->id, 'project_name' => 'HealthPlus Pharmacy Website',     'description' => 'Online pharmacy with prescription uploads, delivery tracking, and health blog.']);
        $p25 = Project::create(['client_id' => $c16->id, 'project_name' => 'Medication Inventory System',     'description' => 'Internal inventory management for multiple pharmacy branches.']);
        $p26 = Project::create(['client_id' => $c17->id, 'project_name' => 'Boadu Events Portfolio',          'description' => 'Event planning showcase with galleries, packages, and inquiry forms.']);
        $p27 = Project::create(['client_id' => $c18->id, 'project_name' => 'Sunrise Hotels Booking Site',     'description' => 'Hotel booking engine with room galleries, seasonal pricing, and guest reviews.']);
        $p28 = Project::create(['client_id' => $c18->id, 'project_name' => 'Hotel Management Dashboard',      'description' => 'Internal dashboard for housekeeping, guest check-in/out, and revenue reports.']);
        $p29 = Project::create(['client_id' => $c19->id, 'project_name' => 'Ampofo Legal Website',            'description' => 'Law firm site with practice areas, attorney profiles, and consultation booking.']);
        $p30 = Project::create(['client_id' => $c20->id, 'project_name' => 'PixelArts Design Portfolio',      'description' => 'Creative agency portfolio with Dribbble-style project showcases and contact form.']);
        $p31 = Project::create(['client_id' => $c20->id, 'project_name' => 'Brand Asset Manager',             'description' => 'Client portal for managing brand assets, style guides, and design deliverables.']);

        // ─── Subscriptions for new projects ─────────────────────────
        $allSubs = [];
        $subsData = [
            // Adjei Tech
            ['project_id' => $p13->id, 'service_type' => ServiceType::Domain,  'provider_id' => $porkbun->id,     'domain_name' => 'adjeitech.com',          'purchase_date' => '2023-05-10', 'expiry_date' => $now->copy()->addDays(55)->toDateString(),  'purchase_cost_usd' => 8.56,    'renewal_cost_usd' => 9.73,    'status' => SubscriptionStatus::Active],
            ['project_id' => $p13->id, 'service_type' => ServiceType::Hosting, 'provider_id' => $linode->id,      'domain_name' => null,                     'purchase_date' => '2023-05-10', 'expiry_date' => $now->copy()->addDays(55)->toDateString(),  'purchase_cost_usd' => 120.00,  'renewal_cost_usd' => 120.00,  'status' => SubscriptionStatus::Active],
            ['project_id' => $p13->id, 'service_type' => ServiceType::SSL,     'provider_id' => $letsEncrypt->id, 'domain_name' => 'adjeitech.com',          'purchase_date' => '2024-06-01', 'expiry_date' => $now->copy()->addDays(55)->toDateString(),  'purchase_cost_usd' => 0.00,    'renewal_cost_usd' => 0.00,    'status' => SubscriptionStatus::Active],
            ['project_id' => $p14->id, 'service_type' => ServiceType::Hosting, 'provider_id' => $digitalOcean->id,'domain_name' => null,                     'purchase_date' => '2024-08-01', 'expiry_date' => $now->copy()->addDays(135)->toDateString(), 'purchase_cost_usd' => 240.00,  'renewal_cost_usd' => 240.00,  'status' => SubscriptionStatus::Active],

            // Golden Stitch
            ['project_id' => $p15->id, 'service_type' => ServiceType::Domain,  'provider_id' => $namecheap->id,   'domain_name' => 'goldenstitch.com',       'purchase_date' => '2023-02-14', 'expiry_date' => $now->copy()->addDays(3)->toDateString(),   'purchase_cost_usd' => 12.98,   'renewal_cost_usd' => 14.98,   'status' => SubscriptionStatus::Expiring],
            ['project_id' => $p15->id, 'service_type' => ServiceType::Hosting, 'provider_id' => $siteground->id,  'domain_name' => null,                     'purchase_date' => '2023-02-14', 'expiry_date' => $now->copy()->addDays(90)->toDateString(),  'purchase_cost_usd' => 179.88,  'renewal_cost_usd' => 179.88,  'status' => SubscriptionStatus::Active],
            ['project_id' => $p15->id, 'service_type' => ServiceType::SSL,     'provider_id' => $cloudflare->id,  'domain_name' => 'goldenstitch.com',       'purchase_date' => '2023-02-14', 'expiry_date' => $now->copy()->addDays(90)->toDateString(),  'purchase_cost_usd' => 0.00,    'renewal_cost_usd' => 0.00,    'status' => SubscriptionStatus::Active],
            ['project_id' => $p15->id, 'service_type' => ServiceType::Maintenance, 'provider_id' => null,         'domain_name' => null,                     'purchase_date' => '2024-03-01', 'expiry_date' => $now->copy()->addDays(45)->toDateString(),  'purchase_cost_usd' => 900.00,  'renewal_cost_usd' => 900.00,  'status' => SubscriptionStatus::Active],

            // Tetra Engineering
            ['project_id' => $p16->id, 'service_type' => ServiceType::Domain,  'provider_id' => $cloudflare->id,  'domain_name' => 'tetraengr.com',          'purchase_date' => '2022-07-01', 'expiry_date' => $now->copy()->addDays(110)->toDateString(), 'purchase_cost_usd' => 9.15,    'renewal_cost_usd' => 9.15,    'status' => SubscriptionStatus::Active],
            ['project_id' => $p16->id, 'service_type' => ServiceType::Hosting, 'provider_id' => $hetzner->id,     'domain_name' => null,                     'purchase_date' => '2022-07-01', 'expiry_date' => $now->copy()->addDays(110)->toDateString(), 'purchase_cost_usd' => 83.88,   'renewal_cost_usd' => 83.88,   'status' => SubscriptionStatus::Active],
            ['project_id' => $p17->id, 'service_type' => ServiceType::Hosting, 'provider_id' => $aws->id,         'domain_name' => null,                     'purchase_date' => '2024-04-01', 'expiry_date' => $now->copy()->addDays(250)->toDateString(), 'purchase_cost_usd' => 360.00,  'renewal_cost_usd' => 360.00,  'status' => SubscriptionStatus::Active],
            ['project_id' => $p17->id, 'service_type' => ServiceType::Domain,  'provider_id' => $namecheap->id,   'domain_name' => 'portal.tetraengr.com',   'purchase_date' => '2024-04-01', 'expiry_date' => $now->copy()->addDays(250)->toDateString(), 'purchase_cost_usd' => 12.98,   'renewal_cost_usd' => 14.98,   'status' => SubscriptionStatus::Active],

            // Kumasi Brews
            ['project_id' => $p18->id, 'service_type' => ServiceType::Domain,  'provider_id' => $porkbun->id,     'domain_name' => 'kumasibrews.com',        'purchase_date' => '2024-01-20', 'expiry_date' => $now->copy()->addDays(305)->toDateString(), 'purchase_cost_usd' => 8.56,    'renewal_cost_usd' => 9.73,    'status' => SubscriptionStatus::Active],
            ['project_id' => $p18->id, 'service_type' => ServiceType::Hosting, 'provider_id' => $digitalOcean->id,'domain_name' => null,                     'purchase_date' => '2024-01-20', 'expiry_date' => $now->copy()->addDays(305)->toDateString(), 'purchase_cost_usd' => 168.00,  'renewal_cost_usd' => 168.00,  'status' => SubscriptionStatus::Active],

            // Darko Auto
            ['project_id' => $p19->id, 'service_type' => ServiceType::Domain,  'provider_id' => $godaddy->id,     'domain_name' => 'darkoauto.com',          'purchase_date' => '2023-06-15', 'expiry_date' => $now->copy()->subDays(10)->toDateString(),  'purchase_cost_usd' => 12.99,   'renewal_cost_usd' => 18.99,   'status' => SubscriptionStatus::Expired],
            ['project_id' => $p19->id, 'service_type' => ServiceType::Hosting, 'provider_id' => $hostinger->id,   'domain_name' => null,                     'purchase_date' => '2023-06-15', 'expiry_date' => $now->copy()->addDays(20)->toDateString(),  'purchase_cost_usd' => 47.88,   'renewal_cost_usd' => 95.88,   'status' => SubscriptionStatus::Expiring],
            ['project_id' => $p20->id, 'service_type' => ServiceType::Hosting, 'provider_id' => $linode->id,      'domain_name' => null,                     'purchase_date' => '2024-03-01', 'expiry_date' => $now->copy()->addDays(175)->toDateString(), 'purchase_cost_usd' => 192.00,  'renewal_cost_usd' => 192.00,  'status' => SubscriptionStatus::Active],

            // EduVault
            ['project_id' => $p21->id, 'service_type' => ServiceType::Domain,  'provider_id' => $cloudflare->id,  'domain_name' => 'eduvault.com',           'purchase_date' => '2023-09-01', 'expiry_date' => $now->copy()->addDays(165)->toDateString(), 'purchase_cost_usd' => 9.15,    'renewal_cost_usd' => 9.15,    'status' => SubscriptionStatus::Active],
            ['project_id' => $p21->id, 'service_type' => ServiceType::Hosting, 'provider_id' => $aws->id,         'domain_name' => null,                     'purchase_date' => '2023-09-01', 'expiry_date' => $now->copy()->addDays(165)->toDateString(), 'purchase_cost_usd' => 600.00,  'renewal_cost_usd' => 600.00,  'status' => SubscriptionStatus::Active],
            ['project_id' => $p21->id, 'service_type' => ServiceType::Maintenance, 'provider_id' => null,         'domain_name' => null,                     'purchase_date' => '2024-01-01', 'expiry_date' => $now->copy()->addDays(8)->toDateString(),   'purchase_cost_usd' => 1800.00, 'renewal_cost_usd' => 1800.00, 'status' => SubscriptionStatus::Expiring],
            ['project_id' => $p22->id, 'service_type' => ServiceType::Hosting, 'provider_id' => $netlify->id,     'domain_name' => null,                     'purchase_date' => '2024-06-01', 'expiry_date' => $now->copy()->addDays(440)->toDateString(), 'purchase_cost_usd' => 0.00,    'renewal_cost_usd' => 0.00,    'status' => SubscriptionStatus::Active],

            // Owusu AgriTech
            ['project_id' => $p23->id, 'service_type' => ServiceType::Domain,  'provider_id' => $namecheap->id,   'domain_name' => 'owusuagritech.com',      'purchase_date' => '2023-11-01', 'expiry_date' => $now->copy()->addDays(225)->toDateString(), 'purchase_cost_usd' => 12.98,   'renewal_cost_usd' => 14.98,   'status' => SubscriptionStatus::Active],
            ['project_id' => $p23->id, 'service_type' => ServiceType::Hosting, 'provider_id' => $digitalOcean->id,'domain_name' => null,                     'purchase_date' => '2023-11-01', 'expiry_date' => $now->copy()->addDays(225)->toDateString(), 'purchase_cost_usd' => 288.00,  'renewal_cost_usd' => 288.00,  'status' => SubscriptionStatus::Active],
            ['project_id' => $p23->id, 'service_type' => ServiceType::SSL,     'provider_id' => $letsEncrypt->id, 'domain_name' => 'owusuagritech.com',      'purchase_date' => '2024-01-01', 'expiry_date' => $now->copy()->addDays(225)->toDateString(), 'purchase_cost_usd' => 0.00,    'renewal_cost_usd' => 0.00,    'status' => SubscriptionStatus::Active],

            // HealthPlus Pharmacy
            ['project_id' => $p24->id, 'service_type' => ServiceType::Domain,  'provider_id' => $godaddy->id,     'domain_name' => 'healthplusgh.com',       'purchase_date' => '2023-03-01', 'expiry_date' => $now->copy()->addDays(6)->toDateString(),   'purchase_cost_usd' => 11.99,   'renewal_cost_usd' => 17.99,   'status' => SubscriptionStatus::Expiring],
            ['project_id' => $p24->id, 'service_type' => ServiceType::Hosting, 'provider_id' => $siteground->id,  'domain_name' => null,                     'purchase_date' => '2023-03-01', 'expiry_date' => $now->copy()->addDays(75)->toDateString(),  'purchase_cost_usd' => 143.88,  'renewal_cost_usd' => 287.88,  'status' => SubscriptionStatus::Active],
            ['project_id' => $p25->id, 'service_type' => ServiceType::Hosting, 'provider_id' => $hetzner->id,     'domain_name' => null,                     'purchase_date' => '2024-06-01', 'expiry_date' => $now->copy()->addDays(260)->toDateString(), 'purchase_cost_usd' => 107.88,  'renewal_cost_usd' => 107.88,  'status' => SubscriptionStatus::Active],

            // Boadu Events
            ['project_id' => $p26->id, 'service_type' => ServiceType::Domain,  'provider_id' => $porkbun->id,     'domain_name' => 'boadu-events.com',       'purchase_date' => '2024-02-14', 'expiry_date' => $now->copy()->addDays(330)->toDateString(), 'purchase_cost_usd' => 8.56,    'renewal_cost_usd' => 9.73,    'status' => SubscriptionStatus::Active],
            ['project_id' => $p26->id, 'service_type' => ServiceType::Hosting, 'provider_id' => $netlify->id,     'domain_name' => null,                     'purchase_date' => '2024-02-14', 'expiry_date' => $now->copy()->addDays(330)->toDateString(), 'purchase_cost_usd' => 0.00,    'renewal_cost_usd' => 0.00,    'status' => SubscriptionStatus::Active],

            // Sunrise Hotels
            ['project_id' => $p27->id, 'service_type' => ServiceType::Domain,  'provider_id' => $namecheap->id,   'domain_name' => 'sunrisehotels.com',      'purchase_date' => '2022-12-01', 'expiry_date' => $now->copy()->addDays(40)->toDateString(),  'purchase_cost_usd' => 12.98,   'renewal_cost_usd' => 14.98,   'status' => SubscriptionStatus::Active],
            ['project_id' => $p27->id, 'service_type' => ServiceType::Hosting, 'provider_id' => $aws->id,         'domain_name' => null,                     'purchase_date' => '2022-12-01', 'expiry_date' => $now->copy()->addDays(40)->toDateString(),  'purchase_cost_usd' => 480.00,  'renewal_cost_usd' => 480.00,  'status' => SubscriptionStatus::Active],
            ['project_id' => $p27->id, 'service_type' => ServiceType::SSL,     'provider_id' => $cloudflare->id,  'domain_name' => 'sunrisehotels.com',      'purchase_date' => '2023-01-01', 'expiry_date' => $now->copy()->addDays(40)->toDateString(),  'purchase_cost_usd' => 0.00,    'renewal_cost_usd' => 0.00,    'status' => SubscriptionStatus::Active],
            ['project_id' => $p27->id, 'service_type' => ServiceType::Maintenance, 'provider_id' => null,         'domain_name' => null,                     'purchase_date' => '2024-01-01', 'expiry_date' => $now->copy()->addDays(40)->toDateString(),  'purchase_cost_usd' => 3600.00, 'renewal_cost_usd' => 3600.00, 'status' => SubscriptionStatus::Active],
            ['project_id' => $p28->id, 'service_type' => ServiceType::Hosting, 'provider_id' => $digitalOcean->id,'domain_name' => null,                     'purchase_date' => '2024-06-01', 'expiry_date' => $now->copy()->addDays(260)->toDateString(), 'purchase_cost_usd' => 288.00,  'renewal_cost_usd' => 288.00,  'status' => SubscriptionStatus::Active],

            // Ampofo Legal
            ['project_id' => $p29->id, 'service_type' => ServiceType::Domain,  'provider_id' => $cloudflare->id,  'domain_name' => 'ampofolaw.com',          'purchase_date' => '2023-08-01', 'expiry_date' => $now->copy()->addDays(135)->toDateString(), 'purchase_cost_usd' => 9.15,    'renewal_cost_usd' => 9.15,    'status' => SubscriptionStatus::Active],
            ['project_id' => $p29->id, 'service_type' => ServiceType::Hosting, 'provider_id' => $ovh->id,         'domain_name' => null,                     'purchase_date' => '2023-08-01', 'expiry_date' => $now->copy()->addDays(135)->toDateString(), 'purchase_cost_usd' => 71.88,   'renewal_cost_usd' => 71.88,   'status' => SubscriptionStatus::Active],

            // PixelArts
            ['project_id' => $p30->id, 'service_type' => ServiceType::Domain,  'provider_id' => $namecheap->id,   'domain_name' => 'pixelarts.com.gh',       'purchase_date' => '2024-01-01', 'expiry_date' => $now->copy()->addDays(285)->toDateString(), 'purchase_cost_usd' => 14.98,   'renewal_cost_usd' => 14.98,   'status' => SubscriptionStatus::Active],
            ['project_id' => $p30->id, 'service_type' => ServiceType::Hosting, 'provider_id' => $vercel->id,      'domain_name' => null,                     'purchase_date' => '2024-01-01', 'expiry_date' => $now->copy()->addDays(285)->toDateString(), 'purchase_cost_usd' => 240.00,  'renewal_cost_usd' => 240.00,  'status' => SubscriptionStatus::Active],
            ['project_id' => $p31->id, 'service_type' => ServiceType::Hosting, 'provider_id' => $digitalOcean->id,'domain_name' => null,                     'purchase_date' => '2024-04-01', 'expiry_date' => $now->copy()->addDays(375)->toDateString(), 'purchase_cost_usd' => 192.00,  'renewal_cost_usd' => 192.00,  'status' => SubscriptionStatus::Active],
            ['project_id' => $p31->id, 'service_type' => ServiceType::Domain,  'provider_id' => $porkbun->id,     'domain_name' => 'assets.pixelarts.com.gh','purchase_date' => '2024-04-01', 'expiry_date' => $now->copy()->addDays(375)->toDateString(), 'purchase_cost_usd' => 8.56,    'renewal_cost_usd' => 9.73,    'status' => SubscriptionStatus::Active],
        ];

        $subs = collect($subsData)->map(fn ($s) => Subscription::create($s));

        // ─── More Renewals ──────────────────────────────────────────
        $renewalsData = [
            ['subscription_id' => $subs[0]->id,  'due_date' => '2024-05-10', 'provider_cost_usd' => 9.73,    'client_cost_usd' => 18.00,   'payment_status' => PaymentStatus::Paid,     'payment_received_date' => '2024-05-05', 'renewal_confirmed_date' => '2024-05-06', 'notes' => 'Adjei Tech domain renewed'],
            ['subscription_id' => $subs[4]->id,  'due_date' => $now->copy()->addDays(3)->toDateString(), 'provider_cost_usd' => 14.98, 'client_cost_usd' => 25.00, 'payment_status' => PaymentStatus::Pending, 'payment_received_date' => null, 'renewal_confirmed_date' => null, 'notes' => 'Golden Stitch domain expiring — urgent'],
            ['subscription_id' => $subs[15]->id, 'due_date' => $now->copy()->subDays(10)->toDateString(), 'provider_cost_usd' => 18.99, 'client_cost_usd' => 30.00, 'payment_status' => PaymentStatus::Lapsed, 'payment_received_date' => null, 'renewal_confirmed_date' => null, 'notes' => 'Darko Auto domain expired — needs attention'],
            ['subscription_id' => $subs[16]->id, 'due_date' => $now->copy()->addDays(20)->toDateString(), 'provider_cost_usd' => 95.88, 'client_cost_usd' => 150.00, 'payment_status' => PaymentStatus::Invoiced, 'payment_received_date' => null, 'renewal_confirmed_date' => null, 'notes' => 'Darko Auto hosting invoice sent'],
            ['subscription_id' => $subs[26]->id, 'due_date' => $now->copy()->addDays(6)->toDateString(),  'provider_cost_usd' => 17.99, 'client_cost_usd' => 30.00, 'payment_status' => PaymentStatus::Pending, 'payment_received_date' => null, 'renewal_confirmed_date' => null, 'notes' => 'HealthPlus domain renewal pending'],
            ['subscription_id' => $subs[32]->id, 'due_date' => '2024-12-01', 'provider_cost_usd' => 14.98,   'client_cost_usd' => 25.00,   'payment_status' => PaymentStatus::Paid,     'payment_received_date' => '2024-11-28', 'renewal_confirmed_date' => '2024-11-29', 'notes' => 'Sunrise Hotels domain renewed'],
            ['subscription_id' => $subs[34]->id, 'due_date' => '2024-06-01', 'provider_cost_usd' => 288.00,  'client_cost_usd' => 450.00,  'payment_status' => PaymentStatus::Paid,     'payment_received_date' => '2024-05-28', 'renewal_confirmed_date' => '2024-05-30', 'notes' => 'Hotel dashboard hosting paid'],
        ];

        foreach ($renewalsData as $r) {
            Renewal::create($r);
        }

        // ─── More Invoices ──────────────────────────────────────────
        $inv7 = Invoice::create(['client_id' => $c10->id, 'project_id' => $p15->id, 'invoice_number' => 'INV-2025-007', 'issued_date' => $now->copy()->subDays(2)->toDateString(), 'due_date' => $now->copy()->addDays(12)->toDateString(), 'tax_rate' => 0, 'tax_amount' => 0, 'subtotal' => 25.00, 'total_amount' => 25.00, 'status' => InvoiceStatus::Sent]);
        $inv8 = Invoice::create(['client_id' => $c13->id, 'project_id' => $p19->id, 'invoice_number' => 'INV-2025-008', 'issued_date' => $now->copy()->subDays(15)->toDateString(), 'due_date' => $now->copy()->subDays(1)->toDateString(), 'tax_rate' => 0, 'tax_amount' => 0, 'subtotal' => 180.00, 'total_amount' => 180.00, 'status' => InvoiceStatus::Overdue]);
        $inv9 = Invoice::create(['client_id' => $c14->id, 'project_id' => $p21->id, 'invoice_number' => 'INV-2025-009', 'issued_date' => $now->copy()->toDateString(), 'due_date' => $now->copy()->addDays(14)->toDateString(), 'tax_rate' => 0, 'tax_amount' => 0, 'subtotal' => 1809.15, 'total_amount' => 1809.15, 'status' => InvoiceStatus::Draft]);
        $inv10 = Invoice::create(['client_id' => $c16->id, 'project_id' => $p24->id, 'invoice_number' => 'INV-2025-010', 'issued_date' => $now->copy()->subDays(3)->toDateString(), 'due_date' => $now->copy()->addDays(11)->toDateString(), 'tax_rate' => 0, 'tax_amount' => 0, 'subtotal' => 30.00, 'total_amount' => 30.00, 'status' => InvoiceStatus::Sent]);
        $inv11 = Invoice::create(['client_id' => $c18->id, 'project_id' => $p27->id, 'invoice_number' => 'INV-2025-011', 'issued_date' => $now->copy()->subDays(7)->toDateString(), 'due_date' => $now->copy()->addDays(7)->toDateString(), 'tax_rate' => 12.5, 'tax_amount' => 621.87, 'subtotal' => 4974.98, 'total_amount' => 5596.85, 'status' => InvoiceStatus::Sent]);
        $inv12 = Invoice::create(['client_id' => $c9->id,  'project_id' => $p13->id, 'invoice_number' => 'INV-2024-012', 'issued_date' => '2024-05-01', 'due_date' => '2024-05-15', 'tax_rate' => 0, 'tax_amount' => 0, 'subtotal' => 148.00, 'total_amount' => 148.00, 'status' => InvoiceStatus::Paid]);
        $inv13 = Invoice::create(['client_id' => $c19->id, 'project_id' => $p29->id, 'invoice_number' => 'INV-2024-013', 'issued_date' => '2024-08-01', 'due_date' => '2024-08-15', 'tax_rate' => 0, 'tax_amount' => 0, 'subtotal' => 81.03, 'total_amount' => 81.03, 'status' => InvoiceStatus::Paid]);
        $inv14 = Invoice::create(['client_id' => $c20->id, 'project_id' => $p30->id, 'invoice_number' => 'INV-2024-014', 'issued_date' => '2024-01-10', 'due_date' => '2024-01-24', 'tax_rate' => 0, 'tax_amount' => 0, 'subtotal' => 254.98, 'total_amount' => 254.98, 'status' => InvoiceStatus::Paid]);

        // ─── Invoice Items ──────────────────────────────────────────
        $items = [
            ['invoice_id' => $inv7->id,  'description' => 'Domain Renewal — goldenstitch.com',             'period' => 'Urgent',              'quantity' => 1, 'unit_price' => 25.00,   'total' => 25.00],
            ['invoice_id' => $inv8->id,  'description' => 'Domain Renewal — darkoauto.com',                'period' => 'Jun 2025 – Jun 2026', 'quantity' => 1, 'unit_price' => 30.00,   'total' => 30.00],
            ['invoice_id' => $inv8->id,  'description' => 'Hosting Renewal — Hostinger',                   'period' => 'Jun 2025 – Jun 2026', 'quantity' => 1, 'unit_price' => 150.00,  'total' => 150.00],
            ['invoice_id' => $inv9->id,  'description' => 'Domain Renewal — eduvault.com',                 'period' => 'Sep 2025 – Sep 2026', 'quantity' => 1, 'unit_price' => 9.15,    'total' => 9.15],
            ['invoice_id' => $inv9->id,  'description' => 'AWS Hosting — EduVault Platform',               'period' => 'Sep 2025 – Sep 2026', 'quantity' => 1, 'unit_price' => 600.00,  'total' => 600.00],
            ['invoice_id' => $inv9->id,  'description' => 'Maintenance Contract — EduVault LMS',           'period' => 'Jan 2026 – Jan 2027', 'quantity' => 1, 'unit_price' => 1200.00, 'total' => 1200.00],
            ['invoice_id' => $inv10->id, 'description' => 'Domain Renewal — healthplusgh.com',             'period' => 'Urgent',              'quantity' => 1, 'unit_price' => 30.00,   'total' => 30.00],
            ['invoice_id' => $inv11->id, 'description' => 'Domain Renewal — sunrisehotels.com',            'period' => 'Dec 2025 – Dec 2026', 'quantity' => 1, 'unit_price' => 14.98,   'total' => 14.98],
            ['invoice_id' => $inv11->id, 'description' => 'AWS Hosting — Booking Engine',                  'period' => 'Dec 2025 – Dec 2026', 'quantity' => 1, 'unit_price' => 480.00,  'total' => 480.00],
            ['invoice_id' => $inv11->id, 'description' => 'Annual Maintenance — Hotel Systems',            'period' => 'Jan 2026 – Jan 2027', 'quantity' => 1, 'unit_price' => 3600.00, 'total' => 3600.00],
            ['invoice_id' => $inv11->id, 'description' => 'Cloudflare SSL — sunrisehotels.com',            'period' => 'Dec 2025 – Dec 2026', 'quantity' => 1, 'unit_price' => 0.00,    'total' => 0.00],
            ['invoice_id' => $inv11->id, 'description' => 'DigitalOcean Hosting — Hotel Dashboard',        'period' => 'Jun 2026 – Jun 2027', 'quantity' => 1, 'unit_price' => 288.00,  'total' => 288.00],
            ['invoice_id' => $inv11->id, 'description' => 'SSL — hotel dashboard',                         'period' => 'Dec 2025 – Dec 2026', 'quantity' => 1, 'unit_price' => 0.00,    'total' => 0.00],
            ['invoice_id' => $inv12->id, 'description' => 'Domain Renewal — adjeitech.com (Porkbun)',      'period' => 'May 2024 – May 2025', 'quantity' => 1, 'unit_price' => 18.00,   'total' => 18.00],
            ['invoice_id' => $inv12->id, 'description' => 'Linode VPS Hosting — Adjei Tech',               'period' => 'May 2024 – May 2025', 'quantity' => 1, 'unit_price' => 130.00,  'total' => 130.00],
            ['invoice_id' => $inv13->id, 'description' => 'Domain Renewal — ampofolaw.com (Cloudflare)',   'period' => 'Aug 2024 – Aug 2025', 'quantity' => 1, 'unit_price' => 15.00,   'total' => 15.00],
            ['invoice_id' => $inv13->id, 'description' => 'OVHcloud Hosting — Ampofo Legal',               'period' => 'Aug 2024 – Aug 2025', 'quantity' => 1, 'unit_price' => 66.03,   'total' => 66.03],
            ['invoice_id' => $inv14->id, 'description' => 'Domain Registration — pixelarts.com.gh',        'period' => 'Jan 2024 – Jan 2025', 'quantity' => 1, 'unit_price' => 14.98,   'total' => 14.98],
            ['invoice_id' => $inv14->id, 'description' => 'Vercel Pro Hosting — PixelArts Portfolio',      'period' => 'Jan 2024 – Jan 2025', 'quantity' => 1, 'unit_price' => 240.00,  'total' => 240.00],
        ];

        foreach ($items as $item) {
            InvoiceItem::create($item);
        }

        $this->command->info('✅ Extra: 5 Providers, 12 Clients, 19 Projects, ' . $subs->count() . ' Subscriptions, ' . count($renewalsData) . ' Renewals, 8 Invoices, ' . count($items) . ' Invoice Items');
    }
}
