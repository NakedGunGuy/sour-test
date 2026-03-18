<?php

declare(strict_types=1);

namespace Sauerkraut\Event;

interface Listener
{
    public function handle(Event $event): void;
}
