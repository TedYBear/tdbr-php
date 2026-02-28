<?php

namespace App\Controller\Admin;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/utilisateurs', name: 'admin_utilisateurs')]
#[IsGranted('ROLE_ADMIN')]
class UserAdminController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepo,
    ) {}

    #[Route('', name: '', methods: ['GET'])]
    public function index(): Response
    {
        $rows = $this->userRepo->findAllWithCommandeCount();

        return $this->render('admin/users/index.html.twig', [
            'rows'  => $rows,
            'total' => count($rows),
        ]);
    }
}
