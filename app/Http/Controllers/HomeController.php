<?php

namespace App\Http\Controllers;

use App\Models\Study;
use KMeans\Space;


class HomeController extends Controller
{
    public function index()
    {
        $space = new Space(8);
        $points  = Study::all()->map(function ($study, $k) {
            return [
                'key' => $study->student_id,
                'val' => [
                $study->matematika,
                $study->fisika,
                $study->kimia,
                $study->biologi,
                $study->sejarah,
                $study->akuntansi,
                $study->sosiologi,
                $study->geografi,
                ],
            ];
        })->toArray();
        foreach ($points as $coordinates) {
            $space->addPoint($coordinates['val'], $coordinates['key']);
        }
        $clusters = $space->solve(2);
        $result = [];
        $iterations = 0;
        foreach ($clusters as $key => $cluster) {
            $result["cluster ".++$key] = [
                'centroid' => $cluster->getCoordinates(),
                'points' => $cluster->toArray()['points'],
                'count' => $cluster->count(),
            ];
        }
        // $this->groupPointByCluster($result);
        $this->groupClusterByPoint($result);
    }
    public function groupPointByCluster(array $result)
    {
        $result = collect($result)->map(function ($item) {
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
        // dd($result);
    }

    public function groupClusterByPoint(array $result)
    {
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
        dd($data);
    }
}
