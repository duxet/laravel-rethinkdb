<?php

class ConnectionTest extends TestCase {

    public function testConnection()
    {
        $connection = DB::connection('rethinkdb');
        $this->assertInstanceOf('duxet\Rethinkdb\Connection', $connection);
    }

    public function testReconnect()
    {
        $c1 = DB::connection('rethinkdb');
        $c2 = DB::connection('rethinkdb');
        $this->assertEquals(spl_object_hash($c1), spl_object_hash($c2));

        $c1 = DB::connection('rethinkdb');
        DB::purge('rethinkdb');
        $c2 = DB::connection('rethinkdb');
        $this->assertNotEquals(spl_object_hash($c1), spl_object_hash($c2));
    }

}
