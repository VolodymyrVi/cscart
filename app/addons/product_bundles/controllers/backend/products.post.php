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
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

if ($mode === 'update') {
    Registry::set('navigation.tabs.product_bundles', [
        'title' => __('product_bundles.product_bundles'),
        'js'    => true,
    ]);

    $params = [
        'product_id' => $_REQUEST['product_id'],
        'lang_code'  => DESCR_SL,
    ];

    $service = ServiceProvider::getService();
    list($bundles,) = $service->getBundles($params);

    Tygh::$app['view']->assign('bundles', $bundles);
}
