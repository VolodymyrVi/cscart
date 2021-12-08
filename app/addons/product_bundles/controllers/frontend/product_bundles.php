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
use Tygh\Enum\SiteArea;

defined('BOOTSTRAP') or die('Access denied');

$bundle_service = ServiceProvider::getService();

/** @var string $mode */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'change_variation') {
        $bundle_id = $_REQUEST['bundle_id'];
        $variation_id = $_REQUEST['product_id'];
        $potential_new_bundle_ids = db_get_fields('SELECT bundle_id FROM ?:product_bundles WHERE parent_bundle_id = ?i', $bundle_id);
        if (empty($potential_new_bundle_ids)) {
            $parent_bundle_id = db_get_field('SELECT parent_bundle_id FROM ?:product_bundles WHERE bundle_id = ?i', $bundle_id);
            if (empty($parent_bundle_id)) {
                return [CONTROLLER_STATUS_NO_CONTENT];
            }
            $potential_new_bundle_ids = db_get_fields(
                'SELECT bundle_id FROM ?:product_bundles WHERE parent_bundle_id = ?i',
                $parent_bundle_id
            );
            $potential_new_bundle_ids[] = $parent_bundle_id;
        }
        $needed_bundle_id = db_get_field(
            'SELECT bundle_id FROM ?:product_bundle_product_links WHERE product_id = ?i AND bundle_id IN (?n)',
            $variation_id,
            $potential_new_bundle_ids
        );
        list($bundle,) = $bundle_service->getBundles(['full_info' => true, 'bundle_id' => $needed_bundle_id, 'get_child_bundles' => true]);

        list($product,) = fn_get_products(['pid' => $variation_id]);
        fn_gather_additional_products_data($product, [
            'get_icon'                    => true,
            'get_detailed'                => true,
            'get_discounts'               => true,
            'get_options'                 => !empty($_REQUEST['display']) || SiteArea::isStorefront(AREA),
            'get_active_options'          => !empty($params['is_order_management']),
            'get_only_selectable_options' => SiteArea::isAdmin(AREA) && !empty($params['only_selectable_options']),
            'get_variation_features_variants' => true,
        ]);

        Tygh::$app['view']->assign('bundles', $bundle);
        Tygh::$app['view']->assign('product_id', $variation_id);
        Tygh::$app['view']->display('addons/product_bundles/views/product_bundles/get_feature_variants.tpl');

        return [CONTROLLER_STATUS_NO_CONTENT];
    }
}

if ($mode === 'get_feature_variants') {
    if (empty($_REQUEST['product_id'])) {
        return [CONTROLLER_STATUS_OK];
    }
    $variation_id = $_REQUEST['product_id'];
    list($product,) = fn_get_products(['pid' => $variation_id]);

    fn_gather_additional_products_data($product, [
        'get_icon'                    => true,
        'get_detailed'                => true,
        'get_discounts'               => true,
        'get_options'                 => !empty($_REQUEST['display']) || SiteArea::isStorefront(AREA),
        'get_active_options'          => !empty($params['is_order_management']),
        'get_only_selectable_options' => SiteArea::isAdmin(AREA) && !empty($params['only_selectable_options']),
        'get_variation_features_variants' => true,
    ]);

    Tygh::$app['view']->assign('product', reset($product));
    if (!isset($_REQUEST['key'], $_REQUEST['bundle_id'])) {
        return [CONTROLLER_STATUS_OK];
    }
    Tygh::$app['view']->assign('key', $_REQUEST['key']);
    Tygh::$app['view']->assign('bundle_id', $_REQUEST['bundle_id']);

    return [CONTROLLER_STATUS_OK];
}
