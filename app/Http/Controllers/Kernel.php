protected $routeMiddleware = [
    // ...
    'force.json' => \App\Http\Middleware\ForceJsonResponse::class,
];