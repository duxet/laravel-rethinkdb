<?php

$loader = require 'vendor/autoload.php';

// Add stuff to autoload
$loader->add('', [__DIR__, 'tests/models', 'tests/seeds']);
