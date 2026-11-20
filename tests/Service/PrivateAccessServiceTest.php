<?php

namespace App\Tests\Service;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\ProductCollection;
use App\Service\CartService;
use App\Service\PrivateAccessService;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class PrivateAccessServiceTest extends TestCase
{
    private Session $session;
    private RequestStack $requestStack;

    protected function setUp(): void
    {
        $this->session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($this->session);

        $this->requestStack = new RequestStack();
        $this->requestStack->push($request);
    }

    private function createService(bool $isAdmin = false, array $cartArticleIds = []): PrivateAccessService
    {
        $security = $this->createMock(Security::class);
        $security->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn($isAdmin);

        $cartService = $this->createMock(CartService::class);
        $cartService->method('hasArticle')
            ->willReturnCallback(fn (int $id) => in_array($id, $cartArticleIds, true));

        return new PrivateAccessService($this->requestStack, $security, $cartService);
    }

    private function setId(object $entity, int $id): void
    {
        $ref = new \ReflectionProperty($entity, 'id');
        $ref->setValue($entity, $id);
    }

    private function buildHierarchy(
        bool $catPrivate = false,
        bool $colPrivate = false,
        bool $artPrivate = false,
        bool $artActif = true
    ): array {
        $cat = (new Category())
            ->setNom('Catégorie Test')
            ->setSlug('cat-test')
            ->setActif(true)
            ->setLienDirectUniquement($catPrivate)
            ->setAccessToken($catPrivate ? 'token-cat-123' : null);
        $this->setId($cat, 10);

        $col = (new ProductCollection())
            ->setNom('Collection Test')
            ->setSlug('col-test')
            ->setActif(true)
            ->setCategorie($cat)
            ->setLienDirectUniquement($colPrivate)
            ->setAccessToken($colPrivate ? 'token-col-456' : null);
        $this->setId($col, 20);

        $art = (new Article())
            ->setNom('Article Test')
            ->setSlug('art-test')
            ->setActif($artActif)
            ->setCollection($col)
            ->setLienDirectUniquement($artPrivate)
            ->setAccessToken($artPrivate ? 'token-art-789' : null);
        $this->setId($art, 30);

        return [$cat, $col, $art];
    }

    public function testPublicHierarchyIsAccessibleWithoutToken(): void
    {
        [$cat, $col, $art] = $this->buildHierarchy(false, false, false);
        $service = $this->createService();

        $this->assertTrue($service->canAccessCategory($cat));
        $this->assertTrue($service->canAccessCollection($col));
        $this->assertTrue($service->canAccessArticle($art));
    }

    public function testPrivateItemsAreDeniedWithoutToken(): void
    {
        [$cat, $col, $art] = $this->buildHierarchy(true, true, true);
        $service = $this->createService();

        $this->assertFalse($service->canAccessCategory($cat));
        $this->assertFalse($service->canAccessCollection($col));
        $this->assertFalse($service->canAccessArticle($art));
    }

    public function testWrongTokenDoesNotUnlock(): void
    {
        [$cat, $col, $art] = $this->buildHierarchy(true, false, false);
        $service = $this->createService();

        $req = new Request(['cle' => 'mauvais-token']);
        $this->assertFalse($service->tryUnlockFromRequest($req, $cat));
        $this->assertFalse($service->canAccessCategory($cat));
        $this->assertFalse($service->canAccessCollection($col));
        $this->assertFalse($service->canAccessArticle($art));
    }

    public function testUnlockingCategoryGrantsAccessToAllLowerHierarchies(): void
    {
        // Même si collection et article sont aussi marqués privés, débloquer la catégorie donne accès à tout en dessous
        [$cat, $col, $art] = $this->buildHierarchy(true, true, true);
        $service = $this->createService();

        $req = new Request(['cle' => 'token-cat-123']);
        $this->assertTrue($service->tryUnlockFromRequest($req, $cat));

        $this->assertTrue($service->canAccessCategory($cat));
        $this->assertTrue($service->canAccessCollection($col));
        $this->assertTrue($service->canAccessArticle($art));
        $this->assertTrue($service->hasCategoryHierarchyAccess($cat));
        $this->assertTrue($service->hasCollectionHierarchyAccess($col));
    }

    public function testUnlockingCollectionGrantsAccessDownwardNotUpward(): void
    {
        [$cat, $col, $art] = $this->buildHierarchy(true, true, true);
        $service = $this->createService();

        $req = new Request(['cle' => 'token-col-456']);
        $this->assertTrue($service->tryUnlockFromRequest($req, $col));

        // Hiérarchie supérieure (catégorie privée) reste inaccessible
        $this->assertFalse($service->canAccessCategory($cat));
        // Collection et hiérarchie inférieure (article privé) sont accessibles
        $this->assertTrue($service->canAccessCollection($col));
        $this->assertTrue($service->canAccessArticle($art));
        $this->assertTrue($service->hasCollectionHierarchyAccess($col));
    }

    public function testUnlockingArticleGrantsAccessOnlyToArticleNotParents(): void
    {
        [$cat, $col, $art] = $this->buildHierarchy(true, true, true);
        $service = $this->createService();

        $req = new Request(['cle' => 'token-art-789']);
        $this->assertTrue($service->tryUnlockFromRequest($req, $art));

        $this->assertFalse($service->canAccessCategory($cat));
        $this->assertFalse($service->canAccessCollection($col));
        $this->assertTrue($service->canAccessArticle($art));
    }

    public function testArticleInCartRemainsAccessibleWithoutSessionUnlock(): void
    {
        [$cat, $col, $art] = $this->buildHierarchy(true, true, true);
        // L'article #30 est dans le panier, mais la session d'accès privé est vide
        $service = $this->createService(false, [30]);

        $this->assertTrue($service->canAccessArticle($art));
        // Les parents privés restent inaccessibles
        $this->assertFalse($service->canAccessCollection($col));
        $this->assertFalse($service->canAccessCategory($cat));
    }

    public function testInactiveArticleIsAlwaysDeniedEvenWithTokenOrCart(): void
    {
        [, , $art] = $this->buildHierarchy(false, false, true, false);
        $service = $this->createService(true, [30]);

        $req = new Request(['cle' => 'token-art-789']);
        $service->tryUnlockFromRequest($req, $art);

        $this->assertFalse($service->canAccessArticle($art));
    }

    public function testAdminCanAccessPrivateItems(): void
    {
        [$cat, $col, $art] = $this->buildHierarchy(true, true, true);
        $service = $this->createService(true);

        $this->assertTrue($service->canAccessCategory($cat));
        $this->assertTrue($service->canAccessCollection($col));
        $this->assertTrue($service->canAccessArticle($art));
    }
}
