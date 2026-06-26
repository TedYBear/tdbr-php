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

        // Force HTTPS — HSTS avec preload.
        // ⚠ Engagement : la directive preload + includeSubDomains impose que TOUS les
        // sous-domaines (preprod.tedybear.fr, etc.) servent du HTTPS valide. À soumettre
        // sur https://hstspreload.org/ pour intégrer la liste preload des navigateurs.
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');

        // Contrôle des référeurs
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Content Security Policy (CSP)
        // - script-src avec nonce : seuls les <script> portant le nonce de la requête
        //   s'exécutent ; bloque les scripts injectés (XSS) et les scripts tiers.
        // - Plus de 'unsafe-eval' : Alpine.js utilise le build @alpinejs/csp (évaluateur
        //   restreint, sans new Function/eval).
        // - Plus de script-src-attr 'unsafe-inline' : tous les handlers onX= ont été
        //   migrés vers une délégation par data-attributes (assets/interactions.js).
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; " .
            "script-src 'self' 'nonce-{$nonce}'; " .
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
