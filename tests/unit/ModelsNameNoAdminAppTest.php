<?php

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/** InterAdmin\Models ships in this package, so it may name no class or helper of the admin app. */
class ModelsNameNoAdminAppTest extends PHPUnitTestCase
{
    public function testTheModelsNameNothingOfTheAdminApp(): void
    {
        $root = dirname(__DIR__, 2).'/InterAdmin/Models';
        $found = [];

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            foreach (token_get_all((string) file_get_contents($file->getPathname())) as $token) {
                if (!is_array($token)) {
                    continue;
                }

                $name = ltrim($token[1], '\\').'\\';
                $appClass = in_array($token[0], [T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)
                    && str_starts_with($name, 'InterAdmin\\') && !str_starts_with($name, 'InterAdmin\\Models\\');
                $appHelper = $token[0] === T_STRING && str_starts_with($token[1], 'interadmin_');

                if ($appClass || $appHelper) {
                    $found[] = substr($file->getPathname(), strlen($root) + 1).':'.$token[2].' '.$token[1];
                }
            }
        }

        $this->assertNotSame([], glob($root.'/*.php'), 'The scan found no models, so it proves nothing.');
        $this->assertSame([], $found, "The models name the admin app, which this package cannot load:\n".implode("\n", $found));
    }
}
