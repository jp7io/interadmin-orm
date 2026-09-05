<?php

use PHPUnit\Framework\Attributes\DataProvider;
use Jp7\InterAdmin\Record;
use Jp7\InterAdmin\RecordClassMap;

class QueryTest extends TestCase
{
    private $oldTimestamp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seeNumRecords(0, 'interadmin_teste_types');
        $this->createUserType();

        RecordClassMap::getInstance()->clearCache();
    }

    public function testWhere()
    {
        $newUser = $this->createUser();

        $userQuery = Test_User::where('varchar_key', '=', $newUser->varchar_key)->first();
        $this->assertEquals($newUser->username, $userQuery->username);

        $userQueryShouldFail = Test_User::where('varchar_key', '=', 'blablabla')->get();
        $this->assertEmpty($userQueryShouldFail);

        $userCompositeQuery = Test_User::where('varchar_key', '=', $newUser->username)
            ->where('bool_key', '=', 1)
            ->first();
        $this->assertEquals($newUser->show, $userCompositeQuery->show);
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
        $newUser = $this->createUser();

        $userRawQuery = Test_User::whereRaw('DATE(date_insert) = CURDATE()')->first();
        $this->assertEquals(date('Y-m-d'), date('Y-m-d', $userRawQuery->date_insert->timestamp));

        $userRawQuery = Test_User::whereRaw('DATE(date_insert) > CURDATE()')->first();
        $this->assertNull($userRawQuery);
    }

    /**
     * A raw expression as the VALUE of a where(), which reaches SQL verbatim rather than
     * as a binding. testUpdate() covers the same unwrapping on the write path; only that
     * one had it, which is how the read path went missing.
     */
    public function testWhereWithRawValue()
    {
        $newUser = $this->createUser();

        $found = Test_User::where('date_insert', '<=', \DB::raw('NOW()'))->first();
        $this->assertEquals($newUser->username, $found->username);

        $this->assertNull(Test_User::where('date_insert', '>', \DB::raw('NOW()'))->first());
    }

    public function testWhereYear()
    {
        $tblee = $this->createUser([
            'varchar_key' => 'tblee',
            'varchar_2' => 'timbernerslee@cern.org',
            'date_insert'=> new Date('1955-01-01')
        ]);

        $user = Test_User::whereYear('date_insert', 1955)->first();
        $this->assertEquals($tblee->date_insert->year, $user->date_insert->year);
    }

    public function testWhereMonth()
    {
        $lpage = $this->createUser([
            'varchar_key' => 'lpage',
            'varchar_2' => 'larrypage@gmail.com',
            'date_insert'=> new Date('2016-10-03')
        ]);

        $user = Test_User::whereMonth('date_insert', 10)->first();
        $this->assertEquals($lpage->date_insert->month, $user->date_insert->month);
    }

    public function testWhereDay()
    {
        $sbrin = $this->createUser([
            'varchar_key' => 'sbrin',
            'varchar_2' => 'sergeybrin@gmail.com',
            'date_insert'=> new Date('2016-10-03')
        ]);

        $user = Test_User::whereDay('date_insert', 3)->first();
        $this->assertEquals($sbrin->date_insert->day, $user->date_insert->day);
    }

    public function testWhereIn()
    {
        $this->createUsersBulk(4);

        $users = Test_User::whereIn('varchar_key', ['User #0', 'User #1'])->get();
        $this->assertTrue($users->contains('username', 'User #0'));
        $this->assertTrue($users->contains('username', 'User #1'));
        $this->assertFalse($users->contains('username', 'User #2'));
        $this->assertFalse($users->contains('username', 'User #3'));
    }

    public function testWhereNotIn()
    {
        $this->createUsersBulk(4);

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
        $this->createUsersBulk(10);

        Test_User::where('username', 'User #0')->orderBy('username')->delete();
        $this->assertEquals(9, Test_User::count());

        Test_User::limit(2)->delete();
        $this->assertEquals(7, Test_User::count());

        Test_User::query()->delete();
        $this->assertEquals(0, Test_User::count());
        $this->seeNumRecords(10, 'interadmin_teste_records');
    }

    public function testForceDelete()
    {
        $this->createUsersBulk(10);

        Test_User::where('username', 'User #0')->orderBy('username')->forceDelete();
        $this->seeNumRecords(9, 'interadmin_teste_records');

        Test_User::limit(2)->forceDelete();
        $this->seeNumRecords(7, 'interadmin_teste_records');

        Test_User::query()->forceDelete();
        $this->seeNumRecords(0, 'interadmin_teste_records');
    }

    public function testUpdate()
    {
        $this->createUsersBulk(10);

        Test_User::where('position', '<', 5)->update([
            'e_mail' => 'updated@jp7.com.br',
            'position' => 0,
            'username' => \DB::raw('CONCAT(username, \' suffix\')'),
        ]);

        $this->assertEquals('User #0 suffix', Test_User::orderBy('username')->first()->username);
        $this->assertEquals(5, Test_User::where('e_mail', 'updated@jp7.com.br')->count());
        $this->assertEquals(5, Test_User::where('username', 'LIKE', '%suffix')->count());
        // Ordered by id: the update leaves five rows sharing position 0, so no order the
        // column itself could give is total and an unordered SELECT rotates them.
        $this->assertEquals('0,0,0,0,0,5,6,7,8,9', Test_User::orderBy('id')->pluck('position')->implode(','));
    }

    public function testIncrement()
    {
        $this->createUsersBulk(10);

        Test_User::where('position', '>=', 5)->increment('position', 10);
        $this->assertEquals('0,1,2,3,4,15,16,17,18,19', Test_User::pluck('position')->implode(','));
    }

    #[DataProvider('publishedProvider')]
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

    #[DataProvider('unpublishedProvider')]
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

    public static function publishedProvider()
    {
        return [
            'no date_expire' => [[
                'bool_key' => 1,
                'publish'  =>  1,
                'deleted'  =>  0,
                'parent_id'  => 0,
                'date_publish'  => '2016-01-01 01:59:59',
                'date_expire' => null // sem date_expire
            ]],
            'not expired yet' => [[
                'bool_key' => 1,
                'publish'  =>  1,
                'deleted'  =>  0,
                'parent_id'  => 0,
                'date_publish'  => '2016-01-01 00:00:00',
                'date_expire' => '2016-01-01 02:01:00' // date_expire no futuro
            ]],
            'children without publish' => [[
                'bool_key' => 1,
                'publish'  =>  0, // sem publish
                'deleted'  =>  0,
                'parent_id'  => 123, // com parent
                'date_publish'  => '2016-01-01 00:00:00',
                'date_expire' => '2016-01-01 02:01:00'
            ]],
        ];
    }

    public static function unpublishedProvider()
    {
        return [
            'not active' => [[
                'bool_key' => 0, // not shown
                'publish'  =>  1,
                'deleted'  =>  0,
                'parent_id'  => 0,
                'date_publish'  => '2016-01-01 01:59:59',
                'date_expire' => '0000-00-00 00:00:00'
            ]],
            'deleted' => [[
                'bool_key' => 1,
                'publish'  =>  1,
                'deleted'  =>  1, // com deleted
                'parent_id'  => 0,
                'date_publish'  => '2016-01-01 01:59:59',
                'date_expire' => '0000-00-00 00:00:00'
            ]],
            'expired' => [[
                'bool_key' => 1,
                'publish'  =>  1,
                'deleted'  =>  0,
                'parent_id'  => 0,
                'date_publish'  => '2016-01-01 01:00:00',
                'date_expire' => '2016-01-01 01:59:59' // date_expire no passado
            ]],
            'not published yet' => [[
                'bool_key' => 1,
                'publish'  =>  1,
                'deleted'  =>  0,
                'parent_id'  => 0,
                'date_publish'  => '2016-01-01 02:01:00', // date_publish no futuro
                'date_expire' => '2016-01-01 03:00:00'
            ]],
            'no publish' => [[
                'bool_key' => 1,
                'publish'  =>  0, // sem publish
                'deleted'  =>  0,
                'parent_id'  => 0, // sem parent
                'date_publish'  => '2016-01-01 00:00:00',
                'date_expire' => '2016-01-01 02:01:00'
            ]],
        ];
    }

}
