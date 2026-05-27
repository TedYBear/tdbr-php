<?php

namespace App\EventSubscriber;

use App\Entity\PageView;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Repository\ProductCollectionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class PageViewSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface      $em,
        private ArticleRepository           $articleRepo,
        private CategoryRepository          $categoryRepo,
        private ProductCollectionRepository $collectionRepo,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::TERMINATE => 'onKernelTerminate'];
    }

    public function onKernelTerminate(TerminateEvent $event): void
    {
        $request  = $event->getRequest();
        $response = $event->getResponse();

        if (!$request->isMethod('GET')) {
            return;
        }

        if ($response->getStatusCode() !== 200) {
            return;
        }

        $routeName = $request->attributes->get('_route');
        if (!$routeName) {
            return;
        }

        if (str_starts_with($routeName, 'admin') || str_starts_with($routeName, 'api')) {
            return;
        }

        $trackedRoutes = [
            'home', 'catalogue', 'presentation', 'avis_liste',
            'devis', 'contact', 'categorie', 'collection', 'article_detail',
        ];
        if (!in_array($routeName, $trackedRoutes, true)) {
            return;
        }

        $entityType = null;
        $entitySlug = null;
        $entityId   = null;

        $routeParams = $request->attributes->get('_route_params', []);
        $slug        = $routeParams['slug'] ?? null;

        if ($slug !== null) {
            match ($routeName) {
                'article_detail' => $entityType = 'article',
                'categorie'      => $entityType = 'categorie',
                'collection'     => $entityType = 'collection',
                default          => $entityType = null,
            };

            if ($entityType !== null) {
                $entitySlug = $slug;
                $entityId   = $this->resolveEntityId($entityType, $slug);
            }
        }

        $ip        = $request->getClientIp() ?? '';
        $userAgent = $request->headers->get('User-Agent', '');
        $date      = (new \DateTimeImmutable())->format('Y-m-d');
        $sessionId = hash('sha256', $ip . '|' . $userAgent . '|' . $date);

        $ipPartial = $this->anonymizeIp($ip);

        $pageView = new PageView();
        $pageView->setRouteName($routeName);
        $pageView->setEntityType($entityType);
        $pageView->setEntitySlug($entitySlug);
        $pageView->setEntityId($entityId);
        $pageView->setSessionId($sessionId);
        $pageView->setIpPartial($ipPartial);

        try {
            $this->em->persist($pageView);
            $this->em->flush();
        } catch (\Throwable) {
            // Never let analytics break the application
        }
    }

    private function resolveEntityId(string $entityType, string $slug): ?int
    {
        $entity = match ($entityType) {
            'article'    => $this->articleRepo->findOneBy(['slug' => $slug]),
            'categorie'  => $this->categoryRepo->findOneBy(['slug' => $slug]),
            'collection' => $this->collectionRepo->findOneBy(['slug' => $slug]),
            default      => null,
        };

        return $entity?->getId();
    }

    private function anonymizeIp(string $ip): ?string
    {
        if ($ip === '') {
            return null;
        }

        if (str_contains($ip, '.')) {
            $parts = explode('.', $ip);
            if (count($parts) === 4) {
                return $parts[0] . '.' . $parts[1] . '.' . $parts[2];
            }
        }

        return null;
    }
}
