<?php

class ModelTest extends TestCase
{
    public function testToArray()
    {
        $item = Item::create(['name' => 'fork', 'type' => 'sharp']);
        $array = $item->toArray();
        $keys = array_keys($array);
        sort($keys);
        $this->assertEquals(['created_at', 'id', 'name', 'type', 'updated_at'], $keys);
        $this->assertTrue(is_string($array['created_at']));
        $this->assertTrue(is_string($array['updated_at']));
        $this->assertTrue(is_string($array['id']));
    }

    public function testDates()
    {
        $birthday = new DateTime('1980/1/1');
        $user = User::create(['name' => 'John Doe', 'birthday' => $birthday]);
        $this->assertInstanceOf('Carbon\Carbon', $user->birthday);

        $check = User::find($user->id);
        $this->assertInstanceOf('Carbon\Carbon', $check->birthday);
        $this->assertEquals($user->birthday, $check->birthday);

        // NOTE: This is only supported in Laravel >= 5.1, prior to this eloquent just casts
        //       the date to a string, ignoring any custom $dateFormat
        // $user = User::where('birthday', '>', new DateTime('1975/1/1'))->first();
        // $this->assertEquals('John Doe', $user->name);
        // // test custom date format for json output
        // $json = $user->toArray();
        // $this->assertEquals($user->birthday->format('l jS \of F Y h:i:s A'), $json['birthday']);
        // $this->assertEquals($user->created_at->format('l jS \of F Y h:i:s A'), $json['created_at']);

        // test default date format for json output
        $item = Item::create(['name' => 'sword']);
        $json = $item->toArray();
        $this->assertEquals($item->created_at->format('Y-m-d H:i:s'), $json['created_at']);

        $user = User::create(['name' => 'Jane Doe', 'birthday' => time()]);
        $this->assertInstanceOf('Carbon\Carbon', $user->birthday);

        $user = User::create(['name' => 'Jane Doe', 'birthday' => 'Monday 8th of August 2005 03:12:46 PM']);
        $this->assertInstanceOf('Carbon\Carbon', $user->birthday);

        $user = User::create(['name' => 'Jane Doe', 'birthday' => '2005-08-08']);
        $this->assertInstanceOf('Carbon\Carbon', $user->birthday);

        // TODO: This requires this library to support subdocuments
        // $user = User::create(['name' => 'Jane Doe', 'entry' => ['date' => '2005-08-08']]);
        // $this->assertInstanceOf('Carbon\Carbon', $user->getAttribute('entry.date'));
        // $user->setAttribute('entry.date', new DateTime);
        // $this->assertInstanceOf('Carbon\Carbon', $user->getAttribute('entry.date'));
        // $data = $user->toArray();
        // $this->assertNotInstanceOf('MongoDB\BSON\UTCDateTime', $data['entry']['date']);
        // $this->assertEquals((string) $user->getAttribute('entry.date')->format('Y-m-d H:i:s'), $data['entry']['date']);
    }

    public function testGetDirtyDates()
    {
        $user = new User();
        $user->setRawAttributes(['name' => 'John Doe', 'birthday' => new DateTime('19 august 1989')], true);
        $this->assertEmpty($user->getDirty());
        $user->birthday = new DateTime('19 august 1989');
        $this->assertEmpty($user->getDirty());
    }

    public function testNativeDatePersistance()
    {
        $item = Item::create(['name' => 'spoon', 'type' => 'round']);
        $connection = DB::connection()->getConnection();
        $raw = r\table('items')->get($item->id)->run($connection, ['timeFormat' => 'raw']);

        $this->assertArrayHasKey('$reql_type$', $raw['created_at']);
        $this->assertEquals('TIME', $raw['created_at']['$reql_type$']);
        $this->assertEquals($item->created_at->getTimestamp(), $raw['created_at']['epoch_time']);
        $this->assertEquals('+00:00', $raw['created_at']['timezone']);
    }
}
