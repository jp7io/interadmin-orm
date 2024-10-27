<?php

use Jp7\Interadmin\Field\SelectField;
use Jp7\Interadmin\RecordClassMap;

class RelationTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function _before()
    {
        $this->tester->seeNumRecords(0, 'interadmin_teste_tipos');
        $this->userType = $this->tester->createUserType();

        $this->cityType = $this->tester->createType(
            [
                'nome' => 'City',
                //'tags' => 'S',
            ],
            [
                ['tipo' => 'varchar_key', 'nome' => 'Name'],
                ['tipo' => 'varchar_1', 'nome' => 'UF'],
                ['tipo' => 'char_key', 'nome' => 'Mostrar']
            ]
        );

        $this->storeType = $this->tester->createType(
            [
                'nome' => 'Store',
                'children' => $this->userType->type_id.'{,}Employees{,}{,}{;}'
            ],
            [
                ['tipo' => 'varchar_key', 'nome' => 'Name'],
                ['tipo' => 'select_1', 'nome' => $this->cityType->type_id, 'xtra' => SelectField::XTRA_RECORD, 'nome_id' => 'city'],
                ['tipo' => 'char_key', 'nome' => 'Mostrar']
            ]
        );

        $this->testimonialType = $this->tester->createType(
            [
                'nome' => 'Testimonial'
            ],
            [
                ['tipo' => 'varchar_key', 'nome' => 'Title'],
                ['tipo' => 'char_key', 'nome' => 'Mostrar']
            ]
        );

        RecordClassMap::getInstance()->clearCache();
    }

    public function testRelationship()
    {
        $city = Test_City::build();
        $city->name = 'São Paulo';
        $city->uf = 'SP';
        $city->save();

        $store = Test_Store::build();
        $store->name = 'Daslu';
        $store->city_id = $city->id;
        $store->save();

        $result = Test_Store::where('city_id', $city->id)->first();
        $this->assertEquals($result->city_id, $city->id);
    }

    public function testJoin()
    {
        $jericoacoara = Test_City::build();
        $jericoacoara->name = 'Jericoacoara';
        $jericoacoara->uf = 'CE';
        $jericoacoara->save();

        $hoccaBar = Test_Store::build();
        $hoccaBar->name = 'Hocca Bar';
        $hoccaBar->city_id = $jericoacoara->id;
        $hoccaBar->save();

        $store = Test_Store::join('city', Test_City::class, 'city.id = city_id')->first();
        $this->assertNotNull($store->city->name);
    }

    public function testHas()
    {
        $store = Test_Store::build();
        $store->name = 'Mercadão Municipal';
        $store->save();

        $result = Test_Store::has('employees')->first();
        $this->assertNull($result);

        $store->employees()->create();

        $result = Test_Store::has('employees')->first();
        $this->assertEquals($result->id, $store->id);
    }

    public function testWhereHas()
    {
        $store = Test_Store::build();
        $store->name = 'Mercadão Municipal';
        $store->save();

        $employee = $store->employees()->build();
        $employee->username = 'alan_turing';
        $employee->save();

        // with query
        $result = Test_Store::whereHas('employees', function ($query) {
            $query->where('username', 'ada_lovelace');
        })->first();
        $this->assertNull($result);

        $result = Test_Store::whereHas('employees', function ($query) {
            $query->where('username', 'alan_turing');
        })->first();
        $this->assertEquals($result->id, $store->id);

        // with array of conditions
        $result = Test_Store::whereHas('employees', [
            'username' => 'ada_lovelace'
        ])->first();
        $this->assertNull($result);

        $result = Test_Store::whereHas('employees', [
            'username' => 'alan_turing'
        ])->first();
        $this->assertEquals($result->id, $store->id);
    }

    public function testWhereDoesntHave()
    {

    }

    public function testWith()
    {
        $city = Test_City::build();
        $city->name = 'São Paulo';
        $city->uf = 'SP';
        $city->save();

        $store = Test_Store::build();
        $store->name = 'Mercadão Municipal';
        $store->city_id = $city->id;
        $store->save();

        $storeWith = Test_Store::with('city')->first();
        $relations = $this->tester->readPrivate($storeWith, 'relations');
        $this->assertEquals($store->city->id, $relations['city']->id);
    }

    public function testLeftJoin()
    {
        $resende = Test_City::build();
        $resende->name = 'Resende';
        $resende->uf = 'RJ';
        $resende->save();

        $florestal = Test_City::build();
        $florestal->name = 'Florestal';
        $florestal->uf = 'MG';
        $florestal->save();

        $aman = Test_Store::build();
        $aman->name = 'Academia Militar das Agulhas Negras';
        $aman->city_id = $resende->id;
        $aman->save();

        $ime = Test_Store::build();
        $ime->name = 'Instituto Militar de Engenharia';
        $ime->city_id = null;
        $ime->save();

        $stores = Test_Store::leftJoin('city', Test_City::class, 'city.id = main.city_id')->get();
        $this->assertFalse($stores->contains('city_id', $florestal->id));
        $this->assertTrue($stores->contains('name', $ime->name));

        $cities = Test_City::leftJoin('store', Test_Store::class, 'store.city_id = main.id')->get();
        $this->assertTrue($cities->contains('id', $florestal->id));
    }

    public function testRightJoin()
    {
        $caceres = Test_City::build();
        $caceres->name = 'Cáceres';
        $caceres->uf = 'MT';
        $caceres->save();

        $lavras = Test_City::build();
        $lavras->name = 'Lavras';
        $lavras->uf = 'MG';
        $lavras->save();

        $ufla = Test_Store::build();
        $ufla->name = 'Universidade Federal de Lavras';
        $ufla->city_id = $lavras->id;
        $ufla->save();

        $sep = Test_Store::build();
        $sep->name = 'Sociedade Esportiva Palmeiras';
        $sep->city_id = null;
        $sep->save();

        $cities = Test_City::rightJoin('store', Test_Store::class, 'store.city_id = main.id')->get();
        $this->assertTrue($cities->contains('name', $lavras->name));
        $this->assertFalse($cities->contains('name', $caceres->name));

        $stores = Test_Store::rightJoin('city', Test_City::class, 'city.id = main.city_id')->get();
        $this->assertTrue($stores->contains('name', $ufla->name));
        $this->assertFalse($stores->contains('name', $sep->name));
    }

    public function testJoinThrough()
    {
        $store = Test_Store::build();
        $store->name = 'New England Patriots';
        $store->city_id = null;
        $store->save();

        $expectedEmployee = $store->employees()->build();
        $expectedEmployee->username = 'jedelman11';
        $expectedEmployee->save();

        $actualEmployee = Test_User::joinThrough(Test_Store::class, 'store.employees')->first();
        $this->assertEquals($expectedEmployee->id, $actualEmployee->id);
    }

    public function testTaggedWith()
    {
        $testimonial1 = Test_Testimonial::create();
        $testimonial2 = Test_Testimonial::create();

        $city1 = Test_City::build();
        $city1->name = 'Ourinhos';
        $city1->uf = 'SP';
        $city1->save();

        $city2 = Test_City::build();
        $city2->name = 'Salto do Itararé';
        $city2->uf = 'PR';
        $city2->save();

        $city3 = Test_City::build();
        $city3->name = 'Ouro Branco';
        $city3->uf = 'MG';
        $city3->save();

        DB::table('tags')->insert([
            ['parent_id' => $testimonial2->id, 'type_id' => $city1->type_id, 'id' => $city1->id],
            ['parent_id' => $testimonial2->id, 'type_id' => $city2->type_id, 'id' => $city2->id],
        ]);

        $result = Test_Testimonial::orderBy('id')->first();
        $this->assertEquals($result->id, $testimonial1->id);

        $result = Test_Testimonial::taggedWith($city1)->orderBy('id')->first();
        $this->assertEquals($result->id, $testimonial2->id);

        $result = Test_Testimonial::taggedWith($city2)->orderBy('id')->first();
        $this->assertEquals($result->id, $testimonial2->id);

        $this->assertNull(Test_Testimonial::taggedWith($city3)->first());
    }
}
