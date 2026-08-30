<?php

use Jp7\InterAdmin\Type;
use Jp7\InterAdmin\TypeClassMap;

class TypeTest extends TestCase
{
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
        $this->seeNumRecords(0, 'interadmin_teste_types');

        $userType = $this->createUserType();

        $this->seeNumRecords(1, 'interadmin_teste_types');

        $type = new Type($userType->type_id);
        $this->assertTrue($type->exists);
        $type->save();

        $this->seeNumRecords(1, 'interadmin_teste_types');

        $type = new Type;
        $this->assertFalse($type->exists);
        $type->save();

        $this->seeNumRecords(2, 'interadmin_teste_types');
    }
}
