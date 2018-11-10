<?php

namespace App\Handlers;

use Slim\Http\Request;
use Slim\Http\Response;
use App\CommonHandler;

class EditPage extends CommonHandler
{
    /**
     * Display the page edit form.
     **/
    public function onGet(Request $request, Response $response)
    {
        $pageName = $request->getQueryParam("name");

        if (empty($pageName))
            return $response->withRedirect("/wiki?name=Welcome", 302);

        $page = $this->db->getPageByName($pageName);
        if ($page === false) {
            if (preg_match('@^\d{4}$@', $pageName)) {
                $contents = "# sebezh-gid.ru #{$pageName}\n\n- Русский: [[страница]]\n- English: [[something]]";
            } else {
                $contents = "# {$pageName}\n\n**{$pageName}** -- something that we don't have information on, yet.\n";
            }
        } else {
            $contents = $page["source"];
        }

        return $this->render($request, "editor.twig", [
            "page_name" => $pageName,
            "page_source" => $contents,
            "is_editable" => $this->isAdmin($request),
        ]);
    }

    /**
     * Update page contents.
     **/
    public function onPost(Request $request, Response $response, array $args)
    {
        $this->requireAdmin($request);

        $name = $_POST["page_name"];
        $text = $_POST["page_source"];

        $this->db->updatePage($name, $text);

        return $response->withRedirect("/wiki?name=" . urlencode($name), 303);
    }
}
