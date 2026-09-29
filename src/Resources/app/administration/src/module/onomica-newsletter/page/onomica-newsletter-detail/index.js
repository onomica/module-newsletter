/*
 * Copyright (c) 2026 Onomica
 * Licensed under the MIT License.
 */

import template from './onomica-newsletter-detail.html.twig';

const { Criteria } = Shopware.Data;
const { Mixin } = Shopware;

export default {
    template,

    inject: ['repositoryFactory', 'acl'],

    mixins: [Mixin.getByName('notification')],

    data() {
        return {
            campaign: null,
            deliveries: null,
            isLoading: false,
            isSaving: false,
            isSaveSuccessful: false,
        };
    },

    computed: {
        campaignRepository() {
            return this.repositoryFactory.create('onomica_newsletter_campaign');
        },

        deliveryRepository() {
            return this.repositoryFactory.create('onomica_newsletter_delivery');
        },

        isNew() {
            return !this.$route.params.id;
        },

        isEditable() {
            return this.campaign && ['draft', 'cancelled', 'failed'].includes(this.campaign.status);
        },

        canCancel() {
            return this.campaign && ['scheduled', 'preparing', 'sending'].includes(this.campaign.status);
        },

        canRetry() {
            return this.campaign && this.campaign.failedCount > 0 && ['completed', 'failed'].includes(this.campaign.status);
        },

        deliveryColumns() {
            return [
                { property: 'email', label: this.$tc('onomica-newsletter.detail.email'), primary: true },
                { property: 'status', label: this.$tc('onomica-newsletter.detail.deliveryStatus') },
                { property: 'sentAt', label: this.$tc('onomica-newsletter.detail.sentAt') },
                { property: 'errorMessage', label: this.$tc('onomica-newsletter.detail.error') },
            ];
        },
    },

    created() {
        this.loadCampaign();
    },

    methods: {
        loadCampaign() {
            if (this.isNew) {
                this.campaign = this.campaignRepository.create(Shopware.Context.api);
                this.campaign.status = 'draft';
                this.campaign.name = '';
                this.campaign.subject = '';
                this.campaign.contentHtml = '';
                this.campaign.contentPlain = '';
                this.campaign.recipientCount = 0;
                this.campaign.sentCount = 0;
                this.campaign.failedCount = 0;
                this.campaign.skippedCount = 0;

                return;
            }

            this.isLoading = true;
            const criteria = new Criteria(1, 1);
            criteria.addAssociation('salesChannel');
            criteria.addAssociation('language');

            this.campaignRepository.get(this.$route.params.id, Shopware.Context.api, criteria).then((campaign) => {
                this.campaign = campaign;
                this.loadDeliveries();
            }).finally(() => {
                this.isLoading = false;
            });
        },

        loadDeliveries() {
            const criteria = new Criteria(1, 25);
            criteria.addFilter(Criteria.equals('campaignId', this.campaign.id));
            criteria.addSorting(Criteria.sort('createdAt', 'DESC'));

            return this.deliveryRepository.search(criteria, Shopware.Context.api).then((deliveries) => {
                this.deliveries = deliveries;
            });
        },

        save() {
            this.isSaving = true;
            this.isSaveSuccessful = false;

            return this.campaignRepository.save(this.campaign, Shopware.Context.api).then(() => {
                if (this.isNew) {
                    this.$router.replace({ name: 'onomica.newsletter.detail', params: { id: this.campaign.id } });
                }

                this.createNotificationSuccess({ message: this.$tc('onomica-newsletter.detail.saved') });
                this.isSaveSuccessful = true;
            }).catch(() => {
                this.isSaveSuccessful = false;
                this.createNotificationError({ message: this.$tc('onomica-newsletter.detail.saveFailed') });
                throw new Error(this.$tc('onomica-newsletter.detail.saveFailed'));
            }).finally(() => {
                this.isSaving = false;
            });
        },

        runAction(action, payload = {}) {
            return this.save().then(() => {
                const httpClient = Shopware.Application.getContainer('init').httpClient;
                return httpClient.post(`/_action/onomica-newsletter/campaign/${this.campaign.id}/${action}`, payload);
            }).then(() => this.loadCampaign()).catch(() => {
                this.createNotificationError({ message: this.$tc('onomica-newsletter.detail.actionFailed') });
            });
        },

        sendNow() {
            return this.runAction('send');
        },

        schedule() {
            if (!this.campaign.scheduledAt) {
                this.createNotificationError({ message: this.$tc('onomica-newsletter.detail.scheduleRequired') });
                return Promise.resolve();
            }

            return this.runAction('schedule', { scheduledAt: this.campaign.scheduledAt });
        },

        cancel() {
            return this.runAction('cancel');
        },

        retry() {
            return this.runAction('retry');
        },
    },
};
