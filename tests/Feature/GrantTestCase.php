<?php

namespace Tests\Feature;

use A2Workspace\SocialEntry\SocialEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseMigrations;

abstract class GrantTestCase extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        SocialEntry::useUserModel(\GrantTestCase\UserStub::class);

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('username')->unique();
            $table->string('password');
        });
    }
}

namespace GrantTestCase;

class UserStub extends \Illuminate\Foundation\Auth\User
{
    use \A2Workspace\SocialEntry\HasSocialIdentifiers;

    public $timestamps = false;
    protected $table = 'users';
    protected $fillable = ['username', 'password'];
}

class AdminStub extends \Illuminate\Foundation\Auth\User
{
    use \A2Workspace\SocialEntry\HasSocialIdentifiers;

    public $timestamps = false;
    protected $table = 'users';
    protected $fillable = ['username', 'password'];
}
