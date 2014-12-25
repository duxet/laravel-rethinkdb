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

        // Test for fixed id
        $id = DB::table('users')->insertGetId([
            'id' => 'john', 'name' => 'John Doe'
        ]);
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
        $this->assertEquals(['fork', 'knife', 'spoon'], $items);
        $types = DB::table('items')->distinct('type')->get(); sort($types);
        $this->assertEquals(2, count($types));
        $this->assertEquals(['round', 'sharp'], $types);
    }

    public function testCustomId()
    {
        DB::table('items')->insert([
            ['id' => 'knife', 'type' => 'sharp', 'amount' => 34],
            ['id' => 'fork',  'type' => 'sharp', 'amount' => 20],
            ['id' => 'spoon', 'type' => 'round', 'amount' => 3]
        ]);
        $item = DB::table('items')->find('knife');
        $this->assertEquals('knife', $item['id']);
        $item = DB::table('items')->where('id', 'fork')->first();
        $this->assertEquals('fork', $item['id']);
        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Jane Doe'],
            ['id' => 2, 'name' => 'John Doe']
        ]);
        $item = DB::table('users')->find(1);
        $this->assertEquals(1, $item['id']);
    }

    public function testTake()
    {
        DB::table('items')->insert([
            ['name' => 'knife', 'type' => 'sharp', 'amount' => 34],
            ['name' => 'fork',  'type' => 'sharp', 'amount' => 20],
            ['name' => 'spoon', 'type' => 'round', 'amount' => 3],
            ['name' => 'spoon', 'type' => 'round', 'amount' => 14]
        ]);
        $items = DB::table('items')->orderBy('name')->take(2)->get();
        $this->assertEquals(2, count($items));
        $this->assertEquals('fork', $items[0]['name']);
    }

    public function testSkip()
    {
        DB::table('items')->insert([
            ['name' => 'knife', 'type' => 'sharp', 'amount' => 34],
            ['name' => 'fork',  'type' => 'sharp', 'amount' => 20],
            ['name' => 'spoon', 'type' => 'round', 'amount' => 3],
            ['name' => 'spoon', 'type' => 'round', 'amount' => 14]
        ]);
        $items = DB::table('items')->orderBy('name')->skip(2)->get();
        $this->assertEquals(2, count($items));
        $this->assertEquals('spoon', $items[0]['name']);
    }

    public function testPluck()
    {
        DB::table('users')->insert([
            ['name' => 'Jane Doe', 'age' => 20],
            ['name' => 'John Doe', 'age' => 25]
        ]);
        $age = DB::table('users')->where('name', 'John Doe')->pluck('age');
        $this->assertEquals(25, $age);
    }

    public function testList()
    {
        DB::table('items')->insert([
            ['name' => 'knife', 'type' => 'sharp', 'amount' => 34],
            ['name' => 'fork',  'type' => 'sharp', 'amount' => 20],
            ['name' => 'spoon', 'type' => 'round', 'amount' => 3],
            ['name' => 'spoon', 'type' => 'round', 'amount' => 14]
        ]);
        $list = DB::table('items')->lists('name');
        sort($list);
        $this->assertEquals(4, count($list));
        $this->assertEquals(['fork', 'knife', 'spoon', 'spoon'], $list);
        $list = DB::table('items')->lists('type', 'name');
        $this->assertEquals(3, count($list));
        $this->assertEquals(['knife' => 'sharp', 'fork' => 'sharp', 'spoon' => 'round'], $list);
    }

    public function testAggregate()
    {
        DB::table('items')->insert([
            ['name' => 'knife', 'type' => 'sharp', 'amount' => 34],
            ['name' => 'fork',  'type' => 'sharp', 'amount' => 20],
            ['name' => 'spoon', 'type' => 'round', 'amount' => 3],
            ['name' => 'spoon', 'type' => 'round', 'amount' => 14]
        ]);
        $this->assertEquals(71, DB::table('items')->sum('amount'));
        $this->assertEquals(4, DB::table('items')->count('amount'));
        $this->assertEquals(3, DB::table('items')->min('amount'));
        $this->assertEquals(34, DB::table('items')->max('amount'));
        $this->assertEquals(17.75, DB::table('items')->avg('amount'));
        $this->assertEquals(2, DB::table('items')->where('name', 'spoon')->count('amount'));
        $this->assertEquals(14, DB::table('items')->where('name', 'spoon')->max('amount'));
    }

    /*
    public function testSubdocumentAggregate()
    {
        DB::table('items')->insert([
            ['name' => 'knife', 'amount' => ['hidden' => 10, 'found' => 3]],
            ['name' => 'fork',  'amount' => ['hidden' => 35, 'found' => 12]],
            ['name' => 'spoon', 'amount' => ['hidden' => 14, 'found' => 21]],
            ['name' => 'spoon', 'amount' => ['hidden' => 6, 'found' => 4]]
        ]);
        $this->assertEquals(65, DB::table('items')->sum('amount.hidden'));
        $this->assertEquals(4, DB::table('items')->count('amount.hidden'));
        $this->assertEquals(6, DB::table('items')->min('amount.hidden'));
        $this->assertEquals(35, DB::table('items')->max('amount.hidden'));
        $this->assertEquals(16.25, DB::table('items')->avg('amount.hidden'));
    }
    */

    public function testUnset()
    {
        $id1 = DB::table('users')->insertGetId(['name' => 'John Doe', 'note1' => 'ABC', 'note2' => 'DEF']);
        $id2 = DB::table('users')->insertGetId(['name' => 'Jane Doe', 'note1' => 'ABC', 'note2' => 'DEF']);
        DB::table('users')->where('name', 'John Doe')->unset('note1');
        $user1 = DB::table('users')->find($id1);
        $user2 = DB::table('users')->find($id2);
        $this->assertFalse(isset($user1['note1']));
        $this->assertTrue(isset($user1['note2']));
        $this->assertTrue(isset($user2['note1']));
        $this->assertTrue(isset($user2['note2']));
        DB::table('users')->where('name', 'Jane Doe')->unset(array('note1', 'note2'));
        $user2 = DB::table('users')->find($id2);
        $this->assertFalse(isset($user2['note1']));
        $this->assertFalse(isset($user2['note2']));
    }

    public function testOperators()
    {
        DB::table('users')->insert([
            ['name' => 'John Doe', 'age' => 30],
            ['name' => 'Jane Doe'],
            ['name' => 'Robert Roe', 'age' => 'thirty-one'],
        ]);
        $results = DB::table('users')->where('age', 'exists', true)->get();
        $this->assertEquals(2, count($results));
        $resultsNames = [$results[0]['name'], $results[1]['name']];
        $this->assertContains('John Doe', $resultsNames);
        $this->assertContains('Robert Roe', $resultsNames);
        $results = DB::table('users')->where('age', 'exists', false)->get();
        $this->assertEquals(1, count($results));
        $this->assertEquals('Jane Doe', $results[0]['name']);
        $results = DB::table('users')->where('age', 'type', 'string')->get();
        $this->assertEquals(1, count($results));
        $this->assertEquals('Robert Roe', $results[0]['name']);
        $results = DB::table('users')->where('age', 'type', 'number')->get();
        $this->assertEquals(1, count($results));
        $this->assertEquals('John Doe', $results[0]['name']);
        $results = DB::table('users')->where('age', 'mod', [15, 0])->get();
        $this->assertEquals(1, count($results));
        $this->assertEquals('John Doe', $results[0]['name']);
        $results = DB::table('users')->where('age', 'mod', [29, 1])->get();
        $this->assertEquals(1, count($results));
        $this->assertEquals('John Doe', $results[0]['name']);
        $results = DB::table('users')->where('age', 'mod', [14, 0])->get();
        $this->assertEquals(0, count($results));
        DB::table('items')->insert([
            ['name' => 'fork',  'tags' => ['sharp', 'pointy']],
            ['name' => 'spork', 'tags' => ['sharp', 'pointy', 'round', 'bowl']],
            ['name' => 'spoon', 'tags' => ['round', 'bowl']],
        ]);
        $results = DB::table('items')->where('tags', 'size', 2)->get();
        $this->assertEquals(2, count($results));
        $results = DB::table('items')->where('tags', 'size', 3)->get();
        $this->assertEquals(0, count($results));
        $results = DB::table('items')->where('tags', 'size', 4)->get();
        $this->assertEquals(1, count($results));
        $results = DB::table('users')->where('name', 'regexp', '(?i).*doe')->get();
        $this->assertEquals(2, count($results));
        $results = DB::table('users')->where('name', 'not regexp', '(?i).*doe')->get();
        $this->assertEquals(1, count($results));
        DB::table('users')->insert([
            [
                'name' => 'John Doe',
                'addresses' => [
                    ['city' => 'Ghent'],
                    ['city' => 'Paris']
                ]
            ],
            [
                'name' => 'Jane Doe',
                'addresses' => [
                    ['city' => 'Brussels'],
                    ['city' => 'Paris']
                ]
            ]
        ]);
        $users = DB::table('users')->where('addresses', 'contains', ['city' => 'Brussels'])->get();
        $this->assertEquals(1, count($users));
        $this->assertEquals('Jane Doe', $users[0]['name']);
    }

}
