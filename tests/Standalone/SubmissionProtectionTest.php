<?php

// Boots Laravel 8 only after redirecting EVERY configured database to in-memory SQLite.
require dirname(__DIR__, 2) . '/vendor/autoload.php';
$app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
$app->afterBootstrapping(\Illuminate\Foundation\Bootstrap\LoadConfiguration::class, function ($app) {
    foreach (array_keys($app['config']->get('database.connections')) as $name) {
        $app['config']->set('database.connections.' . $name, ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
    }
    $app['config']->set('database.default', 'sqlite');
    $app['config']->set('app.key', 'base64:' . base64_encode(str_repeat('s', 32)));
    $app['config']->set('session.driver', 'array');
    $app['config']->set('cache.default', 'array');
});
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

function submissionCheck($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
}
class SubmissionFixture extends \Livewire\Component {
    public static $executions = 0;
    public function save() { self::$executions++; return 42; }
    public function render() { return '<div>Submission fixture</div>'; }
}

\Illuminate\Support\Facades\Schema::connection('hospital')->create('pharm_submission_receipts', function ($t) {
    $t->string('id')->primary(); $t->string('fingerprint'); $t->string('state'); $t->text('response')->nullable(); $t->timestamps();
});
$fixture = \Livewire\Livewire::test(SubmissionFixture::class);
$payload = $fixture->payload;
$firstNonce = $payload['serverMemo']['submissionNonce'] ?? null;
submissionCheck(is_string($firstNonce), 'Initial nonce must be included in the signed server memo');
$payload['updates'] = [
    ['type' => 'callMethod', 'payload' => ['id' => 'first', 'method' => 'save', 'params' => []]],
    ['type' => 'callMethod', 'payload' => ['id' => 'duplicate', 'method' => 'save', 'params' => []]],
];
$next = function ($request) {
    $handler = new class extends \Livewire\Connection\ConnectionHandler {};
    return response()->json($handler->handle($request->all()));
};
$guard = new \App\Http\Middleware\ProtectSubmissions;
$response = $guard->handle(\Illuminate\Http\Request::create('/livewire/message/submission-fixture', 'POST', $payload), $next);
$body = json_decode($response->getContent(), true);
submissionCheck(SubmissionFixture::$executions === 1, 'Bundled double-click must execute once');
submissionCheck($body['effects']['returns']['first'] === 42 && $body['effects']['returns']['duplicate'] === 42, 'Both client promises must receive the result');
submissionCheck($body['serverMemo']['submissionNonce'] !== $firstNonce, 'Successful action must rotate the nonce');
$payload['updates'][0]['payload']['id'] = 'retry';
$replayed = $guard->handle(\Illuminate\Http\Request::create('/livewire/message/submission-fixture', 'POST', $payload), $next);
$body = json_decode($replayed->getContent(), true);
submissionCheck(SubmissionFixture::$executions === 1, 'Retry with a new transport ID must not execute again');
submissionCheck($body['effects']['returns']['retry'] === 42, 'Replay must remap the return value to the new transport ID');
echo "PASS: Laravel 8 / Livewire 2 signed nonce, bundled double-click, request replay, and client return values (SQLite only).\n";
