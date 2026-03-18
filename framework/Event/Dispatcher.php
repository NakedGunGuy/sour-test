<?php

declare(strict_types=1);

namespace Sauerkraut\Event;

class Dispatcher
{
    /** @var array<string, array<Listener|\Closure>> */
    private array $listeners = [];

    public function listen(string $eventClass, Listener|\Closure $listener): void
    {
        $this->listeners[$eventClass][] = $listener;
    }

    public function dispatch(Event $event): Event
    {
        $eventClass = $event::class;

        foreach ($this->listenersFor($eventClass) as $listener) {
            if ($event->isPropagationStopped()) {
                break;
            }

            if ($listener instanceof Listener) {
                $listener->handle($event);
            } else {
                $listener($event);
            }
        }

        return $event;
    }

    public function hasListeners(string $eventClass): bool
    {
        return !empty($this->listeners[$eventClass]);
    }

    public function forget(string $eventClass): void
    {
        unset($this->listeners[$eventClass]);
    }

    /** @return array<Listener|\Closure> */
    private function listenersFor(string $eventClass): array
    {
        return $this->listeners[$eventClass] ?? [];
    }
}
