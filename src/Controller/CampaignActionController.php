<?php declare(strict_types=1);

/*
 * Copyright (c) 2026 Onomica
 * Licensed under the MIT License.
 */

namespace OnomicaNewsletter\Controller;

use OnomicaNewsletter\Core\Content\Campaign\CampaignManager;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\RoutingException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
final class CampaignActionController extends AbstractController
{
    public function __construct(private readonly CampaignManager $campaignManager)
    {
    }

    #[Route(path: '/api/_action/onomica-newsletter/campaign/{campaignId}/send', name: 'api.action.onomica_newsletter.campaign.send', methods: ['POST'], defaults: ['_acl' => ['onomica_newsletter_campaign.editor']])]
    public function send(string $campaignId, Context $context): JsonResponse
    {
        $this->campaignManager->sendNow($campaignId, $context);

        return new JsonResponse(['success' => true]);
    }

    #[Route(path: '/api/_action/onomica-newsletter/campaign/{campaignId}/schedule', name: 'api.action.onomica_newsletter.campaign.schedule', methods: ['POST'], defaults: ['_acl' => ['onomica_newsletter_campaign.editor']])]
    public function schedule(string $campaignId, RequestDataBag $data, Context $context): JsonResponse
    {
        $scheduledAt = $data->get('scheduledAt');

        if (!\is_string($scheduledAt) || trim($scheduledAt) === '') {
            throw RoutingException::missingRequestParameter('scheduledAt');
        }

        $this->campaignManager->schedule($campaignId, new \DateTimeImmutable($scheduledAt), $context);

        return new JsonResponse(['success' => true]);
    }

    #[Route(path: '/api/_action/onomica-newsletter/campaign/{campaignId}/cancel', name: 'api.action.onomica_newsletter.campaign.cancel', methods: ['POST'], defaults: ['_acl' => ['onomica_newsletter_campaign.editor']])]
    public function cancel(string $campaignId, Context $context): JsonResponse
    {
        $this->campaignManager->cancel($campaignId, $context);

        return new JsonResponse(['success' => true]);
    }

    #[Route(path: '/api/_action/onomica-newsletter/campaign/{campaignId}/retry', name: 'api.action.onomica_newsletter.campaign.retry', methods: ['POST'], defaults: ['_acl' => ['onomica_newsletter_campaign.editor']])]
    public function retry(string $campaignId, Context $context): JsonResponse
    {
        $this->campaignManager->retryFailed($campaignId, $context);

        return new JsonResponse(['success' => true]);
    }
}
