<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\WebhookEvent;
use App\Services\Payment\GatewayRegistry;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Single entry point for all payment gateway webhooks.
 *
 * Route: POST /webhooks/{gateway}
 *
 * The gateway slug in the URL resolves the correct implementation via the registry.
 * Adding a new gateway requires no changes here — only a new class + config entry.
 */
class WebhookController extends Controller
{
    public function __invoke(Request $request, string $gateway, GatewayRegistry $registry): Response
    {
        try {
            $gw = $registry->get($gateway);
        } catch (InvalidArgumentException) {
            // Unknown gateway — return 200 to avoid provider retries on unsupported slugs.
            Log::warning("Webhook received for unknown gateway: {$gateway}");

            return response('', 200);
        }

        try {
            $gw->verifyWebhook($request);
        } catch (\Throwable $e) {
            Log::warning("Webhook signature verification failed for gateway [{$gateway}]: ".$e->getMessage());

            return response('Invalid signature', 400);
        }

        $eventId = $gw->webhookEventId($request);
        if ($eventId && ! $this->recordEventOnce($gateway, $eventId)) {
            // Already processed this exact event — provider redelivered it. No-op.
            return response('', 200);
        }

        try {
            $gw->handleWebhook($request);
        } catch (\Throwable $e) {
            // Log but still return 200 — we don't want providers to retry on our app errors.
            Log::error("Webhook handling failed for gateway [{$gateway}]: ".$e->getMessage(), [
                'exception' => $e,
            ]);
        }

        return response('', 200);
    }

    /**
     * Insert the (gateway, event_id) pair, relying on the DB's unique
     * constraint to detect a duplicate delivery atomically — avoids a
     * check-then-insert race between two near-simultaneous redeliveries.
     *
     * @return bool true if this is the first time we've seen this event
     */
    private function recordEventOnce(string $gateway, string $eventId): bool
    {
        try {
            WebhookEvent::create(['gateway' => $gateway, 'event_id' => $eventId]);

            return true;
        } catch (QueryException) {
            return false;
        }
    }
}
