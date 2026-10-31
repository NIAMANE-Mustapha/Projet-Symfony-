<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Contrôleur gérant l'authentification des utilisateurs (connexion / déconnexion).
 */
class SecurityController extends AbstractController
{
    #[Route('/connexion', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Si l'utilisateur est déjà connecté, redirection vers l'accueil
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        // Récupération de l'erreur de connexion si existante
        $error = $authenticationUtils->getLastAuthenticationError();
        // Dernier nom d'utilisateur / email saisi
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/deconnexion', name: 'app_logout')]
    public function logout(): void
    {
        // Cette méthode sera interceptée par le firewall de Symfony
        throw new \LogicException('Cette méthode est interceptée par la clé logout du firewall.');
    }
}
