<?php

namespace HesamRad\LaravelWallet\Tests\Fixtures;

use HesamRad\LaravelWallet\Concerns\InteractsWithWallet;
use HesamRad\LaravelWallet\Contracts\HasWallet;
use Illuminate\Database\Eloquent\Model;

class User extends Model implements HasWallet
{
    use InteractsWithWallet;

    protected $table = 'users';

    protected $guarded = [];
}
