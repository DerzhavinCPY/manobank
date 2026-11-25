<?php

declare(strict_types=1);

namespace App\Modules\Reports\Services;

use Composer\InstalledVersions;
use DOMDocument;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use ZipArchive;

final class SpreadsheetReport
{
    public function __construct(private readonly ReportData $data) {}

    public function bytes(iterable $snapshots): string
    {
        $book = new Spreadsheet;
        $book->getProperties()->setCreator('')->setLastModifiedBy('')->setTitle('МаноБанк — сохранённые расчёты');
        $book->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11);
        $summary = $book->getActiveSheet()->setTitle('Расчёты');
        $rows = $book->createSheet()->setTitle('Графики');
        $this->row($summary, 1, ['ID расчёта', 'Продукт', 'Дата расчёта (UTC+4)', 'Раздел', 'Показатель', 'Значение']);
        $this->row($rows, 1, ['ID расчёта', 'Продукт', 'Месяц', 'Дата', 'Остаток на начало', 'Платёж / выплата', 'Основной долг', 'Проценты', 'Начислено всего', 'Остаток']);
        $summaryRow = 2;
        $scheduleRow = 2;
        foreach ($snapshots as $record) {
            $result = $this->data->prepare($record['payload']);
            foreach (['Исходные данные' => $result['inputs'], 'Итоги' => $result['summary_labels'], 'Условия' => ['Порядок расчёта' => $result['conditions']]] as $section => $values) {
                foreach ($values as $label => $value) {
                    $this->row($summary, $summaryRow++, [$record['id'], $result['product']['name'], $result['calculated_at'], $section, $label, $value]);
                }
            }
            foreach ($result['rows'] as $entry) {
                $this->row($rows, $scheduleRow++, [$record['id'], $result['product']['name'], $entry['month'] ?? '', $entry['date'] ?? '', $entry['opening_balance'], $entry['payment'] ?? '', $entry['principal_payment'] ?? '', $entry['interest'], $entry['accrued_interest'] ?? '', $entry['closing_balance'] ?? '']);
            }
        }
        foreach ([$summary, $rows] as $sheet) {
            $lastColumn = $sheet->getHighestDataColumn();
            $lastRow = $sheet->getHighestDataRow();
            $sheet->freezePane('C2');
            $sheet->setAutoFilter('A1:'.$lastColumn.$lastRow);
            $sheet->getStyle('A1:'.$lastColumn.'1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle('A1:'.$lastColumn.'1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF12355B');
            $sheet->getRowDimension(1)->setRowHeight(32);
            $sheet->getStyle('A1:'.$lastColumn.$lastRow)->getAlignment()->setWrapText(true)->setVertical('top');
            foreach (range('A', $lastColumn) as $column) {
                $sheet->getColumnDimension($column)->setWidth($column === 'A' ? 39 : ($column === 'B' ? 30 : 24));
            }
            $sheet->getPageSetup()->setOrientation('landscape')->setFitToWidth(1)->setFitToHeight(0);
            $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);
        }
        $summary->getColumnDimension('E')->setWidth(38);
        $summary->getColumnDimension('F')->setWidth(65);
        $directory = base_path('../temp/reports');
        File::ensureDirectoryExists($directory);
        $path = tempnam($directory, 'xlsx-');
        try {
            (new Xlsx($book))->save($path);
            $archive = new ZipArchive;
            if ($archive->open($path) !== true) {
                throw new RuntimeException('Не удалось проверить свойства XLSX.');
            }
            $properties = new DOMDocument;
            $properties->loadXML($archive->getFromName('docProps/app.xml'), LIBXML_NONET);
            $properties->getElementsByTagName('Application')->item(0)->nodeValue = 'PhpSpreadsheet';
            $properties->getElementsByTagName('AppVersion')->item(0)->nodeValue = InstalledVersions::getPrettyVersion('phpoffice/phpspreadsheet');
            $archive->addFromString('docProps/app.xml', $properties->saveXML());
            if (! $archive->close()) {
                throw new RuntimeException('Не удалось сохранить свойства XLSX.');
            }

            return file_get_contents($path);
        } finally {
            File::delete($path);
            $book->disconnectWorksheets();
        }
    }

    private function row(Worksheet $sheet, int $row, array $values): void
    {
        foreach (array_values($values) as $index => $value) {
            $sheet->setCellValueExplicit([$index + 1, $row], (string) $value, DataType::TYPE_STRING);
        }
    }
}
