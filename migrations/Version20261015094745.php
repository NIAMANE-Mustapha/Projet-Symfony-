<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261015094745 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creation des tables e-commerce: produit, utilisateur, panier, contenu_panier';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE `contenu_panier` (id INT AUTO_INCREMENT NOT NULL, quantite INT NOT NULL, date DATETIME NOT NULL, produit_id INT NOT NULL, panier_id INT NOT NULL, INDEX IDX_80507DC0F347EFB (produit_id), INDEX IDX_80507DC0F77D927C (panier_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `panier` (id INT AUTO_INCREMENT NOT NULL, date_achat DATETIME DEFAULT NULL, etat TINYINT(1) DEFAULT 0 NOT NULL, utilisateur_id INT NOT NULL, INDEX IDX_24CC0DF2FB88E14F (utilisateur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `produit` (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, prix DOUBLE PRECISION NOT NULL, stock INT NOT NULL, photo VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `utilisateur` (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, roles JSON NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE `contenu_panier` ADD CONSTRAINT FK_80507DC0F347EFB FOREIGN KEY (produit_id) REFERENCES `produit` (id)');
        $this->addSql('ALTER TABLE `contenu_panier` ADD CONSTRAINT FK_80507DC0F77D927C FOREIGN KEY (panier_id) REFERENCES `panier` (id)');
        $this->addSql('ALTER TABLE `panier` ADD CONSTRAINT FK_24CC0DF2FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES `utilisateur` (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `contenu_panier` DROP FOREIGN KEY FK_80507DC0F347EFB');
        $this->addSql('ALTER TABLE `contenu_panier` DROP FOREIGN KEY FK_80507DC0F77D927C');
        $this->addSql('ALTER TABLE `panier` DROP FOREIGN KEY FK_24CC0DF2FB88E14F');
        $this->addSql('DROP TABLE `contenu_panier`');
        $this->addSql('DROP TABLE `panier`');
        $this->addSql('DROP TABLE `produit`');
        $this->addSql('DROP TABLE `utilisateur`');
    }
}
