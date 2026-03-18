# Task Scheduling

Schedule commands to run on a cron expression.

## Setup

Add one crontab entry:

```cron
* * * * * php /path/to/sauerkraut schedule:run >> /dev/null 2>&1
```

The framework handles the rest.

## Making a Command Schedulable

Implement the `Schedulable` interface and add a `schedule()` method:

```php
use Sauerkraut\Console\{Command, Schedulable, Schedule, Input, Output, Signature};

class CleanLogsCommand extends Command implements Schedulable
{
    public function signature(): Signature
    {
        return new Signature(name: 'logs:clean', description: 'Clean old logs');
    }

    public function schedule(): Schedule
    {
        return new Schedule(
            expression: Schedule::DAILY_AT_3AM,
            preventOverlapping: true,
            description: 'Clean logs daily at 3 AM',
        );
    }

    public function handle(Input $input, Output $output): int
    {
        // ... cleanup logic
        return 0;
    }
}
```

## Schedule Constants

| Constant | Expression |
|----------|-----------|
| `Schedule::EVERY_MINUTE` | `* * * * *` |
| `Schedule::EVERY_FIVE_MINUTES` | `*/5 * * * *` |
| `Schedule::EVERY_FIFTEEN_MINUTES` | `*/15 * * * *` |
| `Schedule::EVERY_THIRTY_MINUTES` | `*/30 * * * *` |
| `Schedule::HOURLY` | `0 * * * *` |
| `Schedule::DAILY` | `0 0 * * *` |
| `Schedule::DAILY_AT_3AM` | `0 3 * * *` |
| `Schedule::WEEKLY` | `0 0 * * 0` |
| `Schedule::MONTHLY` | `0 0 1 * *` |

Or use any cron expression: `new Schedule('15 */6 * * *')`

## Overlap Prevention

Set `preventOverlapping: true` to skip a run if the previous one is still going. Uses file-based locks in `storage/schedule/`.

## Listing Schedules

```bash
php sauerkraut schedule:list
```
