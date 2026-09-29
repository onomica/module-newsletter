<?php declare(strict_types=1);

/*
 * Copyright (c) 2026 Onomica
 * Licensed under the MIT License.
 */

namespace OnomicaNewsletter\Core\Content\Campaign;

use OnomicaNewsletter\Core\Content\Campaign\Aggregate\Delivery\DeliveryCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class CampaignEntity extends Entity
{
    use EntityIdTrait;

    protected string $salesChannelId;
    protected string $languageId;
    protected string $name;
    protected string $status;
    protected string $subject;
    protected ?string $previewText = null;
    protected ?string $senderName = null;
    protected ?string $senderEmail = null;
    protected string $contentHtml;
    protected ?string $contentPlain = null;
    protected ?\DateTimeInterface $scheduledAt = null;
    protected ?\DateTimeInterface $startedAt = null;
    protected ?\DateTimeInterface $completedAt = null;
    protected int $recipientCount = 0;
    protected int $sentCount = 0;
    protected int $failedCount = 0;
    protected int $skippedCount = 0;
    protected ?SalesChannelEntity $salesChannel = null;
    protected ?LanguageEntity $language = null;
    protected ?DeliveryCollection $deliveries = null;

    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
    }

    public function setSalesChannelId(string $salesChannelId): void
    {
        $this->salesChannelId = $salesChannelId;
    }

    public function getLanguageId(): string
    {
        return $this->languageId;
    }

    public function setLanguageId(string $languageId): void
    {
        $this->languageId = $languageId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }

    public function getPreviewText(): ?string
    {
        return $this->previewText;
    }

    public function setPreviewText(?string $previewText): void
    {
        $this->previewText = $previewText;
    }

    public function getSenderName(): ?string
    {
        return $this->senderName;
    }

    public function setSenderName(?string $senderName): void
    {
        $this->senderName = $senderName;
    }

    public function getSenderEmail(): ?string
    {
        return $this->senderEmail;
    }

    public function setSenderEmail(?string $senderEmail): void
    {
        $this->senderEmail = $senderEmail;
    }

    public function getContentHtml(): string
    {
        return $this->contentHtml;
    }

    public function setContentHtml(string $contentHtml): void
    {
        $this->contentHtml = $contentHtml;
    }

    public function getContentPlain(): ?string
    {
        return $this->contentPlain;
    }

    public function setContentPlain(?string $contentPlain): void
    {
        $this->contentPlain = $contentPlain;
    }

    public function getScheduledAt(): ?\DateTimeInterface
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(?\DateTimeInterface $scheduledAt): void
    {
        $this->scheduledAt = $scheduledAt;
    }

    public function getStartedAt(): ?\DateTimeInterface
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeInterface $startedAt): void
    {
        $this->startedAt = $startedAt;
    }

    public function getCompletedAt(): ?\DateTimeInterface
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeInterface $completedAt): void
    {
        $this->completedAt = $completedAt;
    }

    public function getRecipientCount(): int
    {
        return $this->recipientCount;
    }

    public function setRecipientCount(int $recipientCount): void
    {
        $this->recipientCount = $recipientCount;
    }

    public function getSentCount(): int
    {
        return $this->sentCount;
    }

    public function setSentCount(int $sentCount): void
    {
        $this->sentCount = $sentCount;
    }

    public function getFailedCount(): int
    {
        return $this->failedCount;
    }

    public function setFailedCount(int $failedCount): void
    {
        $this->failedCount = $failedCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    public function setSkippedCount(int $skippedCount): void
    {
        $this->skippedCount = $skippedCount;
    }

    public function getSalesChannel(): ?SalesChannelEntity
    {
        return $this->salesChannel;
    }

    public function setSalesChannel(?SalesChannelEntity $salesChannel): void
    {
        $this->salesChannel = $salesChannel;
    }

    public function getLanguage(): ?LanguageEntity
    {
        return $this->language;
    }

    public function setLanguage(?LanguageEntity $language): void
    {
        $this->language = $language;
    }

    public function getDeliveries(): ?DeliveryCollection
    {
        return $this->deliveries;
    }

    public function setDeliveries(?DeliveryCollection $deliveries): void
    {
        $this->deliveries = $deliveries;
    }
}
