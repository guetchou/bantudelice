<?php

namespace App\Services\Reports;

use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvStream
{
    public function download(string $filename, array $headers, callable $writer): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $writer): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $headers, ';');
            $writer($output);
            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
