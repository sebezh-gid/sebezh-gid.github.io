<?php
/**
 * Handle sitemap.
 **/

namespace App\Handlers;

use Slim\Http\Request;
use Slim\Http\Response;
use App\CommonHandler;

class Sitemap extends CommonHandler
{
    public function onGet(Request $request, Response $response, array $args)
    {
        $base = $this->container->get("settings")["websiteBase"];

        $xml = "<?xml version='1.0' encoding='utf-8'?".">\n";
        $xml .= "<urlset xmlns='http://www.sitemaps.org/schemas/sitemap/0.9'>\n";

        $pages = $this->db->listPages();
        foreach ($pages as $page) {
            $link = $base . "/wiki?name=" . urlencode($page["name"]);
            $date = strftime("%Y-%m-%d", $page["updated"]);
            $xml .= "<url><loc>{$link}</loc><lastmod>{$date}</lastmod></url>\n";
        }

        $xml .= "</urlset>\n";

        $response = $response->withHeader("Content-Type", "text/xml; charset=utf-8");
        $response->getBody()->write($xml);

        return $response;
    }
}
