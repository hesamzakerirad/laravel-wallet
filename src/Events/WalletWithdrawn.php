<?php

namespace HesamRad\LaravelWallet\Events;

use HesamRad\LaravelWallet\Models\Log;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched after the withdrawal transaction commits.
 */
class WalletWithdrawn
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param \HesamRad\LaravelWallet\Models\Log $log The log written for the withdrawal.
     */
    public function __construct(public Log $log)
    {
        //
    }
}
