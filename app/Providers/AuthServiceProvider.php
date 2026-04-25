<?php

namespace App\Providers;

use App\Models\Activity;
use App\Models\Post;
use App\Policies\ActivityPolicy;
use App\Policies\PostPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Post::class => PostPolicy::class,
        Activity::class => ActivityPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    // The boot() method in Laravel's AuthServiceProvider is a special 
    // method used to define gates and policies for handling authorization logic.
    public function boot()
    {
        $this->registerPolicies();
        // define an administrator user role
        Gate::define('isAdmin', function ($user) {
            return $user->role == 'admin';
        });
        // define an author user role
        Gate::define('isAuthor', function ($user) {
            return $user->role == 'author';
        });
        // define a user role
        Gate::define('isUser', function ($user) {
            return $user->role == 'user';
        });
    }
}
