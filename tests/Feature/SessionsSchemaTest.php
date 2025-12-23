<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('stores session user ids as uuid-compatible column type', function () {
    $type = Schema::getColumnType('sessions', 'user_id');

    $numericTypes = ['int8', 'int4', 'int2', 'bigint', 'integer', 'int', 'smallint'];

    expect(in_array($type, $numericTypes, true))->toBeFalse();
});
