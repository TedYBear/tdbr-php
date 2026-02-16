<?php

namespace App\Controller\Admin;

use App\Service\MongoDBService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

#[Route('/admin/messages')]
#[IsGranted('ROLE_ADMIN')]
class MessageAdminController extends AbstractController
{
    public function __construct(
        private MongoDBService $mongoService
    ) {
    }

    #[Route('', name: 'admin_messages')]
    public function index(): Response
    {
        $messages = $this->mongoService->getCollection('messages')
            ->find([], ['sort' => ['createdAt' => -1]])
            ->toArray();

        return $this->render('admin/messages/index.html.twig', [
            'messages' => $messages
        ]);
    }

    #[Route('/{id}/mark-read', name: 'admin_messages_mark_read', methods: ['POST'])]
    public function markAsRead(string $id): Response
    {
        $this->mongoService->getCollection('messages')->updateOne(
            ['_id' => new ObjectId($id)],
            ['$set' => ['lu' => true]]
        );

        $this->addFlash('success', 'Message marqué comme lu');
        return $this->redirectToRoute('admin_messages');
    }

    #[Route('/{id}/delete', name: 'admin_messages_delete', methods: ['POST'])]
    public function delete(string $id): Response
    {
        $this->mongoService->getCollection('messages')->deleteOne(['_id' => new ObjectId($id)]);
        $this->addFlash('success', 'Message supprimé');
        return $this->redirectToRoute('admin_messages');
    }
}
