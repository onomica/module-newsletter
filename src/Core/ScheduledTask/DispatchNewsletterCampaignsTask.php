<?php declare(strict_types=1);

/*
 * Copyright (c) 2026 Onomica
 * Licensed under the MIT License.
 */

namespace OnomicaNewsletter\Core\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

final class DispatchNewsletterCampaignsTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'onomica_newsletter.dispatch_campaigns';
    }

    public static function getDefaultInterval(): int
    {
        return 300;
    }
}
