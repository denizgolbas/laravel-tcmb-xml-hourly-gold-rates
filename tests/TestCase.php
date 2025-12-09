<?php

namespace DenizTezc\TcmbGold\Tests;

use DenizTezc\TcmbGold\TcmbGoldServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            TcmbGoldServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }
}
