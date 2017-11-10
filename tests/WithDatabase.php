<?php

namespace Scrn\Bakery\Tests;

trait WithDatabase {
    /**
     * Set up a test database. 
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function setupDatabase($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }
}