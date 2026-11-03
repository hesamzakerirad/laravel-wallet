<?php

namespace HesamRad\LaravelWallet\Tests\Feature;

use HesamRad\LaravelWallet\Exceptions\InsufficientBalanceException;
use HesamRad\LaravelWallet\Tests\Fixtures\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;

/**
 * Runs real parallel processes against a file database, so these tests
 * cannot use the transaction-wrapped RefreshDatabase trait.
 */
class ConcurrencyTest extends TestCase
{
    protected string $databasePath;

    protected function setUp(): void
    {
        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped('The pcntl extension is required for concurrency tests.');
        }

        $this->databasePath = tempnam(sys_get_temp_dir(), 'wallet') . '.sqlite';
        touch($this->databasePath);

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        @unlink($this->databasePath);
    }

    protected function getPackageProviders($app): array
    {
        return [\HesamRad\LaravelWallet\LaravelWalletServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => $this->databasePath,
            'prefix' => '',
            'foreign_key_constraints' => true,
            'busy_timeout' => 10000,
            'transaction_mode' => 'IMMEDIATE',
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    public function test_parallel_withdraws_cannot_take_the_balance_below_zero(): void
    {
        $user = User::create(['name' => 'Test']);
        $user->deposit(100);

        $results = $this->inParallel(10, function () use ($user) {
            try {
                return User::find($user->id)->withdraw(30) ? 'ok' : 'failed';
            } catch (InsufficientBalanceException) {
                return 'insufficient';
            }
        });

        $counts = array_count_values($results);

        $this->assertSame(3, $counts['ok'] ?? 0);
        $this->assertSame(7, $counts['insufficient'] ?? 0);
        $this->assertSame(10.0, $user->freshBalance());
        $this->assertDatabaseCount('wallet_logs', 4);
    }

    public function test_parallel_calls_with_the_same_key_move_money_once(): void
    {
        $user = User::create(['name' => 'Test']);

        $results = $this->inParallel(10, function () use ($user) {
            return (string) User::find($user->id)->depositOrFail(50, key: 'job-42')->id;
        });

        $this->assertCount(1, array_unique($results), 'Every call must return the same log: ' . implode(', ', $results));
        $this->assertSame(50.0, $user->freshBalance());
        $this->assertDatabaseCount('wallet_logs', 1);
    }

    /**
     * Run the callback in $processes forked children that start at the same moment.
     *
     * @return list<string> The value each child returned.
     */
    protected function inParallel(int $processes, \Closure $callback): array
    {
        DB::disconnect();

        $startAt = microtime(true) + 0.5;
        $files = [];
        $pids = [];

        for ($i = 0; $i < $processes; $i++) {
            $files[$i] = tempnam(sys_get_temp_dir(), 'wallet-result');
            $pid = pcntl_fork();

            if ($pid === 0) {
                DB::purge();
                time_sleep_until($startAt);

                try {
                    $result = $callback();
                } catch (\Throwable $th) {
                    $result = 'error: ' . $th->getMessage();
                }

                file_put_contents($files[$i], $result);

                // Skip PHPUnit's shutdown handlers in the child.
                posix_kill(posix_getpid(), SIGKILL);
            }

            $pids[] = $pid;
        }

        foreach ($pids as $pid) {
            pcntl_waitpid($pid, $status);
        }

        DB::purge();

        return array_map(function (string $file) {
            $result = file_get_contents($file);
            unlink($file);

            return $result;
        }, $files);
    }
}
