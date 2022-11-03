<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    #[Route('/', name: 'app_login')]
    public function index(AuthenticationUtils $authenticationUtils): Response
    {
        // Si l'utilisateur est connecté, on le redirige vers la page profil
        if ($this->getUser()) {
            if ($this->isGranted('ROLE_CANDIDATE')) {
                return $this->redirectToRoute('app_candidate_profil', ['id' => $this->getUser()->getId()]);
            } else if ($this->isGranted('ROLE_COMPANY')) {

            } else {

            }
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('login/index.html.twig', [
            'last_username' => $lastUsername,
            'error'         => $error,
        ]);
    }


    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout()
    {
        
    }
    
}

