<?php declare(strict_types=1);

/*
 * Copyright (c) 2026 Onomica
 * Licensed under the MIT License.
 */

namespace OnomicaNewsletter\Core\Content\Campaign;

use Doctrine\DBAL\Connection;
use OnomicaNewsletter\Core\Content\Campaign\Aggregate\Delivery\DeliveryEntity;
use OnomicaNewsletter\Core\Content\Campaign\Aggregate\Delivery\DeliveryStatus;
use OnomicaNewsletter\Core\MessageQueue\PrepareCampaignMessage;
use OnomicaNewsletter\Core\MessageQueue\SendDeliveryMessage;
use Shopware\Core\Content\Mail\Service\AbstractMailService;
use Shopware\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientEntity;
use Shopware\Core\Content\Newsletter\SalesChannel\NewsletterSubscribeRoute;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\Messenger\MessageBusInterface;

final class CampaignManager
{
    private const PAGE_SIZE = 250;

    public function __construct(
        private readonly EntityRepository $campaignRepository,
        private readonly EntityRepository $deliveryRepository,
        private readonly EntityRepository $recipientRepository,
        private readonly Connection $connection,
        private readonly MessageBusInterface $messageBus,
        private readonly AbstractMailService $mailService
    ) {
    }

    public function schedule(string $campaignId, \DateTimeImmutable $scheduledAt, Context $context): void
    {
        $campaign = $this->getCampaign($campaignId, $context);

        if (!\in_array($campaign->getStatus(), [CampaignStatus::DRAFT, CampaignStatus::CANCELLED, CampaignStatus::FAILED], true)) {
            throw new \DomainException('Only draft, cancelled or failed campaigns can be scheduled.');
        }

        if (trim($campaign->getSubject()) === '' || trim($campaign->getContentHtml()) === '') {
            throw new \DomainException('A subject and HTML content are required before scheduling.');
        }

        if ($campaign->getSenderEmail() !== null
            && trim($campaign->getSenderEmail()) !== ''
            && filter_var($campaign->getSenderEmail(), \FILTER_VALIDATE_EMAIL) === false
        ) {
            throw new \DomainException('The sender email is invalid.');
        }

        $this->campaignRepository->update([[
            'id' => $campaignId,
            'status' => CampaignStatus::SCHEDULED,
            'scheduledAt' => $scheduledAt,
            'startedAt' => null,
            'completedAt' => null,
            'recipientCount' => 0,
            'sentCount' => 0,
            'failedCount' => 0,
            'skippedCount' => 0,
        ]], $context);
    }

    public function sendNow(string $campaignId, Context $context): void
    {
        $this->schedule($campaignId, new \DateTimeImmutable(), $context);
        $this->messageBus->dispatch(new PrepareCampaignMessage($campaignId));
    }

    public function cancel(string $campaignId, Context $context): void
    {
        $campaign = $this->getCampaign($campaignId, $context);

        if (\in_array($campaign->getStatus(), [CampaignStatus::COMPLETED, CampaignStatus::CANCELLED], true)) {
            return;
        }

        $this->campaignRepository->update([[
            'id' => $campaignId,
            'status' => CampaignStatus::CANCELLED,
            'completedAt' => new \DateTimeImmutable(),
        ]], $context);

        $this->connection->executeStatement(
            'UPDATE `onomica_newsletter_delivery` SET `status` = :cancelled, `updated_at` = :updatedAt WHERE `campaign_id` = :campaignId AND `status` IN (:queued, :sending)',
            [
                'cancelled' => DeliveryStatus::CANCELLED,
                'updatedAt' => $this->now(),
                'campaignId' => Uuid::fromHexToBytes($campaignId),
                'queued' => DeliveryStatus::QUEUED,
                'sending' => DeliveryStatus::SENDING,
            ]
        );
    }

    public function retryFailed(string $campaignId, Context $context): void
    {
        $campaign = $this->getCampaign($campaignId, $context);

        if (!\in_array($campaign->getStatus(), [CampaignStatus::COMPLETED, CampaignStatus::FAILED], true)) {
            throw new \DomainException('Only completed or failed campaigns can retry failed deliveries.');
        }

        $affected = $this->connection->executeStatement(
            'UPDATE `onomica_newsletter_delivery` SET `status` = :queued, `error_message` = NULL, `updated_at` = :updatedAt WHERE `campaign_id` = :campaignId AND `status` = :failed',
            [
                'queued' => DeliveryStatus::QUEUED,
                'updatedAt' => $this->now(),
                'campaignId' => Uuid::fromHexToBytes($campaignId),
                'failed' => DeliveryStatus::FAILED,
            ]
        );

        if ($affected === 0) {
            return;
        }

        $this->campaignRepository->update([[
            'id' => $campaignId,
            'status' => CampaignStatus::SENDING,
            'failedCount' => 0,
            'completedAt' => null,
        ]], $context);

        $this->dispatchQueuedDeliveries($campaignId, $context);
    }

    public function dispatchDueCampaigns(Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('status', CampaignStatus::SCHEDULED));
        $criteria->addFilter(new RangeFilter('scheduledAt', [RangeFilter::LTE => $this->now()]));
        $criteria->setLimit(50);

        $scheduledCampaignIds = $this->campaignRepository->searchIds($criteria, $context)->getIds();

        foreach ($scheduledCampaignIds as $campaignId) {
            $this->messageBus->dispatch(new PrepareCampaignMessage($campaignId));
        }

        $recoveryCriteria = new Criteria();
        $recoveryCriteria->addFilter(new EqualsFilter('status', CampaignStatus::PREPARING));
        $recoveryCriteria->addFilter(new RangeFilter('updatedAt', [RangeFilter::LTE => $this->preparationTimeout()]));
        $recoveryCriteria->setLimit(50);

        $recoveryCampaignIds = $this->campaignRepository->searchIds($recoveryCriteria, $context)->getIds();

        foreach ($recoveryCampaignIds as $campaignId) {
            $this->messageBus->dispatch(new PrepareCampaignMessage($campaignId));
        }

        $sendingCriteria = new Criteria();
        $sendingCriteria->addFilter(new EqualsFilter('status', CampaignStatus::SENDING));
        $sendingCriteria->setLimit(50);

        $sendingCampaignIds = $this->campaignRepository->searchIds($sendingCriteria, $context)->getIds();

        if ($sendingCampaignIds !== []) {
            $this->dispatchQueuedDeliveries($sendingCampaignIds, $context);
        }
    }

    public function prepareAndQueue(string $campaignId, Context $context): void
    {
        $campaign = $this->getCampaign($campaignId, $context);

        if ($campaign->getStatus() === CampaignStatus::SCHEDULED) {
            if ($campaign->getScheduledAt() === null || $campaign->getScheduledAt() > new \DateTimeImmutable()) {
                return;
            }

            $claimed = $this->connection->executeStatement(
                'UPDATE `onomica_newsletter_campaign` SET `status` = :preparing, `updated_at` = :updatedAt WHERE `id` = :id AND `status` = :scheduled',
                [
                    'preparing' => CampaignStatus::PREPARING,
                    'updatedAt' => $this->now(),
                    'id' => Uuid::fromHexToBytes($campaignId),
                    'scheduled' => CampaignStatus::SCHEDULED,
                ]
            );

            if ($claimed === 0) {
                return;
            }
        } elseif ($campaign->getStatus() === CampaignStatus::PREPARING) {
            $reclaimed = $this->connection->executeStatement(
                'UPDATE `onomica_newsletter_campaign` SET `updated_at` = :updatedAt WHERE `id` = :id AND `status` = :preparing AND `updated_at` <= :timeout',
                [
                    'updatedAt' => $this->now(),
                    'id' => Uuid::fromHexToBytes($campaignId),
                    'preparing' => CampaignStatus::PREPARING,
                    'timeout' => $this->preparationTimeout(),
                ]
            );

            if ($reclaimed === 0) {
                return;
            }
        } else {
            return;
        }

        $this->connection->executeStatement(
            'DELETE FROM `onomica_newsletter_delivery` WHERE `campaign_id` = :campaignId',
            ['campaignId' => Uuid::fromHexToBytes($campaignId)]
        );

        $deliveryIds = [];
        $offset = 0;

        do {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('salesChannelId', $campaign->getSalesChannelId()));
            $criteria->addFilter(new EqualsFilter('languageId', $campaign->getLanguageId()));
            $criteria->addFilter(new EqualsAnyFilter('status', [NewsletterSubscribeRoute::STATUS_OPT_IN, NewsletterSubscribeRoute::STATUS_DIRECT]));
            $criteria->setLimit(self::PAGE_SIZE);
            $criteria->setOffset($offset);

            $recipients = $this->recipientRepository->search($criteria, $context)->getEntities();
            $payload = [];

            foreach ($recipients as $recipient) {
                if (!$recipient instanceof NewsletterRecipientEntity) {
                    continue;
                }

                $deliveryId = Uuid::randomHex();
                $deliveryIds[] = $deliveryId;
                $payload[] = [
                    'id' => $deliveryId,
                    'campaignId' => $campaignId,
                    'newsletterRecipientId' => $recipient->getId(),
                    'email' => $recipient->getEmail(),
                    'firstName' => $recipient->getFirstName(),
                    'lastName' => $recipient->getLastName(),
                    'status' => DeliveryStatus::QUEUED,
                ];
            }

            if ($payload !== []) {
                $this->deliveryRepository->create($payload, $context);
            }

            $offset += self::PAGE_SIZE;
        } while ($recipients->count() === self::PAGE_SIZE);

        $status = $deliveryIds === [] ? CampaignStatus::COMPLETED : CampaignStatus::SENDING;
        $now = new \DateTimeImmutable();

        $this->campaignRepository->update([[
            'id' => $campaignId,
            'status' => $status,
            'recipientCount' => \count($deliveryIds),
            'sentCount' => 0,
            'failedCount' => 0,
            'skippedCount' => 0,
            'startedAt' => $now,
            'completedAt' => $deliveryIds === [] ? $now : null,
        ]], $context);

        foreach ($deliveryIds as $deliveryId) {
            $this->messageBus->dispatch(new SendDeliveryMessage($deliveryId));
        }
    }

    public function sendDelivery(string $deliveryId, Context $context): void
    {
        $claimed = $this->connection->executeStatement(
            'UPDATE `onomica_newsletter_delivery` SET `status` = :sending, `updated_at` = :updatedAt WHERE `id` = :id AND `status` = :queued',
            [
                'sending' => DeliveryStatus::SENDING,
                'updatedAt' => $this->now(),
                'id' => Uuid::fromHexToBytes($deliveryId),
                'queued' => DeliveryStatus::QUEUED,
            ]
        );

        if ($claimed === 0) {
            return;
        }

        $criteria = new Criteria([$deliveryId]);
        $criteria->addAssociation('campaign.salesChannel');
        $criteria->addAssociation('newsletterRecipient');
        $delivery = $this->deliveryRepository->search($criteria, $context)->getEntities()->first();

        if (!$delivery instanceof DeliveryEntity || $delivery->getCampaign() === null) {
            return;
        }

        $campaign = $delivery->getCampaign();

        if ($campaign->getStatus() === CampaignStatus::CANCELLED) {
            $this->finishDelivery($delivery, DeliveryStatus::SKIPPED, 'Campaign was cancelled.');

            return;
        }

        $recipient = $delivery->getNewsletterRecipient();

        if ($recipient === null
            || !\in_array($recipient->getStatus(), [NewsletterSubscribeRoute::STATUS_OPT_IN, NewsletterSubscribeRoute::STATUS_DIRECT], true)
            || $recipient->getSalesChannelId() !== $campaign->getSalesChannelId()
            || $recipient->getLanguageId() !== $campaign->getLanguageId()
        ) {
            $this->finishDelivery($delivery, DeliveryStatus::SKIPPED, 'Recipient is no longer subscribed to this sales channel and language.');

            return;
        }

        $recipientName = trim(($delivery->getFirstName() ?? '') . ' ' . ($delivery->getLastName() ?? ''));
        $salesChannel = $campaign->getSalesChannel();
        $senderName = $campaign->getSenderName() ?: ($salesChannel?->getName() ?: 'Newsletter');
        $plainContent = $campaign->getContentPlain();
        $htmlContent = $campaign->getContentHtml();

        if ($campaign->getPreviewText() !== null && trim($campaign->getPreviewText()) !== '') {
            $previewText = htmlspecialchars($campaign->getPreviewText(), \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
            $htmlContent = '<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent">' . $previewText . '</div>' . $htmlContent;
        }

        if ($plainContent === null || trim($plainContent) === '') {
            $plainContent = trim(html_entity_decode(strip_tags($campaign->getContentHtml())));
        }

        if ($plainContent === '') {
            $plainContent = $campaign->getSubject();
        }

        $mailData = [
            'recipients' => [$delivery->getEmail() => $recipientName ?: $delivery->getEmail()],
            'salesChannelId' => $campaign->getSalesChannelId(),
            'subject' => $campaign->getSubject(),
            'senderName' => $senderName,
            'contentHtml' => $htmlContent,
            'contentPlain' => $plainContent,
        ];

        if ($campaign->getSenderEmail() !== null && trim($campaign->getSenderEmail()) !== '') {
            $mailData['senderMail'] = $campaign->getSenderEmail();
        }

        try {
            $mail = $this->mailService->send($mailData, $this->createCampaignContext($context, $campaign->getLanguageId()), [
                'campaign' => $campaign,
                'recipient' => $recipient,
                'previewText' => $campaign->getPreviewText(),
            ]);

            if ($mail === null) {
                throw new \RuntimeException('Shopware mail service did not create a message.');
            }

            $this->finishDelivery($delivery, DeliveryStatus::SENT);
        } catch (\Throwable $exception) {
            $this->finishDelivery($delivery, DeliveryStatus::FAILED, mb_substr($exception->getMessage(), 0, 4000));
        }
    }

    private function dispatchQueuedDeliveries(string|array $campaignIds, Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('campaignId', (array) $campaignIds));
        $criteria->addFilter(new EqualsFilter('status', DeliveryStatus::QUEUED));
        $criteria->setLimit(1000);

        $deliveryIds = $this->deliveryRepository->searchIds($criteria, $context)->getIds();

        foreach ($deliveryIds as $deliveryId) {
            $this->messageBus->dispatch(new SendDeliveryMessage($deliveryId));
        }
    }

    private function finishDelivery(DeliveryEntity $delivery, string $status, ?string $errorMessage = null): void
    {
        $sentIncrement = $status === DeliveryStatus::SENT ? 1 : 0;
        $failedIncrement = $status === DeliveryStatus::FAILED ? 1 : 0;
        $skippedIncrement = $status === DeliveryStatus::SKIPPED ? 1 : 0;
        $now = $this->now();

        $this->connection->transactional(function () use ($delivery, $status, $errorMessage, $sentIncrement, $failedIncrement, $skippedIncrement, $now): void {
            $this->connection->executeStatement(
                'UPDATE `onomica_newsletter_delivery` SET `status` = :status, `error_message` = :errorMessage, `sent_at` = :sentAt, `updated_at` = :updatedAt WHERE `id` = :id',
                [
                    'status' => $status,
                    'errorMessage' => $errorMessage,
                    'sentAt' => $status === DeliveryStatus::SENT ? $now : null,
                    'updatedAt' => $now,
                    'id' => Uuid::fromHexToBytes($delivery->getId()),
                ]
            );

            $this->connection->executeStatement(
                'UPDATE `onomica_newsletter_campaign` SET `sent_count` = `sent_count` + :sent, `failed_count` = `failed_count` + :failed, `skipped_count` = `skipped_count` + :skipped, `updated_at` = :updatedAt WHERE `id` = :id',
                [
                    'sent' => $sentIncrement,
                    'failed' => $failedIncrement,
                    'skipped' => $skippedIncrement,
                    'updatedAt' => $now,
                    'id' => Uuid::fromHexToBytes($delivery->getCampaignId()),
                ]
            );

            $this->connection->executeStatement(
                'UPDATE `onomica_newsletter_campaign` SET `status` = :completed, `completed_at` = :completedAt, `updated_at` = :updatedAt WHERE `id` = :id AND `status` = :sending AND (`sent_count` + `failed_count` + `skipped_count`) >= `recipient_count`',
                [
                    'completed' => CampaignStatus::COMPLETED,
                    'completedAt' => $now,
                    'updatedAt' => $now,
                    'id' => Uuid::fromHexToBytes($delivery->getCampaignId()),
                    'sending' => CampaignStatus::SENDING,
                ]
            );
        });
    }

    private function getCampaign(string $campaignId, Context $context): CampaignEntity
    {
        $criteria = new Criteria([$campaignId]);
        $criteria->addAssociation('salesChannel');
        $campaign = $this->campaignRepository->search($criteria, $context)->getEntities()->first();

        if (!$campaign instanceof CampaignEntity) {
            throw new \DomainException('Campaign not found.');
        }

        return $campaign;
    }

    private function now(): string
    {
        return (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT);
    }

    private function preparationTimeout(): string
    {
        return (new \DateTimeImmutable('-5 minutes'))->format(Defaults::STORAGE_DATE_TIME_FORMAT);
    }

    private function createCampaignContext(Context $context, string $languageId): Context
    {
        return new Context(
            $context->getSource(),
            $context->getRuleIds(),
            $context->getCurrencyId(),
            array_values(array_unique([$languageId, ...$context->getLanguageIdChain(), Defaults::LANGUAGE_SYSTEM])),
            $context->getVersionId(),
            $context->getCurrencyFactor(),
            $context->considerInheritance(),
            $context->getTaxState(),
            $context->getRounding()
        );
    }
}
