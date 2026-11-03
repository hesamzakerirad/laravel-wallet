<?php

namespace HesamRad\LaravelWallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Log extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string|null
     */
    protected $table = 'wallet_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'wallet_id',
        'amount',
        'action',
        'description',
        'meta',
        'reference_type',
        'reference_id',
        'idempotency_key',
        'balance_after',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'wallet_id' => 'integer',
            'amount' => config('wallet.amounts') === 'integer' ? 'integer' : 'float',
            'action' => 'string',
            'description' => 'string',
            'meta' => 'array',
            'idempotency_key' => 'string',
            'balance_after' => config('wallet.amounts') === 'integer' ? 'integer' : 'float',
        ];
    }

    /**
     * Return the wallet parent of this log.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Wallet, Log>
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Return the model this log refers to (e.g. an order), if any.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo<Model, Log>
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
