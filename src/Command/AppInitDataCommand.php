<?php

namespace App\Command;

use App\Entity\ContenuPanier;
use App\Entity\Panier;
use App\Entity\Produit;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:init-data',
    description: 'Initialise les données de test (utilisateurs, produits, paniers)',
)]
class AppInitDataCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $userRepo = $this->entityManager->getRepository(Utilisateur::class);

        $superAdmin = $userRepo->findOneBy(['email' => 'superadmin@shop.com']);
        if (!$superAdmin) {
            $superAdmin = new Utilisateur();
            $superAdmin->setNom('Boss');
            $superAdmin->setPrenom('Super');
            $superAdmin->setEmail('superadmin@shop.com');
            $superAdmin->setRoles(['ROLE_SUPER_ADMIN']);
            $superAdmin->setDateInscription(new \DateTimeImmutable());
            $superAdmin->setPassword($this->passwordHasher->hashPassword($superAdmin, 'password'));
            $this->entityManager->persist($superAdmin);
            $io->note('Création du compte Super Admin: superadmin@shop.com / password');
        }

        $admin = $userRepo->findOneBy(['email' => 'admin@shop.com']);
        if (!$admin) {
            $admin = new Utilisateur();
            $admin->setNom('Admin');
            $admin->setPrenom('Sarah');
            $admin->setEmail('admin@shop.com');
            $admin->setRoles(['ROLE_ADMIN']);
            $admin->setDateInscription(new \DateTimeImmutable());
            $admin->setPassword($this->passwordHasher->hashPassword($admin, 'password'));
            $this->entityManager->persist($admin);
            $io->note('Création du compte Admin: admin@shop.com / password');
        }

        $client = $userRepo->findOneBy(['email' => 'user@shop.com']);
        if (!$client) {
            $client = new Utilisateur();
            $client->setNom('Martin');
            $client->setPrenom('Alex');
            $client->setEmail('user@shop.com');
            $client->setRoles(['ROLE_USER']);
            $client->setDateInscription(new \DateTimeImmutable());
            $client->setPassword($this->passwordHasher->hashPassword($client, 'password'));
            $this->entityManager->persist($client);
            $io->note('Création du compte Client: user@shop.com / password');
        }

        $produitRepo = $this->entityManager->getRepository(Produit::class);
        $sampleProducts = [
            [
                'nom' => 'Casque Audio Sans Fil ANC Prestige',
                'description' => 'Casque circum-aural haute fidélité doté d\'une réduction active de bruit adaptative, autonomie de 40 heures et finitions en cuir véritable.',
                'prix' => 299.99,
                'stock' => 15,
            ],
            [
                'nom' => 'Montre Connectée Chrono Elite',
                'description' => 'Montre intelligente avec boîtier en titane brossé, capteur de fréquence cardiaque avancé, GPS intégré et résistance à l\'eau jusqu\'à 50 mètres.',
                'prix' => 349.00,
                'stock' => 8,
            ],
            [
                'nom' => 'Clavier Mécanique Sans Fil Lumina',
                'description' => 'Clavier mécanique compact 75% avec switches lubrifiés à chaud, rétro-éclairage RGB discret et connectivité tri-mode ultra réactive.',
                'prix' => 149.50,
                'stock' => 25,
            ],
            [
                'nom' => 'Enceinte Bluetooth Nomade Stéréo',
                'description' => 'Enceinte portative délivrant un son 360° puissant et immersif, autonomie de 24h et étanchéité IPX7 pour toutes vos aventures.',
                'prix' => 119.00,
                'stock' => 20,
            ],
            [
                'nom' => 'Sac à Dos Urbain Étanche Premium',
                'description' => 'Conception ergonomique en tissu Cordura déperlant, compartiment rembourré pour ordinateur 16 pouces et rangements sécurisés RFID.',
                'prix' => 89.90,
                'stock' => 12,
            ],
            [
                'nom' => 'Appareil Photo Instantané Rétro',
                'description' => 'Boîtier vintage avec objectif autofocus haute précision, flash dynamique intégré et double exposition créative.',
                'prix' => 129.99,
                'stock' => 5,
            ],
        ];

        foreach ($sampleProducts as $data) {
            $existing = $produitRepo->findOneBy(['nom' => $data['nom']]);
            if (!$existing) {
                $p = new Produit();
                $p->setNom($data['nom']);
                $p->setDescription($data['description']);
                $p->setPrix($data['prix']);
                $p->setStock($data['stock']);
                $this->entityManager->persist($p);
            }
        }

        $this->entityManager->flush();

        $panierRepo = $this->entityManager->getRepository(Panier::class);
        $activeCart = $panierRepo->findOneBy(['utilisateur' => $client, 'etat' => false]);
        if (!$activeCart && $client) {
            $products = $produitRepo->findAll();
            if (!empty($products)) {
                $cart = new Panier();
                $cart->setUtilisateur($client);
                $cart->setEtat(false);

                $item = new ContenuPanier();
                $item->setPanier($cart);
                $item->setProduit($products[0]);
                $item->setQuantite(2);
                $item->setDate(new \DateTimeImmutable());
                $cart->addContenuPanier($item);

                $this->entityManager->persist($cart);
                $this->entityManager->persist($item);
                $this->entityManager->flush();
                $io->note('Panier test non acheté généré pour user@shop.com');
            }
        }

        $io->success('Les données initiales de test ont été créées avec succès !');

        return Command::SUCCESS;
    }
}
