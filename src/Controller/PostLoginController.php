<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PostLoginController extends AbstractController
{
    #[Route('/post-login', name: 'post_login')]
    public function postLogin(): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('connexion');
        }

        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->redirectToRoute('admin_dashboard');
        }

        if (in_array('ROLE_DEPOT_VENTE', $user->getRoles())) {
            return $this->redirectToRoute('depot_vente_dashboard');
        }

        return $this->redirectToRoute('home');
    }
}
