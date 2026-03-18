<?php

declare(strict_types=1);

namespace Sauerkraut\Mail;

class SmtpTransport implements Transport
{
    private const int DEFAULT_TIMEOUT = 30;
    private const string CRLF = "\r\n";

    /** @var resource|null */
    private $socket = null;

    public function __construct(
        private string $host,
        private int $port,
        private ?string $username = null,
        private ?string $password = null,
        private string $encryption = '',
    ) {}

    public static function fromConfig(array $config): self
    {
        return new self(
            $config['host'] ?? 'localhost',
            $config['port'] ?? 587,
            $config['username'] ?? null,
            $config['password'] ?? null,
            $config['encryption'] ?? '',
        );
    }

    public function send(Envelope $envelope): void
    {
        $this->connect();
        $this->ehlo();

        if ($this->encryption === 'tls') {
            $this->startTls();
        }

        if ($this->username !== null) {
            $this->authenticate();
        }

        $this->mailFrom($envelope->from);
        $this->rcptTo($envelope);
        $this->data($envelope);
        $this->quit();
    }

    private function connect(): void
    {
        $protocol = $this->encryption === 'ssl' ? 'ssl://' : '';
        $this->socket = @fsockopen(
            $protocol . $this->host,
            $this->port,
            $errorCode,
            $errorMessage,
            self::DEFAULT_TIMEOUT,
        );

        if ($this->socket === false) {
            throw new \RuntimeException("SMTP connection failed: {$errorMessage}");
        }

        $this->readResponse();
    }

    private function ehlo(): void
    {
        $this->sendCommand('EHLO ' . gethostname());
    }

    private function startTls(): void
    {
        $this->sendCommand('STARTTLS');
        stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        $this->ehlo();
    }

    private function authenticate(): void
    {
        $this->sendCommand('AUTH LOGIN');
        $this->sendCommand(base64_encode($this->username));
        $this->sendCommand(base64_encode($this->password));
    }

    private function mailFrom(string $from): void
    {
        $this->sendCommand("MAIL FROM:<{$from}>");
    }

    private function rcptTo(Envelope $envelope): void
    {
        foreach ([...$envelope->to, ...$envelope->cc, ...$envelope->bcc] as $recipient) {
            $this->sendCommand("RCPT TO:<{$recipient}>");
        }
    }

    private function data(Envelope $envelope): void
    {
        $this->sendCommand('DATA');

        $message = $this->buildMessage($envelope);
        fwrite($this->socket, $message . self::CRLF . '.' . self::CRLF);
        $this->readResponse();
    }

    private function quit(): void
    {
        $this->sendCommand('QUIT');
        fclose($this->socket);
        $this->socket = null;
    }

    private function buildMessage(Envelope $envelope): string
    {
        $headers = [];
        $headers[] = "From: {$envelope->from}";
        $headers[] = 'To: ' . implode(', ', $envelope->to);
        $headers[] = "Subject: {$envelope->subject}";
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Date: ' . date('r');

        if (!empty($envelope->cc)) {
            $headers[] = 'Cc: ' . implode(', ', $envelope->cc);
        }

        if ($envelope->replyTo) {
            $headers[] = "Reply-To: {$envelope->replyTo}";
        }

        if (empty($envelope->attachments)) {
            $contentType = $envelope->isHtml ? 'text/html' : 'text/plain';
            $headers[] = "Content-Type: {$contentType}; charset=UTF-8";

            return implode(self::CRLF, $headers) . self::CRLF . self::CRLF . $envelope->body;
        }

        return $this->buildMultipartMessage($envelope, $headers);
    }

    private function buildMultipartMessage(Envelope $envelope, array $headers): string
    {
        $boundary = bin2hex(random_bytes(16));
        $headers[] = "Content-Type: multipart/mixed; boundary=\"{$boundary}\"";

        $parts = [];
        $contentType = $envelope->isHtml ? 'text/html' : 'text/plain';
        $parts[] = "Content-Type: {$contentType}; charset=UTF-8" . self::CRLF . self::CRLF . $envelope->body;

        foreach ($envelope->attachments as $attachment) {
            $content = base64_encode(file_get_contents($attachment->path));
            $parts[] = "Content-Type: {$attachment->mimeType}; name=\"{$attachment->name}\"" . self::CRLF
                . "Content-Disposition: attachment; filename=\"{$attachment->name}\"" . self::CRLF
                . 'Content-Transfer-Encoding: base64' . self::CRLF . self::CRLF
                . chunk_split($content);
        }

        $body = '';
        foreach ($parts as $part) {
            $body .= "--{$boundary}" . self::CRLF . $part . self::CRLF;
        }
        $body .= "--{$boundary}--";

        return implode(self::CRLF, $headers) . self::CRLF . self::CRLF . $body;
    }

    private function sendCommand(string $command): string
    {
        fwrite($this->socket, $command . self::CRLF);

        return $this->readResponse();
    }

    private function readResponse(): string
    {
        $response = '';

        while ($line = fgets($this->socket, 512)) {
            $response .= $line;

            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        $code = (int) substr($response, 0, 3);

        if ($code >= 400) {
            throw new \RuntimeException("SMTP error ({$code}): {$response}");
        }

        return $response;
    }
}
