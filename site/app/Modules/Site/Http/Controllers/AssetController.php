<?php

declare(strict_types=1);

namespace App\Modules\Site\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class AssetController
{
    public function __invoke(string $asset): BinaryFileResponse
    {
        $files = [
            'entrance' => ['entrance/ВГ-5.png', 'image/png'],
            'map' => ['location/Ижевск_Ленина_112.jpg', 'image/jpeg'],
        ];
        abort_unless(isset($files[$asset]), 404);
        [$path, $mime] = $files[$asset];

        return response()->file(base_path('../visualizations/manobank/'.$path), ['Content-Type' => $mime]);
    }
}
