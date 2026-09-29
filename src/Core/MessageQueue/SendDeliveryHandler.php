<?php declare(strict_types=1);

/*
 * Copyright (c) 2026 Onomica
 * Licensed under the MIT License.
 */

namespace OnomicaNewsletter\Core\MessageQueue;

use OnomicaNewsletter\Core\Content\Campaign\CampaignManager;
use Shopware\Core\Framework\Context;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class SendDeliveryHandler
{
    public function __construct(private readonly CampaignManager $campaignManager)
    {
    }

    public function __invoke(SendDeliveryMessage $message): void
    {
        $this->campaignManager->sendDelivery($message->getDeliveryId(), Context::createCLIContext());
    }
}
