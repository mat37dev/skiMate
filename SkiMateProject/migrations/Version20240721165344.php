<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240721165344 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE price (id INT AUTO_INCREMENT NOT NULL, pass_type VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE recorded_track (id INT AUTO_INCREMENT NOT NULL, distance DOUBLE PRECISION DEFAULT NULL, elevation DOUBLE PRECISION DEFAULT NULL, average_speed DOUBLE PRECISION DEFAULT NULL, maximum_speed DOUBLE PRECISION DEFAULT NULL, track_type VARCHAR(255) DEFAULT NULL, weather VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE role (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ski_level (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE statistic (id INT AUTO_INCREMENT NOT NULL, total_distance DOUBLE PRECISION DEFAULT NULL, total_hours TIME DEFAULT NULL, total_elevation DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, role_id INT NOT NULL, ski_level_id INT DEFAULT NULL, statistic_id INT DEFAULT NULL, lastname VARCHAR(255) NOT NULL, firstname VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, phone_number VARCHAR(10) NOT NULL, ski_level VARCHAR(255) DEFAULT NULL, ski_preference VARCHAR(255) DEFAULT NULL, INDEX IDX_8D93D649D60322AC (role_id), INDEX IDX_8D93D649E17EB1C4 (ski_level_id), INDEX IDX_8D93D64953B6268F (statistic_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_request (id INT AUTO_INCREMENT NOT NULL, request_date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', request_status VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D649D60322AC FOREIGN KEY (role_id) REFERENCES role (id)');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D649E17EB1C4 FOREIGN KEY (ski_level_id) REFERENCES ski_level (id)');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D64953B6268F FOREIGN KEY (statistic_id) REFERENCES statistic (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D649D60322AC');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D649E17EB1C4');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D64953B6268F');
        $this->addSql('DROP TABLE price');
        $this->addSql('DROP TABLE recorded_track');
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP TABLE ski_level');
        $this->addSql('DROP TABLE statistic');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE user_request');
    }
}
