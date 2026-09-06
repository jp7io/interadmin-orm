<?php

use Illuminate\Database\Eloquent\MassAssignmentException;
use Jp7\InterAdmin\DynamicLoader;
use Jp7\InterAdmin\RecordClassMap;
use Jp7\InterAdmin\TypeClassMap;

class CreateTest extends TestCase
{
    private $userType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seeNumRecords(0, 'interadmin_teste_types');

        $this->userType = $this->createUserType();
    }

    // tests

    public function testTypeWasSaved()
    {
        $this->seeInDatabase('interadmin_teste_types', [
            'type_id' => $this->userType->type_id,
            'name' => $this->userType->name,
            'id_slug' => $this->userType->id_slug
        ]);
    }

    public function testDynamicLoader()
    {
        $type = $this->createType(['name' => 'ClassName'], [
            ['type' => 'varchar_key', 'name' => 'Name'],
        ]);

        TypeClassMap::getInstance()->clearCache();
        RecordClassMap::getInstance()->clearCache();

        spl_autoload_unregister([DynamicLoader::class, 'load']);
        $this->assertFalse(class_exists($type->class));
        $this->assertFalse(class_exists($type->class_type));

        spl_autoload_register([DynamicLoader::class, 'load']);
        $this->assertTrue(class_exists($type->class));
        $this->assertTrue(class_exists($type->class_type));
    }

    public function testBuildEntity()
    {
        $user = Test_User::build();
        $this->assertNotNull($user->type_id);
    }

    public function testSave()
    {
        $user = $this->createUser();
        $this->seeInDatabase('interadmin_teste_records', ['type_id' => $user->type_id]);
    }

    public function testCreate()
    {
        Test_User::unguard();

        $user = Test_User::create([
            'varchar_key' => 'argentinopam',
            'password_key' => '123',
        ]);

        $this->seeInDatabase('interadmin_teste_records', [
            'type_id' => $user->type_id,
            'varchar_key' => $user->username,
            'password_key' => $user->password,
        ]);
        Test_User::reguard();
    }

    public function testMassAssignmentValidation()
    {
        $this->assertThrows(MassAssignmentException::class, function () {
            Test_User::create([
                'varchar_key' => 'argentinopam',
                'password_key' => '123',
            ]);
        });
    }

    public function testDelete()
    {
        $user = $this->createUser([
            'varchar_key' => 'isommerville',
            'password_key' => '123'
        ]);

        $user->delete();

        // The row survives carrying a stamp, and the stamp's value cannot be asserted -- delete()
        // writes date('c'). "Not null" is the whole question the column answers.
        $this->seeInDatabase('interadmin_teste_records', ['id' => $user->id]);
        $this->dontSeeInDatabase('interadmin_teste_records', ['id' => $user->id, 'deleted_at' => null]);
    }

    /**
     * The only place a NULL is written to the database, and it only reaches one because
     * `deleted_at` is on RecordAbstract::$nullableAttributes -- every other attribute goes in as
     * '' , which on a datetime is the sentinel and leaves the row deleted forever.
     */
    public function testRestore()
    {
        $user = $this->createUser([
            'varchar_key' => 'isommerville',
            'password_key' => '123'
        ]);

        $user->delete();
        $user->restore();

        $this->seeInDatabase('interadmin_teste_records', ['id' => $user->id, 'deleted_at' => null]);
    }

    public function testForceDelete()
    {
        $user = $this->createUser([
            'varchar_key' => 'isommerville',
            'password_key' => '123'
        ]);

        $user->forceDelete();

        $this->dontSeeInDatabase('interadmin_teste_records', ['id' => $user->id]);
    }

}
