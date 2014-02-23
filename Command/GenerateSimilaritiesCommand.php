<?php
namespace AndresMontanez\RecommendationsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateSimilaritiesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('recommendations:generate-similarities')
             ->setDescription('Generate Item-Based similarities');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
    	$recommendationService = $this->getContainer()->get('andres_montanez_recommendations.recommendation');

    	$recommendationService->generateSimilarities();
    }
}