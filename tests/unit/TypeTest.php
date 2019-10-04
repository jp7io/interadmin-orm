<?php

use Jp7\Interadmin\Type;
use Jp7\Interadmin\TypeClassMap;

class TypeTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function testSetAndGet()
    {
        $type = new Type;
        $this->assertFalse(isset($type->newProp));
        $type->newProp = [];
        $this->assertTrue(isset($type->newProp));

        $type->newProp[] = 1;
        $type->newProp[] = 2;
        $this->assertEquals($type->newProp, [1, 2]);

        unset($type->newProp);
        $this->assertFalse(isset($type->newProp));

        $type->date_modify = date('c');
        $this->assertInstanceOf('Date', $type->date_modify);
    }

    public function testSave()
    {
        $this->tester->seeNumRecords(0, 'interadmin_teste_tipos');

        $userType = $this->tester->createUserType();

        $this->tester->seeNumRecords(1, 'interadmin_teste_tipos');

        $type = new Type($userType->id_tipo);
        $this->assertTrue($type->exists);
        $type->save();

        $this->tester->seeNumRecords(1, 'interadmin_teste_tipos');

        $type = new Type;
        $this->assertFalse($type->exists);
        $type->save();

        $this->tester->seeNumRecords(2, 'interadmin_teste_tipos');
    }
}
