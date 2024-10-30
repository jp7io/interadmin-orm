<?php

use Jp7\Interadmin\Record;
use Jp7\Interadmin\RecordClassMap;

class QueryTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        $this->tester->seeNumRecords(0, 'interadmin_teste_tipos');
        $this->tester->createUserType();

        RecordClassMap::getInstance()->clearCache();
    }

    public function testWhere()
    {
        $newUser = $this->tester->createUser();

        $userQuery = Test_User::where('varchar_key', '=', $newUser->varchar_key)->first();
        $this->assertEquals($newUser->username, $userQuery->username);

        $userQueryShouldFail = Test_User::where('varchar_key', '=', 'blablabla')->get();
        $this->assertEmpty($userQueryShouldFail);

        $userCompositeQuery = Test_User::where('varchar_key', '=', $newUser->username)
            ->where('char_key', '=', 'S')
            ->first();
        $this->assertEquals($newUser->mostrar, $userCompositeQuery->mostrar);
    }

    public function testOptionsArray()
    {
        $this->assertEquals(
            "varchar_key = 'blablabla'",
            Test_User::where('varchar_key', '=', 'blablabla')->getOptionsArray()['where'][0]
        );
    }

    public function testWhereRaw()
    {
        $newUser = $this->tester->createUser();

        $userRawQuery = Test_User::whereRaw('DATE(date_insert) = CURDATE()')->first();
        $this->assertEquals(date('Y-m-d'), date('Y-m-d', $userRawQuery->date_insert->timestamp));

        $userRawQuery = Test_User::whereRaw('DATE(date_insert) > CURDATE()')->first();
        $this->assertNull($userRawQuery);
    }

    public function testWhereYear()
    {
        $tblee = $this->tester->createUser([
            'varchar_key' => 'tblee',
            'varchar_2' => 'timbernerslee@cern.org',
            'date_insert'=> new Date('1955-01-01')
        ]);

        $user = Test_User::whereYear('date_insert', 1955)->first();
        $this->assertEquals($tblee->date_insert->year, $user->date_insert->year);
    }

    public function testWhereMonth()
    {
        $lpage = $this->tester->createUser([
            'varchar_key' => 'lpage',
            'varchar_2' => 'larrypage@gmail.com',
            'date_insert'=> new Date('2016-10-03')
        ]);

        $user = Test_User::whereMonth('date_insert', 10)->first();
        $this->assertEquals($lpage->date_insert->month, $user->date_insert->month);
    }

    public function testWhereDay()
    {
        $sbrin = $this->tester->createUser([
            'varchar_key' => 'sbrin',
            'varchar_2' => 'sergeybrin@gmail.com',
            'date_insert'=> new Date('2016-10-03')
        ]);

        $user = Test_User::whereDay('date_insert', 3)->first();
        $this->assertEquals($sbrin->date_insert->day, $user->date_insert->day);
    }

    public function testWhereIn()
    {
        $this->tester->createUsersBulk(4);

        $users = Test_User::whereIn('varchar_key', ['User #0', 'User #1'])->get();
        $this->assertTrue($users->contains('username', 'User #0'));
        $this->assertTrue($users->contains('username', 'User #1'));
        $this->assertFalse($users->contains('username', 'User #2'));
        $this->assertFalse($users->contains('username', 'User #3'));
    }

    public function testWhereNotIn()
    {
        $this->tester->createUsersBulk(4);

        $users = Test_User::whereNotIn('varchar_key', ['User #0', 'User #1'])->get();
        $this->assertFalse($users->contains('varchar_key', 'User #0'));
        $this->assertFalse($users->contains('varchar_key', 'User #1'));
        $this->assertTrue($users->contains('varchar_key', 'User #2'));
        $this->assertTrue($users->contains('varchar_key', 'User #3'));
    }

    public function testOrderBy()
    {
        $first = Test_User::build();
        $first->username = 'Alfenas';
        $first->save();

        $middle = Test_User::build();
        $middle->username = 'Bady Bassitt';
        $middle->save();

        $last = Test_User::build();
        $last->username = 'Mariana';
        $last->save();

        $users = Test_User::orderBy('username')->get();
        $this->assertEquals($users->first()->name, $first->name);
        $this->assertEquals($users->last()->name, $last->name);
    }

    public function testOrderByRaw()
    {
        $list = collect();
        for ($i = 0; $i < 10; $i++) {
            $user = Test_User::build();
            $user->username = 'User #' . $i;
            $user->save();

            $list[] = $user;
        }

        $users = Test_User::orderByRaw('username DESC')->get();
        foreach ($users as $user) {
            $this->assertEquals($list->pop()->username, $user->username);
        }
    }

    public function testDelete()
    {
        $this->tester->createUsersBulk(10);

        Test_User::where('username', 'User #0')->orderBy('username')->delete();
        $this->assertEquals(9, Test_User::count());

        Test_User::limit(2)->delete();
        $this->assertEquals(7, Test_User::count());

        Test_User::query()->delete();
        $this->assertEquals(0, Test_User::count());
        $this->tester->seeNumRecords(10, 'interadmin_teste_registros');
    }

    public function testForceDelete()
    {
        $this->tester->createUsersBulk(10);

        Test_User::where('username', 'User #0')->orderBy('username')->forceDelete();
        $this->tester->seeNumRecords(9, 'interadmin_teste_registros');

        Test_User::limit(2)->forceDelete();
        $this->tester->seeNumRecords(7, 'interadmin_teste_registros');

        Test_User::query()->forceDelete();
        $this->tester->seeNumRecords(0, 'interadmin_teste_registros');
    }

    public function testUpdate()
    {
        $this->tester->createUsersBulk(10);

        Test_User::where('ordem', '<', 5)->update([
            'e_mail' => 'updated@jp7.com.br',
            'ordem' => 0,
            'username' => \DB::raw('CONCAT(username, \' suffix\')'),
        ]);

        $this->assertEquals('User #0 suffix', Test_User::orderBy('username')->first()->username);
        $this->assertEquals(5, Test_User::where('e_mail', 'updated@jp7.com.br')->count());
        $this->assertEquals(5, Test_User::where('username', 'LIKE', '%suffix')->count());
        $this->assertEquals('0,0,0,0,0,5,6,7,8,9', Test_User::pluck('ordem')->implode(','));
    }

    public function testIncrement()
    {
        $this->tester->createUsersBulk(10);

        Test_User::where('ordem', '>=', 5)->increment('ordem', 10);
        $this->assertEquals('0,1,2,3,4,15,16,17,18,19', Test_User::pluck('ordem')->implode(','));
    }

    /**
     * @dataProvider publishedProvider
     */
    public function testPublished(array $attributes)
    {
        $this->oldTimestamp = Record::hasTimestamp() ? Record::getTimestamp() : null;
        Record::setTimestamp(strtotime('2016-01-01 02:00:00'));

        $user = Test_User::build();
        $user->setRawAttributes($attributes);
        $user->saveRaw();

        $result = Test_User::find($user->id);
        $this->assertEquals($user->id, $result->id);

        Record::setTimestamp($this->oldTimestamp);
    }

    /**
     * @dataProvider unpublishedProvider
     */
    public function testUnpublished(array $attributes)
    {
        $this->oldTimestamp = Record::hasTimestamp() ? Record::getTimestamp() : null;
        Record::setTimestamp(strtotime('2016-01-01 02:00:00'));

        $user = Test_User::build();
        $user->setRawAttributes($attributes);
        $user->saveRaw();

        $result = Test_User::find($user->id);
        $this->assertNull($result);

        $result = Test_User::published(false)->find($user->id);
        $this->assertEquals($user->id, $result->id);

        Record::setTimestamp($this->oldTimestamp);
    }

    public function publishedProvider()
    {
        return [
            'no date_expire' => [[
                'char_key' => 'S',
                'publish'  => 'S',
                'deleted_at'  => null,
                'parent_id'  => 0,
                'date_publish'  => '2016-01-01 01:59:59',
                'date_expire' => '0000-00-00 00:00:00' // sem date_expire
            ]],
            'not expired yet' => [[
                'char_key' => 'S',
                'publish'  => 'S',
                'deleted_at'  => null,
                'parent_id'  => 0,
                'date_publish'  => '2016-01-01 00:00:00',
                'date_expire' => '2016-01-01 02:01:00' // date_expire no futuro
            ]],
            'children without publish' => [[
                'char_key' => 'S',
                'publish'  => '', // sem publish
                'deleted_at'  => null,
                'parent_id'  => 123, // com parent
                'date_publish'  => '2016-01-01 00:00:00',
                'date_expire' => '2016-01-01 02:01:00'
            ]],
        ];
    }

    public function unpublishedProvider()
    {
        return [
            'not active' => [[
                'char_key' => '', // sem mostrar
                'publish'  => 'S',
                'deleted_at'  => null,
                'parent_id'  => 0,
                'date_publish'  => '2016-01-01 01:59:59',
                'date_expire' => '0000-00-00 00:00:00'
            ]],
            'deleted_at' => [[
                'char_key' => 'S',
                'publish'  => 'S',
                'deleted_at'  => 'S', // com deleted
                'parent_id'  => 0,
                'date_publish'  => '2016-01-01 01:59:59',
                'date_expire' => '0000-00-00 00:00:00'
            ]],
            'expired' => [[
                'char_key' => 'S',
                'publish'  => 'S',
                'deleted_at'  => null,
                'parent_id'  => 0,
                'date_publish'  => '2016-01-01 01:00:00',
                'date_expire' => '2016-01-01 01:59:59' // date_expire no passado
            ]],
            'not published yet' => [[
                'char_key' => 'S',
                'publish'  => 'S',
                'deleted_at'  => null,
                'parent_id'  => 0,
                'date_publish'  => '2016-01-01 02:01:00', // date_publish no futuro
                'date_expire' => '2016-01-01 03:00:00'
            ]],
            'no publish' => [[
                'char_key' => 'S',
                'publish'  => '', // sem publish
                'deleted_at'  => null,
                'parent_id'  => 0, // sem parent
                'date_publish'  => '2016-01-01 00:00:00',
                'date_expire' => '2016-01-01 02:01:00'
            ]],
        ];
    }

}
