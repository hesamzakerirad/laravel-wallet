<?php

namespace HesamRad\LaravelWallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
            'amount' => 'float',
            'action' => 'string',
            'description' => 'string',
            'meta' => 'array',
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
}
