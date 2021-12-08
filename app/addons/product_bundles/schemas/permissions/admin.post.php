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

defined('BOOTSTRAP') or die('Access denied');

/** @var array $schema */
$schema['product_bundles'] = [
    'permissions' => ['GET' => 'view_catalog', 'POST' => 'manage_catalog'],
    'modes' => [
        'delete'            => [
            'permissions' => 'manage_catalog'
        ],
        'm_delete'          => [
            'permissions' => 'manage_catalog'
        ],
        'm_update_statuses' => [
            'permissions' => 'manage_catalog'
        ],
    ],
];
$schema['tools']['modes']['update_status']['param_permissions']['table']['product_bundles'] = 'manage_catalog';

return $schema;
