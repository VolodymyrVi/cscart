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

use Tygh\Enum\Addons\VendorDataPremoderation\ProductStatuses;
use Tygh\Enum\UserTypes;

defined('BOOTSTRAP') or die('Access denied');

$storefront_id = empty($_REQUEST['storefront_id'])
    ? 0
    : (int) $_REQUEST['storefront_id'];

if ($mode === 'index' && defined('AJAX_REQUEST')) {

    /** @var Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];

    /** @var \Tygh\Storefront\Repository $storefront_repository */
    $storefront_repository = Tygh::$app['storefront.repository'];
    /** @var \Tygh\Storefront\Storefront $storefront */
    $storefront = $storefront_repository->findById($storefront_id);

    $auth = Tygh::$app['session']['auth'];
    if ($auth['user_type'] === UserTypes::ADMIN) {
        $company_ids = $storefront ? $storefront->getCompanyIds() : [];
    } else {
        $company_ids = [$auth['company_id']];
    }

    $require_approval_params = $disapproved_params = [
        'only_short_fields' => true,
        'extend'            => ['companies'],
        'get_conditions'    => true,
        'company_ids'       => $company_ids,
    ];

    $require_approval_params['status'] = ProductStatuses::REQUIRES_APPROVAL;
    $disapproved_params['status'] = ProductStatuses::DISAPPROVED;

    list(, $joins, $conditions) = fn_get_products($require_approval_params);
    $require_approval_count = db_get_field(
        'SELECT COUNT(DISTINCT products.product_id) FROM ?:products AS products ?p WHERE 1 ?p',
        $joins,
        $conditions
    );

    list(, $joins, $conditions) = fn_get_products($disapproved_params);
    $disapproved_count = db_get_field(
        'SELECT COUNT(DISTINCT products.product_id) FROM ?:products AS products ?p WHERE 1 ?p',
        $joins,
        $conditions
    );

    $view->assign([
        'vendor_data_premoderation' => [
            'require_approval_count' => $require_approval_count,
            'disapproved_count'      => $disapproved_count,
        ],
    ]);
}

return [CONTROLLER_STATUS_OK];
