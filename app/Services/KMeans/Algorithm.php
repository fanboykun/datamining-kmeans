<?php

//source : https://dev.to/thormeier/algorithm-explained-k-means-clustering-with-php-4nog

namespace App\Services\KMeans;

class Algorithm
{
    // declare(strict_types=1);

    public function iterate($centroids, $dataPoints)
    {
        do {
            $newCentroids = $this->moveCentroids($centroids, $dataPoints);
            $movedDistances = array_map(function ($a, $b) {
                return $this->getDistance($a, $b);
            }, $centroids, $newCentroids);

            $averageDistanceTravelled = array_sum($movedDistances) / count($movedDistances);

            $centroids = $newCentroids;
        } while ($averageDistanceTravelled > 0.0001);
        return $centroids;
    }

    /**
     * @param float $lowerBoundX
     * @param float $lowerBoundY
     * @param float $upperBoundX
     * @param float $upperBoundY
     * @param int $numberOfPoints
     * @return array
     */
    public function generateDataPoints(
        float $lowerBoundX,
        float $lowerBoundY,
        float $upperBoundX,
        float $upperBoundY,
        int $numberOfPoints
    ): array {
        $precision = 1000;

        $lowerBoundX = (int) round($lowerBoundX * $precision);
        $lowerBoundY = (int) round($lowerBoundY * $precision);
        $upperBoundX = (int) round($upperBoundX * $precision);
        $upperBoundY = (int) round($upperBoundY * $precision);

        $points = [];

        for ($i = 0; $i < $numberOfPoints; $i++) {
            $points[] = [
                mt_rand($lowerBoundX, $upperBoundX) / $precision,
                mt_rand($lowerBoundY, $upperBoundY) / $precision,
            ];
        }

        return $points;
    }

    /**
     * @param array $p1
     * @param array $p2
     * @return float
     */
    public function getDistance(array $p1, array $p2): float {
        return sqrt(($p2[0] - $p1[0]) ** 2 + ($p2[1] - $p1[1]) ** 2);
    }

    /**
     * @param array $p
     * @param array $centroids
     * @return int
     */
    public function getNearestCentroidIndex(array $p, array $centroids): int {
        $centroids = array_map(function(array $centroid) use ($p) {
            return $this->getDistance($p, $centroid);
        }, $centroids);

        return array_search(min($centroids), $centroids);
    }

    /**
     * @param array $points
     * @return array
     */
    public function getAveragePoint(array $points): array {
        $pointsCount = count($points);
        if ($pointsCount === 0) {
            return [0, 0];
        }

        return [
            array_sum(array_column($points, 0)) / $pointsCount,
            array_sum(array_column($points, 1)) / $pointsCount,
        ];
    }

    /**
     * @param array $centroids
     * @param array $dataPoints
     * @return array
     */
    public function moveCentroids(array $centroids, array $dataPoints): array {
        $nearestCentroidsMap = array_map(function (array $point) use ($centroids): array {
            return [
                ...$point,
                $this->getNearestCentroidIndex($point, $centroids)
            ];
        }, $dataPoints);

        $newCentroids = [];

        foreach ($centroids as $key => $value) {
            $newCentroids[$key] = $this->getAveragePoint(array_filter($nearestCentroidsMap, function (array $point) use ($key) {
                return $point[2] === $key;
            }));
        }

        return $newCentroids;
    }

}
