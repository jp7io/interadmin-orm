<?php

class InterAdminLang
{
    private $locale;
    private $repository;

    public function __construct($locale)
    {
        $this->setLocale($locale);
    }

    public function setLocale($locale)
    {
        $this->locale = $locale;
        $config = [];
        foreach (glob(base_path('resources/lang/'.$locale.'/*.php')) as $filename) {
            $config[basename($filename, '.php')] = require $filename;
        }
        $this->repository = new Illuminate\Config\Repository($config);
    }

    public function getLocale()
    {
        return $this->locale;
    }

    public function has($key)
    {
        return isset($this->repository[$key]);
    }

    public function get($key)
    {
        if (!isset($this->repository[$key])) {
            throw new OutOfBoundsException($key);
        }
        return $this->repository[$key];
    }
}
