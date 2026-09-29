<?php declare(strict_types=1);

/*
 * Copyright (c) 2026 Onomica
 * Licensed under the MIT License.
 */

namespace OnomicaNewsletter\Core\Content\Campaign\Aggregate\Delivery;

use OnomicaNewsletter\Core\Content\Campaign\CampaignEntity;
use Shopware\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class DeliveryEntity extends Entity
{
    use EntityIdTrait;

    protected string $campaignId;
    protected ?string $newsletterRecipientId = null;
    protected string $email;
    protected ?string $firstName = null;
    protected ?string $lastName = null;
    protected string $status;
    protected ?string $errorMessage = null;
    protected ?\DateTimeInterface $sentAt = null;
    protected ?CampaignEntity $campaign = null;
    protected ?NewsletterRecipientEntity $newsletterRecipient = null;

    public function getCampaignId(): string
    {
        return $this->campaignId;
    }

    public function setCampaignId(string $campaignId): void
    {
        $this->campaignId = $campaignId;
    }

    public function getNewsletterRecipientId(): ?string
    {
        return $this->newsletterRecipientId;
    }

    public function setNewsletterRecipientId(?string $newsletterRecipientId): void
    {
        $this->newsletterRecipientId = $newsletterRecipientId;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): void
    {
        $this->errorMessage = $errorMessage;
    }

    public function getSentAt(): ?\DateTimeInterface
    {
        return $this->sentAt;
    }

    public function setSentAt(?\DateTimeInterface $sentAt): void
    {
        $this->sentAt = $sentAt;
    }

    public function getCampaign(): ?CampaignEntity
    {
        return $this->campaign;
    }

    public function setCampaign(?CampaignEntity $campaign): void
    {
        $this->campaign = $campaign;
    }

    public function getNewsletterRecipient(): ?NewsletterRecipientEntity
    {
        return $this->newsletterRecipient;
    }

    public function setNewsletterRecipient(?NewsletterRecipientEntity $newsletterRecipient): void
    {
        $this->newsletterRecipient = $newsletterRecipient;
    }
}
