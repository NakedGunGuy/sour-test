<?php

declare(strict_types=1);

namespace Sauerkraut\Mail;

readonly class Envelope
{
    /**
     * @param string[] $to
     * @param string[] $cc
     * @param string[] $bcc
     * @param Attachment[] $attachments
     */
    public function __construct(
        public array $to,
        public string $subject,
        public string $body,
        public string $from = '',
        public string $replyTo = '',
        public array $cc = [],
        public array $bcc = [],
        public bool $isHtml = false,
        public array $attachments = [],
    ) {}
}
