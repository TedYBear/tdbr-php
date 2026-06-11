<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SecurityHeadersSubscriber implements EventSubscriberInterface
{
    public const NONCE_ATTRIBUTE = 'csp_nonce';

    public static function getSubscribedEvents(): array
    {
        return [
            // Priorité haute : le nonce doit exister avant le rendu des templates
            KernelEvents::REQUEST => ['onKernelRequest', 100],
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        // Nonce CSP unique par requête, exposé aux templates via csp_nonce()
        $event->getRequest()->attributes->set(
            self::NONCE_ATTRIBUTE,
            base64_encode(random_bytes(16))
        );
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        $nonce = (string) $event->getRequest()->attributes->get(self::NONCE_ATTRIBUTE, '');

        // Prévient le clickjacking
        $response->headers->set('X-Frame-Options', 'DENY');

        // Prévient la détection de MIME type
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Force HTTPS
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

        // Contrôle des référeurs
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Content Security Policy (CSP)
        // - script-src avec nonce : seuls les <script> portant le nonce de la requête
        //   s'exécutent ; bloque les scripts injectés (XSS) et les scripts tiers.
        // - 'unsafe-eval' requis par Alpine.js (build standard) ; à supprimer si
        //   migration vers @alpinejs/csp.
        // - script-src-attr 'unsafe-inline' : tolère les handlers onclick=/onsubmit=
        //   existants ; à supprimer une fois ces handlers migrés vers Alpine.
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; " .
            "script-src 'self' 'nonce-{$nonce}' 'unsafe-eval'; " .
            "script-src-attr 'unsafe-inline'; " .
            "style-src 'self' 'unsafe-inline'; " .
            "img-src 'self' data: https:; " .
            "font-src 'self'; " .
            "connect-src 'self'; " .
            "object-src 'none'; " .
            "frame-ancestors 'none'; " .
            "base-uri 'self'; " .
            "form-action 'self'"
        );

        // Permissions Policy
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
    }
}
