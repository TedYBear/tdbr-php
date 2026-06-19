<?php

namespace App\Controller\Admin;

use App\Entity\VarianteTemplate;
use App\Repository\CaracteristiqueRepository;
use App\Repository\TypePersonnalisationRepository;
use App\Repository\VarianteTemplateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/templates')]
#[IsGranted('ROLE_ADMIN')]
class TemplateAdminController extends AbstractController
{
    private const PDF_RELATIVE_DIR = 'uploads/templates';

    public function __construct(
        private EntityManagerInterface $em,
        private VarianteTemplateRepository $templateRepo,
        private CaracteristiqueRepository $caracRepo,
        private TypePersonnalisationRepository $persoRepo,
    ) {
    }

    #[Route('', name: 'admin_templates')]
    public function index(): Response
    {
        $templates = $this->templateRepo->findBy([], ['nom' => 'ASC']);

        return $this->render('admin/templates/index.html.twig', [
            'templates' => $templates,
        ]);
    }

    #[Route('/new', name: 'admin_templates_new')]
    public function new(Request $request, SluggerInterface $slugger): Response
    {
        $caracteristiques = $this->caracRepo->findBy([], ['nom' => 'ASC']);
        $personnalisations = $this->persoRepo->findBy([], ['ordre' => 'ASC', 'nom' => 'ASC']);

        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            $template = new VarianteTemplate();
            $template->setNom(trim($data['nom']));
            $template->setDescription($data['description'] ?? null);

            $this->handleGuidePdfUpload($template, $request, $slugger);

            $caracIds = array_filter((array)($data['caracteristiques'] ?? []), fn($v) => !empty($v));
            foreach ($caracIds as $caracId) {
                $carac = $this->caracRepo->find((int)$caracId);
                if ($carac) {
                    $template->addCaracteristique($carac);
                }
            }

            $persoIds = array_filter((array)($data['personnalisations'] ?? []), fn($v) => !empty($v));
            foreach ($persoIds as $persoId) {
                $perso = $this->persoRepo->find((int)$persoId);
                if ($perso) {
                    $template->addPersonnalisation($perso);
                }
            }

            $this->em->persist($template);
            $this->em->flush();

            $this->addFlash('success', 'Template créé avec succès');
            return $this->redirectToRoute('admin_templates');
        }

        return $this->render('admin/templates/form.html.twig', [
            'template'          => null,
            'caracteristiques'  => $caracteristiques,
            'personnalisations' => $personnalisations,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_templates_edit')]
    public function edit(int $id, Request $request, SluggerInterface $slugger): Response
    {
        $template = $this->templateRepo->find($id);

        if (!$template) {
            throw $this->createNotFoundException('Template introuvable');
        }

        $caracteristiques = $this->caracRepo->findBy([], ['nom' => 'ASC']);
        $personnalisations = $this->persoRepo->findBy([], ['ordre' => 'ASC', 'nom' => 'ASC']);

        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            $template->setNom(trim($data['nom']));
            $template->setDescription($data['description'] ?? null);

            $this->handleGuidePdfUpload($template, $request, $slugger);

            // Reset caractéristiques
            foreach ($template->getCaracteristiques()->toArray() as $c) {
                $template->removeCaracteristique($c);
            }
            $caracIds = array_filter((array)($data['caracteristiques'] ?? []), fn($v) => !empty($v));
            foreach ($caracIds as $caracId) {
                $carac = $this->caracRepo->find((int)$caracId);
                if ($carac) {
                    $template->addCaracteristique($carac);
                }
            }

            // Reset personnalisations
            foreach ($template->getPersonnalisations()->toArray() as $p) {
                $template->removePersonnalisation($p);
            }
            $persoIds = array_filter((array)($data['personnalisations'] ?? []), fn($v) => !empty($v));
            foreach ($persoIds as $persoId) {
                $perso = $this->persoRepo->find((int)$persoId);
                if ($perso) {
                    $template->addPersonnalisation($perso);
                }
            }

            $this->em->flush();

            $this->addFlash('success', 'Template modifié avec succès');
            return $this->redirectToRoute('admin_templates');
        }

        return $this->render('admin/templates/form.html.twig', [
            'template'          => $template,
            'caracteristiques'  => $caracteristiques,
            'personnalisations' => $personnalisations,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_templates_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        $template = $this->templateRepo->find($id);
        if ($template) {
            $this->deleteGuidePdfFile($template->getGuideTaillePdfUrl());
            $this->em->remove($template);
            $this->em->flush();
        }

        $this->addFlash('success', 'Template supprimé');
        return $this->redirectToRoute('admin_templates');
    }

    /**
     * Gère trois cas pour le PDF guide des tailles :
     * - case "Supprimer le PDF actuel" cochée → suppression du fichier + null
     * - nouveau fichier uploadé → suppression de l'ancien + stockage du nouveau
     * - aucune action → on garde la valeur actuelle
     */
    private function handleGuidePdfUpload(VarianteTemplate $template, Request $request, SluggerInterface $slugger): void
    {
        // Suppression explicite
        if ($request->request->has('removeGuideTaillePdf')) {
            $this->deleteGuidePdfFile($template->getGuideTaillePdfUrl());
            $template->setGuideTaillePdfUrl(null);
            return;
        }

        /** @var UploadedFile|null $file */
        $file = $request->files->get('guideTaillePdfFile');
        if (!$file instanceof UploadedFile) {
            return;
        }

        if (strtolower($file->getClientOriginalExtension()) !== 'pdf'
            && $file->getMimeType() !== 'application/pdf') {
            $this->addFlash('error', 'Le fichier doit être un PDF.');
            return;
        }

        $original = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safe     = $slugger->slug($original)->toString();
        $newName  = $safe . '-' . uniqid() . '.pdf';

        try {
            $file->move(
                $this->getParameter('kernel.project_dir') . '/public/' . self::PDF_RELATIVE_DIR,
                $newName
            );
            // Supprime l'ancien fichier seulement après upload réussi
            $this->deleteGuidePdfFile($template->getGuideTaillePdfUrl());
            $template->setGuideTaillePdfUrl('/' . self::PDF_RELATIVE_DIR . '/' . $newName);
        } catch (FileException $e) {
            $this->addFlash('error', "Erreur lors de l'upload du PDF : " . $e->getMessage());
        }
    }

    private function deleteGuidePdfFile(?string $url): void
    {
        if (!$url) return;
        // On ne supprime que les fichiers locaux (chemin commençant par /uploads/templates/)
        if (!str_starts_with($url, '/' . self::PDF_RELATIVE_DIR . '/')) return;

        $abs = $this->getParameter('kernel.project_dir') . '/public' . $url;
        if (is_file($abs)) {
            @unlink($abs);
        }
    }
}
