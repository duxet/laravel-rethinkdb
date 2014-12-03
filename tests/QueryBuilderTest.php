<?php

class QueryBuilderTest extends TestCase
{

    public function tearDown()
    {
        DB::table('users')->truncate();
        DB::table('items')->truncate();
    }

    public function testTable()
    {
        $this->assertInstanceOf('duxet\Rethinkdb\Query\Builder', DB::table('users'));
    }

    public function testGet()
    {
        $users = DB::table('users')->get();
        $this->assertEquals(0, count($users));

        DB::table('users')->insert(['name' => 'John Doe']);

        $users = DB::table('users')->get();
        $this->assertEquals(1, count($users));
    }

    public function testNoDocument()
    {
        $items = DB::table('items')->where('name', 'nothing')->get();
        $this->assertEquals(array(), $items);
        $item = DB::table('items')->where('name', 'nothing')->first();
        $this->assertEquals(null, $item);
        $item = DB::table('items')->where('id', '51c33d8981fec6813e00000a')->first();
        $this->assertEquals(null, $item);
    }

    public function testInsert()
    {
        DB::table('users')->insert([
            'tags' => ['tag1', 'tag2'],
            'name' => 'John Doe',
        ]);
        $users = DB::table('users')->get();
        $this->assertEquals(1, count($users));
        $user = $users[0];
        $this->assertEquals('John Doe', $user['name']);
        $this->assertTrue(is_array($user['tags']));
    }

    public function testInsertGetId()
    {
        $id = DB::table('users')->insertGetId(['name' => 'John Doe']);
        $this->assertInternalType('string', $id);
    }

    public function testBatchInsert()
    {
        DB::table('users')->insert([
            [
                'tags' => ['tag1', 'tag2'],
                'name' => 'Jane Doe',
            ],
            [
                'tags' => ['tag3'],
                'name' => 'John Doe',
            ],
        ]);
        $users = DB::table('users')->get();
        $this->assertEquals(2, count($users));
        $this->assertTrue(is_array($users[0]['tags']));
    }

    public function testFind()
    {
        $id = DB::table('users')->insertGetId(['name' => 'John Doe']);
        $user = DB::table('users')->find($id);
        $this->assertEquals('John Doe', $user['name']);
    }

    public function testFindNull()
    {
        $user = DB::table('users')->find(null);
        $this->assertEquals(null, $user);
    }

    public function testCount()
    {
        DB::table('users')->insert([
            ['name' => 'Jane Doe'],
            ['name' => 'John Doe']
        ]);
        $this->assertEquals(2, DB::table('users')->count());
    }

    public function testUpdate()
    {
        DB::table('users')->insert([
            ['name' => 'Jane Doe', 'age' => 20],
            ['name' => 'John Doe', 'age' => 21]
        ]);
        DB::table('users')->where('name', 'John Doe')->update(['age' => 100]);
        $users = DB::table('users')->get();
        $john = DB::table('users')->where('name', 'John Doe')->first();
        $jane = DB::table('users')->where('name', 'Jane Doe')->first();
        $this->assertEquals(100, $john['age']);
        $this->assertEquals(20, $jane['age']);
    }

    public function testDelete()
    {
        DB::table('users')->insert([
            ['name' => 'Jane Doe', 'age' => 20],
            ['name' => 'John Doe', 'age' => 25]
        ]);
        DB::table('users')->where('age', '<', 10)->delete();
        $this->assertEquals(2, DB::table('users')->count());
        DB::table('users')->where('age', '<', 25)->delete();
        $this->assertEquals(1, DB::table('users')->count());
    }

    public function testTruncate()
    {
        DB::table('users')->insert(['name' => 'John Doe']);
        DB::table('users')->truncate();
        $this->assertEquals(0, DB::table('users')->count());
    }

    /*
    public function testSubKey()
    {
        DB::table('users')->insert([
            [
                'name' => 'John Doe',
                'address' => ['country' => 'Belgium', 'city' => 'Ghent']
            ],
            [
                'name' => 'Jane Doe',
                'address' => ['country' => 'France', 'city' => 'Paris']
            ]
        ]);
        $users = DB::table('users')->where('address.country', 'Belgium')->get();
        $this->assertEquals(1, count($users));
        $this->assertEquals('John Doe', $users[0]['name']);
    }
    */

    public function testInArray()
    {
        DB::table('items')->insert([
            [
                'tags' => ['tag1', 'tag2', 'tag3', 'tag4']
            ],
            [
                'tags' => ['tag2']
            ]
        ]);
        $items = DB::table('items')->where('tags', 'contains', 'tag2')->get();
        $this->assertEquals(2, count($items));
        $items = DB::table('items')->where('tags', 'contains', 'tag1')->get();
        $this->assertEquals(1, count($items));
    }

    public function testPush()
    {
        $id = DB::table('users')->insertGetId([
            'name' => 'John Doe',
            'tags' => array(),
            'messages' => array(),
        ]);
        DB::table('users')->where('id', $id)->push('tags', 'tag1');
        $user = DB::table('users')->find($id);
        $this->assertTrue(is_array($user['tags']));
        $this->assertEquals(1, count($user['tags']));
        $this->assertEquals('tag1', $user['tags'][0]);
        DB::table('users')->where('id', $id)->push('tags', 'tag2');
        $user = DB::table('users')->find($id);
        $this->assertEquals(2, count($user['tags']));
        $this->assertEquals('tag2', $user['tags'][1]);

        // Add duplicate
        DB::table('users')->where('id', $id)->push('tags', 'tag2');
        $user = DB::table('users')->find($id);
        $this->assertEquals(3, count($user['tags']));

        // Add unique
        DB::table('users')->where('id', $id)->push('tags', 'tag1', true);
        $user = DB::table('users')->find($id);
        $this->assertEquals(3, count($user['tags']));
        $message = ['from' => 'Jane', 'body' => 'Hi John'];
        DB::table('users')->where('id', $id)->push('messages', $message);
        $user = DB::table('users')->find($id);
        $this->assertTrue(is_array($user['messages']));
        $this->assertEquals(1, count($user['messages']));
        $this->assertEquals($message, $user['messages'][0]);
    }

    public function testPull()
    {
        $message1 = ['from' => 'Jane', 'body' => 'Hi John'];
        $message2 = ['from' => 'Mark', 'body' => 'Hi John'];
        $id = DB::table('users')->insertGetId([
            'name' => 'John Doe',
            'tags' => ['tag1', 'tag2', 'tag3', 'tag4'],
            'messages' => [$message1, $message2]
        ]);
        DB::table('users')->where('id', $id)->pull('tags', 'tag3');
        $user = DB::table('users')->find($id);
        $this->assertTrue(is_array($user['tags']));
        $this->assertEquals(3, count($user['tags']));
        $this->assertEquals('tag4', $user['tags'][2]);
        DB::table('users')->where('id', $id)->pull('messages', $message1);
        $user = DB::table('users')->find($id);
        $this->assertTrue(is_array($user['messages']));
        $this->assertEquals(1, count($user['messages']));
    }

    public function testDistinct()
    {
        DB::table('items')->insert([
            ['name' => 'knife', 'type' => 'sharp'],
            ['name' => 'fork',  'type' => 'sharp'],
            ['name' => 'spoon', 'type' => 'round'],
            ['name' => 'spoon', 'type' => 'round']
        ]);
        $items = DB::table('items')->distinct('name')->get(); sort($items);
        $this->assertEquals(3, count($items));
        $this->assertEquals(array('fork', 'knife', 'spoon'), $items);
        $types = DB::table('items')->distinct('type')->get(); sort($types);
        $this->assertEquals(2, count($types));
        $this->assertEquals(array('round', 'sharp'), $types);
    }

}