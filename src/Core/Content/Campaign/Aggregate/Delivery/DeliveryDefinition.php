<?php declare(strict_types=1);

/*
 * Copyright (c) 2026 Onomica
 * Licensed under the MIT License.
 */

namespace OnomicaNewsletter\Core\Content\Campaign\Aggregate\Delivery;

use OnomicaNewsletter\Core\Content\Campaign\CampaignDefinition;
use Shopware\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class DeliveryDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'onomica_newsletter_delivery';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return DeliveryCollection::class;
    }

    public function getEntityClass(): string
    {
        return DeliveryEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new FkField('campaign_id', 'campaignId', CampaignDefinition::class))->addFlags(new Required()),
            new FkField('newsletter_recipient_id', 'newsletterRecipientId', NewsletterRecipientDefinition::class),
            (new StringField('email', 'email'))->addFlags(new Required()),
            new StringField('first_name', 'firstName'),
            new StringField('last_name', 'lastName'),
            (new StringField('status', 'status'))->addFlags(new Required()),
            new LongTextField('error_message', 'errorMessage'),
            new DateTimeField('sent_at', 'sentAt'),
            new ManyToOneAssociationField('campaign', 'campaign_id', CampaignDefinition::class, 'id', false),
            new ManyToOneAssociationField('newsletterRecipient', 'newsletter_recipient_id', NewsletterRecipientDefinition::class, 'id', false),
        ]);
    }
}
