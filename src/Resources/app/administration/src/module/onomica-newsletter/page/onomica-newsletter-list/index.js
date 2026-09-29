/*
 * Copyright (c) 2026 Onomica
 * Licensed under the MIT License.
 */

import template from './onomica-newsletter-list.html.twig';

const { Criteria } = Shopware.Data;

export default {
    template,

    inject: ['repositoryFactory', 'acl'],

    data() {
        return {
            campaigns: null,
            isLoading: false,
            total: 0,
            page: 1,
            limit: 25,
            sortBy: 'createdAt',
            sortDirection: 'DESC',
        };
    },

    computed: {
        campaignRepository() {
            return this.repositoryFactory.create('onomica_newsletter_campaign');
        },

        columns() {
            return [
                { property: 'name', label: this.$tc('onomica-newsletter.list.name'), routerLink: 'onomica.newsletter.detail', primary: true },
                { property: 'status', label: this.$tc('onomica-newsletter.list.status') },
                { property: 'salesChannel.name', label: this.$tc('onomica-newsletter.list.salesChannel') },
                { property: 'scheduledAt', label: this.$tc('onomica-newsletter.list.scheduledAt') },
                { property: 'recipientCount', label: this.$tc('onomica-newsletter.list.recipients'), align: 'right' },
                { property: 'sentCount', label: this.$tc('onomica-newsletter.list.sent'), align: 'right' },
                { property: 'failedCount', label: this.$tc('onomica-newsletter.list.failed'), align: 'right' },
            ];
        },
    },

    created() {
        this.loadCampaigns();
    },

    methods: {
        loadCampaigns() {
            this.isLoading = true;
            const criteria = new Criteria(this.page, this.limit);
            criteria.addAssociation('salesChannel');
            criteria.addSorting(Criteria.sort(this.sortBy, this.sortDirection));

            return this.campaignRepository.search(criteria, Shopware.Context.api).then((campaigns) => {
                this.campaigns = campaigns;
                this.total = campaigns.total;
            }).finally(() => {
                this.isLoading = false;
            });
        },

        onPageChange({ page, limit }) {
            this.page = page;
            this.limit = limit;
            this.loadCampaigns();
        },

        onDelete(id) {
            return this.campaignRepository.delete(id, Shopware.Context.api).then(() => this.loadCampaigns());
        },
    },
};
