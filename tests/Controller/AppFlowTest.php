<?php

namespace App\Tests\Controller;

use App\Entity\Produit;
use App\Entity\Utilisateur;
use App\Repository\PanierRepository;
use App\Repository\ProduitRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AppFlowTest extends WebTestCase
{
    public function testHomePageIsSuccessful(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('h1');
    }

    public function testAnonymousUserRedirectedFromProtectedRoutes(): void
    {
        $client = static::createClient();

        // Panier requiert d'être connecté
        $client->request('GET', '/panier');
        $this->assertResponseRedirects('/connexion');

        // Compte requiert d'être connecté
        $client->request('GET', '/compte');
        $this->assertResponseRedirects('/connexion');

        // Super Admin requiert d'être connecté
        $client->request('GET', '/super-admin');
        $this->assertResponseRedirects('/connexion');
    }

    public function testAuthenticatedUserCanAccessPanierAndCompte(): void
    {
        $client = static::createClient();
        $userRepo = static::getContainer()->get(UtilisateurRepository::class);
        $user = $userRepo->findOneBy(['email' => 'user@shop.com']);

        $this->assertNotNull($user, 'L\'utilisateur user@shop.com doit exister');

        $client->loginUser($user);

        $client->request('GET', '/panier');
        $this->assertResponseIsSuccessful();

        $client->request('GET', '/compte');
        $this->assertResponseIsSuccessful();
    }

    public function testAdminAccessToProductCreation(): void
    {
        $client = static::createClient();
        $userRepo = static::getContainer()->get(UtilisateurRepository::class);
        $admin = $userRepo->findOneBy(['email' => 'admin@shop.com']);

        $this->assertNotNull($admin, 'L\'administrateur admin@shop.com doit exister');

        $client->loginUser($admin);

        $client->request('GET', '/admin/produit/nouveau');
        $this->assertResponseIsSuccessful();
    }

    public function testSuperAdminDashboardAccess(): void
    {
        $client = static::createClient();
        $userRepo = static::getContainer()->get(UtilisateurRepository::class);
        $superAdmin = $userRepo->findOneBy(['email' => 'superadmin@shop.com']);

        $this->assertNotNull($superAdmin, 'Le super admin superadmin@shop.com doit exister');

        $client->loginUser($superAdmin);

        $crawler = $client->request('GET', '/super-admin');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Super Admin');
    }

    public function testNormalUserForbiddenFromSuperAdmin(): void
    {
        $client = static::createClient();
        $userRepo = static::getContainer()->get(UtilisateurRepository::class);
        $user = $userRepo->findOneBy(['email' => 'user@shop.com']);

        $client->loginUser($user);

        $client->request('GET', '/super-admin');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testProductDetailPageAndAddToCart(): void
    {
        $client = static::createClient();
        $userRepo = static::getContainer()->get(UtilisateurRepository::class);
        $produitRepo = static::getContainer()->get(ProduitRepository::class);

        $user = $userRepo->findOneBy(['email' => 'user@shop.com']);
        $produit = $produitRepo->findOneBy([]);

        $this->assertNotNull($produit, 'Au moins un produit doit exister');

        // Sans connexion : affiche le bouton de connexion
        $client->request('GET', '/produit/' . $produit->getId());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('a[href="/connexion"]');

        // Avec connexion : formulaire d'ajout au panier disponible
        $client->loginUser($user);
        $crawler = $client->request('GET', '/produit/' . $produit->getId());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form[action="/panier/ajouter/' . $produit->getId() . '"]');
    }

    public function testCartCheckoutAndOrderCreation(): void
    {
        $client = static::createClient();
        $userRepo = static::getContainer()->get(UtilisateurRepository::class);
        $panierRepo = static::getContainer()->get(PanierRepository::class);

        $user = $userRepo->findOneBy(['email' => 'user@shop.com']);
        $client->loginUser($user);

        // Accès à la page panier
        $crawler = $client->request('GET', '/panier');
        $this->assertResponseIsSuccessful();

        // Récupération du formulaire d'achat
        $button = $crawler->selectButton('Acheter le panier');
        if ($button->count() > 0) {
            $form = $button->form();
            $client->submit($form);

            // Doit rediriger vers la page de commande
            $this->assertResponseRedirects();
            $crawler = $client->followRedirect();
            $this->assertResponseIsSuccessful();
            $this->assertSelectorTextContains('h1', 'Contenu de la commande #');
        }
    }
}
