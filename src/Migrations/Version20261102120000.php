<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * The notes of the products (a section of the product's forms).
 */
final class Version20261102120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'The notes of the products';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE product_note (product_id INTEGER NOT NULL, note CLOB NOT NULL, PRIMARY KEY (product_id))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE product_note');
    }
}
