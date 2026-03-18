<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use PHPUnit\Framework\TestCase;
use Sauerkraut\Mail\Attachment;
use Sauerkraut\Mail\Envelope;
use Sauerkraut\Mail\Mailer;
use Sauerkraut\Mail\Transport;

class InMemoryTransport implements Transport
{
    /** @var Envelope[] */
    public array $sent = [];

    public function send(Envelope $envelope): void
    {
        $this->sent[] = $envelope;
    }
}

class MailerTest extends TestCase
{
    public function testSendsEnvelope(): void
    {
        $transport = new InMemoryTransport();
        $mailer = new Mailer($transport);

        $envelope = new Envelope(
            to: ['user@example.com'],
            subject: 'Welcome',
            body: 'Hello!',
            from: 'app@example.com',
        );

        $mailer->send($envelope);

        $this->assertCount(1, $transport->sent);
        $this->assertSame('Welcome', $transport->sent[0]->subject);
        $this->assertSame(['user@example.com'], $transport->sent[0]->to);
    }

    public function testDefaultFromIsApplied(): void
    {
        $transport = new InMemoryTransport();
        $mailer = new Mailer($transport, 'default@example.com');

        $envelope = new Envelope(
            to: ['user@example.com'],
            subject: 'Test',
            body: 'Body',
        );

        $mailer->send($envelope);

        $this->assertSame('default@example.com', $transport->sent[0]->from);
    }

    public function testExplicitFromOverridesDefault(): void
    {
        $transport = new InMemoryTransport();
        $mailer = new Mailer($transport, 'default@example.com');

        $envelope = new Envelope(
            to: ['user@example.com'],
            subject: 'Test',
            body: 'Body',
            from: 'explicit@example.com',
        );

        $mailer->send($envelope);

        $this->assertSame('explicit@example.com', $transport->sent[0]->from);
    }

    public function testEnvelopeProperties(): void
    {
        $envelope = new Envelope(
            to: ['a@b.com', 'c@d.com'],
            subject: 'Subject',
            body: '<h1>Hello</h1>',
            from: 'sender@b.com',
            replyTo: 'reply@b.com',
            cc: ['cc@b.com'],
            bcc: ['bcc@b.com'],
            isHtml: true,
        );

        $this->assertSame(['a@b.com', 'c@d.com'], $envelope->to);
        $this->assertSame('Subject', $envelope->subject);
        $this->assertTrue($envelope->isHtml);
        $this->assertSame(['cc@b.com'], $envelope->cc);
        $this->assertSame(['bcc@b.com'], $envelope->bcc);
        $this->assertSame('reply@b.com', $envelope->replyTo);
    }

    public function testAttachmentFromPath(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tmpFile, 'content');

        $attachment = Attachment::fromPath($tmpFile, 'document.txt');

        $this->assertSame($tmpFile, $attachment->path);
        $this->assertSame('document.txt', $attachment->name);

        unlink($tmpFile);
    }

    public function testMailerFromConfigWithLogDriver(): void
    {
        $logDir = sys_get_temp_dir() . '/sauerkraut_mail_test_' . uniqid();

        $mailer = Mailer::fromConfig([
            'driver' => 'log',
            'from' => 'test@example.com',
            'log_path' => $logDir,
        ]);

        $mailer->send(new Envelope(
            to: ['user@example.com'],
            subject: 'Log test',
            body: 'Body',
        ));

        $logFile = $logDir . '/' . date('Y-m-d') . '.log';
        $this->assertFileExists($logFile);
        $this->assertStringContainsString('Log test', file_get_contents($logFile));

        unlink($logFile);
        rmdir($logDir);
    }
}
