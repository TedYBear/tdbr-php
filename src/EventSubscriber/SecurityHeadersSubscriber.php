<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SecurityHeadersSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();

        // Prévient le clickjacking
        $response->headers->set('X-Frame-Options', 'DENY');

        // Prévient la détection de MIME type
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Force HTTPS
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

        // Protection XSS (deprecated dans les navigateurs modernes mais utile)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Contrôle des référeurs
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Content Security Policy (CSP) - politique permissive pour démarrer, à restreindre
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; " .
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' cdn.jsdelivr.net unpkg.com; " .
            "style-src 'self' 'unsafe-inline' fonts.googleapis.com; " .
            "font-src 'self' fonts.gstatic.com; " .
            "img-src 'self' data: https:; " .
            "connect-src 'self' *.mollie.com *.googleapis.com; " .
            "frame-ancestors 'none'; " .
            "base-uri 'self'; " .
            "form-action 'self'"
        );

        // Permissions Policy (ex Permissions Policy)
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
    }
}
