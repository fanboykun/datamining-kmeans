<?php

namespace App\Http\Controllers;

use App\Models\Study;
use App\Services\KMeans\Space;
use App\Services\KMeans\Algorithm;

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
        $space->addAllPointToSpace($points, 'key', 'val');
        $dataset = collect($space->solveWithIterationCallback(2, 3, [0,1]));
        // dd($dataset->all());
        return view('welcome', ['dataset' => $dataset->except('iteration_count')]);
        // return $space->solveAndGroupPointByCluster(2);
        // return $space->solveAndroupClusterByPoint(2);
    }

}
