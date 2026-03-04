<?php

namespace HesamRad\LaravelWallet\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    /** @use HasFactory<\HesamRad\LaravelWallet\Database\Factories\WalletFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'owner_type',
        'owner_id',
        'balance',
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
            'owner_type' => 'string',
            'owner_id' => 'integer',
            'balance' => 'double',
        ];
    }

    /**
     * Return the logs belonging to this wallet.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Log, Wallet>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(Log::class)->latest();
    }
}
