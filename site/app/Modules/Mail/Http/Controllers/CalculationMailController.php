<?php

namespace App\Modules\Mail\Http\Controllers;

use App\Modules\Calculations\Services\CalculationService;
use App\Modules\Mail\Services\CalculationDelivery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CalculationMailController
{
    public function __invoke(Request $request, string $id, CalculationService $calculations, CalculationDelivery $delivery): JsonResponse
    {
        $input = $request->validate(['email' => ['required', 'email:rfc', 'max:254']], ['email.required' => 'Введите Email.', 'email.email' => 'Проверьте адрес Email.']);
        $record = $calculations->owned($id, $request->session()->getId());
        if (! $delivery->send($record->payload, $input['email'])) {
            return response()->json(['message' => 'Не удалось отправить письмо. Почтовая отправка недоступна. Попробуйте позднее.'], 503);
        }

        return response()->json(['success' => true, 'message' => 'Расчёт отправлен. Ожидайте письмо в течение 5 минут']);
    }
}
