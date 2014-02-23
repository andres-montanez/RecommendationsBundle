<?php
namespace AndresMontanez\RecommendationsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use MongoClient;

class LoadFixtureCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('recommendations:load-fixture')
             ->setDescription('Load fixtures');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $mongo = new MongoClient('mongodb://dbserver:27017');
        $db = $mongo->selectDB('recommendations');

        // Load Tags into memory
        $output->writeln('Loading tags...');
        $tagsToLoad = file(__DIR__ . '/../Resources/fixtures/u.genre');
        $tagsHash = array();
        foreach ($tagsToLoad as $idx => $tag) {
        	$tag = trim($tag);
        	if ($tag != '') {
        		$tag = explode('|', $tag);
        		$tagsHash[$tag[0]] = (integer) $tag[1];
        	}
        }

        // Load Items into mongoDB
        $output->writeln('Loading items...');
        $itemsToLoad = file(__DIR__ . '/../Resources/fixtures/movies.dat');
        $itemsCollection = $db->items;
        foreach ($itemsToLoad as $idx => $item) {
        	$item = trim($item);
        	if ($item != '') {
        		$item = explode('::', $item);

        		$document = array(
    				'namespace' => 'default',
    				'type' => 'movie',
    				'id' => (integer) $item[0],
    				'tags' => array(),
    				'title' => utf8_encode($item[1])
        		);

        		$tags = explode('|', $item[2]);
        		foreach ($tags as $tag) {
    				$document['tags'][] = array(
						'id' => $tagsHash[$tag],
						'title' => $tag
    				);
        		}

        		$itemsCollection->insert($document);
        	}

        	if ($idx % 100 == 0) {
        		$output->writeln($idx);
        	}
        }

        // Load Users Rating
        $output->writeln('Loading ratings...');
        $ratingToLoad = file(__DIR__ . '/../Resources/fixtures/ratings.dat');
        $actionsCollection = $db->actions;
        foreach ($ratingToLoad as $idx => $rating) {
        	$rating = trim($rating);
        	if ($rating != '') {
        		$rating = explode('::', $rating);

        		$document = array(
    				'namespace' => 'default',
    				'verb' => 'rate',
    				'user' => (integer) $rating[0],
    				'item' => (integer) $rating[1],
    				'value' => (integer) $rating[2]
        		);

        		$actionsCollection->insert($document);
        	}

        	if ($idx % 100000 == 0) {
        		$output->writeln($idx);
        	}
        }

/**
 * For 100K ratings
        // Load Tags into memory
        $output->writeln('Loading tags...');
        $tagsToLoad = file(__DIR__ . '/../Resources/fixtures/u.genre');
        $tagsHash = array();
        foreach ($tagsToLoad as $idx => $tag) {
        	$tag = trim($tag);
        	if ($tag != '') {
        		$tag = explode('|', $tag);
        		$tagsHash[(integer) $tag[1]] = $tag[0];
        	}
        }

        // Load Items into mongoDB
        $output->writeln('Loading items...');
        $itemsToLoad = file(__DIR__ . '/../Resources/fixtures/u.item');
        $itemsCollection = $db->items;
        foreach ($itemsToLoad as $idx => $item) {
        	$item = trim($item);
        	if ($item != '') {
        		$item = explode('|', $item);

        		$document = array(
    				'namespace' => 'default',
    				'type' => 'movie',
				    'id' => (integer) $item[0],
    				'tags' => array(),
    				'title' => utf8_encode($item[1])
				);

        		for ($i = 5; $i <= 23; $i++) {
        			if ($item[$i] == 1) {
        				$document['tags'][] = array(
    						'id' => (integer) ($i - 5),
    						'title' => $tagsHash[(integer) ($i - 5)]
        				);
        			}
        		}

        		$itemsCollection->insert($document);
        	}

        	if ($idx % 100 == 0) {
        		$output->writeln($idx);
        	}
        }

        // Load Users Rating
        $output->writeln('Loading ratings...');
        $ratingToLoad = file(__DIR__ . '/../Resources/fixtures/u.data');
        $actionsCollection = $db->actions;
        foreach ($ratingToLoad as $idx => $rating) {
        	$rating = trim($rating);
        	if ($rating != '') {
        		$rating = explode("\t", $rating);

        		$document = array(
    				'namespace' => 'default',
    				'verb' => 'rate',
				    'user' => (integer) $rating[0],
    				'item' => (integer) $rating[1],
    				'value' => (integer) $rating[2]
				);

        		$actionsCollection->insert($document);
        	}

        	if ($idx % 10000 == 0) {
        		$output->writeln($idx);
        	}
        }
*/

    	/*
    	$em = $this->getContainer()->get('doctrine')->getManager();

    	// Load Items
    	$output->writeln('Loading tags...');
    	$tagsToLoad = file(__DIR__ . '/../Resources/fixtures/u.genre');
    	foreach ($tagsToLoad as $idx => $tag) {
    		$tag = trim($tag);
    		if ($tag != '') {
    			$tag = explode('|', $tag);

    			$em->getConnection()->exec(sprintf('INSERT INTO recomm_tag VALUES (%s, %s)', (integer) $tag[1], (integer) $tag[1]));
    		}
    	}

    	// Load Items
    	$output->writeln('Loading items...');
        $itemsToLoad = file(__DIR__ . '/../Resources/fixtures/u.item');
        foreach ($itemsToLoad as $idx => $item) {
        	$item = trim($item);
        	if ($item != '') {
        		$item = explode('|', $item);

        		$em->getConnection()->exec(sprintf('INSERT INTO recomm_item VALUES (%s, %s, "movie")', (integer) $item[0], (integer) $item[0]));

        		for ($i = 5; $i <= 23; $i++) {
        			if ($item[$i] == 1) {
        				$em->getConnection()->exec(sprintf('INSERT INTO recomm_item_tag VALUES (%s, %s)', (integer) $item[0], (integer) ($i - 5)));
        			}
        		}
        	}

        	if ($idx % 100 == 0) {
        		$output->writeln($idx);
        	}
        }

        // Load Users
        $output->writeln('Loading users...');
        $usersToLoad = file(__DIR__ . '/../Resources/fixtures/u.user');
        foreach ($usersToLoad as $idx => $user) {
        	$user = trim($user);
        	if ($user != '') {
        		$user = explode('|', $user);

        		$em->getConnection()->exec(sprintf('INSERT INTO recomm_user VALUES (%s, %s)', (integer) $user[0], (integer) $user[0]));
        	}

        	if ($idx % 100 == 0) {
        		$output->writeln($idx);
        	}
        }

        // Load Users Rating
        $output->writeln('Loading ratings...');
        $ratingToLoad = file(__DIR__ . '/../Resources/fixtures/u.data');
        foreach ($ratingToLoad as $idx => $rating) {
        	$rating = trim($rating);
        	if ($rating != '') {
        		$rating = explode("\t", $rating);

        		$em->getConnection()->exec(sprintf('INSERT INTO recomm_action VALUES (NULL, %s, %s, "rated", %s)', (integer) $rating[0], (integer) $rating[1], (integer) $rating[2]));
        	}

        	if ($idx % 10000 == 0) {
        		$output->writeln($idx);
        	}
        }
        */
    }
}