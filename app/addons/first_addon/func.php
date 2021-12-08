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
use Tygh\Enum\YesNo;


if (!defined('BOOTSTRAP')) { die('Access denied'); }

function fn_first_addon_get_users($params, &$fields, &$sortings, $condition, &$join, $auth){
    
    if(empty($params['p_ids']) || empty($params['product_view_id'])){
            $join .= db_quote(' LEFT JOIN ?:orders ON ?:orders.user_id = ?:users.user_id AND ?:orders.is_parent_order != ?s', YesNo::YES);
    }
    
    $fields[] = 'COUNT(?:orders.order_id) as orders_count';
    
    $sortings['orders_count'] = 'orders_count';
}
 