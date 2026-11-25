<?php

namespace App\Modules\Site\Http\Controllers;

use App\Modules\Catalog\Services\ProductCatalog;
use App\Modules\Site\Services\SearchMetadata;
use Symfony\Component\HttpFoundation\Response;

final class SearchController
{
    public function robots(SearchMetadata $metadata): Response
    {
        return response("User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /calculations\nDisallow: /account\nDisallow: /media\nSitemap: ".$metadata->canonical('/sitemap.xml')."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap(ProductCatalog $catalog, SearchMetadata $metadata): Response
    {
        $paths = ['/', '/calculator', '/contacts', '/requisites'];
        foreach ($catalog->publicProducts() as $product) {
            $paths[] = '/calculator/'.$product->code;
        }
        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($paths as $path) {
            $xml .= '<url><loc>'.htmlspecialchars($metadata->canonical($path), ENT_XML1 | ENT_QUOTES, 'UTF-8').'</loc></url>';
        }

        return response($xml.'</urlset>', 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
