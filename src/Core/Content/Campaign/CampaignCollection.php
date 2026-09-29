<?php declare(strict_types=1);

/*
 * Copyright (c) 2026 Onomica
 * Licensed under the MIT License.
 */

namespace OnomicaNewsletter\Core\Content\Campaign;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class CampaignCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return CampaignEntity::class;
    }
}
