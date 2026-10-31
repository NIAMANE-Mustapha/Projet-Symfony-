<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Contrôleur gérant l'inscription d'un nouvel internaute.
 */
class RegistrationController extends AbstractController
{
    #[Route('/inscription', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator
    ): Response {
        // Si l'utilisateur est déjà connecté, redirection vers l'accueil
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $user = new Utilisateur();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hachage sécurisé du mot de passe
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            // Rôle utilisateur par défaut
            $user->setRoles(['ROLE_USER']);

            // La date d'inscription est automatiquement initialisée dans l'entité
            if (!$user->getDateInscription()) {
                $user->setDateInscription(new \DateTimeImmutable());
            }

            $entityManager->persist($user);
            $entityManager->flush();

            // Message flash de succès
            $this->addFlash('success', $translator->trans('flash.registered'));

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
