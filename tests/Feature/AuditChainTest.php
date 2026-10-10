<?php

namespace Tests\Feature;

use App\Domain\Compliance\AuditLogger;
use App\Domain\Compliance\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

class AuditChainTest extends TestCase
{
  use RefreshDatabase;

  public function test_chain_is_valid_and_detects_tampering(): void
  {
    $audit = app(AuditLogger::class);
    [$tenant] = $this->organizer();
    $audit->log('a', $tenant, ['x' => 1], ['b' => 2, 'a' => ['z' => 1, 'y' => 2]]);
    $second = $audit->log('b', $tenant, null, ['status' => 'ok']);
    $audit->log('c', null, null, ['n' => 3]);

    $this->assertNull($audit->verifyChain());
    $this->artisan('audit:verify')->assertSuccessful();

    DB::table('audit_logs')->where('id', $second->id)->update(['after' => json_encode(['status' => 'hacked'])]);

    $this->assertSame($second->id, $audit->verifyChain());
    $this->artisan('audit:verify')->assertFailed();
  }

  public function test_records_cannot_be_changed_through_the_model(): void
  {
    $log = app(AuditLogger::class)->log('x');

    $this->expectException(LogicException::class);
    $log->update(['action' => 'y']);
  }

  public function test_sensitive_fields_are_not_logged(): void
  {
    $log = app(AuditLogger::class)->log('x', null, null, ['password' => 'secret', 'asaas_api_key' => 'k', 'name' => 'ok']);

    $this->assertSame(['name' => 'ok'], AuditLog::find($log->id)->after);
  }
}
