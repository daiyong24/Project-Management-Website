<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    // $policies is a protected property of the AuthServiceProvider class.
    // When you register this in the AuthServiceProvider, Laravel will use the PostPolicy class 
    // whenever an authorization check is done for the Post model.
    protected $policies = [
      Post::class => PostPolicy::class,
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
