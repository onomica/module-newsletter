<?php declare(strict_types=1);

/*
 * Copyright (c) 2026 Onomica
 * Licensed under the MIT License.
 */

namespace OnomicaNewsletter\Core\MessageQueue;

final class SendDeliveryMessage
{
    public function __construct(private readonly string $deliveryId)
    {
    }

    public function getDeliveryId(): string
    {
        return $this->deliveryId;
    }
}
