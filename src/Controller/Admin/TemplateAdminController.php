<?php

namespace App\Controller\Admin;

use App\Entity\VarianteTemplate;
use App\Repository\CaracteristiqueRepository;
use App\Repository\VarianteTemplateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/templates')]
#[IsGranted('ROLE_ADMIN')]
class TemplateAdminController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private VarianteTemplateRepository $templateRepo,
        private CaracteristiqueRepository $caracRepo,
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
    public function new(Request $request): Response
    {
        $caracteristiques = $this->caracRepo->findBy([], ['nom' => 'ASC']);

        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            $template = new VarianteTemplate();
            $template->setNom(trim($data['nom']));
            $template->setDescription($data['description'] ?? null);

            $caracIds = array_filter((array)($data['caracteristiques'] ?? []), fn($v) => !empty($v));
            foreach ($caracIds as $caracId) {
                $carac = $this->caracRepo->find((int)$caracId);
                if ($carac) {
                    $template->addCaracteristique($carac);
                }
            }

            $this->em->persist($template);
            $this->em->flush();

            $this->addFlash('success', 'Template créé avec succès');
            return $this->redirectToRoute('admin_templates');
        }

        return $this->render('admin/templates/form.html.twig', [
            'template'         => null,
            'caracteristiques' => $caracteristiques,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_templates_edit')]
    public function edit(int $id, Request $request): Response
    {
        $template = $this->templateRepo->find($id);

        if (!$template) {
            throw $this->createNotFoundException('Template introuvable');
        }

        $caracteristiques = $this->caracRepo->findBy([], ['nom' => 'ASC']);

        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            $template->setNom(trim($data['nom']));
            $template->setDescription($data['description'] ?? null);

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

            $this->em->flush();

            $this->addFlash('success', 'Template modifié avec succès');
            return $this->redirectToRoute('admin_templates');
        }

        return $this->render('admin/templates/form.html.twig', [
            'template'         => $template,
            'caracteristiques' => $caracteristiques,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_templates_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        $template = $this->templateRepo->find($id);
        if ($template) {
            $this->em->remove($template);
            $this->em->flush();
        }

        $this->addFlash('success', 'Template supprimé');
        return $this->redirectToRoute('admin_templates');
    }
}
