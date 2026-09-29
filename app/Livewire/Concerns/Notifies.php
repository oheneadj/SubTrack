<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

/**
 * Shows a toast for an action that stays on the same page (no redirect).
 *
 * session()->flash() only renders on the *next full page load* — a
 * Livewire action re-renders just the component's own DOM, never the
 * surrounding layout where the flash-message toast lives, so a flash set
 * during an in-place action (e.g. clicking "Generate Receipt") silently
 * never appears. Dispatching a browser event instead is picked up by the
 * layout's live-toast listener immediately, regardless of whether a
 * redirect happens. Safe to use even when a redirect *does* follow —
 * unlike session()->flash(), it isn't lost either way.
 */
trait Notifies
{
    protected function notifySuccess(string $message): void
    {
        $this->dispatch('notify', type: 'success', message: $message);
    }

    protected function notifyError(string $message): void
    {
        $this->dispatch('notify', type: 'error', message: $message);
    }

    protected function notifyWarning(string $message): void
    {
        $this->dispatch('notify', type: 'warning', message: $message);
    }
}
