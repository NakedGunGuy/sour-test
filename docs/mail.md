# Mail

Send emails via SMTP or log them in development.

## Configuration

`config/mail.php`:

```php
return [
    'driver' => 'log',             // 'smtp' or 'log'
    'from' => 'noreply@example.com',

    // SMTP settings
    'host' => 'smtp.example.com',
    'port' => 587,
    'username' => 'user',
    'password' => 'secret',
    'encryption' => 'tls',         // 'tls', 'ssl', or ''
];
```

## Sending Mail

```php
use Sauerkraut\Mail\{Mailer, Envelope};

$mailer = Mailer::fromConfig($this->app->config('mail'));

$mailer->send(new Envelope(
    to: ['user@example.com'],
    subject: 'Welcome',
    body: '<h1>Welcome!</h1><p>Thanks for signing up.</p>',
    isHtml: true,
));
```

## Envelope Properties

| Property | Type | Description |
|----------|------|-------------|
| `to` | `string[]` | Recipients |
| `subject` | `string` | Subject line |
| `body` | `string` | Email body |
| `from` | `string` | Sender (falls back to config default) |
| `replyTo` | `string` | Reply-to address |
| `cc` | `string[]` | CC recipients |
| `bcc` | `string[]` | BCC recipients |
| `isHtml` | `bool` | HTML or plain text |
| `attachments` | `Attachment[]` | File attachments |

## Attachments

```php
use Sauerkraut\Mail\Attachment;

$mailer->send(new Envelope(
    to: ['user@example.com'],
    subject: 'Report',
    body: 'See attached.',
    attachments: [
        Attachment::fromPath('/path/to/report.pdf'),
        Attachment::fromPath('/path/to/data.csv', 'export.csv'),
    ],
));
```

## Transports

| Driver | Class | Use case |
|--------|-------|----------|
| `log` | `LogTransport` | Development — logs to file instead of sending |
| `smtp` | `SmtpTransport` | Production — sends via SMTP with TLS/auth |

Custom transports: implement the `Transport` interface with a `send(Envelope)` method.
