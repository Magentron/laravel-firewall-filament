<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/stubs.php';

if (! function_exists('config')) {
    function config(string $key, $default = null)
    {
        return \Magentron\LaravelFirewallFilament\Tests\ConfigStub::$values[$key] ?? $default;
    }
}
