<?php declare(strict_types=1);

/*
 * Copyright (c) 2026 Onomica
 * Licensed under the MIT License.
 */

namespace OnomicaNewsletter\Core\Content\Campaign\Aggregate\Delivery;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class DeliveryCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return DeliveryEntity::class;
    }
}
