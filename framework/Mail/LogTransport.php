<?php

declare(strict_types=1);

namespace Sauerkraut\Mail;

use Sauerkraut\Log\Logger;

class LogTransport implements Transport
{
    public function __construct(private Logger $logger) {}

    public function send(Envelope $envelope): void
    {
        $this->logger->info('Mail sent to {to}: {subject}', [
            'to' => implode(', ', $envelope->to),
            'subject' => $envelope->subject,
        ]);
    }
}
