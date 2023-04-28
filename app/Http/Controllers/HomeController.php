<?php

namespace App\Http\Controllers;

use App\Models\Study;
use App\Services\KMeans\Space;
use App\Services\KMeans\Algorithm;

class HomeController extends Controller
{

    public function index()
    {
        $lib = $this->calculateByLib();
        // $alg =  $this->calculateByAlg();
        dd($lib);
    }

    public function calculateByAlg()
    {
        $algo = new Algorithm();
        // $dataPoints = $algo->generateDataPoints(0, 0, 100, 100, 10);
        $dataPoints = [
            [0.567, 0.7], [0.259, 0.58], [0.89, 0.785], [0.447, 0.498], [0.254, 0.311],
            [0.741, 0.138], [0.088, 0.371], [0.146, 0.12], [0.022, 0.202], [0.111, 0.284],
            [2.45, 2.829], [2.101, 2.728], [2.018, 2.813], [2.498, 2.929], [2.613, 2.799],
            [2.663, 2.435], [2.757, 2.314], [2.571, 2.457], [2.086, 2.804], [2.636, 2.785],
        ];
        $centroid = array($dataPoints[0], $dataPoints[1]);
        dd($algo->iterate($centroid, $dataPoints));
    }

    public function calculateByLib()
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
        $space->addAllPointToSpace($points, 'key', 'val');
        $data = collect($space->solveWithIterationCallback(2));
        return $data->all();
        // return $space->solveAndGroupPointByCluster(2);
        // return $space->solveAndroupClusterByPoint(2);
    }

}
