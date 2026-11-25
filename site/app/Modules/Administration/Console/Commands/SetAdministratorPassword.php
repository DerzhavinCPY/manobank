<?php

namespace App\Modules\Administration\Console\Commands;

use App\Modules\Administration\Models\Administrator;
use Illuminate\Console\Command;

final class SetAdministratorPassword extends Command
{
    protected $signature = 'manobank:admin-password';

    protected $description = 'Задать отдельный пароль администратора МаноБанка';

    public function handle(): int
    {
        $password = $this->secret('Новый пароль приложения (не менее 12 символов)');
        if (! is_string($password) || mb_strlen($password) < 12 || $password !== $this->secret('Повторите пароль')) {
            $this->error('Пароль не сохранён: проверьте длину и совпадение.');

            return self::FAILURE;
        }
        Administrator::query()->updateOrCreate(['email' => 'supp.manobank@mail.ru'], ['password' => $password]);
        $this->info('Пароль административного приложения сохранён.');

        return self::SUCCESS;
    }
}
