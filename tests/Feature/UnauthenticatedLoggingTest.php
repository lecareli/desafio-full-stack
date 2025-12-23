<?php

use App\Enums\ErrorLevelEnum;
use App\Models\AuditLog;
use App\Models\ErrorLog;
use App\Models\Wallet;
use App\Services\Logging\AuditLogger;
use App\Services\Logging\ErrorLogger;
use App\Support\RequestContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('logs an error with a null actor in unauthenticated contexts', function () {
    $ctx = new RequestContext(Request::create('http://localhost/test', 'GET'));
    $logger = new ErrorLogger($ctx);

    $log = $logger->log(ErrorLevelEnum::ERROR, 'Something went wrong');

    expect($log)->toBeInstanceOf(ErrorLog::class)
        ->and($log->actor_user_id)->toBeNull();

    $this->assertDatabaseCount('error_logs', 1);
    $this->assertDatabaseHas('error_logs', [
        'id' => $log->id,
        'actor_user_id' => null,
    ]);
});

it('logs an audit event with a null actor in unauthenticated contexts', function () {
    $ctx = new RequestContext(Request::create('http://localhost/test', 'GET'));
    $logger = new AuditLogger($ctx);

    $entityId = (string) Str::uuid();

    $log = $logger->log('TEST', Wallet::class, $entityId, null, null);

    expect($log)->toBeInstanceOf(AuditLog::class)
        ->and($log->actor_user_id)->toBeNull();

    $this->assertDatabaseCount('audit_logs', 1);
    $this->assertDatabaseHas('audit_logs', [
        'id' => $log->id,
        'actor_user_id' => null,
        'entity_id' => $entityId,
    ]);
});
