<?php

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/** The legacy half stays out of InterAdmin\Models before round 3 deletes it and after: its 24 classes by NAME, never by file. */
class ModelsNameNoLegacyOrmTest extends PHPUnitTestCase
{
    private const LEGACY = [
        'ChildUtil', 'Collection', 'DynamicLoader', 'EagerLoadedQuery', 'EloquentProxy', 'FieldUtil',
        'FileDatabase', 'FileRecord', 'Log', 'PublishedFilter', 'Query', 'RawSql', 'Record',
        'RecordAbstract', 'RecordClassMap', 'Relation', 'SqlCompiler', 'Type', 'TypeClassMap',
        'TypelessQuery', 'Query\\BaseQuery', 'Query\\FileQuery', 'Query\\TypeQuery', 'Relation\\HasMany',
    ];

    public function testTheModelsNameNothingOfTheLegacyHalf(): void
    {
        $root = dirname(__DIR__, 2).'/InterAdmin/Models';
        $found = [];

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            foreach ($this->legacyNames((string) file_get_contents($file->getPathname())) as [$line, $name]) {
                $found[] = substr($file->getPathname(), strlen($root) + 1).':'.$line.' '.$name;
            }
        }

        // jp7io/classes declares the same prefix, and its Schema\RecordClassMap is one segment from the ORM's.
        $planted = "<?php\nuse Jp7\\InterAdmin\\Schema\\RecordClassMap;\nuse Jp7\\InterAdmin\\Field\\TypeInterface;\n"
            ."use Jp7\\InterAdmin\\RecordClassMap;\n\$x instanceof \\jp7\\interadmin\\query\\basequery;\n";
        $this->assertSame(
            [[4, 'Jp7\\InterAdmin\\RecordClassMap'], [5, '\\jp7\\interadmin\\query\\basequery']],
            $this->legacyNames($planted),
            'The scan misreads a planted reference, so it proves nothing.'
        );
        $this->assertNotSame([], glob($root.'/*.php'), 'The scan found no models, so it proves nothing.');
        $this->assertSame([], $found, "The models name the legacy half, which round 3 deletes:\n".implode("\n", $found));
    }

    /** @return list<array{int, string}> each qualified name that resolves to one of the 24, case-blind as PHP is */
    private function legacyNames(string $code): array
    {
        $legacy = array_map(fn (string $name) => strtolower('Jp7\\InterAdmin\\'.$name), self::LEGACY);
        $found = [];

        foreach (token_get_all($code) as $token) {
            if (is_array($token) && in_array($token[0], [T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)
                && in_array(strtolower(ltrim($token[1], '\\')), $legacy, true)) {
                $found[] = [$token[2], $token[1]];
            }
        }

        return $found;
    }
}
