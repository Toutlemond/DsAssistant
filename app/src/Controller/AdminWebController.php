<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminWebController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(): Response
    {
        $aaaa= 1;
        return $this->render('admin_web/index.html.twig', [
            'controller_name' => 'AdminWebController',
        ]);
    }
}
