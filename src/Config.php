<?php

declare(strict_types=1);

namespace App;

use RuntimeException;

class Config
{
    /** @var array **/
    protected $data;

    public function __construct()
    {
        $fn = __DIR__ . '/../config/settings.php';
        if (!file_exists($fn)) {
            throw new RuntimeException('settings file not found');
        }

        $this->data = include $fn;

        if (!is_array($this->data)) {
            throw new RuntimeException('config/settings.php did not return an array');
        }
    }

    /**
     * @param mixed $default
     *
     * @return mixed
     **/
    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }
}
