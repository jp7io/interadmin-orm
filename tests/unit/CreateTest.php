<?php

use Illuminate\Database\Eloquent\MassAssignmentException;
use Jp7\Interadmin\DynamicLoader;
use Jp7\Interadmin\RecordClassMap;
use Jp7\Interadmin\TypeClassMap;

class CreateTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        $this->tester->seeNumRecords(0, 'interadmin_teste_tipos');

        $this->userType = $this->tester->createUserType();
    }

    // tests

    public function testTypeWasSaved()
    {
        $this->tester->seeInDatabase('interadmin_teste_tipos', [
            'type_id' => $this->userType->type_id,
            'nome' => $this->userType->nome,
            'id_slug' => $this->userType->id_slug
        ]);
    }

    public function testDynamicLoader()
    {
        $type = $this->tester->createType(['nome' => 'ClassName'], [
            ['tipo' => 'varchar_key', 'nome' => 'Name'],
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
        $user = $this->tester->createUser();
        $this->tester->seeInDatabase('interadmin_teste_registros', ['type_id' => $user->type_id]);
    }

    public function testCreate()
    {
        Test_User::unguard();

        $user = Test_User::create([
            'varchar_key' => 'argentinopam',
            'password_key' => '123',
        ]);

        $this->tester->seeInDatabase('interadmin_teste_registros', [
            'type_id' => $user->type_id,
            'varchar_key' => $user->username,
            'password_key' => $user->password,
        ]);
        Test_User::reguard();
    }

    public function testMassAssignmentValidation()
    {
        $this->tester->expectException(MassAssignmentException::class, function () {
            Test_User::create([
                'varchar_key' => 'argentinopam',
                'password_key' => '123',
            ]);
        });
    }

    public function testDelete()
    {
        $user = $this->tester->createUser([
            'varchar_key' => 'isommerville',
            'password_key' => '123'
        ]);

        $user->delete();

        $this->tester->seeInDatabase('interadmin_teste_registros', ['id' => $user->id, 'deleted_at' => 'S']);
    }

    public function testForceDelete()
    {
        $user = $this->tester->createUser([
            'varchar_key' => 'isommerville',
            'password_key' => '123'
        ]);

        $user->forceDelete();

        $this->tester->dontSeeInDatabase('interadmin_teste_registros', ['id' => $user->id]);
    }

}
