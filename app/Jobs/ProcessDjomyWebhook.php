<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Billing\HandleDjomyWebhook;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Traite un webhook Djomy sur la file basse priorité (RGF-13), pour ne jamais dégrader
 * la réactivité de l'application.
 */
class ProcessDjomyWebhook implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(public array $payload)
    {
        $this->onQueue('low');
    }

    public function handle(): void
    {
        (new HandleDjomyWebhook)->handle($this->payload);
    }
}
