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

class MassiveDataSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $this->command->info('🚀 Starting massive seed (300+ subscriptions)...');

        $providers = Provider::all();
        if ($providers->isEmpty()) {
            $this->command->error('No providers found. Run RealisticDataSeeder first.');
            return;
        }

        $existingClients = Client::all();
        $existingProjects = Project::all();

        // 1. Create ~40 more clients to avoid overcrowding
        $this->command->info('Creating 40 more clients...');
        for ($i = 0; $i < 40; $i++) {
            Client::create([
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'phone' => fake()->phoneNumber(),
                'company_name' => fake()->company(),
            ]);
        }
        $clients = Client::all();

        // 2. Create ~60 more projects
        $this->command->info('Creating 60 more projects...');
        for ($i = 0; $i < 60; $i++) {
            Project::create([
                'client_id' => $clients->random()->id,
                'project_name' => fake()->words(3, true),
                'description' => fake()->sentence(10),
            ]);
        }
        $projects = Project::all();

        // 3. Create 300 Subscriptions
        $this->command->info('Creating 300 subscriptions...');
        for ($i = 0; $i < 300; $i++) {
            $purchaseDate = $now->copy()->subDays(rand(30, 720));
            $expiryDate = $purchaseDate->copy()->addYear();
            
            // Randomly pick a status
            $status = rand(0, 10) > 8 ? SubscriptionStatus::Expired : (rand(0, 10) > 7 ? SubscriptionStatus::Expiring : SubscriptionStatus::Active);
            
            // Override expiry date for expired/expiring
            if ($status === SubscriptionStatus::Expired) {
                $expiryDate = $now->copy()->subDays(rand(5, 60));
            } elseif ($status === SubscriptionStatus::Expiring) {
                $expiryDate = $now->copy()->addDays(rand(1, 28));
            }

            Subscription::create([
                'project_id' => $projects->random()->id,
                'service_type' => fake()->randomElement(ServiceType::cases()),
                'provider_id' => $providers->random()->id,
                'domain_name' => fake()->optional()->domainName(),
                'purchase_date' => $purchaseDate->toDateString(),
                'expiry_date' => $expiryDate->toDateString(),
                'purchase_cost_usd' => rand(10, 500) . '.00',
                'renewal_cost_usd' => rand(15, 600) . '.00',
                'status' => $status,
            ]);
        }

        // 4. Create 100 Renewals
        $this->command->info('Creating 100 renewals...');
        $allSubscriptions = Subscription::all();
        for ($i = 0; $i < 100; $i++) {
            $sub = $allSubscriptions->random();
            Renewal::create([
                'subscription_id' => $sub->id,
                'due_date' => $now->copy()->addDays(rand(-30, 30))->toDateString(),
                'provider_cost_usd' => $sub->renewal_cost_usd,
                'client_cost_usd' => $sub->renewal_cost_usd + rand(20, 100),
                'payment_status' => fake()->randomElement(PaymentStatus::cases()),
                'notes' => fake()->optional()->sentence(),
            ]);
        }

        // 5. Create 50 Invoices
        $this->command->info('Creating 50 invoices...');
        for ($i = 0; $i < 50; $i++) {
            $client = $clients->random();
            $project = Project::where('client_id', $client->id)->first() ?? $projects->random();
            
            $invoice = Invoice::create([
                'client_id' => $client->id,
                'project_id' => $project->id,
                'invoice_number' => 'INV-' . strtoupper(fake()->bothify('####-????')),
                'issued_date' => $now->copy()->subDays(rand(1, 60))->toDateString(),
                'due_date' => $now->copy()->addDays(rand(1, 30))->toDateString(),
                'tax_rate' => rand(0, 15) > 10 ? 15.00 : 0.00,
                'tax_amount' => 0, // Calculated later
                'subtotal' => 0,   // Calculated later
                'total_amount' => 0, // Calculated later
                'status' => fake()->randomElement(InvoiceStatus::cases()),
            ]);

            // Create 1-3 items per invoice
            $subtotal = 0;
            for ($j = 0; $j < rand(1, 3); $j++) {
                $price = rand(50, 500);
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => fake()->sentence(4),
                    'period' => 'Annual Renewal',
                    'quantity' => 1,
                    'unit_price' => $price,
                    'total' => $price,
                ]);
                $subtotal += $price;
            }

            $taxAmount = ($subtotal * $invoice->tax_rate) / 100;
            $invoice->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $subtotal + $taxAmount,
            ]);
        }

        $this->command->info('✅ Massive seed complete!');
    }
}
