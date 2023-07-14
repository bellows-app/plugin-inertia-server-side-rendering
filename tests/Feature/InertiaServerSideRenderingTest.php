<?php

use Bellows\Plugins\InertiaServerSideRendering;
use Bellows\PluginSdk\Facades\Deployment;

it('can set the env variable if there are other ports in use', function () {
    $site1 = $this->fakeSite()->returnEnv([
        'SSR_PORT' => 13716,
    ]);

    $site2 = $this->fakeSite()->returnEnv([
        'SSR_PORT' => 13717,
    ]);

    Deployment::server()->shouldReceive('sites')->andReturn(collect([$site1, $site2]));

    $result = $this->plugin(InertiaServerSideRendering::class)->deploy();

    expect($result->getEnvironmentVariables())->toBe([
        'SSR_PORT'      => 13718,
        'VITE_SSR_PORT' => '${SSR_PORT}',
    ]);
});

it('can set the env variable if there are no other ports in use', function () {
    Deployment::server()->shouldReceive('sites')->andReturn(collect([
        $this->fakeSite(),
    ]));

    $result = $this->plugin(InertiaServerSideRendering::class)->deploy();

    expect($result->getEnvironmentVariables())->toBe([
        'SSR_PORT'      => 13716,
        'VITE_SSR_PORT' => '${SSR_PORT}',
    ]);
});

it('can create a daemon', function () {
    Deployment::server()->shouldReceive('sites')->andReturn(collect([
        $this->fakeSite(),
    ]));

    $result = $this->plugin(InertiaServerSideRendering::class)->deploy();

    $daemons = $result->getDaemons();

    expect($daemons)->toHaveCount(1);

    expect($daemons[0]->toArray())->toBe([
        'command'   => 'inertia:start-ssr',
        'user'      => null,
        'directory' => null,
    ]);
});

it('can update the deploy script', function () {
    Deployment::server()->shouldReceive('sites')->andReturn(collect([
        $this->fakeSite(),
    ]));

    $result = $this->plugin(InertiaServerSideRendering::class)->deploy();

    $deployScript = $result->getUpdateDeployScript();

    expect($deployScript)->toContain('inertia:stop-ssr');
});
