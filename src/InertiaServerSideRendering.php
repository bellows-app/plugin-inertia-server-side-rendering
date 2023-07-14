<?php

namespace Bellows\Plugins;

use Bellows\PluginSdk\Contracts\Deployable;
use Bellows\PluginSdk\Contracts\ServerProviders\SiteInterface;
use Bellows\PluginSdk\Data\DaemonParams;
use Bellows\PluginSdk\Facades\Artisan;
use Bellows\PluginSdk\Facades\Deployment;
use Bellows\PluginSdk\Facades\DeployScript;
use Bellows\PluginSdk\Plugin;
use Bellows\PluginSdk\PluginResults\CanBeDeployed;
use Bellows\PluginSdk\PluginResults\DeploymentResult;

class InertiaServerSideRendering extends Plugin implements Deployable
{
    use CanBeDeployed;

    protected int $ssrPort;

    public function getName(): string
    {
        return 'Inertia Server-Side Rendering';
    }

    public function deploy(): ?DeploymentResult
    {
        $defaultSSRPort = 13716;

        $highestSSRPortInUse = Deployment::server()->sites()
            ->map(fn (SiteInterface $s) => $s->env()->get('SSR_PORT'))
            ->filter()
            ->map(fn ($s) => (int) $s)
            ->max() ?: $defaultSSRPort - 1;

        $this->ssrPort = $highestSSRPortInUse + 1;

        return DeploymentResult::create()
            ->environmentVariables([
                'SSR_PORT'      => $this->ssrPort,
                'VITE_SSR_PORT' => '${SSR_PORT}',
            ])
            ->daemon(new DaemonParams('inertia:start-ssr'))
            ->updateDeployScript(
                fn () => DeployScript::addBeforePHPReload(
                    Artisan::inDeployScript('inertia:stop-ssr'),
                )
            );

        return DeploymentResult::create();
    }

    public function anyRequiredNpmPackages(): array
    {
        // TODO: React doesn't require any additional dependencies, how should we handle?
        return  [
            '@vue/server-renderer',
        ];
    }

    public function shouldDeploy(): bool
    {
        return !Deployment::site()->env()->hasAll('SSR_PORT', 'VITE_SSR_PORT')
            || !Deployment::site()->isInDeploymentScript('inertia:stop-ssr')
            || !Deployment::server()->hasDaemon('inertia:start-ssr');
    }
}
