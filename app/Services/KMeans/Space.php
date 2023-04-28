<?php

namespace App\Services\KMeans;

use Illuminate\Support\Collection;

class Space extends \SplObjectStorage
{
    protected $dimention;

    protected static $rng = 'mt_rand';

    public function __construct($dimention)
    {
        if ($dimention < 1) {
            throw new \LogicException("a space dimention cannot be null or negative");
        }

        $this->dimention = $dimention;
    }

    public static function setRng(callable $fn): void
    {
        static::$rng = $fn;
    }

    public function toArray(): array
    {
        $points = [];
        foreach ($this as $point) {
            $points[] = $point->toArray();
        }

        return ['points' => $points];
    }

    public function newPoint(array $coordinates): Point
    {
        if (count($coordinates) != $this->dimention) {
            throw new \LogicException("(" . implode(',', $coordinates) . ") is not a point of this space");
        }

        return new Point($this, $coordinates);
    }

    public function addPoint(array $coordinates, $data = null): Point
    {
        $this->attach($point = $this->newPoint($coordinates), $data);

        return $point;
    }

    public function addAllPointToSpace(array $points, string $key = null, string $value = null): void
    {
        if(! is_array($points)){
            throw new \LogicException("points must be an array");
        }
        if($key != null && $value != null){
            if(! array_key_exists($value, $points[0]) && ! array_key_exists($key, $points[0])){
                throw new \LogicException("key or value not found in array");
            }
            foreach ($points as $coordinates) {
                $this->addPoint($coordinates[$value], $coordinates[$key]);
            }
        }
        elseif($value == null && $key == null){
            foreach ($points as $coordinates) {
                $this->addPoint($coordinates);
            }
        }

    }

    public function attach($point, $data = null): void
    {
        if (!$point instanceof Point) {
            throw new \InvalidArgumentException("can only attach points to spaces");
        }

        parent::attach($point, $data);
    }

    public function getDimention(): int
    {
        return $this->dimention;
    }

    public function getBoundaries(): array
    {
        if (!count($this)) {
            return [];
        }

        $min = $this->newPoint(array_fill(0, $this->dimention, null));
        $max = $this->newPoint(array_fill(0, $this->dimention, null));

        foreach ($this as $point) {
            for ($n = 0; $n < $this->dimention; $n++) {
                if ($min[$n] === null || $min[$n] > $point[$n]) {
                    $min[$n] = $point[$n];
                }

                if ($max[$n] === null || $max[$n] < $point[$n]) {
                    $max[$n] = $point[$n];
                }
            }
        }

        return [$min, $max];
    }

    public function getRandomPoint(Point $min, Point $max): Point
    {
        $point = $this->newPoint(array_fill(0, $this->dimention, null));
        $rng = static::$rng;

        for ($n = 0; $n < $this->dimention; $n++) {
            $point[$n] = $rng($min[$n], $max[$n]);
        }

        return $point;
    }

    public function solve(int $nbClusters, callable $iterationCallback = null, $initMethod = Cluster::INIT_RANDOM, $selectedPoints = []): array
    {
        // initialize K clusters
        $clusters = $this->initializeClusters($nbClusters, $initMethod, $selectedPoints);

        // there's only one cluster, clusterization has no meaning
        if (count($clusters) == 1) {
            return $clusters;
        }

        // until convergence is reached
        do {
            if ($iterationCallback) {
                $iterationCallback($this, $clusters);
            }
        } while ($this->iterate($clusters));

        // clustering is done.
        return $clusters;
    }

    public function solveAndGroupPointByCluster(int $numberOfCluster, $initMethod = 1, $selectedPoints = []): Collection
    {
        $clusters = $this->solve($numberOfCluster, null, $initMethod, $selectedPoints);
        $result = [];
        foreach ($clusters as $key => $cluster) {
            $result["cluster ".++$key] = [
                'centroid' => $cluster->getCoordinates(),
                'points' => $cluster->toArray()['points'],
                'count' => $cluster->count(),
            ];
        }

        $res = collect($result)->map(function ($item) {
            return [
                'centroid' => $item['centroid'],
                'count' => $item['count'],
                'points' => collect($item['points'])->map(function ($item) {
                    return [
                        'key' => $item['data'],
                        'data' => $item['coordinates'],
                    // 'distance' => $point['distance'],
                    ];
                }),
            ];
        })->sortByDesc('count');
        return $res;
    }

    public function solveAndroupClusterByPoint(int $numberOfCluster, $initMethod = 1, $selectedPoints = []): Collection
    {
        $clusters = $this->solve($numberOfCluster, null, $initMethod, $selectedPoints);
        $result = [];
        foreach ($clusters as $key => $cluster) {
            $result["cluster ".++$key] = [
                'centroid' => $cluster->getCoordinates(),
                'points' => $cluster->toArray()['points'],
                'count' => $cluster->count(),
            ];
        }

        $result = collect($result);
        $data = collect([]);
        foreach ($result as $key => $cluster) {
            foreach ($cluster['points'] as $k => $point) {
                $data->push([
                    'key' => $point['data'],
                    'data' => $point['coordinates'],
                    'cluster' => $key,
                    'centroid' => $cluster['centroid'],
                    // 'distance' => $point['distance'],
                ]);
            }
        }
        return $data;
    }

    public function solveWithIterationCallback(int $numberOfCluster, $initMethod = 1, $selectedPoints = []): array
    {
        $clustersEachIteration = array();
        $this->solve($numberOfCluster, function($space, $clusters) use(&$clustersEachIteration) {
            static $iterations = 0;
            $clustersEachIteration['iteration_count'] = ++$iterations;
            $clustersEachIteration["iteration ".$iterations] = array();

            foreach ($clusters as $i => $cluster) {
                $cluster_number = $i + 1;
                $arr_cluster = $cluster->toArray();
                $clustersEachIteration["iteration ".$iterations]["cluster ".$cluster_number] = [
                    'centroid' => $arr_cluster['centroid']['coordinates'],
                    'points' => $arr_cluster['points'],
                    'points_count' => count($arr_cluster['points']),
                ];
                foreach ($cluster as $j => $point){
                    $arr_point = $point->toArray();
                    $clustersEachIteration["iteration ".$iterations]["cluster ".$cluster_number]['points'][$j] = $arr_point;
                    $clustersEachIteration["iteration ".$iterations]["cluster ".$cluster_number]['points'][$j]['distance'] = $cluster->getDistanceWith($point);
                }
            }
        }, $initMethod, $selectedPoints);
        return $clustersEachIteration;
    }

    protected function initializeClusters(int $nbClusters, int $initMethod, array $points = []): array
    {
        if ($nbClusters <= 0) {
            throw new \InvalidArgumentException("invalid clusters number");
        }

        switch ($initMethod) {
            case Cluster::INIT_RANDOM:
                $clusters = $this->initializeRandomClusters($nbClusters);

                break;

            case Cluster::INIT_KMEANS_PLUS_PLUS:
                $clusters = $this->initializeKmeansPlusPlusClusters($nbClusters);

                break;
            case Cluster::INIT_MANUAL_SELECT:
                $clusters = $this->initializeSelectedCluster($nbClusters, $points);

                break;

            default:
                return [];
        }

        // assign all points to the first cluster
        $clusters[0]->attachAll($this);

        return $clusters;
    }

    protected function initializeKmeansPlusPlusClusters(int $nbClusters): array
    {
        $clusters = [];
        $clusters[] = new Cluster($this, $this->current()->getCoordinates());

        for ($i = 1; $i < $nbClusters; ++$i) {
            $sum = 0;
            $distances = [];
            foreach ($this as $point) {
                $distance = $point->getDistanceWith($point->getClosest($clusters), false);
                $distances[] = $distance;
                $sum += $distance;
            }

            $probabilities = [];
            foreach ($distances as $distance) {
                $probabilities[] = $distance / $sum;
            }

            $cumulativeProbabilities = array_reduce($probabilities, function ($c, $i) {
                $c[] = end($c) + $i;
                return $c;
            }, []);

            $rng = static::$rng;
            $rand = $rng() / mt_getrandmax();
            foreach ($cumulativeProbabilities as $j => $cumulativeProbability) {
                if ($rand < $cumulativeProbability) {
                    foreach ($this as $key => $value) {
                        if ($j == $key) {
                            $clusters[] = new Cluster($this, $value->getCoordinates());
                            break;
                        }
                    }
                    break;
                }
            }
        }

        return $clusters;
    }

    protected function initializeRandomClusters(int $nbClusters): array
    {
        $clusters = [];

        // get the space boundaries to avoid placing clusters centroid too far from points
        list($min, $max) = $this->getBoundaries();

        // initialize N clusters with a random point within space boundaries
        for ($n = 0; $n < $nbClusters; $n++) {
            $clusters[] = new Cluster($this, $this->getRandomPoint($min, $max)->getCoordinates());
        }
        return $clusters;
    }

    public function initializeSelectedCluster(int $nbClusters, array $selectedPoints): array
    {
        if ($nbClusters != count($selectedPoints)) {
            throw new \InvalidArgumentException("invalid selected points key");
        }

        $clusters = [];
        foreach ($selectedPoints as $selectedPoint) {
            $this->rewind();
            foreach ($this as $key => $value) {
                if ($selectedPoint == $key) {
                    $clusters[] = new Cluster($this, $value->getCoordinates());
                    break;
                }
            }
        }

        return $clusters;
    }

    protected function iterate(array $clusters): bool
    {
        $continue = false;

        // migration storages
        $attach = new \SplObjectStorage();
        $detach = new \SplObjectStorage();

        // calculate proximity amongst points and clusters
        foreach ($clusters as $cluster) {
            foreach ($cluster as $point) {
                // find the closest cluster
                $closest = $point->getClosest($clusters);

                // move the point from its old cluster to its closest
                if ($closest !== $cluster) {
                    if (! isset($attach[$closest])) {
                        $attach[$closest] = new \SplObjectStorage();
                    }

                    if (! isset($detach[$cluster])) {
                        $detach[$cluster] = new \SplObjectStorage();
                    }

                    $attach[$closest]->attach($point);
                    $detach[$cluster]->attach($point);

                    $continue = true;
                }
            }
        }

        // perform points migrations
        foreach ($attach as $cluster) {
            $cluster->attachAll($attach[$cluster]);
        }

        foreach ($detach as $cluster) {
            $cluster->detachAll($detach[$cluster]);
        }

        // update all cluster's centroids
        foreach ($clusters as $cluster) {
            $cluster->updateCentroid();
        }

        return $continue;
    }
}
