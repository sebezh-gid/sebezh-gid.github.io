<?php

declare(strict_types=1);

namespace App\Templates;

use App\Config;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class TwigTemplates implements TemplateInterface
{
    /** @var string **/
    protected $cachePath;

    /** @var string **/
    protected $templatePath;

    /** @var Environment **/
    protected $twig;

    public function __construct(Config $config)
    {
        $this->cachePath = $config->get('template.cache-path');
        $this->templatePath = $config->get('template.path');
    }

    public function render(string $templateName, array $data): string
    {
        $twig = $this->getTwig();
        $template = $twig->load($templateName . '.twig');
        $output = $template->render($data);
        return $output;
    }

    protected function getTwig(): Environment
    {
        if ($this->twig === null) {
            $loader = new FilesystemLoader($this->templatePath);
            $environment = new Environment($loader);

            if ($this->cachePath !== null) {
                $environment->setCache($this->cachePath);
            }

            $this->setupFilters($environment);

            $this->twig = $environment;
        }

        return $this->twig;
    }

    protected function processTypography(string $text): string
    {
        $patterns = [
            '@<p>(.+?)</p>@ms',
            '@<td>(.+?)</td>@ms',
            '@<li>(.+?)</li>@ms'
        ];

        foreach ($patterns as $pattern) {
            $text = preg_replace_callback($pattern, function (array $m): string {
                $text = $m[0];

                // Some typography.
                $text = preg_replace('@\s+--\s+@', '&nbsp;— ', $text);
                $text = preg_replace('@\.  @', '.&nbsp; ', $text);

                // Use nbsp with some words.
                $text = preg_replace('@ (а|В|в|Для|и|из|на|о|от|с)\s+@u', ' \1&nbsp;', $text);
                $text = preg_replace('@\s+(году|год)([.,])@u', '&nbsp;\1\2', $text);

                return $text;
            }, $text);
        }

        return $text;
    }

    protected function setupFilters(Environment $twig): void
    {
        $twig->addFilter(new \Twig\TwigFilter("typo", function ($text): string {
            return $text === null ? '' : $this->processTypography($text);
        }));
    }
}
