<?php declare(strict_types=1);

/*
 * Copyright (c) 2026 Onomica
 * Licensed under the MIT License.
 */

namespace OnomicaNewsletter\Core\MessageQueue;

final class PrepareCampaignMessage
{
    public function __construct(private readonly string $campaignId)
    {
    }

    public function getCampaignId(): string
    {
        return $this->campaignId;
    }
}
