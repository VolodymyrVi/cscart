<?php
/***************************************************************************
 *                                                                          *
 *   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
 *                                                                          *
 * This  is  commercial  software,  only  users  who have purchased a valid *
 * license  and  accept  to the terms of the  License Agreement can install *
 * and use this program.                                                    *
 *                                                                          *
 ****************************************************************************
 * PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
 * "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/

namespace Tygh\Addons\ProductBundles;

use Tygh\Core\ApplicationInterface;
use Tygh\Core\BootstrapInterface;
use Tygh\Core\HookHandlerProviderInterface;
use Tygh\SmartyEngine\Core;

class Bootstrap implements BootstrapInterface, HookHandlerProviderInterface
{
    /**
     * @inheritDoc
     */
    public function boot(ApplicationInterface $app)
    {
        $app->register(new ServiceProvider());
    }

    /**
     * @inheritDoc
     */
    public function getHookHandlerMap()
    {
        return [
            'get_promotions' => [
                'addons.product_bundles.hook_handlers.promotions',
                'onGetPromotions',
            ],
            'promotion_apply_pre' => [
                'addons.product_bundles.hook_handlers.promotions',
                'onPrePromotionApply',
            ],
            'delete_product_pre' => [
                'addons.product_bundles.hook_handlers.products',
                'onPreProductDelete',
            ],
            'tools_change_status' => [
                'addons.product_bundles.hook_handlers.tools',
                'onToolsChangeStatus',
            ],
            'get_products_pre' => [
                'addons.product_bundles.hook_handlers.products',
                'onGetProductsPre',
                2000,
                'product_variations',
            ],
            'product_bundle_service_update_bundle' => [
                'addons.product_bundles.hook_handlers.product_variations',
                'onUpdateBundle',
                null,
                'product_variations',
            ],
            'product_bundle_service_update_bundle_post' => [
                'addons.product_bundles.hook_handlers.product_variations',
                'onPostUpdateBundle',
                null,
                'product_variations',
            ],
            'product_bundle_service_delete_bundle_pre' => [
                'addons.product_bundles.hook_handlers.product_variations',
                'onPreDeleteBundle',
                null,
                'product_variations',
            ],
            'product_bundle_service_get_bundles_post' => [
                'addons.product_bundles.hook_handlers.product_variations',
                'onPostGetBundles',
                null,
                'product_variations',
            ],
            'product_bundle_service_get_bundles_pre' => [
                'addons.product_bundles.hook_handlers.product_variations',
                'onPreGetBundles',
                null,
                'product_variations',
            ],
            'product_bundle_service_get_bundles' => [
                'addons.product_bundles.hook_handlers.master_products',
                'onGetBundles',
                null,
                'master_products',
            ],
            'product_bundles_service_update_hidden_promotion_before_update' => [
                'addons.product_bundles.hook_handlers.direct_payments',
                'onPreUpdatePromotion',
                null,
                'direct_payments',
            ],
            'pre_validate_promotion_attribute' => [
                'addons.product_bundles.hook_handlers.promotions',
                'onValidatePromotionAttributePre',
                null,
                'product_variations',
            ],
            'init_templater_post' => static function (Core $view) {
                $view->addPluginsDir(__DIR__ . '/SmartyPlugins');
            },
        ];
    }
}
