/*
 * Copyright (c) 2026 Onomica
 * Licensed under the MIT License.
 */

import './acl';

Shopware.Component.register('onomica-newsletter-list', () => import('./page/onomica-newsletter-list'));
Shopware.Component.register('onomica-newsletter-detail', () => import('./page/onomica-newsletter-detail'));

Shopware.Module.register('onomica-newsletter', {
    type: 'plugin',
    name: 'OnomicaNewsletter',
    title: 'onomica-newsletter.general.title',
    description: 'onomica-newsletter.general.description',
    color: '#1f6fb2',
    icon: 'regular-envelope',
    entity: 'onomica_newsletter_campaign',
    routes: {
        index: {
            component: 'onomica-newsletter-list',
            path: 'index',
            meta: {
                privilege: 'onomica_newsletter_campaign.viewer',
            },
        },
        create: {
            component: 'onomica-newsletter-detail',
            path: 'create',
            meta: {
                privilege: 'onomica_newsletter_campaign.creator',
                parentPath: 'onomica.newsletter.index',
            },
        },
        detail: {
            component: 'onomica-newsletter-detail',
            path: 'detail/:id',
            meta: {
                privilege: 'onomica_newsletter_campaign.viewer',
                parentPath: 'onomica.newsletter.index',
            },
        },
    },
    navigation: [{
        id: 'onomica-newsletter',
        label: 'onomica-newsletter.general.title',
        color: '#1f6fb2',
        path: 'onomica.newsletter.index',
        icon: 'regular-envelope',
        parent: 'sw-marketing',
        privilege: 'onomica_newsletter_campaign.viewer',
        position: 90,
    }],
});
