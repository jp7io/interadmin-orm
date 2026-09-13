<?php

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/** Phase 5 deletes this package's Jp7\InterAdmin half, so InterAdmin\Models may name none of it. */
class ModelsNameNoLegacyOrmTest extends PHPUnitTestCase
{
    public function testTheModelsNameNothingOfTheLegacyHalf(): void
    {
        $package = dirname(__DIR__, 2);
        $root = $package.'/InterAdmin/Models';
        $found = [];

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            foreach (token_get_all((string) file_get_contents($file->getPathname())) as $token) {
                if (!is_array($token) || !in_array($token[0], [T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)) {
                    continue;
                }

                // By FILE, not by prefix: jp7io/classes ships Schema\ and Field\ under the same namespace.
                $relative = str_replace('\\', '/', ltrim($token[1], '\\')).'.php';
                if (str_starts_with($relative, 'Jp7/InterAdmin/') && is_file($package.'/'.$relative)) {
                    $found[] = substr($file->getPathname(), strlen($root) + 1).':'.$token[2].' '.$token[1];
                }
            }
        }

        $this->assertFileExists($package.'/Jp7/InterAdmin/Record.php', 'No legacy half to find, so the scan proves nothing.');
        $this->assertNotSame([], glob($root.'/*.php'), 'The scan found no models, so it proves nothing.');
        $this->assertSame([], $found, "The models name the legacy half, which Phase 5 deletes:\n".implode("\n", $found));
    }
}
