<?php
namespace AndresMontanez\RecommendationsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TestCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('recommendations:test')
             ->setDescription('Test');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
    	$recommendationService = $this->getContainer()->get('andres_montanez_recommendations.recommendation');

    	$recommendations = $recommendationService->getRecommendations(10, 15);
    	print_r($recommendations);
    }
}