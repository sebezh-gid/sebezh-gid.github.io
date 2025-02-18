<?php

namespace App;

class Util
{
    /**
     * Clean up the HTML structure from useless white spaces.
     *
     * Text typography is done in the typo twig filter.
     **/
    public static function cleanHtml($html)
    {
        // See also the |type markdown filter.

        // Closing tags should never have leading space.
        $html = preg_replace('@\s+</([a-z0-9]+)>@', '</\1>', $html);

        // Clean up.
        $all = "html|head|body|header|main|footer|aside|nav|div|p|ul|ol|li|input|label|textarea|button|meta|title|h1|h2|h3|h4|h5|script|style|link|table|thead|tfoot|tbody|tr|th|td|img";
        $html = preg_replace($re = '@\s*<(' . $all . '|!--)([^>]*>)\s*@', '<\1\2', $html);
        $html = preg_replace('@\s*</(' . $all . ')>\s*@ms', '</\1>', $html);

        $html = preg_replace('@</a>\s+@', '</a> ', $html);
        $html = preg_replace('@\s+</a>@', ' </a>', $html);

        return $html;
    }

    public static function typo($html)
    {
        // Подклеиваем висячие предлоги и союзы.
        $html = preg_replace('@\s+(по|во|о|об|от|но|не|в|на|под|при|из|вы|с|к|и|или|а|для)\s+@ui', ' \1&nbsp;', $html);

        // Подклеиваем единицы измерения.
        $html = preg_replace('@(\d)\s+(га|км|м|шт)@', '\1&nbsp;\2', $html);

        // Добавляем двойные пробелы в концы предложений.
        $html = preg_replace('@([.?!])\s+@', '\1  ', $html);

        // Правим и подклеиваем двойные тире.
        $html = preg_replace('@\s+--\s+@', ' — ', $html);

        return $html;
    }

    /**
     * Разбор описания страницы.
     *
     * Вытаскивает метаданные и свойства.
     *
     * @param array $page Запись из таблицы pages.
     * @return array Описание страницы.
     **/
    public static function parsePage(array $page)
    {
        $props = [
            "name" => $page["name"],
            "title" => $page["name"],
            "language" => "ru",
        ];

        $text = $page["source"];
        $lines = preg_split('@(\r\n|\n)@', $text);
        foreach ($lines as $idx => $line) {
            if (preg_match('@^([a-z0-9_]+):\s+(.+)$@', $line, $m)) {
                $props[$m[1]] = $m[2];
            } elseif ($line == "---") {
                $lines = array_slice($lines, $idx + 1);
                $text = implode("\r\n", $lines);
                break;
            }
        }

        $props["text"] = $text;
        return $props;
    }

    public static function parseHtmlAttrs($tag)
    {
        $res = [];

        if (preg_match_all('@([a-z-]+)="([^"]+)"@', $tag, $m)) {
            foreach ($m[1] as $idx => $key)
                $res[$key] = trim($m[2][$idx]);
        }

        if (preg_match_all("@([a-z-]+)='([^']+)'@", $tag, $m)) {
            foreach ($m[1] as $idx => $key)
                $res[$key] = trim($m[2][$idx]);
        }

        return $res;
    }
}
