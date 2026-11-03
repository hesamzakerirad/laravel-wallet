<?php

namespace HesamRad\LaravelWallet\Events;

use HesamRad\LaravelWallet\Models\Log;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched after the deposit transaction commits.
 */
class WalletDeposited
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param \HesamRad\LaravelWallet\Models\Log $log The log written for the deposit.
     */
    public function __construct(public Log $log)
    {
        //
    }
}
