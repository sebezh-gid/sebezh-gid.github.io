<?php

declare(strict_types=1);

namespace App\Helpers;

class Config
{
    /**
     * @var array
     **/
    protected $data;

    public function __construct()
    {
        $this->data = include __DIR__ . '/../../config/settings.php';

        if (!is_array($this->data)) {
            throw new \RuntimeException('config/settings.php did not return an array');
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
