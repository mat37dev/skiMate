<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240929150544 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE roles (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ski_level (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE statistics (id INT AUTO_INCREMENT NOT NULL, total_distance DOUBLE PRECISION NOT NULL, total_hours DOUBLE PRECISION NOT NULL, total_elevation DOUBLE PRECISION NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE users (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', ski_level_id INT DEFAULT NULL, statistics_id INT NOT NULL, lastname VARCHAR(255) NOT NULL, firstname VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, phone_number VARCHAR(255) NOT NULL, INDEX IDX_1483A5E9E17EB1C4 (ski_level_id), UNIQUE INDEX UNIQ_1483A5E99A2595B2 (statistics_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_roles (users_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', roles_id INT NOT NULL, INDEX IDX_54FCD59F67B3B43D (users_id), INDEX IDX_54FCD59F38C751C4 (roles_id), PRIMARY KEY(users_id, roles_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9E17EB1C4 FOREIGN KEY (ski_level_id) REFERENCES ski_level (id)');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E99A2595B2 FOREIGN KEY (statistics_id) REFERENCES statistics (id)');
        $this->addSql('ALTER TABLE user_roles ADD CONSTRAINT FK_54FCD59F67B3B43D FOREIGN KEY (users_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_roles ADD CONSTRAINT FK_54FCD59F38C751C4 FOREIGN KEY (roles_id) REFERENCES roles (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E9E17EB1C4');
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E99A2595B2');
        $this->addSql('ALTER TABLE user_roles DROP FOREIGN KEY FK_54FCD59F67B3B43D');
        $this->addSql('ALTER TABLE user_roles DROP FOREIGN KEY FK_54FCD59F38C751C4');
        $this->addSql('DROP TABLE roles');
        $this->addSql('DROP TABLE ski_level');
        $this->addSql('DROP TABLE statistics');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE user_roles');
    }
}
