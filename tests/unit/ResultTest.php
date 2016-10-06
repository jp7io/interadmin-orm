<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Jp7\Interadmin\RecordClassMap;

class ResultTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        $this->tester->seeNumRecords(0, 'interadmin_teste_tipos');
        $this->userType = $this->tester->createUserType();

        RecordClassMap::getInstance()->clearCache();
    }

    public function testAll()
    {
        $users = Test_User::all();
        $this->assertEmpty($users);

        $newUser = $this->tester->createUser();

        $users = Test_User::all();
        $this->assertCount(1, $users);
        $this->assertEquals($newUser->id, $users->first()->id);
    }

    public function testFirstOrFail()
    {
        $this->tester->expectException(ModelNotFoundException::class, function() {
            Test_User::firstOrFail();
        });

        $claudio = Test_User::build();
        $claudio->username = 'Cláudio';
        $claudio->save();

        $city = Test_User::firstOrFail();
        $this->assertEquals($city->username, $claudio->username);
    }

    public function testFindById()
    {
        $user = Test_User::build();
        $user->username = 'Santa Rita do Passa Quatro';
        $user->save();

        $result = Test_User::find($user->id);
        $this->assertEquals($user->id, $result->id);
    }

    public function testFindByIdSlug()
    {
        $user = Test_User::build();
        $user->username = 'John Smith';
        $user->save();

        $result = Test_User::find('john-smith');
        $this->assertEquals($user->id, $result->id);
    }

    public function testFindOrFail()
    {
        $this->tester->expectException(ModelNotFoundException::class, function() {
            Test_User::findOrFail('john-smith');
        });

        $user = Test_User::build();
        $user->username = 'John Smith';
        $user->save();

        $result = Test_User::findOrFail('john-smith');
        $this->assertEquals($user->id, $result->id);
    }

    public function testCount()
    {
        $this->assertEquals(0, Test_User::count());

        $this->tester->createUsersBulk(10);

        $this->assertEquals(10, Test_User::count());
    }

    public function testLists()
    {
        $users = $this->tester->createUsersBulk(10);
        $listExpected = [];
        foreach ($users as $user) {
            $listExpected[$user->id] = $user->username;
        }
        $result = Test_User::orderBy('id')->lists('username', 'id');
        $this->assertEquals($listExpected, $result->all());
    }

    public function testJsonList()
    {
        $users = $this->tester->createUsersBulk(10);
        $listExpected = [];
        foreach ($users as $user) {
            $listExpected[] = [
                'key' => $user->id,
                'value' => $user->username
            ];
        }
        $result = Test_User::orderBy('id')->jsonList('username', 'id');
        $this->assertEquals($listExpected, $result);
    }

    public function testSkipTake()
    {
        $users = $this->tester->createUsersBulk(40);

        $usersSkip = Test_User::skip(3)->take(4)->orderBy('id')->get();
        $expectedList = array_slice($users, 3, 4);
        foreach ($usersSkip as $actualUser) {
            $expectedUser = array_shift($expectedList);

            $this->assertEquals($expectedUser->id, $actualUser->id);
        }
    }

    public function testLimit()
    {
        $users = $this->tester->createUsersBulk(10);

        $usersTake = Test_User::take(3)->orderBy('id')->get();
        $expectedList = array_slice($users, 0, 3);
        foreach ($usersTake as $actualUser) {
            $expectedUser = array_shift($expectedList);
            $this->assertEquals($expectedUser->id, $actualUser->id);
        }

    }

}
