<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MailTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MailTemplate>
 */
class MailTemplateFactory extends Factory
{
    protected $model = MailTemplate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(2),
            'name' => fake()->words(3, true),
            'subject' => 'Hello {client_name}',
            'body' => 'This is a message for {client_name} from {company_name}.',
            'description' => fake()->sentence(),
            'variables' => ['{client_name}', '{company_name}'],
        ];
    }
}
