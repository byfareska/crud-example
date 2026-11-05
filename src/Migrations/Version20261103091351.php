<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261103091351 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category ADD COLUMN version INTEGER DEFAULT 1 NOT NULL');
        $this->addSql('CREATE TEMPORARY TABLE __temp__product_note AS SELECT product_id, note FROM product_note');
        $this->addSql('DROP TABLE product_note');
        $this->addSql('CREATE TABLE product_note (product_id INTEGER NOT NULL, note CLOB NOT NULL, PRIMARY KEY (product_id))');
        $this->addSql('INSERT INTO product_note (product_id, note) SELECT product_id, note FROM __temp__product_note');
        $this->addSql('DROP TABLE __temp__product_note');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__category AS SELECT id, name, description FROM category');
        $this->addSql('DROP TABLE category');
        $this->addSql('CREATE TABLE category (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(80) NOT NULL, description CLOB DEFAULT NULL)');
        $this->addSql('INSERT INTO category (id, name, description) SELECT id, name, description FROM __temp__category');
        $this->addSql('DROP TABLE __temp__category');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_64C19C15E237E06 ON category (name)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__product_note AS SELECT product_id, note FROM product_note');
        $this->addSql('DROP TABLE product_note');
        $this->addSql('CREATE TABLE product_note (product_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, note CLOB NOT NULL)');
        $this->addSql('INSERT INTO product_note (product_id, note) SELECT product_id, note FROM __temp__product_note');
        $this->addSql('DROP TABLE __temp__product_note');
    }
}
