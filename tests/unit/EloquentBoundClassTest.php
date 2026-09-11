<?php

namespace OffTheOrmTree {
    // Where InterMail's Record and Type stand once they extend InterAdmin\Models\*.
    class Record extends \Illuminate\Database\Eloquent\Model
    {
    }

    class Type extends \Illuminate\Database\Eloquent\Model
    {
    }

    class BoundRecord extends Record
    {
    }

    class BoundType extends Type
    {
    }
}

namespace {

use Jp7\InterAdmin\Record;
use Jp7\InterAdmin\RecordClassMap;
use Jp7\InterAdmin\Type;
use Jp7\InterAdmin\TypeClassMap;

/** A binding to a class off the ORM's tree is no binding here, as the ORM's is none on Eloquent's. */
class EloquentBoundClassTest extends TestCase
{
    protected function tearDown(): void
    {
        Type::setDefaultClass(Type::class);
        $this->forgetClassMaps();

        parent::tearDown();
    }

    public function testABindingOnTheOrmTreeStillHydratesIntoItsClass()
    {
        $type = $this->createBoundType('GuardControl', 'Test_GuardControl', 'Test_GuardControlTipo');

        $this->assertSame('Test_GuardControl', get_class($this->saveAndReload($type)));
        $this->assertSame('Test_GuardControlTipo', get_class(Type::getInstance($type->type_id)));
    }

    public function testARecordBoundOffTheTreeHydratesAsThePlainRecord()
    {
        $type = $this->createBoundType('GuardRecord', \OffTheOrmTree\BoundRecord::class, '');

        $loaded = $this->saveAndReload($type);

        $this->assertSame(Record::class, get_class($loaded));
        $this->assertSame('Um registro', $loaded->nome);
    }

    public function testATypeBoundOffTheTreeInstantiatesAsThePlainType()
    {
        $type = $this->createBoundType('GuardType', '', \OffTheOrmTree\BoundType::class);

        $this->assertSame(Type::class, get_class(Type::getInstance($type->type_id)));
    }

    public function testADefaultNamespaceOffTheTreeFallsBackToTheOrmClasses()
    {
        $type = $this->createBoundType('GuardNamespace', '', '');
        $options = ['default_namespace' => 'OffTheOrmTree\\'];

        $this->assertSame(Type::class, get_class(Type::getInstance($type->type_id, $options)));
        $this->assertSame(Record::class, get_class(Record::getInstance(0, $options, $type)));
    }

    public function testADefaultTypeClassOffTheTreeFallsBackToTheOrmType()
    {
        $type = $this->createBoundType('GuardDefault', '', '');
        Type::setDefaultClass(\OffTheOrmTree\Type::class);

        $this->assertSame(Type::class, get_class(Type::getInstance($type->type_id)));
    }

    public function testAClassThatDoesNotExistStillFailsLoudly()
    {
        $type = $this->createBoundType('GuardMissing', '', '');
        $options = ['default_namespace' => 'NoSuchTree\\'];

        $this->assertThrows(Error::class, fn () => Type::getInstance($type->type_id, $options));
        $this->assertThrows(Error::class, fn () => Record::getInstance(0, $options, $type));
    }

    private function createBoundType(string $name, string $class, string $classType): Type
    {
        $type = $this->createType(['name' => $name, 'class' => $class, 'class_type' => $classType], [
            ['type' => 'varchar_key', 'name' => 'Nome'],
        ]);
        $this->forgetClassMaps();

        return $type;
    }

    private function saveAndReload(Type $type): Record
    {
        $record = $type->records()->build();
        // bool_key/publish, or the ORM's own published filter hides the row again.
        $record->setRawAttributes(['varchar_key' => 'Um registro', 'bool_key' => 1, 'publish' => 1]);
        $record->save();

        return $type->records()->get()->first();
    }

    private function forgetClassMaps(): void
    {
        TypeClassMap::getInstance()->clearCache();
        RecordClassMap::getInstance()->clearCache();
    }
}
}
