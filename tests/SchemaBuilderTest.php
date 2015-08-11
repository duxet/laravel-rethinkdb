<?php

class SchemaBuilderTest extends TestCase
{
    public function tearDown()
    {
        if (Schema::hasTable('newtable')) {
            Schema::drop('newtable');
        }
    }

    public function testCreate()
    {
        Schema::create('newtable');
        $this->assertTrue(Schema::hasTable('newtable'));
    }

    public function testCreateWithCallback()
    {
        $instance = $this;

        Schema::create('newtable', function($collection) use ($instance) {
            $instance->assertInstanceOf('duxet\RethinkDB\Schema\Blueprint', $collection);
        });

        $this->assertTrue(Schema::hasTable('newtable'));
    }

    public function testDrop()
    {
        Schema::create('newtable');
        Schema::drop('newtable');
        $this->assertFalse(Schema::hasTable('newtable'));
    }

    public function testBluePrint()
    {
        $instance = $this;

        Schema::table('newtable', function($table) use ($instance) {
            $instance->assertInstanceOf('duxet\RethinkDB\Schema\Blueprint', $table);
        });
    }
}
