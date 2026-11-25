<?php

declare(strict_types=1);

namespace App\Modules\Site\Services;

final class BankDetails
{
    public static function contacts(): array
    {
        return [
            'address' => '426009, г. Ижевск, ул. Ленина, д. 112',
            'address_lines' => ['426009, г. Ижевск,', 'ул. Ленина, д. 112'],
            'phones' => ['+7 (341) 200-77-22', '+7 900 200-77-22'],
            'email' => 'mano.2004@mail.ru',
            'support' => 'supp.manobank@mail.ru',
        ];
    }

    public static function requisites(): array
    {
        return [
            'Юридический адрес' => self::contacts()['address'],
            'Почтовый адрес' => self::contacts()['address'],
            'ИНН' => '1831999009',
            'КПП' => '183101001',
            'ОГРН' => '1251800999009',
            'Расчётный счёт' => '40702810100000000909',
            'Корреспондентский счёт' => '30101810300000000999',
            'БИК' => '049499999',
        ];
    }
}
