<?php
namespace App\EventSubscriber;

use App\Repository\CategoryRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class NavbarSubscriber implements EventSubscriberInterface
{
    public function __construct(private CategoryRepository $categoryRepo) {}

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 10]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $categories = $this->categoryRepo->findBy(
            ['actif' => true],
            ['ordre' => 'ASC', 'nom' => 'ASC']
        );

        $event->getRequest()->attributes->set('_categories', $categories);
    }
}
