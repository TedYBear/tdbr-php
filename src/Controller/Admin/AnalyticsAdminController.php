<?php

namespace App\Controller\Admin;

use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Repository\PageViewRepository;
use App\Repository\ProductCollectionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/analytics')]
#[IsGranted('ROLE_ADMIN')]
class AnalyticsAdminController extends AbstractController
{
    private const ROUTE_LABELS = [
        'home'         => 'Accueil',
        'catalogue'    => 'Catalogue',
        'presentation' => 'Présentation',
        'avis_liste'   => 'Avis',
        'devis'        => 'Devis / Sur-mesure',
        'contact'      => 'Contact',
        'categorie'    => 'Catégories (toutes)',
        'collection'   => 'Collections (toutes)',
        'article_detail' => 'Articles (tous)',
    ];

    public function __construct(
        private PageViewRepository          $pageViewRepo,
        private ArticleRepository           $articleRepo,
        private CategoryRepository          $categoryRepo,
        private ProductCollectionRepository $collectionRepo,
    ) {}

    #[Route('', name: 'admin_analytics')]
    public function index(Request $request): Response
    {
        $periode = $request->query->get('periode', '7j');
        [$from, $to] = $this->resolvePeriod($periode);

        $totalVues        = $this->pageViewRepo->countViews($from, $to);
        $totalVisiteurs   = $this->pageViewRepo->countUniqueVisitors($from, $to);

        $todayFrom = new \DateTimeImmutable('today midnight');
        $todayTo   = new \DateTimeImmutable('today 23:59:59');
        $vuesToday  = $this->pageViewRepo->countViews($todayFrom, $todayTo);

        $topPageAllTime = $this->pageViewRepo->mostViewedPageAllTime();
        $topPageLabel   = $topPageAllTime
            ? (self::ROUTE_LABELS[$topPageAllTime['routeName']] ?? $topPageAllTime['routeName'])
            : '—';

        $topArticlesRaw   = $this->pageViewRepo->topArticles($from, $to, 10);
        $topPagesRaw      = $this->pageViewRepo->topPages($from, $to, 10);
        $topCatsRaw       = $this->pageViewRepo->topCategories($from, $to, 5);
        $topColsRaw       = $this->pageViewRepo->topCollections($from, $to, 5);
        $viewsByDay       = $this->pageViewRepo->viewsByDay($from, $to, 30);

        $topArticles = $this->hydrateArticles($topArticlesRaw);
        $topCategories = $this->hydrateCategories($topCatsRaw);
        $topCollections = $this->hydrateCollections($topColsRaw);

        $topPages = array_map(function (array $row) {
            $row['label'] = self::ROUTE_LABELS[$row['routeName']] ?? $row['routeName'];
            return $row;
        }, $topPagesRaw);

        return $this->render('admin/analytics/index.html.twig', [
            'periode'        => $periode,
            'from'           => $from,
            'to'             => $to,
            'totalVues'      => $totalVues,
            'totalVisiteurs' => $totalVisiteurs,
            'vuesToday'      => $vuesToday,
            'topPageLabel'   => $topPageLabel,
            'topArticles'    => $topArticles,
            'topPages'       => $topPages,
            'topCategories'  => $topCategories,
            'topCollections' => $topCollections,
            'viewsByDay'     => $viewsByDay,
        ]);
    }

    private function resolvePeriod(string $periode): array
    {
        $to = new \DateTimeImmutable('today 23:59:59');

        $from = match ($periode) {
            'today' => new \DateTimeImmutable('today midnight'),
            '7j'    => new \DateTimeImmutable('-6 days midnight'),
            '30j'   => new \DateTimeImmutable('-29 days midnight'),
            'all'   => new \DateTimeImmutable('2000-01-01 00:00:00'),
            default => new \DateTimeImmutable('-6 days midnight'),
        };

        return [$from, $to];
    }

    private function hydrateArticles(array $rows): array
    {
        $ids = array_filter(array_column($rows, 'entityId'));
        $articles = $ids
            ? $this->articleRepo->findBy(['id' => array_values($ids)])
            : [];

        $map = [];
        foreach ($articles as $a) {
            $map[$a->getId()] = $a;
        }

        $result = [];
        foreach ($rows as $row) {
            $article = $map[$row['entityId']] ?? null;
            $result[] = [
                'slug' => $row['entitySlug'],
                'nom'  => $article?->getNom() ?? $row['entitySlug'],
                'vues' => (int) $row['vues'],
            ];
        }
        return $result;
    }

    private function hydrateCategories(array $rows): array
    {
        $ids  = array_filter(array_column($rows, 'entityId'));
        $cats = $ids ? $this->categoryRepo->findBy(['id' => array_values($ids)]) : [];
        $map  = [];
        foreach ($cats as $c) { $map[$c->getId()] = $c; }

        $result = [];
        foreach ($rows as $row) {
            $cat = $map[$row['entityId']] ?? null;
            $result[] = [
                'slug' => $row['entitySlug'],
                'nom'  => $cat?->getNom() ?? $row['entitySlug'],
                'vues' => (int) $row['vues'],
            ];
        }
        return $result;
    }

    private function hydrateCollections(array $rows): array
    {
        $ids  = array_filter(array_column($rows, 'entityId'));
        $cols = $ids ? $this->collectionRepo->findBy(['id' => array_values($ids)]) : [];
        $map  = [];
        foreach ($cols as $c) { $map[$c->getId()] = $c; }

        $result = [];
        foreach ($rows as $row) {
            $col = $map[$row['entityId']] ?? null;
            $result[] = [
                'slug' => $row['entitySlug'],
                'nom'  => $col?->getNom() ?? $row['entitySlug'],
                'vues' => (int) $row['vues'],
            ];
        }
        return $result;
    }
}
