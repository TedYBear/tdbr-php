<?php

namespace App\Controller\Admin;

use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/messages')]
#[IsGranted('ROLE_ADMIN')]
class MessageAdminController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private MessageRepository $messageRepo,
    ) {
    }

    #[Route('', name: 'admin_messages')]
    public function index(): Response
    {
        $messages = $this->messageRepo->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/messages/index.html.twig', [
            'messages' => $messages
        ]);
    }

    #[Route('/{id}/mark-read', name: 'admin_messages_mark_read', methods: ['POST'])]
    public function markAsRead(int $id): Response
    {
        $message = $this->messageRepo->find($id);
        if ($message) {
            $message->setLu(true);
            $this->em->flush();
        }

        $this->addFlash('success', 'Message marqué comme lu');
        return $this->redirectToRoute('admin_messages');
    }

    #[Route('/{id}/delete', name: 'admin_messages_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        $message = $this->messageRepo->find($id);
        if ($message) {
            $this->em->remove($message);
            $this->em->flush();
        }

        $this->addFlash('success', 'Message supprimé');
        return $this->redirectToRoute('admin_messages');
    }
}
