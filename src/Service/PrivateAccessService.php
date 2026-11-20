<?php

namespace App\Service;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\ProductCollection;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Gère l'accès par lien direct (token secret) aux catégories, collections et articles.
 *
 * Règle hiérarchique descendante :
 *  - Débloquer une catégorie donne accès à toutes ses collections et articles enfants.
 *  - Débloquer une collection donne accès à tous ses articles enfants, mais pas à sa catégorie parente si privée.
 *  - Débloquer un article donne accès uniquement à cet article, pas à sa collection/catégorie parente si privées.
 *  - Un article ajouté au panier reste accessible tant qu'il est dans le panier.
 */
class PrivateAccessService
{
    public const QUERY_PARAM = 'cle';
    private const SESSION_KEY = '_private_access';

    public function __construct(
        private RequestStack $requestStack,
        private Security $security,
        private CartService $cartService,
    ) {
    }

    public function generateToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Tente de débloquer une ressource via le paramètre ?cle=... de la requête.
     * En cas de succès, mémorise l'accès en session.
     */
    public function tryUnlockFromRequest(Request $request, Category|ProductCollection|Article $entity): bool
    {
        $cle = $request->query->get(self::QUERY_PARAM);
        $expected = $entity->getAccessToken();

        if (!is_string($cle) || $cle === '' || $expected === null || $expected === '') {
            return false;
        }

        if (!hash_equals($expected, $cle)) {
            return false;
        }

        $id = $entity->getId();
        if ($id === null) {
            return false;
        }

        if ($entity instanceof Category) {
            $this->unlock('categories', $id);
        } elseif ($entity instanceof ProductCollection) {
            $this->unlock('collections', $id);
        } else {
            $this->unlock('articles', $id);
        }

        return true;
    }

    public function canAccessCategory(Category $category): bool
    {
        if (!$category->isActif()) {
            return false;
        }

        if (!$category->isEffectivelyPrivate()) {
            return true;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        return $this->isUnlocked('categories', $category->getId());
    }

    public function canAccessCollection(ProductCollection $collection): bool
    {
        if (!$collection->isActif()) {
            return false;
        }

        if (!$collection->isEffectivelyPrivate()) {
            return true;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        if ($this->isUnlocked('collections', $collection->getId())) {
            return true;
        }

        $category = $collection->getCategorie();
        if ($category !== null && $this->isUnlocked('categories', $category->getId())) {
            return true;
        }

        return false;
    }

    public function canAccessArticle(Article $article): bool
    {
        if (!$article->isActif()) {
            return false;
        }

        if (!$article->isEffectivelyPrivate()) {
            return true;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $articleId = $article->getId();
        if ($articleId !== null && ($this->isUnlocked('articles', $articleId) || $this->cartService->hasArticle($articleId))) {
            return true;
        }

        $collection = $article->getCollection();
        if ($collection !== null && $this->isUnlocked('collections', $collection->getId())) {
            return true;
        }

        $category = $collection?->getCategorie();
        if ($category !== null && $this->isUnlocked('categories', $category->getId())) {
            return true;
        }

        return false;
    }

    /**
     * Indique si l'utilisateur a débloqué cette catégorie (via son lien direct ou en tant qu'admin sur catégorie privée),
     * ce qui lui donne accès à toute la hiérarchie inférieure de cette catégorie.
     */
    public function hasCategoryHierarchyAccess(Category $category): bool
    {
        if ($this->isUnlocked('categories', $category->getId())) {
            return true;
        }

        return $category->isEffectivelyPrivate() && $this->security->isGranted('ROLE_ADMIN');
    }

    /**
     * Indique si l'utilisateur a débloqué cette collection (directement, via sa catégorie parente, ou admin sur collection privée),
     * ce qui lui donne accès à tous les articles de cette collection.
     */
    public function hasCollectionHierarchyAccess(ProductCollection $collection): bool
    {
        if ($this->isUnlocked('collections', $collection->getId())) {
            return true;
        }

        $category = $collection->getCategorie();
        if ($category !== null && $this->isUnlocked('categories', $category->getId())) {
            return true;
        }

        return $collection->isEffectivelyPrivate() && $this->security->isGranted('ROLE_ADMIN');
    }

    private function getSession(): ?SessionInterface
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request || !$request->hasSession()) {
            return null;
        }

        return $request->getSession();
    }

    private function unlock(string $bucket, int $id): void
    {
        $session = $this->getSession();
        if (!$session) {
            return;
        }

        $data = $session->get(self::SESSION_KEY, [
            'categories'  => [],
            'collections' => [],
            'articles'    => [],
        ]);

        if (!in_array($id, $data[$bucket], true)) {
            $data[$bucket][] = $id;
            $session->set(self::SESSION_KEY, $data);
        }
    }

    private function isUnlocked(string $bucket, ?int $id): bool
    {
        if ($id === null) {
            return false;
        }

        $session = $this->getSession();
        if (!$session) {
            return false;
        }

        $data = $session->get(self::SESSION_KEY, []);
        return in_array($id, $data[$bucket] ?? [], true);
    }
}
