<?php

namespace App\Http\Controllers;

use App\Services\GoogleSheetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GoogleSheetController extends Controller
{
    public function index()
    {
        $rows = 10;
        $cols = 52;
        $data = [];

        // Log::info("")
        for ($i = 0; $i < $rows; $i++) {
            $data[$i] = [];
            for ($j = 0; $j < $cols; $j++) {
                // Gerar valores de uma distribuição uniforme [0, 1]
                $data[$i][$j] = mt_rand() / mt_getrandmax();
            }
        }

        // Log::info("")
        $cumulativeData = [];
        foreach ($data as $i => $row) {
            $cumulativeData[$i] = [];
            $sum = 0;
            foreach ($row as $value) {
                $sum += $value;
                $cumulativeData[$i][] = [
                    'sum' => $sum,
                    'value' => $value
                ];
            }
        }
        $googleSheetService = new GoogleSheetService();
        $googleSheetService->uploadToGoogleSheets($cumulativeData);
    }
}
