<?php

namespace App\Controller\Admin;

use App\Entity\GuideTaille;
use App\Repository\GuideTailleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/guides-tailles')]
#[IsGranted('ROLE_ADMIN')]
class GuideTailleAdminController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private GuideTailleRepository $repo,
        private SluggerInterface $slugger,
    ) {
    }

    #[Route('', name: 'admin_guides_tailles')]
    public function index(): Response
    {
        return $this->render('admin/guides_tailles/index.html.twig', [
            'guides' => $this->repo->findBy([], ['nom' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'admin_guides_tailles_new')]
    public function new(Request $request): Response
    {
        $guide = new GuideTaille();
        return $this->save($guide, $request, true);
    }

    #[Route('/{id}/edit', name: 'admin_guides_tailles_edit', requirements: ['id' => '\d+'])]
    public function edit(int $id, Request $request): Response
    {
        $guide = $this->repo->find($id);
        if (!$guide) {
            throw $this->createNotFoundException('Guide introuvable');
        }
        return $this->save($guide, $request, false);
    }

    #[Route('/{id}/clone', name: 'admin_guides_tailles_clone', methods: ['POST'])]
    public function clone(int $id): Response
    {
        $src = $this->repo->find($id);
        if (!$src) {
            throw $this->createNotFoundException('Guide introuvable');
        }

        $copy = new GuideTaille();
        $copy->setNom($src->getNom() . ' (copie)');
        $copy->setSlug($this->generateUniqueSlug($copy->getNom()));
        $copy->setDescription($src->getDescription());
        $copy->setImageDiagramme($src->getImageDiagramme());
        $copy->setMesures($src->getMesures());
        $copy->setColonnes($src->getColonnes());
        $copy->setLignes($src->getLignes());
        $copy->setUnite($src->getUnite());
        $copy->setActif($src->isActif());

        $this->em->persist($copy);
        $this->em->flush();

        $this->addFlash('success', 'Guide cloné avec succès');
        return $this->redirectToRoute('admin_guides_tailles_edit', ['id' => $copy->getId()]);
    }

    #[Route('/{id}/delete', name: 'admin_guides_tailles_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        $guide = $this->repo->find($id);
        if ($guide) {
            $this->em->remove($guide);
            $this->em->flush();
            $this->addFlash('success', 'Guide supprimé');
        }
        return $this->redirectToRoute('admin_guides_tailles');
    }

    private function save(GuideTaille $guide, Request $request, bool $isNew): Response
    {
        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $guide->setNom(trim($data['nom'] ?? ''));

            $slugInput = trim($data['slug'] ?? '');
            if ($slugInput === '') {
                $slugInput = $guide->getNom();
            }
            $guide->setSlug($this->generateUniqueSlug($slugInput, $guide->getId()));

            $guide->setDescription($data['description'] ?? null);
            $guide->setImageDiagramme($data['imageDiagramme'] ?? null);
            $guide->setUnite(($data['unite'] ?? 'cm') === 'pouces' ? 'pouces' : 'cm');
            $guide->setActif(isset($data['actif']));

            $guide->setMesures($this->buildMesures($data['mesures'] ?? []));
            $guide->setColonnes($this->buildColonnes($data['colonnes'] ?? []));
            $guide->setLignes($this->buildLignes($data['lignes'] ?? [], count($guide->getColonnes())));

            if (!$isNew) {
                $guide->setUpdatedAt(new \DateTimeImmutable());
            }

            $this->em->persist($guide);
            $this->em->flush();

            $this->addFlash('success', $isNew ? 'Guide créé' : 'Guide mis à jour');
            return $this->redirectToRoute('admin_guides_tailles_edit', ['id' => $guide->getId()]);
        }

        return $this->render('admin/guides_tailles/form.html.twig', ['guide' => $guide]);
    }

    /** Filtre et nettoie le tableau des points de mesure. */
    private function buildMesures(array $raw): array
    {
        $out = [];
        foreach ($raw as $row) {
            $lettre = trim($row['lettre'] ?? '');
            $nom    = trim($row['nom'] ?? '');
            $desc   = trim($row['description'] ?? '');
            if ($lettre === '' && $nom === '') continue;
            $out[] = ['lettre' => $lettre, 'nom' => $nom, 'description' => $desc];
        }
        return $out;
    }

    /** Filtre et nettoie le tableau d'entêtes de colonnes. */
    private function buildColonnes(array $raw): array
    {
        $out = [];
        foreach ($raw as $col) {
            $col = trim((string)$col);
            if ($col !== '') $out[] = $col;
        }
        return $out;
    }

    /** Filtre et nettoie les lignes du tableau de tailles. */
    private function buildLignes(array $raw, int $nbColonnes): array
    {
        $out = [];
        foreach ($raw as $row) {
            $taille = trim($row['taille'] ?? '');
            if ($taille === '') continue;
            $valeurs = [];
            for ($i = 0; $i < $nbColonnes; $i++) {
                $v = $row['valeurs'][$i] ?? '';
                $valeurs[] = $v === '' ? null : (float)$v;
            }
            $out[] = ['taille' => $taille, 'valeurs' => $valeurs];
        }
        return $out;
    }

    private function generateUniqueSlug(string $source, ?int $exceptId = null): string
    {
        $base = strtolower($this->slugger->slug($source)->toString());
        if ($base === '') $base = 'guide';
        $slug = $base;
        $i = 2;
        while (true) {
            $existing = $this->repo->findOneBy(['slug' => $slug]);
            if (!$existing || ($exceptId !== null && $existing->getId() === $exceptId)) {
                return $slug;
            }
            $slug = $base . '-' . $i++;
        }
    }
}
