<?php

namespace App\Support\Reports;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExporter
{
    /**
     * Export a collection or array to a streamed CSV response.
     */
    public static function download(iterable $data, array $headers, string $filename = 'export.csv'): StreamedResponse
    {
        return response()->streamDownload(function () use ($data, $headers) {
            $handle = fopen('php://output', 'w');

            // Add BOM for Excel UTF-8 support
            fputs($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Add Header Row
            fputcsv($handle, $headers);

            // Add Data Rows
            foreach ($data as $row) {
                fputcsv($handle, (array) $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
