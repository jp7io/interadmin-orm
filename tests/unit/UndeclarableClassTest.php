<?php

use PHPUnit\Framework\Attributes\DataProvider;
use Jp7\InterAdmin\DynamicLoader;
use Jp7\InterAdmin\Record;
use Jp7\InterAdmin\RecordClassMap;
use Jp7\InterAdmin\Type;
use Jp7\InterAdmin\TypeClassMap;

/**
 * A content type bound to a class name PHP cannot declare.
 *
 * `types.class` becomes a class name by a literal `_` → `\` swap, so a type called "Include" or
 * "String" asks DynamicLoader to generate `class Include {}`. It cannot; the question is what the
 * ORM does about it. It used to generate the code anyway and let the eval fail — a RuntimeException
 * out of `class_exists()`, which is not something an autoloader is allowed to throw, and it took
 * down every screen that touched such a type (found on a real tenant, one type, six dead paths).
 *
 * ⚠ Two failure modes hide here, and only one is catchable. `Include` is a ParseError; `String`,
 * `self` and the twelve like them are an E_COMPILE_ERROR that `try`/`catch` never sees. So if the
 * guard regresses, THIS FILE KILLS THE SUITE PROCESS rather than reporting a failure — which is
 * the loudest signal available and is deliberate.
 */
class UndeclarableClassTest extends TestCase
{
    public static function undeclarableNames(): array
    {
        return [
            'keyword (ParseError)' => ['Include'],
            'reserved type name (uncatchable fatal)' => ['String'],
        ];
    }

    public function testIsDeclarableAnswersForBothRefusals()
    {
        $this->assertTrue(DynamicLoader::isDeclarable('Produto'));
        $this->assertTrue(DynamicLoader::isDeclarable('InterMail\IncludeTipo'));
        // Only the LAST segment is a class name; PHP is happy with a keyword as a namespace.
        $this->assertTrue(DynamicLoader::isDeclarable('Include\Produto'));

        $this->assertFalse(DynamicLoader::isDeclarable('Include'));
        $this->assertFalse(DynamicLoader::isDeclarable('InterMail\Include'));
        $this->assertFalse(DynamicLoader::isDeclarable('List'));
        $this->assertFalse(DynamicLoader::isDeclarable('String'));
        $this->assertFalse(DynamicLoader::isDeclarable('Self'));
    }

    #[DataProvider('undeclarableNames')]
    public function testTheAutoloaderReturnsQuietlyInsteadOfThrowing(string $name)
    {
        $this->createBoundType($name);

        $this->assertFalse(class_exists($name));
    }

    #[DataProvider('undeclarableNames')]
    public function testRecordsStillLoadAsTheDefaultClass(string $name)
    {
        $type = $this->createBoundType($name);

        $record = $type->records()->build();
        // bool_key/publish, or the ORM's own published filter hides the row again.
        $record->setRawAttributes(['varchar_key' => 'Um registro', 'bool_key' => 1, 'publish' => 'S']);
        $record->save();

        $loaded = $type->records()->get();

        $this->assertCount(1, $loaded);
        $this->assertSame(Record::class, get_class($loaded->first()));
        $this->assertSame('Um registro', $loaded->first()->nome);
        $this->assertEquals($record->id, $loaded->first()->id);
    }

    #[DataProvider('undeclarableNames')]
    public function testTheTypeItselfFallsBackToTheDefaultTypeClass(string $name)
    {
        $type = $this->createBoundType($name);

        $this->assertSame(Type::class, get_class(Type::getInstance($type->type_id)));
    }

    /** A type whose `class` AND `class_type` are the undeclarable name, with the maps rebuilt. */
    private function createBoundType(string $name): Type
    {
        $type = $this->createType([
            'name' => $name,
            'class' => $name,
            'class_type' => $name,
        ], [
            ['type' => 'varchar_key', 'name' => 'Nome'],
        ]);

        TypeClassMap::getInstance()->clearCache();
        RecordClassMap::getInstance()->clearCache();

        return $type;
    }
}
