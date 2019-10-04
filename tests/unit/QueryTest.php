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
        $alovelace = $this->tester->createUser([
            'varchar_key' => 'alovelace',
            'varchar_2' => 'alovelace@byron.com'
        ]);

        $aturing = $this->tester->createUser([
            'varchar_key' => 'aturing',
            'varchar_2' => 'aturing@compsci.org'
        ]);

        $atanenbaum = $this->tester->createUser([
            'varchar_key' => 'atanenbaum',
            'varchar_2' => 'atanenbaum@vua.edu'
        ]);

        $cbabbage = $this->tester->createUser([
            'varchar_key' => 'cbabbage',
            'varchar_2' => 'cbabbage@cbi.org'
        ]);

        $users = Test_User::whereIn('varchar_key', ['aturing', 'alovelace'])->get();
        $this->assertTrue($users->contains('username', 'alovelace'));
        $this->assertTrue($users->contains('username', 'aturing'));
        $this->assertFalse($users->contains('username', 'cbabbage'));
        $this->assertFalse($users->contains('username', 'atanenbaum'));
    }

    public function testWhereNotIn()
    {
        $this->tester->createUser([
            'varchar_key' => 'alovelace',
            'varchar_2' => 'alovelace@byron.com'
        ]);

        $this->tester->createUser([
            'varchar_key' => 'aturing',
            'varchar_2' => 'aturing@compsci.org'
        ]);

        $this->tester->createUser([
            'varchar_key' => 'atanenbaum',
            'varchar_2' => 'atanenbaum@vua.edu'
        ]);

        $this->tester->createUser([
            'varchar_key' => 'cbabbage',
            'varchar_2' => 'cbabbage@cbi.org'
        ]);

        $users = Test_User::whereNotIn('varchar_key', ['aturing', 'alovelace'])->get();
        $this->assertFalse($users->contains('varchar_key', 'alovelace'));
        $this->assertFalse($users->contains('varchar_key', 'aturing'));
        $this->assertTrue($users->contains('varchar_key', 'cbabbage'));
        $this->assertTrue($users->contains('varchar_key', 'atanenbaum'));
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
                'deleted'  => '',
                'parent_id'  => 0,
                'date_publish'  => '2016-01-01 01:59:59',
                'date_expire' => '0000-00-00 00:00:00' // sem date_expire
            ]],
            'not expired yet' => [[
                'char_key' => 'S',
                'publish'  => 'S',
                'deleted'  => '',
                'parent_id'  => 0,
                'date_publish'  => '2016-01-01 00:00:00',
                'date_expire' => '2016-01-01 02:01:00' // date_expire no futuro
            ]],
            'children without publish' => [[
                'char_key' => 'S',
                'publish'  => '', // sem publish
                'deleted'  => '',
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
                'deleted'  => '',
                'parent_id'  => 0,
                'date_publish'  => '2016-01-01 01:59:59',
                'date_expire' => '0000-00-00 00:00:00'
            ]],
            'deleted' => [[
                'char_key' => 'S',
                'publish'  => 'S',
                'deleted'  => 'S', // com deleted
                'parent_id'  => 0,
                'date_publish'  => '2016-01-01 01:59:59',
                'date_expire' => '0000-00-00 00:00:00'
            ]],
            'expired' => [[
                'char_key' => 'S',
                'publish'  => 'S',
                'deleted'  => '',
                'parent_id'  => 0,
                'date_publish'  => '2016-01-01 01:00:00',
                'date_expire' => '2016-01-01 01:59:59' // date_expire no passado
            ]],
            'not published yet' => [[
                'char_key' => 'S',
                'publish'  => 'S',
                'deleted'  => '',
                'parent_id'  => 0,
                'date_publish'  => '2016-01-01 02:01:00', // date_publish no futuro
                'date_expire' => '2016-01-01 03:00:00'
            ]],
            'no publish' => [[
                'char_key' => 'S',
                'publish'  => '', // sem publish
                'deleted'  => '',
                'parent_id'  => 0, // sem parent
                'date_publish'  => '2016-01-01 00:00:00',
                'date_expire' => '2016-01-01 02:01:00'
            ]],
        ];
    }

}
