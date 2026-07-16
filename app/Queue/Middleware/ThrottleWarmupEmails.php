<?php

namespace App\Queue\Middleware;

use App\Services\EmailWarmupService;
use Closure;

class ThrottleWarmupEmails
{
    /**
     * Handle the job.
     *
     * @param  mixed  $job
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($job, Closure $next)
    {
        $service = new EmailWarmupService();

        // Check if warmup is active
        if ($service->getSchedule()->status !== 'active') {
            return $next($job);
        }

        // Check if can send now
        if (!$service->canSendEmail()) {
            $delay = $service->getDelayBeforeNextEmail();

            if ($delay > 0) {
                // Release job back to queue with delay
                $job->release($delay);
                return;
            }

            // Check if already hit daily limit
            if (!$service->getSchedule()->canSendToday()) {
                $job->fail(new \Exception('Daily email limit reached for warmup day'));
                return;
            }
        }

        return $next($job);
    }
}
