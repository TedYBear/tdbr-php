<?php
namespace App\Controller;

use App\Entity\Avis;
use App\Form\AvisType;
use App\Repository\AvisRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class AvisController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private AvisRepository $avisRepo,
    ) {}

    #[Route('/vos-retours', name: 'avis_liste')]
    public function liste(): Response
    {
        return $this->render('public/avis.html.twig', [
            'avisListe' => $this->avisRepo->findVisibles(),
        ]);
    }

    #[Route('/vos-retours/ajouter', name: 'avis_ajouter', methods: ['GET', 'POST'])]
    public function ajouter(Request $request, SluggerInterface $slugger): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('connexion', ['redirect' => '/vos-retours/ajouter']);
        }

        $form = $this->createForm(AvisType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $photoFilename = null;
            $photoFile = $form->get('photo')->getData();

            if ($photoFile) {
                $safeFilename = $slugger->slug(pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME));
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/avis';

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }

                $photoFile->move($uploadDir, $newFilename);
                $photoFilename = $newFilename;
            }

            $avis = new Avis();
            $avis->setUser($this->getUser());
            $avis->setContenu($form->get('contenu')->getData());
            $avis->setNote($form->get('note')->getData());
            $avis->setPhotoFilename($photoFilename);

            $this->em->persist($avis);
            $this->em->flush();

            $this->addFlash('success', 'Merci pour votre avis ! Il sera publié après validation par notre équipe.');
            return $this->redirectToRoute('avis_liste');
        }

        return $this->render('public/avis_form.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
