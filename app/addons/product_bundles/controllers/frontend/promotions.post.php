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

if ($mode === 'list') {
    $bundle_service = ServiceProvider::getService();
    $params = [
        'display_in_promotions' => true,
        'full_info'             => true,
        'status'                => ObjectStatuses::ACTIVE,
    ];
    list($bundles,) = $bundle_service->getBundles($params);
    Tygh::$app['view']->assign('bundles', $bundles);
}
