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

use Tygh\Addons\ProductBundles\ServiceProvider;
use Tygh\Enum\ObjectStatuses;

defined('BOOTSTRAP') or die('Access denied');

/**
 * @param array{product: array{product_id: int}} $params   Block params
 * @param string                                 $content  Block content
 * @param \Smarty_Internal_Template              $template Smarty template
 *
 * @return string
 */
function smarty_component_product_bundles_bundles_on_product_tab(
    array $params,
    $content,
    Smarty_Internal_Template $template
) {
    $product = $params['product'];
    if (empty($product['product_id'])) {
        return '';
    }

    $bundle_service = ServiceProvider::getService();
    list($bundles,) = $bundle_service->getBundles(
        [
            'product_id'            => $product['product_id'],
            'full_info'             => true,
            'status'                => ObjectStatuses::ACTIVE,
            'show_on_products_page' => true,
        ]
    );

    $template->assign([
        'bundles' => $bundles,
    ]);

    try {
        return $template->fetch('addons/product_bundles/blocks/product_tabs/components/product_bundles.tpl');
    } catch (Exception $e) {
        return '';
    }
}
