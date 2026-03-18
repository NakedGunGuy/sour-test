<?php

declare(strict_types=1);

namespace Sauerkraut\Mail;

interface Transport
{
    public function send(Envelope $envelope): void;
}
