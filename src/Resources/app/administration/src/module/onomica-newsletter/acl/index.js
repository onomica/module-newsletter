/*
 * Copyright (c) 2026 Onomica
 * Licensed under the MIT License.
 */

Shopware.Service('privileges').addPrivilegeMappingEntry({
    category: 'permissions',
    parent: 'marketing',
    key: 'onomica_newsletter_campaign',
    roles: {
        viewer: {
            privileges: [
                'onomica_newsletter_campaign:read',
                'onomica_newsletter_delivery:read',
                'sales_channel:read',
                'language:read',
            ],
            dependencies: [],
        },
        editor: {
            privileges: [
                'onomica_newsletter_campaign:update',
                'onomica_newsletter_delivery:update',
            ],
            dependencies: ['onomica_newsletter_campaign.viewer'],
        },
        creator: {
            privileges: ['onomica_newsletter_campaign:create'],
            dependencies: [
                'onomica_newsletter_campaign.viewer',
                'onomica_newsletter_campaign.editor',
            ],
        },
        deleter: {
            privileges: [
                'onomica_newsletter_campaign:delete',
                'onomica_newsletter_delivery:delete',
            ],
            dependencies: ['onomica_newsletter_campaign.viewer'],
        },
    },
});
