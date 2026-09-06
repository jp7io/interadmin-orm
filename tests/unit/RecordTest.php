<?php

use PHPUnit\Framework\Attributes\DataProvider;
use Jp7\InterAdmin\Record;
use Jp7\InterAdmin\RecordClassMap;

class RecordTest extends TestCase
{
    private $oldTimestamp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->oldTimestamp = Record::hasTimestamp() ? Record::getTimestamp() : null;

        Record::setTimestamp(strtotime('2016-01-01 02:00:00'));
    }

    protected function tearDown(): void
    {
        Record::setTimestamp($this->oldTimestamp);

        parent::tearDown();
    }

    public function testSetAndGet()
    {
        $this->createUserType();
        RecordClassMap::getInstance()->clearCache();

        $user = Test_User::build();
        $username = 'jp7_kant';
        $user->username = $username;
        $this->assertEquals($user->username, $username);
        $this->assertEquals($user->varchar_key, $username);

        $this->assertFalse(isset($user->newProp));
        $user->newProp = [];
        $this->assertTrue(isset($user->newProp));

        $user->newProp[] = 1;
        $user->newProp[] = 2;
        $this->assertEquals($user->newProp, [1, 2]);

        unset($user->newProp);
        $this->assertFalse(isset($user->newProp));

        $user->publish_at = date('c');
        $this->assertInstanceOf('Date', $user->publish_at);
    }

    #[DataProvider('publishedProvider')]
    public function testPublished(array $attributes)
    {
        $record = new Record($attributes);
        $this->assertTrue($record->isPublished());
    }

    #[DataProvider('unpublishedProvider')]
    public function testUnpublished(array $attributes)
    {
        $record = new Record($attributes);
        $this->assertFalse($record->isPublished());
    }

    public static function publishedProvider()
    {
        return [
            [[
                'bool_key' => 1,
                'publish'  =>  1,
                'deleted_at'  =>  null,
                'parent_id'  => 0,
                'publish_at'  => '2016-01-01 01:59:59',
                'expire_at' => '0000-00-00 00:00:00' // sem expire_at
            ]],
            [[
                'bool_key' => 1,
                'publish'  =>  1,
                'deleted_at'  =>  null,
                'parent_id'  => 0,
                'publish_at'  => '2016-01-01 00:00:00',
                'expire_at' => '2016-01-01 02:00:01' // expire_at no futuro
            ]],
            [[
                'bool_key' => 1,
                'publish'  =>  0, // sem publish
                'deleted_at'  =>  null,
                'parent_id'  => 123, // com parent
                'publish_at'  => '2016-01-01 00:00:00',
                'expire_at' => '2016-01-01 02:00:01'
            ]],
        ];
    }

    public static function unpublishedProvider()
    {
        return [
            [[
                'bool_key' => 0, // not shown
                'publish'  =>  1,
                'deleted_at'  =>  null,
                'parent_id'  => 0,
                'publish_at'  => '2016-01-01 01:59:59',
                'expire_at' => '0000-00-00 00:00:00'
            ]],
            [[
                'bool_key' => 1,
                'publish'  =>  1,
                'deleted_at'  =>  date('c'), // com deleted
                'parent_id'  => 0,
                'publish_at'  => '2016-01-01 01:59:59',
                'expire_at' => '0000-00-00 00:00:00'
            ]],
            [[
                'bool_key' => 1,
                'publish'  =>  1,
                'deleted_at'  =>  null,
                'parent_id'  => 0,
                'publish_at'  => '2016-01-01 01:00:00',
                'expire_at' => '2016-01-01 01:59:59' // expire_at no passado
            ]],
            [[
                'bool_key' => 1,
                'publish'  =>  1,
                'deleted_at'  =>  null,
                'parent_id'  => 0,
                'publish_at'  => '2016-01-01 02:00:01', // publish_at no futuro
                'expire_at' => '2016-01-01 03:00:00'
            ]],
            [[
                'bool_key' => 1,
                'publish'  =>  0, // sem publish
                'deleted_at'  =>  null,
                'parent_id'  => 0, // sem parent
                'publish_at'  => '2016-01-01 00:00:00',
                'expire_at' => '2016-01-01 02:00:01'
            ]],
        ];
    }
}
