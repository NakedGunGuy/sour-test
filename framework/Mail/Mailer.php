<?php

declare(strict_types=1);

namespace Sauerkraut\Mail;

class Mailer
{
    public function __construct(
        private Transport $transport,
        private string $defaultFrom = '',
    ) {}

    public static function fromConfig(array $config): self
    {
        $driver = $config['driver'] ?? 'log';

        $transport = match ($driver) {
            'smtp' => SmtpTransport::fromConfig($config),
            'log' => new LogTransport(
                new \Sauerkraut\Log\Logger('mail', $config['log_path'] ?? 'storage/logs'),
            ),
            default => throw new \RuntimeException("Unknown mail driver: {$driver}"),
        };

        return new self($transport, $config['from'] ?? '');
    }

    public function send(Envelope $envelope): void
    {
        if (!$envelope->from && $this->defaultFrom) {
            $envelope = new Envelope(
                to: $envelope->to,
                subject: $envelope->subject,
                body: $envelope->body,
                from: $this->defaultFrom,
                replyTo: $envelope->replyTo,
                cc: $envelope->cc,
                bcc: $envelope->bcc,
                isHtml: $envelope->isHtml,
                attachments: $envelope->attachments,
            );
        }

        $this->transport->send($envelope);
    }
}
