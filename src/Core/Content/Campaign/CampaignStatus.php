<?php declare(strict_types=1);

/*
 * Copyright (c) 2026 Onomica
 * Licensed under the MIT License.
 */

namespace OnomicaNewsletter\Core\Content\Campaign;

final class CampaignStatus
{
    public const DRAFT = 'draft';
    public const SCHEDULED = 'scheduled';
    public const PREPARING = 'preparing';
    public const SENDING = 'sending';
    public const COMPLETED = 'completed';
    public const FAILED = 'failed';
    public const CANCELLED = 'cancelled';
}
