<?php

namespace App\Jobs;

use App\Models\ParsingTask;
use App\Services\ProfileParserService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ParseProfileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     * AI calls are unreliable; we allow 2 retries.
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     * Matching your service timeout.
     */
    public $timeout = 400;

    public function __construct(
        protected ParsingTask $task
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ProfileParserService $parser): void
    {
        $this->task->update(['status' => 'processing']);

        try {
            // Call the service logic you already wrote
            $structuredData = $parser->parse($this->task->raw_text);

            // Save the result to our tracking table
            $this->task->update([
                'status' => 'completed',
                'payload' => $structuredData
            ]);
        } catch (\Exception $e) {
            $this->task->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);

            Log::error("Job Failed for Task #{$this->task->id}: " . $e->getMessage());
            throw $e; // Throwing allows the queue to handle retries
        }
    }

    public function middleware(): array
    {
        return [new \Illuminate\Queue\Middleware\RateLimited('nvidia-api')];
    }
}
