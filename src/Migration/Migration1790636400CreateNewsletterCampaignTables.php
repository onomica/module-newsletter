<?php declare(strict_types=1);

/*
 * Copyright (c) 2026 Onomica
 * Licensed under the MIT License.
 */

namespace OnomicaNewsletter\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1790636400CreateNewsletterCampaignTables extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1790636400;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS `onomica_newsletter_campaign` (
                `id` BINARY(16) NOT NULL,
                `sales_channel_id` BINARY(16) NOT NULL,
                `language_id` BINARY(16) NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                `status` VARCHAR(32) NOT NULL DEFAULT 'draft',
                `subject` VARCHAR(255) NOT NULL,
                `preview_text` VARCHAR(255) NULL,
                `sender_name` VARCHAR(255) NULL,
                `sender_email` VARCHAR(255) NULL,
                `content_html` LONGTEXT NOT NULL,
                `content_plain` LONGTEXT NULL,
                `scheduled_at` DATETIME(3) NULL,
                `started_at` DATETIME(3) NULL,
                `completed_at` DATETIME(3) NULL,
                `recipient_count` INT UNSIGNED NOT NULL DEFAULT 0,
                `sent_count` INT UNSIGNED NOT NULL DEFAULT 0,
                `failed_count` INT UNSIGNED NOT NULL DEFAULT 0,
                `skipped_count` INT UNSIGNED NOT NULL DEFAULT 0,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                KEY `idx.onomica_newsletter_campaign.status_scheduled` (`status`, `scheduled_at`),
                CONSTRAINT `fk.onomica_newsletter_campaign.sales_channel_id`
                    FOREIGN KEY (`sales_channel_id`) REFERENCES `sales_channel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.onomica_newsletter_campaign.language_id`
                    FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            SQL);

        $connection->executeStatement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS `onomica_newsletter_delivery` (
                `id` BINARY(16) NOT NULL,
                `campaign_id` BINARY(16) NOT NULL,
                `newsletter_recipient_id` BINARY(16) NULL,
                `email` VARCHAR(255) NOT NULL,
                `first_name` VARCHAR(255) NULL,
                `last_name` VARCHAR(255) NULL,
                `status` VARCHAR(32) NOT NULL DEFAULT 'queued',
                `error_message` LONGTEXT NULL,
                `sent_at` DATETIME(3) NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq.onomica_newsletter_delivery.campaign_email` (`campaign_id`, `email`),
                KEY `idx.onomica_newsletter_delivery.campaign_status` (`campaign_id`, `status`),
                CONSTRAINT `fk.onomica_newsletter_delivery.campaign_id`
                    FOREIGN KEY (`campaign_id`) REFERENCES `onomica_newsletter_campaign` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.onomica_newsletter_delivery.newsletter_recipient_id`
                    FOREIGN KEY (`newsletter_recipient_id`) REFERENCES `newsletter_recipient` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            SQL);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
