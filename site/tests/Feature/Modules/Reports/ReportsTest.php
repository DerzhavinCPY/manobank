<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Reports;

use App\Modules\Administration\Models\Administrator;
use App\Modules\Calculations\Services\CalculationHistory;
use App\Modules\Catalog\Database\Seeders\CalculatorSeeder;
use App\Modules\Reports\Services\PdfReport;
use App\Modules\Reports\Services\SpreadsheetReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Tests\TestCase;

final class ReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CalculatorSeeder::class);
    }

    public function test_pdf_uses_all_saved_rows_and_the_same_formatted_values(): void
    {
        $id = $this->postJson('/calculations/pension', ['amount' => 3000, 'years' => 22, 'gender' => 'male'])->assertCreated()->json('id');
        $snapshot = app(CalculationHistory::class)->snapshot($id);
        $report = app(PdfReport::class);
        $html = $report->html($snapshot);
        self::assertStringContainsString('6 183 ₽', $html);
        self::assertStringContainsString('20 365 ₽', $html);
        self::assertSame(240, substr_count($html, 'data-schedule-row'));
        self::assertStringStartsWith('%PDF-', $report->bytes($snapshot));
    }

    public function test_xlsx_preserves_inputs_totals_full_schedule_and_safe_text(): void
    {
        $id = $this->postJson('/calculations/mortgage', ['amount' => 2000000, 'down_payment' => 25, 'years' => 20])->assertCreated()->json('id');
        $snapshot = app(CalculationHistory::class)->snapshot($id);
        $snapshot['product']['name'] = '=HYPERLINK("https://example.com")';
        $bytes = app(SpreadsheetReport::class)->bytes([['id' => $id, 'payload' => $snapshot]]);
        $path = tempnam(sys_get_temp_dir(), 'report-check-');
        try {
            file_put_contents($path, $bytes);
            $archive = new \ZipArchive;
            self::assertTrue($archive->open($path));
            self::assertStringContainsString('<Application>PhpSpreadsheet</Application>', $archive->getFromName('docProps/app.xml'));
            $archive->close();
            $book = (new Xlsx)->load($path);
            $summary = $book->getSheetByName('Расчёты');
            $rows = $book->getSheetByName('Графики');
            self::assertSame($snapshot['product']['name'], $summary->getCell('B2')->getValue());
            self::assertSame('s', $summary->getCell('B2')->getDataType());
            self::assertSame(241, $rows->getHighestDataRow());
            self::assertSame('14 080 ₽', $rows->getCell('F2')->getValue());
            self::assertStringContainsString('35 200 ₽', json_encode($summary->toArray(), JSON_UNESCAPED_UNICODE));
            $book->disconnectWorksheets();
        } finally {
            @unlink($path);
        }
    }

    public function test_xlsx_export_is_available_only_after_administrative_login(): void
    {
        $this->get('/admin/calculations/export')->assertRedirect('/admin/login');
        $id = $this->postJson('/calculations/consumer', ['amount' => 100000, 'years' => 1])->assertCreated()->json('id');
        $admin = Administrator::query()->create(['email' => 'supp.manobank@mail.ru', 'password' => 'Local-Test-Password-2026']);
        $this->actingAs($admin, 'admin')->get('/admin/calculations/export?'.http_build_query(['selected' => [$id]]))->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_export_contains_only_selected_calculations_and_their_complete_schedules(): void
    {
        $mortgage = $this->postJson('/calculations/mortgage', ['amount' => 2000000, 'down_payment' => 25, 'years' => 1])->assertCreated()->json('id');
        $auto = $this->postJson('/calculations/auto', ['amount' => 1000000, 'down_payment' => 0, 'years' => 1])->assertCreated()->json('id');
        $excluded = $this->postJson('/calculations/consumer', ['amount' => 100000, 'years' => 1])->assertCreated()->json('id');
        $admin = Administrator::query()->create(['email' => 'supp.manobank@mail.ru', 'password' => 'Local-Test-Password-2026']);
        $response = $this->actingAs($admin, 'admin')->get('/admin/calculations/export?'.http_build_query(['selected' => [$mortgage, $auto]]))->assertOk();
        $path = tempnam(base_path('../temp/reports'), 'selected-check-');
        try {
            file_put_contents($path, $response->getContent());
            $book = (new Xlsx)->load($path);
            foreach ($book->getAllSheets() as $sheet) {
                $ids = array_values(array_unique(array_column(array_slice($sheet->toArray(), 1), 0)));
                self::assertSame([$mortgage, $auto], $ids);
                self::assertNotContains($excluded, $ids);
            }
            self::assertSame(25, $book->getSheetByName('Графики')->getHighestDataRow());
            $book->disconnectWorksheets();
        } finally {
            @unlink($path);
        }
    }

    public function test_export_rejects_missing_malformed_or_unknown_selection_instead_of_exporting_everything(): void
    {
        $id = $this->postJson('/calculations/consumer', ['amount' => 100000, 'years' => 1])->assertCreated()->json('id');
        $admin = Administrator::query()->create(['email' => 'supp.manobank@mail.ru', 'password' => 'Local-Test-Password-2026']);
        $this->actingAs($admin, 'admin');
        foreach ([[], ['selected' => 'all'], ['selected' => ['invalid']], ['selected' => [123]], ['selected' => [['nested' => $id]]], ['selected' => ['00000000-0000-4000-8000-000000000000']], ['selected' => [$id, '00000000-0000-4000-8000-000000000000']], ['selected' => [$id, $id]]] as $query) {
            $this->getJson('/admin/calculations/export?'.http_build_query($query))->assertUnprocessable();
        }
    }
}
