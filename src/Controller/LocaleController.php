<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur permettant de basculer la langue de l'application.
 */
class LocaleController extends AbstractController
{
    #[Route('/changer-langue/{locale}', name: 'app_change_locale')]
    public function changeLocale(string $locale, Request $request): Response
    {
        // Validation des langues autorisées (français et anglais)
        if (in_array($locale, ['fr', 'en'], true)) {
            $request->getSession()->set('_locale', $locale);
            $request->setLocale($locale);
        }

        // Redirection vers la page précédente ou vers l'accueil par défaut
        $referer = $request->headers->get('referer');
        if ($referer && !str_contains($referer, '/changer-langue/')) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('app_home');
    }
}
