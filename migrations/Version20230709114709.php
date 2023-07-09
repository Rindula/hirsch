<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\Uid\Uuid;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230709114709 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('SET FOREIGN_KEY_CHECKS=0');
        $this->addSql('ALTER TABLE payhistory CHANGE paypalme_id paypalme_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE paypalmes CHANGE id id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\'');

        $paypalmes = $this->connection->fetchAllAssociative('SELECT * FROM paypalmes');
        foreach ($paypalmes as $paypalme) {
            $this->addSql('UPDATE paypalmes SET id = :newid WHERE id = :id', ['id' => $paypalme['id'], 'newid' => Uuid::v7()->toBinary()]);
            $this->addSql('UPDATE payhistory SET paypalme_id = :newid WHERE paypalme_id = :id', ['id' => $paypalme['id'], 'newid' => Uuid::v7()->toBinary()]);
        }
        $this->addSql('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(Schema $schema): void
    {
        throw new \RuntimeException('No way to go down!');
    }
}
