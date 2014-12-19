<?php

class QueryTest extends TestCase {

    public static function setUpBeforeClass()
    {
        User::create(['name' => 'John Doe', 'age' => 35, 'title' => 'admin']);
        User::create(['name' => 'Jane Doe', 'age' => 33, 'title' => 'admin']);
        User::create(['name' => 'Harry Hoe', 'age' => 13, 'title' => 'user']);
        User::create(['name' => 'Robert Roe', 'age' => 37, 'title' => 'user']);
        User::create(['name' => 'Mark Moe', 'age' => 23, 'title' => 'user']);
        User::create(['name' => 'Brett Boe', 'age' => 35, 'title' => 'user']);
        User::create(['name' => 'Tommy Toe', 'age' => 33, 'title' => 'user']);
        User::create(['name' => 'Yvonne Yoe', 'age' => 35, 'title' => 'admin']);
        User::create(['name' => 'Error', 'age' => null, 'title' => null]);
    }

    public static function tearDownAfterClass()
    {
        //User::truncate();
    }

    public function testWhere()
    {
        $users = User::where('age', 35)->get();
        $this->assertEquals(3, count($users));

        $users = User::where('age', '=', 35)->get();
        $this->assertEquals(3, count($users));

        $users = User::where('age', '>=', 35)->get();
        $this->assertEquals(4, count($users));

        $users = User::where('age', '<=', 18)->get();
        $this->assertEquals(2, count($users));

        $users = User::where('age', '!=', 35)->get();
        $this->assertEquals(6, count($users));

        $users = User::where('age', '<>', 35)->get();
        $this->assertEquals(6, count($users));
    }

    public function testAndWhere()
    {
        $users = User::where('age', 35)->where('title', 'admin')->get();
        $this->assertEquals(2, count($users));

        $users = User::where('age', '>=', 35)->where('title', 'user')->get();
        $this->assertEquals(2, count($users));
    }

    public function testLike()
    {
        $users = User::where('name', 'like', '%doe')->get();
        $this->assertEquals(2, count($users));

        $users = User::where('name', 'like', '%y%')->get();
        $this->assertEquals(3, count($users));

        $users = User::where('name', 'LIKE', '%y%')->get();
        $this->assertEquals(3, count($users));

        $users = User::where('name', 'like', 't%')->get();
        $this->assertEquals(1, count($users));
    }

    public function testSelect()
    {
        $user = User::where('name', 'John Doe')->select('name')->first();

        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals(null, $user->age);
        $this->assertEquals(null, $user->title);

        $user = User::where('name', 'John Doe')->select('name', 'title')->first();

        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('admin', $user->title);
        $this->assertEquals(null, $user->age);

        $user = User::where('name', 'John Doe')->select(['name', 'title'])->get()->first();

        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('admin', $user->title);
        $this->assertEquals(null, $user->age);

        $user = User::where('name', 'John Doe')->get(['name'])->first();

        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals(null, $user->age);
    }

    public function testOrWhere()
    {
        $users = User::where('age', 13)->orWhere('title', 'admin')->get();
        $this->assertEquals(4, count($users));
        $users = User::where('age', 13)->orWhere('age', 23)->get();
        $this->assertEquals(2, count($users));
    }

    public function testBetween()
    {
        $users = User::whereBetween('age', [0, 25])->get();
        $this->assertEquals(2, count($users));
        $users = User::whereBetween('age', [13, 23])->get();
        $this->assertEquals(2, count($users));
    }

    public function testWhereNull()
    {
        $users = User::whereNull('age')->get();
        $this->assertEquals(1, count($users));
    }

    public function testWhereNotNull()
    {
        $users = User::whereNotNull('age')->get();
        $this->assertEquals(8, count($users));
    }

}
