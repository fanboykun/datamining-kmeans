<?php

namespace App\Services\KMeans;

class Cluster extends Point implements \IteratorAggregate, \Countable
{
    public const INIT_RANDOM = 1;
    public const INIT_KMEANS_PLUS_PLUS = 2;
    public const INIT_MANUAL_SELECT = 3;

    protected $space;
    protected $points;

    public function __construct(Space $space, array $coordinates)
    {
        parent::__construct($space, $coordinates);
        $this->points = new \SplObjectStorage();
    }

    public function toArray(): array
    {
        $points = [];
        foreach ($this->points as $point) {
            $points[] = $point->toArray();
        }

        return [
            'centroid' => parent::toArray(),
            'points'   => $points,
        ];
    }

    public function attach(Point $point): Point
    {
        if ($point instanceof self) {
            throw new \LogicException("cannot attach a cluster to another");
        }

        $this->points->attach($point);
        return $point;
    }

    public function detach(Point $point): Point
    {
        $this->points->detach($point);
        return $point;
    }

    public function attachAll(\SplObjectStorage $points): void
    {
        $this->points->addAll($points);
    }

    public function detachAll(\SplObjectStorage $points): void
    {
        $this->points->removeAll($points);
    }

    public function updateCentroid(): void
    {
        if (!$count = count($this->points)) {
            return;
        }

        $centroid = $this->space->newPoint(array_fill(0, $this->dimention, 0));

        foreach ($this->points as $point) {
            for ($n = 0; $n < $this->dimention; $n++) {
                $centroid->coordinates[$n] += $point->coordinates[$n];
            }
        }

        for ($n = 0; $n < $this->dimention; $n++) {
            $this->coordinates[$n] = $centroid->coordinates[$n] / $count;
        }
    }

    public function getIterator(): \Iterator
    {
        return $this->points;
    }

    public function count(): int
    {
        return count($this->points);
    }
}
