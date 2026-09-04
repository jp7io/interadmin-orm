<?php

use Illuminate\Container\Container;
use Jp7\InterAdmin\DynamicLoader;

/**
 * The autoloader running with no application behind it.
 *
 * A test process boots one application per test and flushes the previous one, while this
 * autoloader stays registered for the whole process. Laravel's next bootstrap asks
 * `class_exists('env')`, so load() runs between two applications and has to decline quietly.
 *
 * Asking the App facade there is what it must not do: the flushed container has no 'app'
 * binding, `App` is by then an alias of a CONCRETE facade class, and PHP matches class names
 * case-insensitively, so resolving 'app' BUILDS the facade and the guard dies with "Call to
 * undefined method Illuminate\Support\Facades\App::bound()". That took out 64 of ci-intranet's
 * 353 unit tests on every machine without a generated bootstrap/cache/classes.php.
 */
class AutoloaderWithoutApplicationTest extends TestCase
{
    public function testLoadDeclinesWhenNothingIsBound()
    {
        $application = Container::getInstance();
        Container::setInstance(new Container());

        try {
            $this->assertFalse(DynamicLoader::load('Test_User'));
        } finally {
            Container::setInstance($application);
        }
    }
}
