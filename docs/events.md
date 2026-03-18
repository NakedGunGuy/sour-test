# Events

Decouple actions from their side effects with the event dispatcher.

## Defining Events

Events are plain classes extending `Event`:

```php
<?php

declare(strict_types=1);

namespace App\Events;

use Sauerkraut\Event\Event;

class UserRegistered extends Event
{
    public function __construct(
        public readonly int $userId,
        public readonly string $email,
    ) {}
}
```

## Listening for Events

**With closures:**
```php
$dispatcher = new Dispatcher();

$dispatcher->listen(UserRegistered::class, function (UserRegistered $event) {
    // Send welcome email, log activity, etc.
});
```

**With listener classes:**
```php
use Sauerkraut\Event\Listener;
use Sauerkraut\Event\Event;

class SendWelcomeEmail implements Listener
{
    public function handle(Event $event): void
    {
        // Send email to $event->email
    }
}

$dispatcher->listen(UserRegistered::class, new SendWelcomeEmail());
```

## Dispatching Events

```php
$dispatcher->dispatch(new UserRegistered(userId: 42, email: 'john@example.com'));
```

## Stopping Propagation

An event can prevent subsequent listeners from running:

```php
$dispatcher->listen(OrderPlaced::class, function (OrderPlaced $event) {
    if ($event->total > 10000) {
        $event->stopPropagation(); // No more listeners run
    }
});
```

## API

| Method | Description |
|--------|-------------|
| `listen($eventClass, $listener)` | Register a listener |
| `dispatch($event)` | Fire an event (returns the event) |
| `hasListeners($eventClass)` | Check if any listeners registered |
| `forget($eventClass)` | Remove all listeners for an event |
