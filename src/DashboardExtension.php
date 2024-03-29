<?php

namespace Jackal\Extension\Dashboard;

use Slim\Routing\RouteCollectorProxy;
use App\Constracts\AssetTypeEnum;
use App\Constracts\BackendExtensionConstract;
use App\Constracts\FrontendExtensionConstract;
use Jackal\Jackal\AssetManager;
use Jackal\Jackal\Assets\AssetScriptOptions;
use Jackal\Jackal\Extension;
use Jackal\Jackal\ExtensionManager;
use Jackal\Jackal\Helper;
use Jackal\Jackal\HookManager;
use Jackal\Jackal\Settings\SettingsInterface;
use App\Http\Middleware\Authenticate;
use Jackal\Extension\Dashboard\Http\Controllers\DashboardController;

class DashboardExtension extends Extension implements FrontendExtensionConstract, BackendExtensionConstract
{
    protected $isBuiltIn = true;

    public function bootstrap()
    {
        /**
         * @var \Jackal\Extension\React\ReactExtension
         */
        $reactExt = ExtensionManager::getExtension('jackal-cms/react');
        $reactAsset = $reactExt->getReactAsset();
        AssetManager::getInstance()->getBackendBucket()->addAsset($reactAsset);
    }

    public function registerRoutes()
    {
        /** @var SettingsInterface $settings */
        $settings     = $this->container->get(SettingsInterface::class);
        $admin_prefix = $settings->get('admin_prefix', 'dashboard');
        $app          = &$this->app;
        $extension    = &$this;

        $this->app->group($admin_prefix, function (RouteCollectorProxy $group) use ($app, $settings) {
            $group->get('', [DashboardController::class, 'handle'])->setName('dashboardTop');
            $group->get('{pagePath:/?.+}', [DashboardController::class, 'handle']);

            // Support custom dashboard or register dashboard content by other extensions
            HookManager::executeAction('setup_dashboard', $group, $app, $settings);
        })
            ->add(new Authenticate())
            ->add(function ($request, $handler) use ($extension) {
                $reponse = $handler->handle($request);

                AssetManager::registerBackendAsset(
                    'dashboard',
                    Helper::createExtensionAssetUrl($extension->getExtensionDir(), 'js/dashboard.js', 'js/dashboard.min.js'),
                    AssetTypeEnum::JS(),
                    ['react'],
                    '1.0.0',
                    AssetScriptOptions::parseOptionFromArray([
                        'is_footer' => true,
                    ])
                )->enqueue();

                AssetManager::registerBackendAsset(
                    'dashboard',
                    Helper::createExtensionAssetUrl($extension->getExtensionDir(), 'css/dashboard.css', 'css/dashboard.min.css'),
                    AssetTypeEnum::CSS(),
                    [],
                    '1.0.0',
                    AssetScriptOptions::parseOptionFromArray([
                        'is_footer' => true,
                    ])
                )->enqueue();


                return $reponse;
            });
    }
}
