<?php
namespace AndresMontanez\RecommendationsBundle\Service;

use AndresMontanez\RecommendationsBundle\Entity\User;
use AndresMontanez\RecommendationsBundle\Entity\Item;
use AndresMontanez\RecommendationsBundle\Entity\Tag;
use AndresMontanez\RecommendationsBundle\Entity\Action;

use MongoClient;

class RecommendationService
{
	protected $cacheDir;
	protected $db;

	public function __construct($cacheDir, $mongoServer, $mongoDatabase)
	{
		$this->cacheDir = $cacheDir . '/recomendations';
		$mongo = new MongoClient($mongoServer);
		$this->db = $mongo->selectDB($mongoDatabase);

		if (!is_dir($this->cacheDir)) {
			mkdir($this->cacheDir);
		}
	}

    public function itemRegistered($item, $type, $namespace = 'default')
    {
        $query = array(
            'namespace' => $namespace,
            'type' => $type,
            'id' => $item
        );

        $itemsCollection = $this->db->items;
        $document = $itemsCollection->findOne($query);

        return (boolean) $document;
    }

	public function registerItem($item, $type, $tags = array(), $namespace = 'default')
	{
		$document = array(
			'namespace' => $namespace,
			'type' => $type,
			'id' => $item,
			'tags' => $tags
		);

		$itemsCollection = $this->db->items;
		$itemsCollection->insert($document);
	}

	public function removeItem($item, $namespace = 'default')
	{
		$document = array(
			'namespace' => $namespace,
			'id' => $item,
		);

		$itemsCollection = $this->db->items;
		$itemsCollection->remove($document);
	}

	/**
	 * User <Jon> <rated> the <movie Batman> with a value of <5>
	 * User <Betty> <liked> the <episode Pilot>
	 */
	public function addAction($user, $verb, $item, $value = 1, $namespace = 'default')
	{
        $document = array(
    		'namespace' => $namespace,
    		'verb' => $verb,
    		'user' => $user,
    		'item' => $item,
    		'value' => $value
        );

        $actionsCollection = $this->db->actions;
        $actionsCollection->insert($document);
	}

	public function getRecommendations($userId, $top = 30, $type = null, $tag = null, $namespace = 'default') {
		$masterTable = $this->getSimilarities($type, $tag, $namespace);

		$actionsCollection = $this->db->actions;
		$actions = $actionsCollection->find(array('user' => $userId));

		$scores = array();
		$totalSimilarities = array();
		$ranking = array();

		$alreadyRatedByUser = array();
		foreach ($actions as $action) {
			$alreadyRatedByUser[$action['item']] = 1;
		}

        foreach ($actions as $action) {
            foreach ($masterTable[$action['item']] as $itemId => $similarity) {
            	if (isset($alreadyRatedByUser[$itemId])) {
            		continue;
            	}

            	// Weighted sum of rating times similarity
                if (!isset($scores[$itemId])) {
                	$scores[$itemId] = 0;
                }
                $scores[$itemId] += ($similarity * $action['value']);

                // Sum of all the similarities
                if (!isset($totalSimilarities[$itemId])) {
                	$totalSimilarities[$itemId] = 0;
                }
                $totalSimilarities[$itemId] += $similarity;
        	}
    	}

    	foreach ($scores as $itemId => $score) {
    		if ($totalSimilarities[$itemId] > 0) {
    			$newScore = ($score / $totalSimilarities[$itemId]);
    		} else {
    			$newScore = 0;
    		}

    		$ranking[$itemId] = $newScore;
    	}

    	arsort($ranking);
    	$ranking = array_slice($ranking, 0, $top, true);

    	return $this->normalizeRanking($ranking);
	}

	protected function normalizeRanking($ranking)
	{
		$result = array();
		$itemsCollection = $this->db->items;

		foreach ($ranking as $itemId => $value) {
			$item = $itemsCollection->findOne(array('id' => $itemId));
			$result[] = array(
				'id' => $item['id'],
				'type' => $item['type'],
				'ranking' => $value
			);
		}

		return $result;
	}

	protected function getSimilarities($type = null, $tag = null, $namespace = 'default')
	{
		if ($type !== null) {
	        if ($tag !== null) {
	        	$cacheFile = $this->cacheDir . '/dataset-' . $namespace . '-type-' . $type . '-tag-' . $tag . '.php';
	        	return include $cacheFile;
	        }

	        $cacheFile = $this->cacheDir . '/dataset-' . $namespace . '-type-' . $type . '.php';
	        return include $cacheFile;
		}

		if ($tag !== null) {
			$cacheFile = $this->cacheDir . '/dataset-' . $namespace . '-tag-' . $tag . '.php';
			return include $cacheFile;
		}

		$cacheFile = $this->cacheDir . '/dataset-' . $namespace . '-global.php';
		return include $cacheFile;

/*
		$data = array();
		$actionsCollection = $this->db->actions;
		$actions = $actionsCollection->find();

		foreach ($actions as $action) {
            if (!isset($data[$action['item']])) {
			 	$data[$action['item']] = array();
            }

			if (!isset($data[$action['item']][$action['user']])) {
				$data[$action['item']][$action['user']] = 0;
			}

			$data[$action['item']][$action['user']] += $action['value'];
		}

		$topMatches = array();
		$items = array_keys($data);
		foreach ($items as $itemId) {
			$topMatches[$itemId] = $this->topMatches($data, $itemId);
		}

		return $topMatches;
*/
	}

	public function generateSimilarities()
	{
		$actionsCollection = $this->db->actions;
		$itemsCollection = $this->db->items;
		$namespaces = $actionsCollection->distinct('namespace');

		foreach ($namespaces as $namespace) {
			// Global
			$actions = $actionsCollection->find(array('namespace' => $namespace));
			$data = $this->buildDataset($actions);

			$cacheFile = $this->cacheDir . '/dataset-' . $namespace . '-global.php';
			file_put_contents($cacheFile, "<?php \n return " . var_export($data, true) . ';');

			// Tags
			$tags = $itemsCollection->distinct('tags.id', array('namespace' => $namespace));
            foreach ($tags as $tag) {
            	$itemsIds = array();
            	$items = $itemsCollection->find(array('namespace' => $namespace, 'tags.id' => $tag));
                foreach ($items as $item) {
                	$itemsIds[] = $item['id'];
                }

            	$actions = $actionsCollection->find(array('namespace' => $namespace, 'item' => array('$in' => $itemsIds)));
            	$data = $this->buildDataset($actions);

            	$cacheFile = $this->cacheDir . '/dataset-' . $namespace . '-tag-' . $tag . '.php';
            	file_put_contents($cacheFile, "<?php \n return " . var_export($data, true) . ';');
            }

            // Types
            $types = $itemsCollection->distinct('type', array('namespace' => $namespace));
            foreach ($types as $type) {
            	$itemsIds = array();
            	$items = $itemsCollection->find(array('namespace' => $namespace, 'type' => $type));
            	foreach ($items as $item) {
            		$itemsIds[] = $item['id'];
            	}

            	$actions = $actionsCollection->find(array('namespace' => $namespace, 'item' => array('$in' => $itemsIds)));
            	$data = $this->buildDataset($actions);

            	$cacheFile = $this->cacheDir . '/dataset-' . $namespace . '-type-' . $type . '.php';
            	file_put_contents($cacheFile, "<?php \n return " . var_export($data, true) . ';');

            	// Types and Tags
            	$tags = $itemsCollection->distinct('tags.id', array('namespace' => $namespace, 'type' => $type));
            	foreach ($tags as $tag) {
            		$itemsIds = array();
            		$items = $itemsCollection->find(array('namespace' => $namespace, 'type' => $type, 'tags.id' => $tag));
            		foreach ($items as $item) {
            			$itemsIds[] = $item['id'];
            		}

            		$actions = $actionsCollection->find(array('namespace' => $namespace, 'item' => array('$in' => $itemsIds)));
            		$data = $this->buildDataset($actions);

            		$cacheFile = $this->cacheDir . '/dataset-' . $namespace . '-type-' . $type . '-tag-' . $tag . '.php';
            		file_put_contents($cacheFile, "<?php \n return " . var_export($data, true) . ';');
            	}
            }
		}
	}

	protected function buildDataset($actions)
	{
		$data = array();

		foreach ($actions as $action) {
			if (!isset($data[$action['item']])) {
				$data[$action['item']] = array();
			}

			if (!isset($data[$action['item']][$action['user']])) {
				$data[$action['item']][$action['user']] = 0;
			}

			$data[$action['item']][$action['user']] += $action['value'];
		}

		$topMatches = array();
		$items = array_keys($data);
		foreach ($items as $itemId) {
			$topMatches[$itemId] = $this->topMatches($data, $itemId);
		}

		return $topMatches;
	}

	protected function topMatches($data, $itemId, $top = 100)
	{
		$topMatches = array();
		foreach ($data as $otherItemId => $scores) {
			if ($otherItemId != $itemId) {
				$topMatches[$otherItemId] = $this->distance($data, $itemId, $otherItemId);
			}
		}

		arsort($topMatches);
		$topMatches = array_slice($topMatches, 0, $top, true);

		return $topMatches;
	}

	protected function distance($data, $item1, $item2)
	{
		$distances = array();
		foreach ($data[$item1] as $userId => $rating) {
			if (isset($data[$item2][$userId])) {
				$distances[] = $userId;
			}
		}

        if (count($distances) == 0) {
        	return 0;
        }

        // Add up all the preferences
        $sum1 = 0;
        foreach ($distances as $userId) {
        	$sum1 += $data[$item1][$userId];
        }

        $sum2 = 0;
        foreach ($distances as $userId) {
        	$sum2 += $data[$item2][$userId];
        }

        // Sum up the squares
        $sum1Sq = 0;
        foreach ($distances as $userId) {
        	$sum1Sq += pow($data[$item1][$userId], 2);
        }

        $sum2Sq = 0;
        foreach ($distances as $userId) {
        	$sum2Sq += pow($data[$item2][$userId], 2);
        }

        $pSum = 0;
        foreach ($distances as $userId) {
        	$pSum += ($data[$item1][$userId] * $data[$item2][$userId]);
        }

        # Calculate Pearson score
        $num = $pSum - (($sum1 * $sum2) / count($distances));

        $den = sqrt(
        		($sum1Sq - (pow($sum1, 2) / count($distances)))
        		*
        		($sum2Sq - (pow($sum2, 2) / count($distances)))
			);
        if ($den == 0) {
        	return 0;
        }

        $r = $num / $den;

        return $r;
	}
}
