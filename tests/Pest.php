<?php

use App\Mcp\Auth\McpContext;
use App\Mcp\Auth\McpScope;
use App\Mcp\Support\McpTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit
| test case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/*
|--------------------------------------------------------------------------
| MCP Test Helpers
|--------------------------------------------------------------------------
|
| `mcpToken()` creates an MCP token through the real service layer and
| returns the raw bearer token so tests can act as an MCP client. It also
| binds the matching `McpContext` into the container so package testing
| facilities (e.g. `Server::tool(...)`) resolve authorization correctly.
|
*/

function mcpToken(?array $scopes = null): string
{
    $scopes ??= McpScope::values();

    $token = app(McpTokenService::class)->generate(
        name: 'test-token',
        scopes: $scopes,
        expiresInDays: null,
        createdBy: null,
    );

    app()->instance(McpContext::class, new McpContext(
        token: $token['token'],
        scopes: $token['token']->scopeEnumValues(),
        requestId: 'test-request',
    ));

    return $token['raw'];
}
