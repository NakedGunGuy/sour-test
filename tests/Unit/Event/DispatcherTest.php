<?php

declare(strict_types=1);

namespace Tests\Unit\Event;

use PHPUnit\Framework\TestCase;
use Sauerkraut\Event\Dispatcher;
use Sauerkraut\Event\Event;
use Sauerkraut\Event\Listener;

class TestEvent extends Event
{
    public string $data = '';
}

class AppendListener implements Listener
{
    public function handle(Event $event): void
    {
        if ($event instanceof TestEvent) {
            $event->data .= 'A';
        }
    }
}

class DispatcherTest extends TestCase
{
    public function testDispatchesClosureListener(): void
    {
        $dispatcher = new Dispatcher();
        $called = false;

        $dispatcher->listen(TestEvent::class, function () use (&$called) {
            $called = true;
        });

        $dispatcher->dispatch(new TestEvent());

        $this->assertTrue($called);
    }

    public function testDispatchesClassListener(): void
    {
        $dispatcher = new Dispatcher();
        $dispatcher->listen(TestEvent::class, new AppendListener());

        $event = new TestEvent();
        $dispatcher->dispatch($event);

        $this->assertSame('A', $event->data);
    }

    public function testMultipleListenersRunInOrder(): void
    {
        $dispatcher = new Dispatcher();

        $dispatcher->listen(TestEvent::class, function (TestEvent $event) {
            $event->data .= '1';
        });

        $dispatcher->listen(TestEvent::class, function (TestEvent $event) {
            $event->data .= '2';
        });

        $event = new TestEvent();
        $dispatcher->dispatch($event);

        $this->assertSame('12', $event->data);
    }

    public function testStopPropagation(): void
    {
        $dispatcher = new Dispatcher();

        $dispatcher->listen(TestEvent::class, function (TestEvent $event) {
            $event->data .= '1';
            $event->stopPropagation();
        });

        $dispatcher->listen(TestEvent::class, function (TestEvent $event) {
            $event->data .= '2';
        });

        $event = new TestEvent();
        $dispatcher->dispatch($event);

        $this->assertSame('1', $event->data);
    }

    public function testHasListeners(): void
    {
        $dispatcher = new Dispatcher();

        $this->assertFalse($dispatcher->hasListeners(TestEvent::class));

        $dispatcher->listen(TestEvent::class, function () {});

        $this->assertTrue($dispatcher->hasListeners(TestEvent::class));
    }

    public function testForgetListeners(): void
    {
        $dispatcher = new Dispatcher();
        $dispatcher->listen(TestEvent::class, function () {});
        $dispatcher->forget(TestEvent::class);

        $this->assertFalse($dispatcher->hasListeners(TestEvent::class));
    }

    public function testDispatchReturnsEvent(): void
    {
        $dispatcher = new Dispatcher();
        $event = new TestEvent();

        $returned = $dispatcher->dispatch($event);

        $this->assertSame($event, $returned);
    }

    public function testNoListenersDoesNothing(): void
    {
        $dispatcher = new Dispatcher();
        $event = new TestEvent();

        $dispatcher->dispatch($event);

        $this->assertSame('', $event->data);
    }
}
