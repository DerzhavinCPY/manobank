<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Administration;

use App\Modules\Administration\Models\Administrator;
use App\Modules\Catalog\Database\Seeders\CalculatorSeeder;
use App\Modules\Catalog\Models\Calculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class AdministrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(CalculatorSeeder::class);
    }

    public function test_every_admin_operation_requires_a_separate_login(): void
    {
        foreach (['/admin', '/admin/calculators', '/admin/calculations', '/admin/calculations/export'] as $path) {
            $this->get($path)->assertRedirect('/admin/login');
        }
        $this->post('/admin/calculators', [])->assertRedirect('/admin/login');
    }

    public function test_login_and_logout_do_not_use_the_public_account_windows(): void
    {
        $admin = $this->administrator();
        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'wrong-password'])->assertSessionHasErrors('email');
        $this->assertGuest('admin');
        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'Local-Test-Password-2026'])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($admin, 'admin');
        $this->get('/admin')->assertOk()->assertSee('Администрирование');
        $this->post('/admin/logout')->assertRedirect('/admin/login');
        $this->assertGuest('admin');
    }

    public function test_only_the_approved_email_can_log_in_even_if_another_row_exists(): void
    {
        Administrator::query()->create(['email' => 'other@example.com', 'password' => Hash::make('Local-Test-Password-2026')]);
        $this->post('/admin/login', ['email' => 'other@example.com', 'password' => 'Local-Test-Password-2026'])->assertSessionHasErrors('email');
        $this->assertGuest('admin');
    }

    public function test_draft_crud_really_changes_records_without_publishing_them(): void
    {
        $this->actingAs($this->administrator(), 'admin');
        $data = ['name' => 'Внутренний расчёт', 'type' => 'mortgage', 'rate' => '9.6', 'subtitle' => 'Проверка параметров', 'parameters' => '{"min_amount":100000,"max_amount":15000000,"min_years":1,"max_years":30,"min_down_payment":10}'];
        $this->post('/admin/calculators', $data + ['is_public' => true, 'code' => 'mortgage'])->assertRedirect('/admin/calculators');
        $draft = Calculator::query()->where('name', $data['name'])->firstOrFail();
        self::assertFalse($draft->is_public);
        self::assertNull($draft->code);
        $this->put('/admin/calculators/'.$draft->id, array_replace($data, ['name' => 'Изменённый внутренний расчёт']))->assertRedirect('/admin/calculators');
        self::assertSame('Изменённый внутренний расчёт', $draft->fresh()->name);
        $this->get('/')->assertDontSee('Изменённый внутренний расчёт');
        self::assertSame(5, Calculator::query()->where('is_public', true)->count());
        $this->delete('/admin/calculators/'.$draft->id)->assertRedirect('/admin/calculators');
        self::assertNull($draft->fresh());
    }

    public function test_public_products_cannot_be_edited_or_deleted_and_invalid_parameters_are_rejected(): void
    {
        $this->actingAs($this->administrator(), 'admin');
        $product = Calculator::query()->where('code', 'mortgage')->firstOrFail();
        $this->put('/admin/calculators/'.$product->id, ['name' => 'Подмена'])->assertForbidden();
        $this->delete('/admin/calculators/'.$product->id)->assertForbidden();
        $this->post('/admin/calculators', ['name' => 'Черновик', 'type' => 'unknown', 'rate' => '0', 'parameters' => '{}'])->assertSessionHasErrors();
        self::assertSame(5, Calculator::query()->count());
    }

    public function test_history_shows_the_saved_snapshot_without_recalculating_it(): void
    {
        $record = $this->postJson('/calculations/mortgage', ['amount' => 2000000, 'down_payment' => 25, 'years' => 20])->assertCreated()->json();
        $this->actingAs($this->administrator(), 'admin');
        $this->get('/admin/calculations')->assertOk()->assertSee('Ипотека');
        $this->get('/admin/calculations/'.$record['id'])->assertOk()->assertSee('14 080 ₽')->assertSee('35 200 ₽');
        $this->get('/admin/calculations/00000000-0000-4000-8000-000000000000')->assertNotFound();
    }

    private function administrator(): Administrator
    {
        return Administrator::query()->create(['email' => 'supp.manobank@mail.ru', 'password' => Hash::make('Local-Test-Password-2026')]);
    }
}
