<?php

namespace App\Controller;

use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminWebController extends AbstractController
{
    private UserRepository $userRepository;
    private MessageRepository $messageRepository;

    public function __construct(
        MessageRepository $messageRepository,
        UserRepository $userRepository
    ) {
        $this->messageRepository = $messageRepository;
        $this->userRepository = $userRepository;
    }
    #[Route('/admin', name: 'app_admin')]
    public function index(): Response
    {
        $aaaa = 1;
        return $this->render('admin_web/index.html.twig', [
            'controller_name' => 'AdminWebController',
        ]);
    }

    #[Route('/admin/users', name: 'admin_users')]
    public function users(): Response
    {
        $users = $this->userRepository->findAll();
        return $this->render('admin_web/users.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/admin/thoughts', name: 'admin_thoughts')]
    public function thoughts(): Response
    {
        //$users = $this->userRepository->findAll();
        return $this->render('admin_web/thoughts.html.twig', [
            'thoughts' => null,
        ]);
    }

    #[Route('/admin/messages', name: 'admin_messages')]
    public function messages(): Response
    {
        $messages = $this->messageRepository->findAll(); // получи сообщения
        return $this->render('admin_web/messages.html.twig', [
            'messages' => $messages,
            'user' => null,  // явно указываем, что нет конкретного пользователя
        ]);
    }

    #[Route('/admin/messages/{userId}', name: 'admin_user_messages')]
    public function userMessages(int $userId): Response
    {
        $user = $this->userRepository->find($userId);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }
        $messages = $this->messageRepository->findBy(['user' => $user]);

        return $this->render('admin_web/messages.html.twig', [
            'messages' => $messages,
            'user' => $user,
        ]);
    }
}
