<?php

namespace App\Services\KMeans;

class Point implements \ArrayAccess
{
    protected $space;
    protected $dimention;
    protected $coordinates;

    public function __construct(Space $space, array $coordinates)
    {
        $this->space       = $space;
        $this->dimention   = $space->getDimention();
        $this->coordinates = $coordinates;
    }

    public function toArray(): array
    {
        return [
            'coordinates' => $this->coordinates,
            'data' => isset($this->space[$this]) ? $this->space[$this] : null,
        ];
    }

    public function getDistanceWith(self $point, bool $precise = true): float
    {
        if ($point->space !== $this->space) {
            throw new \LogicException("can only calculate distances from points in the same space/cluster");
        }

        $distance = 0;
        for ($n = 0; $n < $this->dimention; $n++) {
            $difference = $this->coordinates[$n] - $point->coordinates[$n];
            $distance  += $difference * $difference;
        }

        return $precise ? sqrt($distance) : $distance;
    }

    public function getClosest(iterable $points): ?Point
    {
        $minDistance = PHP_INT_MAX;
        $minPoint = null;
        foreach ($points as $point) {
            $distance = $this->getDistanceWith($point, false);

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $minPoint    = $point;
            }
        }

        return $minPoint;
    }

    public function belongsTo(Space $space): bool
    {
        return $this->space === $space;
    }

    public function getSpace(): Space
    {
        return $this->space;
    }

    public function getCoordinates(): array
    {
        return $this->coordinates;
    }

    public function offsetExists($offset): bool
    {
        return isset($this->coordinates[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->coordinates[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        $this->coordinates[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->coordinates[$offset]);
    }
}
