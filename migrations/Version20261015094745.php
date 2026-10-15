<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261015094745 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE "contenu_panier" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, quantite INTEGER NOT NULL, date DATETIME NOT NULL, produit_id INTEGER NOT NULL, panier_id INTEGER NOT NULL, CONSTRAINT FK_80507DC0F347EFB FOREIGN KEY (produit_id) REFERENCES "produit" (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_80507DC0F77D927C FOREIGN KEY (panier_id) REFERENCES "panier" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_80507DC0F347EFB ON "contenu_panier" (produit_id)');
        $this->addSql('CREATE INDEX IDX_80507DC0F77D927C ON "contenu_panier" (panier_id)');
        $this->addSql('CREATE TABLE "panier" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, date_achat DATETIME DEFAULT NULL, etat BOOLEAN DEFAULT 0 NOT NULL, utilisateur_id INTEGER NOT NULL, CONSTRAINT FK_24CC0DF2FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES "utilisateur" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_24CC0DF2FB88E14F ON "panier" (utilisateur_id)');
        $this->addSql('CREATE TABLE "produit" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, prix DOUBLE PRECISION NOT NULL, stock INTEGER NOT NULL, photo VARCHAR(255) DEFAULT NULL)');
        $this->addSql('CREATE TABLE "utilisateur" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, roles CLOB NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON "utilisateur" (email)');
        $this->addSql('CREATE TABLE messenger_messages (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, body CLOB NOT NULL, headers CLOB NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL)');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages (queue_name, available_at, delivered_at, id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE "contenu_panier"');
        $this->addSql('DROP TABLE "panier"');
        $this->addSql('DROP TABLE "produit"');
        $this->addSql('DROP TABLE "utilisateur"');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
