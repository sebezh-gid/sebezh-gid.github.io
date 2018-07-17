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
}
