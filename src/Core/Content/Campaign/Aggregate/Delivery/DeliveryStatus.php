<?php declare(strict_types=1);

/*
 * Copyright (c) 2026 Onomica
 * Licensed under the MIT License.
 */

namespace OnomicaNewsletter\Core\Content\Campaign\Aggregate\Delivery;

final class DeliveryStatus
{
    public const QUEUED = 'queued';
    public const SENDING = 'sending';
    public const SENT = 'sent';
    public const FAILED = 'failed';
    public const SKIPPED = 'skipped';
    public const CANCELLED = 'cancelled';
}
