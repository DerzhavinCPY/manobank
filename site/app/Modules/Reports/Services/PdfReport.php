<?php

declare(strict_types=1);

namespace App\Modules\Reports\Services;

use Barryvdh\DomPDF\PDF;
use Illuminate\Support\Facades\File;

final class PdfReport
{
    public function __construct(private readonly ReportData $data) {}

    public function html(array $snapshot): string
    {
        return view('reports::calculation', ['result' => $this->data->prepare($snapshot)])->render();
    }

    public function bytes(array $snapshot): string
    {
        $temp = base_path('../temp/reports');
        File::ensureDirectoryExists($temp);
        File::ensureDirectoryExists(storage_path('fonts'));
        $pdf = app(PDF::class);
        $pdf->setOptions(['isRemoteEnabled' => false, 'isPhpEnabled' => false, 'tempDir' => $temp, 'defaultFont' => 'DejaVu Sans'], true);
        $pdf->loadHTML($this->html($snapshot))->setPaper('a4');
        $pdf->addInfo(['Title' => 'МаноБанк — '.$snapshot['product']['name'], 'Author' => '', 'Creator' => '']);
        $pdf->render();
        $pdf->getDomPDF()->getCanvas()->page_text(42, 812, '{PAGE_NUM} / {PAGE_COUNT}', null, 8, [0.36, 0.42, 0.47]);

        return $pdf->output();
    }
}
