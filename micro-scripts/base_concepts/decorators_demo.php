<?php
declare(strict_types=1);

/**
 * ============================================================
 * 1. ATTRIBUTES (PHP 8+) + Reflection
 * ============================================================
 */

#[Attribute(Attribute::TARGET_METHOD)]
class Authorize
{
    public function __construct(public string $role) {}
}

class UserService
{
    #[Authorize('admin')]
    public function deleteUser(int $id): void
    {
        echo "User {$id} deleted\n";
    }
}

echo "\n=== 1. ATTRIBUTES ===\n";

$service = new UserService();
$method  = new ReflectionMethod(UserService::class, 'deleteUser');
$attrs   = $method->getAttributes(Authorize::class);

foreach ($attrs as $attr) {
    $instance = $attr->newInstance();
    echo "Authorization required: {$instance->role}\n";
}

$service->deleteUser(10);

/**
 * ============================================================
 * 2. CLASSIC DECORATOR PATTERN (ООП)
 * ============================================================
 */

interface Notifier
{
    public function send(string $message): void;
}

class EmailNotifier implements Notifier
{
    public function send(string $message): void
    {
        echo "Email sent: $message\n";
    }
}

class LoggingNotifier implements Notifier
{
    public function __construct(private Notifier $notifier) {}

    public function send(string $message): void
    {
        echo "[LOG] Sending notification...\n";
        $this->notifier->send($message);
    }
}

echo "\n=== 2. DECORATOR PATTERN ===\n";

$notifier = new LoggingNotifier(new EmailNotifier());
$notifier->send("Hello world");

/**
 * ============================================================
 * 3. FUNCTIONAL DECORATOR (closures)
 * ============================================================
 */

function timing(callable $fn): callable
{
    return function (...$args) use ($fn) {
        $start = microtime(true);
        $result = $fn(...$args);
        $end = microtime(true);
        echo "Execution time: " . ($end - $start) . " sec\n";
        return $result;
    };
}

echo "\n=== 3. FUNCTION DECORATOR ===\n";

$work = timing(function (int $x) {
    usleep(200_000);
    echo "Working with $x\n";
});

$work(42);

/**
 * ============================================================
 * 4. MIDDLEWARE STYLE (request pipeline)
 * ============================================================
 */

$request = ['user' => 'admin'];

$authMiddleware = function ($request, $next) {
    if ($request['user'] !== 'admin') {
        throw new Exception('Forbidden');
    }
    echo "Auth OK\n";
    return $next($request);
};

$loggingMiddleware = function ($request, $next) {
    echo "Request received\n";
    return $next($request);
};

$controller = function ($request) {
    echo "Controller executed\n";
};

function pipeline(array $middlewares, callable $controller): callable
{
    return array_reduce(
        array_reverse($middlewares),
        fn ($next, $mw) => fn ($req) => $mw($req, $next),
        $controller
    );
}

echo "\n=== 4. MIDDLEWARE ===\n";

$app = pipeline([$loggingMiddleware, $authMiddleware], $controller);
$app($request);

/**
 * ============================================================
 * 5. PROXY / AOP-LIKE DECORATOR
 * ============================================================
 */

interface Calculator
{
    public function add(int $a, int $b): int;
}

class SimpleCalculator implements Calculator
{
    public function add(int $a, int $b): int
    {
        return $a + $b;
    }
}

class CalculatorProxy implements Calculator
{
    public function __construct(private Calculator $calc) {}

    public function add(int $a, int $b): int
    {
        echo "Calling add($a, $b)\n";
        $result = $this->calc->add($a, $b);
        echo "Result: $result\n";
        return $result;
    }
}

echo "\n=== 5. PROXY / AOP ===\n";

$calc = new CalculatorProxy(new SimpleCalculator());
$calc->add(3, 5);