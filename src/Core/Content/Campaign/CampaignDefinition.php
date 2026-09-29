<?php declare(strict_types=1);

/*
 * Copyright (c) 2026 Onomica
 * Licensed under the MIT License.
 */

namespace OnomicaNewsletter\Core\Content\Campaign;

use OnomicaNewsletter\Core\Content\Campaign\Aggregate\Delivery\DeliveryDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\Language\LanguageDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;

class CampaignDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'onomica_newsletter_campaign';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return CampaignCollection::class;
    }

    public function getEntityClass(): string
    {
        return CampaignEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new FkField('sales_channel_id', 'salesChannelId', SalesChannelDefinition::class))->addFlags(new Required()),
            (new FkField('language_id', 'languageId', LanguageDefinition::class))->addFlags(new Required()),
            (new StringField('name', 'name'))->addFlags(new Required()),
            (new StringField('status', 'status'))->addFlags(new Required()),
            (new StringField('subject', 'subject'))->addFlags(new Required()),
            new StringField('preview_text', 'previewText'),
            new StringField('sender_name', 'senderName'),
            new StringField('sender_email', 'senderEmail'),
            (new LongTextField('content_html', 'contentHtml'))->addFlags(new Required()),
            new LongTextField('content_plain', 'contentPlain'),
            new DateTimeField('scheduled_at', 'scheduledAt'),
            new DateTimeField('started_at', 'startedAt'),
            new DateTimeField('completed_at', 'completedAt'),
            (new IntField('recipient_count', 'recipientCount'))->addFlags(new Required()),
            (new IntField('sent_count', 'sentCount'))->addFlags(new Required()),
            (new IntField('failed_count', 'failedCount'))->addFlags(new Required()),
            (new IntField('skipped_count', 'skippedCount'))->addFlags(new Required()),
            new ManyToOneAssociationField('salesChannel', 'sales_channel_id', SalesChannelDefinition::class, 'id', false),
            new ManyToOneAssociationField('language', 'language_id', LanguageDefinition::class, 'id', false),
            (new OneToManyAssociationField('deliveries', DeliveryDefinition::class, 'campaign_id'))->addFlags(new CascadeDelete()),
        ]);
    }
}
