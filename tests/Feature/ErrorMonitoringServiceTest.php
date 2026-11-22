<?php

namespace Tests\Feature;

use App\Models\ErrorIncident;
use App\Models\User;
use App\Services\ErrorMonitoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorMonitoringServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @var ErrorMonitoringService */
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ErrorMonitoringService(config('error_monitoring'));
    }

    public function test_it_classifies_uncaught_exception_as_critical(): void
    {
        $exception = new \Error('Test error');
        $severity = $this->invokeMethod($this->service, 'classifySeverity', [$exception]);

        $this->assertSame('critical', $severity);
    }

    public function test_it_classifies_5xx_http_exception_as_critical(): void
    {
        $exception = new \Symfony\Component\HttpKernel\Exception\HttpException(500, 'Server error');
        $severity = $this->invokeMethod($this->service, 'classifySeverity', [$exception]);

        $this->assertSame('critical', $severity);
    }

    public function test_it_classifies_regular_exception_as_error(): void
    {
        $exception = new \Exception('Regular error');
        $severity = $this->invokeMethod($this->service, 'classifySeverity', [$exception]);

        $this->assertSame('error', $severity);
    }

    public function test_it_ignores_validation_exceptions(): void
    {
        $validator = \Illuminate\Support\Facades\Validator::make(
            ['email' => ''],
            ['email' => 'required']
        );
        $exception = new \Illuminate\Validation\ValidationException($validator);
        $result = $this->invokeMethod($this->service, 'shouldIgnore', [$exception]);

        $this->assertTrue($result);
    }

    public function test_it_ignores_404_exceptions(): void
    {
        $exception = new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException('Not found');
        $result = $this->invokeMethod($this->service, 'shouldIgnore', [$exception]);

        $this->assertTrue($result);
    }

    public function test_it_generates_deterministic_fingerprint(): void
    {
        $exception = new \Exception('Something went wrong');

        $fp1 = $this->invokeMethod($this->service, 'generateFingerprint', [$exception]);
        $fp2 = $this->invokeMethod($this->service, 'generateFingerprint', [$exception]);

        $this->assertSame($fp1, $fp2);
    }

    public function test_it_generates_different_fingerprint_for_different_messages(): void
    {
        $exception1 = new \Exception('Error message one');
        $exception2 = new \Exception('Error message two');

        $fp1 = $this->invokeMethod($this->service, 'generateFingerprint', [$exception1]);
        $fp2 = $this->invokeMethod($this->service, 'generateFingerprint', [$exception2]);

        $this->assertNotSame($fp1, $fp2);
    }

    public function test_it_sanitizes_sensitive_keys(): void
    {
        $message = 'password=secret123 api_key=abc123 normal_value=hello';
        $sanitized = $this->invokeMethod($this->service, 'sanitize', [$message]);

        $this->assertStringContainsString('password=[REDACTED]', $sanitized);
        $this->assertStringContainsString('api_key=[REDACTED]', $sanitized);
        $this->assertStringContainsString('normal_value=hello', $sanitized);
    }

    public function test_it_stores_incident_and_returns_it(): void
    {
        $exception = new \Exception('Test error message');
        $user = User::factory()->create();

        $incident = $this->invokeMethod($this->service, 'findOrCreateIncident', [
            'abc123',
            'error',
            $exception,
            'Test error message',
            'Stack trace here',
            ['request_method' => 'GET', 'request_url' => '/test', 'route_name' => 'home', 'http_status' => 500, 'request_id' => 'req-123', 'user_id' => $user->id],
        ]);

        $this->assertInstanceOf(ErrorIncident::class, $incident);
        $this->assertSame('abc123', $incident->fingerprint);
        $this->assertSame('error', $incident->severity);
        $this->assertSame('Test error message', $incident->message);
        $this->assertSame(1, $incident->occurrence_count);
        $this->assertSame($user->id, $incident->user_id);
    }

    public function test_it_increments_occurrence_count_for_duplicate_fingerprint(): void
    {
        $exception = new \Exception('Test error message');

        $incident1 = $this->invokeMethod($this->service, 'findOrCreateIncident', [
            'fp123', 'error', $exception, 'Test error message', 'trace', [],
        ]);

        $incident2 = $this->invokeMethod($this->service, 'findOrCreateIncident', [
            'fp123', 'error', $exception, 'Test error message', 'trace', [],
        ]);

        $this->assertSame($incident1->id, $incident2->id);
        $this->assertSame(2, $incident2->fresh()->occurrence_count);
    }

    public function test_it_suppresses_duplicate_notifications_within_interval(): void
    {
        $exception = new \Exception('Test error');

        $incident = $this->invokeMethod($this->service, 'findOrCreateIncident', [
            'fp456', 'error', $exception, 'Test error', 'trace', [],
        ]);

        // First occurrence should notify
        $this->assertTrue($this->invokeMethod($this->service, 'shouldNotify', [$incident, 'error']));

        // Second occurrence within suppression interval should not notify
        $incident->update(['occurrence_count' => 2, 'last_seen_at' => now()]);
        $this->assertFalse($this->invokeMethod($this->service, 'shouldNotify', [$incident, 'error']));
    }

    public function test_it_allows_notification_after_suppression_interval(): void
    {
        $exception = new \Exception('Test error');

        $incident = $this->invokeMethod($this->service, 'findOrCreateIncident', [
            'fp789', 'error', $exception, 'Test error', 'trace', [],
        ]);

        $incident->update([
            'occurrence_count' => 2,
            'last_seen_at' => now()->subMinutes(61), // Outside 60-minute suppression
        ]);

        $this->assertTrue($this->invokeMethod($this->service, 'shouldNotify', [$incident, 'error']));
    }

    /**
     * Invoke a protected/private method for testing.
     */
    protected function invokeMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
