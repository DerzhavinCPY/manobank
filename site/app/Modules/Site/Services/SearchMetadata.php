<?php

namespace App\Modules\Site\Services;

final class SearchMetadata
{
    public function canonical(string $path): string
    {
        return rtrim(config('app.url'), '/').($path === '/' ? '/' : '/'.ltrim($path, '/'));
    }
}
