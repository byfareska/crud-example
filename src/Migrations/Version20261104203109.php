<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261104203109 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Files: the library and the usages of the uploaded files, the gallery of a product, the logo of a supplier.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE crud_file (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, storage VARCHAR(64) NOT NULL, path VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, mime_type VARCHAR(127) DEFAULT NULL, size BIGINT DEFAULT NULL, width INTEGER DEFAULT NULL, height INTEGER DEFAULT NULL, created_at DATETIME NOT NULL)');
        $this->addSql('CREATE INDEX crud_file_created ON crud_file (storage, created_at)');
        $this->addSql('CREATE UNIQUE INDEX crud_file_reference ON crud_file (storage, path)');
        $this->addSql('CREATE TABLE crud_file_usage (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, reference VARCHAR(320) NOT NULL, resource VARCHAR(64) NOT NULL, record_id VARCHAR(64) NOT NULL, field VARCHAR(128) NOT NULL)');
        $this->addSql('CREATE INDEX crud_file_usage_reference ON crud_file_usage (reference)');
        $this->addSql('CREATE INDEX crud_file_usage_record ON crud_file_usage (resource, record_id)');
        $this->addSql('CREATE TABLE product_image (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, file VARCHAR(255) NOT NULL, position INTEGER NOT NULL, alt VARCHAR(255) DEFAULT NULL, product_id INTEGER NOT NULL, CONSTRAINT FK_64617F034584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_64617F034584665A ON product_image (product_id)');
        $this->addSql('ALTER TABLE supplier ADD COLUMN logo VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE crud_file');
        $this->addSql('DROP TABLE crud_file_usage');
        $this->addSql('DROP TABLE product_image');
        $this->addSql('CREATE TEMPORARY TABLE __temp__supplier AS SELECT id, name, email FROM supplier');
        $this->addSql('DROP TABLE supplier');
        $this->addSql('CREATE TABLE supplier (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(120) NOT NULL, email VARCHAR(180) DEFAULT NULL)');
        $this->addSql('INSERT INTO supplier (id, name, email) SELECT id, name, email FROM __temp__supplier');
        $this->addSql('DROP TABLE __temp__supplier');
    }
}
