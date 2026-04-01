<?php

namespace App\Controller;

use App\Entity\Focus;
use App\Entity\Thought;
use App\Entity\User;
use App\Repository\FocusRepository;
use App\Service\FocusManager;
use App\Service\FocusSourceManager;
use App\Service\InternalThinkingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ThinkingController extends AbstractController
{
    #[Route('/thinking/focus/create', name: 'thinking_create_focus', methods: ['POST'])]
    public function createFocus(Request $request, EntityManagerInterface $em): JsonResponse
    {
// Получаем данные из POST-запроса
        $userId = $request->request->get('user_id');
        $type = $request->request->get('type');
        $topic = $request->request->get('topic');


        $focus = new Focus();
        if ($userId) {
            $user = $em->getRepository(User::class)->find($userId);
            $focus->setUser($user);
        }
        $focus->setSource($type);
        $focus->setTopic($topic);
        $em->persist($focus);
        $em->flush();

// Возвращаем JSON-ответ
        return $this->json([
            'success' => true,
            'message' => 'Данные получены',
            'data' => [
                'user_id' => $userId,
                'type' => $type,
                'topic' => $topic,
            ]
        ]);
    }

    #[Route('/thinking/focus/random_create', name: 'thinking_create_random_focus', methods: ['POST'])]
    public function createRandomFocus(FocusSourceManager $focusSourceManager): JsonResponse
    {
        $focusSourceManager->generate();
        return $this->json([
            'success' => true,
            'message' => 'Данные получены',
            'data' => [
            ]
        ]);
    }

    #[Route('/thinking/think/once', name: 'thinking_think_once', methods: ['POST'])]
    public function thinkOnce(FocusRepository $fr, FocusManager $fm, FocusSourceManager $fsm): JsonResponse
    {
        $message = 'Уже есть о чем подумать!';
        $focusPending = $fr->findNextPending();
        if (empty($focusPending)) {
            $message = 'Не найдено фокусов';
            $focus = $fr->findNextNew();
            if (!$focus) {

                $focus = $fsm->generate();
            }
            if (!empty($focus)) {
                $message = 'Запущено Размышление!';
                $fm->markAsPending($focus);
            }
        }
        return $this->json([
            'success' => true,
            'message' => $message,
            'data' => [
            ]
        ]);
    }
}
