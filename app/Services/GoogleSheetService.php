<?php

namespace App\Services;

use Exception;
use Google\Client;
use Google\Service\Sheets;
class GoogleSheetService
{
    public function uploadToGoogleSheets($cumulativeData)
    {

        $pathJsonCredentials = getenv('','app/google/credentials.json');

        if(!$pathJsonCredentials){
            throw new Exception("No google credentials configuration files. Please configure your .env", 1);
        }

        // Autenticação com o Google Sheets
        $client = new Client();
        $client->setAuthConfig(storage_path($pathJsonCredentials));
        $client->addScope(Sheets::SPREADSHEETS);

        $service = new Sheets($client);

        // Criar nova planilha
        $spreadsheet = new \Google\Service\Sheets\Spreadsheet([
            'properties' => [
                'title' => 'Cumulative Sums Data'
            ]
        ]);

        $spreadsheet = $service->spreadsheets->create($spreadsheet, [
            'fields' => 'spreadsheetId'
        ]);
        $spreadsheetId = $spreadsheet->spreadsheetId;

        // Prepare data for upload to Google Sheets
        $values = [];
        foreach ($cumulativeData as $rowIndex => $row) {
            array_unshift($row, 'Individual ' . ($rowIndex + 1)); // Add the individual's name
            $values[] = $row;
        }

        // Send data to spreadsheet
        $body = new \Google\Service\Sheets\ValueRange([
            'values' => $values
        ]);
        $range = 'Sheet1!A1';
        $service->spreadsheets_values->update($spreadsheetId, $range, $body, [
            'valueInputOption' => 'RAW'
        ]);

        // Create a chart in Google Sheets
        $this->createChart($service, $spreadsheetId);
    }

    private function createChart($service, $spreadsheetId)
    {
        $requests = [
            new \Google\Service\Sheets\Request([
                'addChart' => [
                    'chart' => [
                        'spec' => [
                            'title' => 'Somas Cumulativas',
                            'basicChart' => [
                                'chartType' => 'LINE',
                                'legendPosition' => 'BOTTOM_LEGEND',
                                'axis' => [
                                    ['position' => 'BOTTOM_AXIS', 'title' => 'Week'],
                                    ['position' => 'LEFT_AXIS', 'title' => 'Cumulative sum']
                                ],
                                'domains' => [
                                    [
                                        'domain' => [
                                            'sourceRange' => [
                                                'sources' => [
                                                    ['sheetId' => 0, 'startRowIndex' => 0, 'endRowIndex' => 52, 'startColumnIndex' => 1, 'endColumnIndex' => 52]
                                                ]
                                            ]
                                        ]
                                    ]
                                ],
                                'series' => [
                                    [
                                        'series' => [
                                            'sourceRange' => [
                                                'sources' => [
                                                    ['sheetId' => 0, 'startRowIndex' => 0, 'endRowIndex' => 10, 'startColumnIndex' => 1, 'endColumnIndex' => 52]
                                                ]
                                            ]
                                        ],
                                        'targetAxis' => 'LEFT_AXIS'
                                    ]
                                ]
                            ]
                        ],
                        'position' => [
                            'overlayPosition' => [
                                'anchorCell' => ['sheetId' => 0, 'rowIndex' => 0, 'columnIndex' => 0],
                                'offsetXPixels' => 0,
                                'offsetYPixels' => 0
                            ]
                        ]
                    ]
                ]
            ])
        ];

        $batchUpdateRequest = new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
            'requests' => $requests
        ]);
        $service->spreadsheets->batchUpdate($spreadsheetId, $batchUpdateRequest);
    }
}
