<?php

namespace PayPecker\WooPaypecker\Api;

use Illuminate\Validation;
use Illuminate\Translation;
use Illuminate\Filesystem\Filesystem;

class ValidatorFactoryMaker
{
    private $factoryInstance;

    public function __construct()
    {
        $this->factoryInstance = new Validation\Factory(
            $this->loadTranslator()
        );
    }
    protected function loadTranslator()
    {
        $filesystem = new Filesystem();
        $path = WOO_PAYPECKER_BASE_PATH . DIRECTORY_SEPARATOR . 'lang';
        $loader = new Translation\FileLoader(
            $filesystem,
            $path
        );
        $loader->addNamespace(
            'lang',
            $path
        );
        $loader->load('en', 'validation', 'lang');
        return new Translation\Translator($loader, 'en');
    }
    public function __call($method, $args)
    {
        return call_user_func_array(
            [$this->factoryInstance, $method],
            $args
        );
    }
}
