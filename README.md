# Onomica Newsletter

Onomica Newsletter adds reusable newsletter campaign management to Shopware 6.6 and 6.7. Campaigns use Shopware's native newsletter recipients, mail configuration, header and footer, scheduled tasks, and message queue.

## Features

- Create draft campaigns in Administration under Marketing.
- Target one sales channel and recipient language per campaign.
- Send immediately or schedule a campaign.
- Snapshot active `optIn` and `direct` newsletter recipients when sending starts.
- Recheck every subscription before sending, so unsubscribed or reassigned recipients are skipped.
- Process each delivery through the Shopware message queue.
- Cancel queued campaigns and retry failed deliveries.
- Monitor recipient, sent, failed, and skipped totals.
- Use a configured sender per campaign or inherit the sales channel mail settings.
- Write HTML and optional plain text content with Shopware Twig variables.
- Extend Administration translations by adding another locale file next to `en-GB.json`.

## Requirements

- PHP 8.2 or newer
- Shopware 6.6 or 6.7
- A configured Shopware mailer
- A running message queue consumer and scheduled task runner

Compatibility is verified with Shopware 6.6.10.27 on PHP 8.2 and Shopware 6.7.14.2 on PHP 8.3.

## Installation

Place the plugin in `custom/plugins/OnomicaNewsletter`, then run:

```bash
bin/console plugin:refresh
bin/console plugin:install --activate OnomicaNewsletter
bin/console cache:clear
bin/console scheduled-task:register
bin/console administration:build
```

For asynchronous and scheduled delivery, keep these Shopware processes running through your process manager:

```bash
bin/console messenger:consume async low_priority --time-limit=300
bin/console scheduled-task:run --time-limit=300
```

## Creating a campaign

1. Open **Marketing → Newsletter campaigns**.
2. Create a campaign and choose its sales channel and recipient language.
3. Enter the subject, HTML content, and optional plain text content.
4. Save the draft, then send it immediately or choose a date and schedule it.

Only active native Shopware newsletter recipients matching both the selected sales channel and language are included. The recipient list is fixed when preparation begins, while every recipient's current subscription is checked again before the message is sent.

The subject, sender name, HTML, and plain text fields support Shopware Twig syntax. Available values include:

```twig
{{ recipient.firstName }}
{{ recipient.lastName }}
{{ recipient.email }}
{{ campaign.name }}
{{ campaign.previewText }}
{{ salesChannel.name }}
```

Campaign content should contain a link to the shop's newsletter unsubscribe page. Subscription, double opt-in, confirmation, and unsubscribe handling remain with Shopware's native newsletter functionality.

## Delivery states

- `queued`: waiting for a message queue worker
- `sending`: claimed by a worker
- `sent`: accepted by the Shopware mail service
- `failed`: the mail service returned an error
- `skipped`: the recipient was no longer eligible at send time
- `cancelled`: delivery was stopped before sending

The retry action queues only failed deliveries. Previously sent messages are never intentionally queued again.

## Development checks

```bash
composer validate --strict
composer cs-fixer-dry
composer phpstan
```

The Composer scripts expect the plugin to be installed inside a Shopware project so they can use the project's development tools.
