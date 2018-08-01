<?php

namespace Wiki;

class Util
{
    public static function cleanHtml($html)
    {
        // Some typography.
        $html = preg_replace('@\s+--\s+@', '&nbsp;— ', $html);
        $html = preg_replace('@\.  @', '.&nbsp; ', $html);

        // Clean up.
        $html = preg_replace('@\s+<(html|head|body|div|ul|li|p|header|footer|meta|title|aside|form|input|main|h1|h2|h3|h4|h5|script|!--)@', '<\\1', $html);
        $html = preg_Replace('@\s+</(html|head|body|div|ul|ol|li|form|input|aside|main|header|footer)>@', '</\\1>', $html);

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
}
