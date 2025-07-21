<?php

namespace App\Command;

use App\Service\WeatherApiService;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:update-weather')]
class UpdateWeatherCommand extends Command
{
    protected static string $defaultName = 'app:update-weather';
    private WeatherApiService $weatherApiService;

    public function __construct(WeatherApiService $weatherApiService)
    {
        parent::__construct();
        $this->weatherApiService = $weatherApiService;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Mise à jour quotidienne des prévisions météo pour toutes les stations.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Début de la mise à jour météo...');

        try {
            $this->weatherApiService->saveWeeklyWeatherForAllLocations();
            $output->writeln('Mise à jour météo terminée avec succès.');
            return Command::SUCCESS;
        } catch (Exception $e) {
            $output->writeln("Erreur lors de la mise à jour : {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
