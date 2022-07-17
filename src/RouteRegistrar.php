<?php

namespace A2Workspace\SocialEntry;

use Illuminate\Contracts\Routing\Registrar as Router;

class RouteRegistrar
{
    /**
     * @var \Illuminate\Contracts\Routing\Registrar
     */
    protected $router;

    /**
     * @var array
     */
    protected array $defaultOptions = [
        'prefix' => '/auth/socialite',
        'namespace' => '\A2Workspace\SocialEntry\Http\Controllers',
        'as' => 'social-entry.',
    ];

    /**
     * @param  \Illuminate\Contracts\Routing\Registrar  $router
     * @return void
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Register all the routes of social-entry.
     *
     * @param  array  $options
     * @return void
     */
    public function all(array $options = [])
    {
        $this->forAuthorization($options);
        $this->forAccessToken($options);
        $this->forUserAccesses($options);
    }

    /**
     * Register the routes for authorization.
     *
     * @param  array  $options
     * @return void
     */
    public function forAuthorization(array $options = [])
    {
        $options = array_merge($this->defaultOptions, $options);

        $this->router->group($options, function ($router) {
            $router->get('/', [
                'uses' => 'AuthorizationController@authorize',
                'as' => 'authorizations.authorize',
                'middleware' => 'web',
            ]);

            $router->get('/{provider}/callback', [
                'uses' => 'CompleteAuthorizationController@callback',
                'as' => 'authorizations.callback',
                'middleware' => 'web',
            ]);
        });
    }

    /**
     * Register the routes to handle auth code.
     *
     * @param  array  $options
     * @return void
     */
    public function forAccessToken(array $options = [])
    {
        $options = array_merge($this->defaultOptions, $options);

        $this->router->group($options, function ($router) {
            $router->post('/token', [
                'uses' => 'IssueAccessTokenController',
                'as' => 'token',
                'middleware' => 'throttle',
            ]);
        });
    }

    /**
     * Register the routes for user accesses.
     *
     * @param  array  $options
     * @return void
     */
    public function forUserAccesses(array $options = [])
    {
        $options = array_merge($this->defaultOptions, $options);

        $this->router->group($options, function ($router) {
            $router->post('/login', [
                'uses' => 'AuthenticationController',
                'as' => 'login',
            ]);

            $router->post('/connect', [
                'uses' => 'ConnectionController',
                'as' => 'connect',
            ]);

            $router->post('/disconnect', [
                'uses' => 'DisconnectionController',
                'as' => 'disconnect',
            ]);
        });
    }
}
