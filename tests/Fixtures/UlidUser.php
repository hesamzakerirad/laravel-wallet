<?php

namespace HesamRad\LaravelWallet\Tests\Fixtures;

use HesamRad\LaravelWallet\Concerns\InteractsWithWallet;
use HesamRad\LaravelWallet\Contracts\HasWallet;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class UlidUser extends Model implements HasWallet
{
    use HasUlids, InteractsWithWallet;

    protected $table = 'ulid_users';

    protected $guarded = [];
}
