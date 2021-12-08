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

namespace Tygh\Addons\ProductBundles\Services;

use Tygh\Addons\ProductBundles\Enum\DiscountType;
use Tygh\Addons\ProductBundles\ServiceProvider;
use Tygh\Enum\ImagePairTypes;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\ProductTracking;
use Tygh\Enum\SiteArea;
use Tygh\Enum\YesNo;
use Tygh\Languages\Languages;
use Tygh\Registry;

class ProductBundleService
{
    /**
     * Gets all bundle ids with specified product.
     *
     * @param int $product_id Product identifier.
     *
     * @return array<string>
     */
    public function getBundleIdsByProductId($product_id)
    {
        return db_get_fields('SELECT DISTINCT(bundle_id) FROM ?:product_bundle_product_links WHERE product_id = ?i', $product_id);
    }

    /**
     * Get bundles according to search parameters.
     *
     * @param array<string|int|bool|array<string>> $params Parameters for bundles search.
     *
     * @return array<string, array<string>>
     */
    public function getBundles(array $params)
    {
        $default_params = [
            'bundle_id'         => null,
            'with_image'        => true,
            'get_child_bundles' => false,
            'lang_code'         => CART_LANGUAGE,
            'area'              => AREA,
            'page'              => 1,
            'items_per_page'    => null,
            'get_total'         => false,
        ];

        $params = array_merge($default_params, $params);
        if ($params['items_per_page'] === null) {
            $params['items_per_page'] = Registry::get('settings.Appearance.admin_elements_per_page');
        }

        /**
         * Allows to modify parameters before selecting bundles.
         *
         * @param array<string|int|array<string>> $params Parameters for bundles search.
         */
        fn_set_hook('product_bundle_service_get_bundles_pre', $params);

        $fields = '*';

        $limit = [];
        if ((int) $params['items_per_page'] !== 0) {
            $limit['items_per_page'] = (int) $params['items_per_page'];
        }
        if ($params['page']) {
            $limit['page'] = $params['page'];
        }

        $joins = [
            'descriptions' => db_quote(
                'LEFT JOIN ?:product_bundle_descriptions as descriptions ON bundles.bundle_id = descriptions.bundle_id AND descriptions.lang_code = ?s',
                $params['lang_code']
            )
        ];
        $conditions = [];
        if (!empty($params['bundle_id'])) {
            $conditions['bundle_id'] = db_quote(' AND bundles.bundle_id IN (?n)', $params['bundle_id']);
        }
        if (!empty($params['company_id'])) {
            $conditions['company_id'] = db_quote(' AND bundles.company_id = ?s', $params['company_id']);
        }
        if (!empty($params['product_id'])) {
            $joins['links'] = db_quote(' LEFT JOIN ?:product_bundle_product_links as links ON bundles.bundle_id = links.bundle_id');
            $conditions['product_id'] = db_quote(' AND links.product_id = ?i', $params['product_id']);
        }

        if (!empty($params['show_on_products_page'])) {
            $joins['links'] = db_quote(' LEFT JOIN ?:product_bundle_product_links as links ON bundles.bundle_id = links.bundle_id');
            $conditions['show_on_product_page'] = db_quote(' AND links.show_on_product_page = ?s', YesNo::YES);
        }

        if (!empty($params['q'])) {
            $conditions['bundle_name'] = db_quote(' AND descriptions.name LIKE ?l', '%' . $params['q'] . '%');
        }

        if (!empty($params['display_in_promotions'])) {
            $conditions['display_in_promotions'] = db_quote(' AND bundles.display_in_promotions = ?s', YesNo::YES);
        }

        if (!$params['get_child_bundles']) {
            $conditions['get_child_bundles'] = db_quote(' AND bundles.parent_bundle_id = ?i', 0);
        }

        if (!empty($params['status'])) {
            $conditions['status'] = db_quote(' AND bundles.status = ?s', $params['status']);
        }

        /**
         * Allows to modify queries for selecting bundles right before sql request.
         *
         * @param array<string|int>     $params     Parameters for bundles search.
         * @param string                $fields     Requesting product bundles fields.
         * @param array<string, string> $joins      Joining tables for request.
         * @param array<string, string> $conditions Conditions of request.
         * @param array<string, string> $limit      Limit conditions of request.
         */
        fn_set_hook('product_bundle_service_get_bundles', $params, $fields, $joins, $conditions, $limit);

        $joins_query = implode('', $joins);
        $condition_query = implode('', $conditions);
        if (isset($limit['page'], $limit['items_per_page'])) {
            $limit_query = db_paginate($limit['page'], $limit['items_per_page']);
        } else {
            $limit_query = '';
        }

        $bundles = db_get_array(
            'SELECT ?p FROM ?:product_bundles as bundles ?p WHERE 1 ?p ?p',
            $fields,
            $joins_query,
            $condition_query,
            $limit_query
        );

        if ($params['get_total']) {
            $params['total_items'] = db_get_field(
                'SELECT COUNT(1) FROM ?:product_bundles as bundles ?p WHERE 1 ?p',
                $joins_query,
                $condition_query
            );
        }

        if (empty($bundles)) {
            return [$bundles, $params];
        }
        if (!empty($params['full_info'])) {
            $bundles = $this->getAdditionalInfoForBundles($bundles, $params);
        }

        if (!$params['with_image']) {
            return [$bundles, $params];
        }
        if (!empty($bundles) && is_array($bundles)) {
            $bundle_image_ids = $this->getBundleImageIds(array_filter(array_column($bundles, 'bundle_id')));
            $images = fn_get_image_pairs(
                array_column($bundle_image_ids, 'bundle_image_id'),
                'product_bundle',
                ImagePairTypes::MAIN
            );
            foreach ($bundles as &$bundle) {
                if (empty($bundle_image_ids[$bundle['bundle_id']])) {
                    continue;
                }
                $bundle['main_pair'] = reset($images[$bundle_image_ids[$bundle['bundle_id']]['bundle_image_id']]);
            }
            unset($bundle);
        }
        /**
         * Allows to modify selected bundles set and perform needed operations after selecting bundles.
         *
         * @param array<string> $params  Bundle parameters for selected set of bundles.
         * @param array<string> $bundles Selected bundles.
         */
        fn_set_hook('product_bundle_service_get_bundles_post', $params, $bundles);

        return [$bundles, $params];
    }

    /**
     * Updating bundle status and all other entities associated with this bundle.
     *
     * @param int    $bundle_id Bundle identifier.
     * @param string $status    New bundle status.
     *
     * @return void
     */
    public function updateBundleStatus($bundle_id, $status)
    {
        $bundle_service = ServiceProvider::getService();
        list($bundle,) = $bundle_service->getBundles(['bundle_id' => $bundle_id]);
        $bundle = reset($bundle);
        db_query('UPDATE ?:promotions SET status = ?s WHERE promotion_id = ?i', $status, $bundle['linked_promotion_id']);
        db_query('UPDATE ?:product_bundles SET status = ?s WHERE bundle_id = ?i', $status, $bundle['bundle_id']);
        $bundle_service->changeStatusOfChildBundles((int) $bundle['bundle_id'], $status);
    }

    /**
     * Gets image identifiers for specified bundles.
     *
     * @param array<string> $bundle_ids Bundle identifiers.
     *
     * @return array<int, array<string>>
     */
    private function getBundleImageIds(array $bundle_ids)
    {
        return db_get_hash_array('SELECT bundle_image_id, bundle_id FROM ?:product_bundle_images WHERE bundle_id IN (?n)', 'bundle_id', $bundle_ids);
    }

    /**
     * Gets additional information for products into bundles.
     *
     * @param array<string> $bundles Bundles.
     * @param array<string> $params  Parameters for request.
     *
     * @return array<string>
     */
    private function getAdditionalInfoForBundles(array $bundles, array $params = [])
    {
        $product_ids = [];
        foreach ($bundles as $bundle) {
            $bundle_products = unserialize($bundle['products']);
            if (empty($bundle_products)) {
                continue;
            }
            foreach ($bundle_products as $product) {
                $product_ids[$product['product_id']] = $product['product_id'];
            }
        }
        if (!empty($product_ids)) {
            list($products) = fn_get_products(['pid' => $product_ids]);
        }
        foreach ($bundles as $key => &$bundle) {
            $bundle['products'] = unserialize($bundle['products']);

            $is_valid = true;

            if (empty($bundle['products'])) {
                if (!isset($params['allow_empty_products'])) {
                    unset($bundles[$key]);
                }
                continue;
            }

            $bundle['products_info'] = $bundle['products'];

            $total_price = 0;
            $discounted_price = 0;
            foreach ($bundle['products'] as $hash => &$bundle_product) {
                if (empty($product['product_id']) || !isset($products[$bundle_product['product_id']])) {
                    if (!isset($params['allow_empty_products'])) {
                        unset($bundles[$key]);
                    }
                    break;
                }

                $product = $products[$bundle_product['product_id']];

                $selected_options = isset($params['selected_options']) ? $params['selected_options'] : [];
                if (!empty($bundle_product['product_options'])) {
                    $product['selected_options'] = $bundle_product['product_options'];
                } else {
                    $product['selected_options'] = isset($selected_options[$product['product_id']]['selected_options'])
                        ? $selected_options[$product['product_id']]['selected_options']
                        : '';

                    $product['changed_option'] = isset($selected_options[$product['product_id']]['changed_option'])
                        ? $selected_options[$product['product_id']]['changed_option']
                        : '';
                }

                fn_gather_additional_products_data($product, [
                    'get_icon'       => true,
                    'get_detailed'   => true,
                    'get_additional' => false,
                    'get_options'    => true,
                    'get_discounts'  => true
                ]);

                if (!empty($bundle_product['product_options'])) {
                    $product['amount'] = isset($product['inventory_amount']) ? $product['inventory_amount'] : $product['amount'];
                } else {
                    $product['amount'] = fn_get_product_amount($bundle_product['product_id']);
                }

                if (
                    SiteArea::isStorefront($params['area'])
                    && ($product['status'] === ObjectStatuses::HIDDEN
                        || (
                            isset($product['tracking'])
                            && $product['tracking'] !== ProductTracking::DO_NOT_TRACK
                            && !YesNo::toBool(Registry::get('settings.General.show_out_of_stock_products'))
                            && empty($product['amount'])
                        )
                    )
                ) {
                    $is_valid = false;
                    break;
                }

                $product['min_qty'] = ($product['min_qty'] > 0) ? $product['min_qty'] : 1;

                $bundle_product['product_name'] = $product['product'];
                $bundle_product['min_qty'] = $product['min_qty'];
                $bundle_product['price'] = empty($bundle_product['price']) ? $product['price'] : $bundle_product['price'];
                $bundle_product['list_price'] = $product['list_price'];

                if (isset($product['main_pair'])) {
                    $bundle_product['main_pair'] = $product['main_pair'];
                }

                $bundle_product['discount'] = $this->calculateDiscount(
                    empty($bundle_product['modifier_type']) ? 'to_fixed' : $bundle_product['modifier_type'],
                    $product['price'],
                    empty($bundle_product['modifier']) ? 0 : $bundle_product['modifier']
                );
                $bundle_product['discounted_price'] = $product['price'] - $bundle_product['discount'];

                if ($bundle_product['discounted_price'] < 0) {
                    $bundle_product['discounted_price'] = 0;
                }

                $bundle_product['options_type'] = $product['options_type'];
                $bundle_product['exceptions_type'] = $product['exceptions_type'];
                $bundle_product['options_update'] = isset($product['options_update']) ? $product['options_update'] : false;

                $total_price += $product['price'] * $bundle_product['amount'];
                $discounted_price += $bundle_product['discounted_price'] * $bundle_product['amount'];

                if (!empty($bundle_product['product_options'])) {
                    $bundle_product['product_options_short'] = $bundle_product['product_options'];

                    $options = fn_get_selected_product_options_info($bundle_product['product_options'], DESCR_SL);
                    $bundle_product['product_options'] = $options;
                } elseif (!empty($product['product_options'])) {
                    $bundle_product['aoc'] = true; // Allow any option combinations
                    $bundle_product['options'] = $product['product_options'];
                }

                $bundle_product['product_data'] = $product;

                $bundles[$key]['products_info'][$hash]['price'] = $bundle_product['price'];
                $bundles[$key]['products_info'][$hash]['discount'] = $bundle_product['discount'];
                $bundles[$key]['products_info'][$hash]['discounted_price'] = $bundle_product['discounted_price'];
            }
            unset($bundle_product);

            if (!$is_valid) {
                unset($bundles[$key]);
                continue;
            }

            $bundle['total_price'] = $total_price;
            $bundle['discounted_price'] = $discounted_price;
        }
        unset($bundle);

        return $bundles;
    }

    /**
     * Checks if specified products with specified amounts were linked into some bundle.
     *
     * @param array<string> $product_amounts Product amounts.
     * @param array<string> $promotions      Promotions for bundles.
     *
     * @return array<string>
     */
    public function checkForCompleteBundles(array $product_amounts, array $promotions)
    {
        $result = [];
        $bundles_with_products = db_get_hash_array(
            'SELECT DISTINCT(?:product_bundle_product_links.bundle_id), linked_promotion_id FROM ?:product_bundle_product_links'
            . ' LEFT JOIN ?:product_bundles ON ?:product_bundles.bundle_id = ?:product_bundle_product_links.bundle_id'
            . ' WHERE product_id IN (?n) AND linked_promotion_id IN (?n)',
            'linked_promotion_id',
            array_keys($product_amounts),
            $promotions
        );
        $promotions = array_filter($promotions, static function ($promotion) use ($bundles_with_products) {
            return in_array($promotion, array_keys($bundles_with_products));
        });
        $bundles_with_products = fn_array_merge(array_flip($promotions), $bundles_with_products, true);
        foreach ($bundles_with_products as $bundle_id) {
            list($result[$bundle_id['bundle_id']], $product_amounts) = $this->howManyBundlesInTheCart($bundle_id['bundle_id'], $product_amounts);
        }
        return $result;
    }

    /**
     * Checks how many times specific bundle added to cart and which products in what amount was not included in this bundle.
     *
     * @param int           $bundle_id       Bundle identifier.
     * @param array<string> $product_amounts Cart products amounts.
     *
     * @return array<array<string>>
     */
    private function howManyBundlesInTheCart($bundle_id, array $product_amounts)
    {
        $amount_of_bundles = 0;
        $bundle_products = db_get_array(
            'SELECT product_id, amount FROM ?:product_bundle_product_links WHERE bundle_id = ?i',
            $bundle_id
        );
        foreach ($bundle_products as $bundle_product) {
            if (!isset($product_amounts[$bundle_product['product_id']])) {
                $amount_of_bundles = 0;
                break;
            }
            $is_bundle_complete = $product_amounts[$bundle_product['product_id']] >= $bundle_product['amount'];
            if (!$is_bundle_complete) {
                $amount_of_bundles = 0;
                break;
            }
            $new_amount = (int) floor($product_amounts[$bundle_product['product_id']] / $bundle_product['amount']);
            if ($amount_of_bundles === 0) {
                $amount_of_bundles = $new_amount;
            } elseif ($amount_of_bundles > $new_amount) {
                $amount_of_bundles = $new_amount;
            }
        }
        if ($amount_of_bundles) {
            foreach ($bundle_products as $bundle_product) {
                $product_amounts[$bundle_product['product_id']] -= $bundle_product['amount'] * $amount_of_bundles;
            }
        }
        return [$amount_of_bundles, $product_amounts];
    }

    /**
     * Updates bundle information or creating new bundle.
     *
     * @param array<string, array<string, string>> $bundle_data Bundle info.
     * @param int                                  $bundle_id   Bundle id.
     *
     * @return int
     */
    public function updateBundle(array $bundle_data, $bundle_id = 0)
    {
        $product_amounts = [];
        if (isset($bundle_data['products'])) {
            $bundle_data['products'] = array_filter($bundle_data['products'], static function ($product) {
                return !empty($product['amount']);
            });
            $product_amounts = array_column($bundle_data['products'], null, 'product_id');

            $bundle_data['products'] = serialize($bundle_data['products']);
        }

        if (!empty($bundle_data['date_from']) && !empty($bundle_data['date_to'])) {
            $bundle_data['date_from'] = fn_parse_date($bundle_data['date_from']);
            $bundle_data['date_to'] = fn_parse_date($bundle_data['date_to']);
        }

        $bundle_description = [];
        if (!empty($bundle_data['name'])) {
            $bundle_description['name'] = $bundle_data['name'];
        }
        if (!empty($bundle_data['storefront_name'])) {
            $bundle_description['storefront_name'] = $bundle_data['storefront_name'];
        } else {
            $bundle_description['storefront_name'] = $bundle_data['name'];
        }
        if (!empty($bundle_data['description'])) {
            $bundle_description['description'] = $bundle_data['description'];
        }
        /** @var string $bundle_data['lang_code'] */
        if (!empty($bundle_data['lang_code'])) {
            $lang_code = $bundle_data['lang_code'];
        } else {
            $lang_code = DESCR_SL;
        }

        /**
         * Allows to update some information right before updating product bundle.
         *
         * @param array<string> $bundle_data Bundle information.
         * @param int           $bundle_id   Bundle identifier.
         */
        fn_set_hook('product_bundle_service_update_bundle', $bundle_data, $bundle_id);

        if (empty($bundle_id)) {
            $bundle_data['linked_promotion_id'] = $this->updateHiddenPromotion($bundle_data, $bundle_description);
            $bundle_id = db_query('INSERT INTO ?:product_bundles ?e', $bundle_data);
            $bundle_description['bundle_id'] = $bundle_id;

            foreach (array_keys(Languages::getAll()) as $bundle_description['lang_code']) {
                db_query('INSERT INTO ?:product_bundle_descriptions ?e', $bundle_description);
            }
        } else {
            if (!empty($bundle_data) && !empty($bundle_description)) {
                if (empty($bundle_data['linked_promotion_id'])) {
                    $bundle_data['linked_promotion_id'] = db_get_field('SELECT linked_promotion_id FROM ?:product_bundles WHERE bundle_id = ?i', $bundle_id);
                }
                $bundle_data['linked_promotion_id'] = $this->updateHiddenPromotion($bundle_data, $bundle_description, $bundle_data['linked_promotion_id']);
            }
            if (!empty($bundle_data)) {
                db_query('UPDATE ?:product_bundles SET ?u WHERE bundle_id = ?i', $bundle_data, $bundle_id);
            }
            if (!empty($bundle_description)) {
                db_query(
                    'UPDATE ?:product_bundle_descriptions SET ?u WHERE bundle_id = ?i AND lang_code = ?s',
                    $bundle_description,
                    $bundle_id,
                    $lang_code
                );
            }
        }
        $this->updateImage($bundle_id, $lang_code);

        /**
         * Allows to update some information after updating product bundle.
         *
         * @param array<string> $bundle_data        Bundle information.
         * @param int           $bundle_id          Bundle identifier.
         * @param array<string> $bundle_description Bundle description.
         */
        fn_set_hook('product_bundle_service_update_bundle_post', $bundle_data, $bundle_id, $bundle_description);

        $this->updateLinks($bundle_id, $product_amounts);

        return $bundle_id;
    }

    /**
     * Validates bundle data, accordingly to context or specified params.
     *
     * @param array<string> $bundle_data Validating data.
     * @param array<string> $params      Specified params.
     *
     * @return array<string>
     */
    public function validateBundleData(array $bundle_data, array $params)
    {
        $required_company_id = isset($params['company_id']) ? $params['company_id'] : fn_get_runtime_company_id();
        if (!$required_company_id) {
            $required_company_id = $bundle_data['company_id'];
        } elseif ((int) $bundle_data['company_id'] !== $required_company_id) {
            $bundle_data['company_id'] = $required_company_id;
        }
        if (!fn_check_permissions('promotions', 'manage', 'admin', 'POST')) {
            $bundle_data['display_in_promotions'] = YesNo::NO;
        }

        if (isset($bundle_data['products'])) {
            $bundle_data['products'] = array_filter($bundle_data['products'], static function ($product) {
                return !empty($product['amount']);
            });
            $product_ids = array_column($bundle_data['products'], 'product_id');

            list($products,) = fn_get_products(['pid' => $product_ids]);
            $products = array_filter($products, static function ($product) use ($required_company_id) {
                return (int) $product['company_id'] === (int) $required_company_id;
            });

            $product_ids = array_keys($products);

            $bundle_data['products'] = array_filter($bundle_data['products'], static function ($product) use ($product_ids) {
                return in_array($product['product_id'], $product_ids);
            });
        }
        return $bundle_data;
    }

    /**
     * Create promotion for just created product bundle.
     *
     * @param array<string> $bundle_data         Bundle information.
     * @param array<string> $bundle_descriptions Bundle descriptions.
     * @param int           $promotion_id        Promotion identifier.
     *
     * @return int
     */
    private function updateHiddenPromotion(array $bundle_data, array $bundle_descriptions, $promotion_id = 0)
    {
        if (is_string($bundle_data['products'])) {
            $bundle_data['products'] = unserialize($bundle_data['products']);
        }
        if (empty($bundle_data['products'])) {
            if (!$promotion_id) {
                return 0;
            }
            $bundle_data['status'] = ObjectStatuses::DISABLED;
        }
        $data = [
            'zone' => 'cart',
            'name' => $bundle_descriptions['name'],
            'from_date' => $bundle_data['date_from'],
            'to_date' => $bundle_data['date_to'],
            'priority' => 0,
            'stop_other_rules' => isset($bundle_data['stop_other_rules']) ? $bundle_data['stop_other_rules'] : YesNo::NO,
            'display_in_promotions' => $bundle_data['display_in_promotions'],
            'status' => $bundle_data['status'],
            'conditions' =>
                [
                    'set' => 'all',
                    'set_value' => '1',
                ],
            'bonuses' =>
                [
                    [
                        'bonus' => 'order_discount',
                        'discount_bonus' => 'by_fixed',
                        'discount_value' => isset($bundle_data['total_price'], $bundle_data['price_for_all'])
                            ? (float) $bundle_data['total_price'] - (float) $bundle_data['price_for_all']
                            : $this->calculatePromotionBonus($bundle_data),
                    ],
                ]
        ];
        $condition_index = 1;
        foreach ($bundle_data['products'] as $product_key => $product) {
            $data['conditions']['conditions'][$condition_index] =
                [
                    'operator' => 'in',
                    'condition' => 'products',
                    'value' => [
                        $product_key => [
                            'product_id' => $product['product_id'],
                            'amount'     => $product['amount'],
                        ],
                    ],
                ];
            if (!empty($product['product_options'])) {
                $data['conditions']['conditions'][$condition_index]['value'][$product_key]['product_options']
                    = $product['product_options'];
            }
            $condition_index++;
        }

        /**
         * Executes before updating promotion allows to change promotion data.
         *
         * @param array<string> $bundle_data         Bundle information.
         * @param array<string> $bundle_descriptions Bundle descriptions.
         * @param int           $promotion_id        Promotion identifier.
         * @param array<string> $data                Promotion data.
         */
        fn_set_hook('product_bundles_service_update_hidden_promotion_before_update', $bundle_data, $bundle_descriptions, $promotion_id, $data);

        $lang_code = isset($bundle_data['lang_code']) ? $bundle_data['lang_code'] : CART_LANGUAGE;

        return fn_update_promotion($data, $promotion_id, $lang_code);
    }

    /**
     * Calculates promotion bonus according to bundle data.
     *
     * @param array<string>     $bundle_data    Bundle information.
     * @param array<int, float> $product_prices Current prices of product.
     *
     * @return float|int
     */
    public function calculatePromotionBonus(array $bundle_data, array $product_prices = [])
    {
        $result = 0;
        if (empty($bundle_data['products'])) {
            return $result;
        }

        if (is_string($bundle_data['products'])) {
            $bundle_data['products'] = unserialize($bundle_data['products']);
        }
        foreach ($bundle_data['products'] as $product) {
            if (isset($product_prices[$product['product_id']])) {
                $price = $product_prices[$product['product_id']];
            } else {
                $auth = fn_fill_auth();
                $price = fn_get_product_price($product['product_id'], $product['amount'], $auth);
            }
            $discount = $this->calculateDiscount($product['modifier_type'], $price, $product['modifier']);
            $result += $discount * $product['amount'];
        }
        return $result;
    }

    /**
     * Deletes specified product bundle.
     *
     * @param int $bundle_id Bundle identifier.
     *
     * @return void
     */
    public function deleteBundle($bundle_id)
    {
        /**
         * Allows to change information before deleting product bundle.
         *
         * @param int $bundle_id Bundle identifier.
         */
        fn_set_hook('product_bundle_service_delete_bundle_pre', $bundle_id);

        $linked_promotion_id = db_get_field('SELECT linked_promotion_id FROM ?:product_bundles WHERE bundle_id = ?i', $bundle_id);
        if ($linked_promotion_id) {
            fn_delete_promotions([$linked_promotion_id]);
        }

        $this->deleteImage($bundle_id);

        db_query('DELETE FROM ?:product_bundles WHERE bundle_id = ?i', $bundle_id);
        db_query('DELETE FROM ?:product_bundle_descriptions WHERE bundle_id = ?i', $bundle_id);
        db_query('DELETE FROM ?:product_bundle_product_links WHERE bundle_id = ?i', $bundle_id);
    }

    /**
     * Creates link between bundle identifier and product identifier.
     *
     * @param int                          $bundle_id     Bundle identifier.
     * @param array<string, array<string>> $products_data Products info.
     *
     * @return void
     */
    private function updateLinks($bundle_id, array $products_data)
    {
        if (empty($products_data)) {
            return;
        }
        $result = true;
        $data = [
            'bundle_id' => $bundle_id,
        ];
        db_query('DELETE FROM ?:product_bundle_product_links WHERE bundle_id = ?s', $bundle_id);
        foreach ($products_data as $product_id => $product_data) {
            $data['product_id'] = $product_id;
            $data['amount'] = $product_data['amount'];
            if (isset($product_data['show_on_product_page'])) {
                $data['show_on_product_page'] = $product_data['show_on_product_page'];
            }
            $result = $result && db_query('INSERT INTO ?:product_bundle_product_links ?e', $data);
        }
    }

    /**
     * Updates product bundle image.
     *
     * @param int    $bundle_id Bundle identifier.
     * @param string $lang_code Language code.
     *
     * @return void
     */
    protected function updateImage($bundle_id, $lang_code = DESCR_SL)
    {
        if (empty($bundle_id)) {
            return;
        }

        $exists_image_ids = db_get_hash_array(
            'SELECT bundle_image_id, lang_code FROM ?:product_bundle_images WHERE bundle_id = ?i',
            'lang_code',
            $bundle_id
        );

        if (fn_filter_uploaded_data('bundle_main_image_icon')) {
            $image_data = [
                'bundle_id' => $bundle_id,
                'lang_code' => $lang_code
            ];

            if (isset($exists_image_ids[$lang_code])) {
                $this->deleteImage($bundle_id, $lang_code);
            }

            $bundle_image_id = db_query('INSERT INTO ?:product_bundle_images ?e', $image_data);
        }

        if (empty($bundle_image_id) && empty($exists_image_ids[$lang_code])) {
            return;
        }

        $bundle_image_id = empty($bundle_image_id) ? $exists_image_ids[$lang_code] : $bundle_image_id;
        fn_attach_image_pairs('bundle_main', 'product_bundle', $bundle_image_id, $lang_code);

        if (!empty($exists_image_ids)) {
            return;
        }
        $this->addLinksToLangs($bundle_id, $lang_code);
    }

    /**
     * Deletes image attached to product bundle.
     *
     * @param int         $bundle_id Bundle identifier.
     * @param string|null $lang_code Language code.
     *
     * @return void
     */
    protected function deleteImage($bundle_id, $lang_code = null)
    {
        $conditions = [
            'bundle_id' => $bundle_id,
            'lang_code' => $lang_code,
        ];

        $images = db_get_array('SELECT bundle_image_id FROM ?:product_bundle_images WHERE ?w', array_filter($conditions));

        if (empty($images)) {
            return;
        }

        $bundle_image_ids = array_column($images, 'bundle_image_id');
        $bundle_image_pairs = fn_get_image_pairs($bundle_image_ids, 'product_bundle', ImagePairTypes::MAIN);

        db_query('DELETE FROM ?:product_bundle_images WHERE bundle_image_id IN (?n)', $bundle_image_ids);

        foreach ($bundle_image_pairs as $image_pairs) {
            $pair = reset($image_pairs);
            if (!isset($pair['image_id'], $pair['pair_id'])) {
                continue;
            }
            fn_delete_image($pair['image_id'], $pair['pair_id'], 'product_bundle');
        }
    }

    /**
     * Adds information about bundle to several specified languages.
     *
     * @param int           $bundle_id     Bundle identifier.
     * @param string        $original_lang Original language.
     * @param array<string> $cloned_langs  Additional languages.
     *
     * @return void
     */
    protected function addLinksToLangs($bundle_id, $original_lang = CART_LANGUAGE, array $cloned_langs = [])
    {
        if (empty($bundle_id)) {
            return;
        }

        if (empty($cloned_langs)) {
            $languages = Languages::getAll();
            unset($languages[$original_lang]);

            $cloned_langs = array_keys($languages);
        }

        $bundle_image = db_get_row(
            'SELECT promo_images.bundle_image_id, images_links.pair_id'
            . ' FROM ?:product_bundle_images AS promo_images'
            . ' INNER JOIN ?:images_links AS images_links'
            . '     ON images_links.object_id = promo_images.bundle_image_id AND images_links.object_type = ?s'
            . ' WHERE promo_images.bundle_id = ?i AND promo_images.lang_code = ?s',
            'product_bundle',
            $bundle_id,
            $original_lang
        );

        if (empty($bundle_image)) {
            return;
        }

        foreach ($cloned_langs as $lang_code) {
            $promo_image_id = db_replace_into('product_bundle_images', [
                'bundle_id' => $bundle_id,
                'lang_code'    => $lang_code
            ]);
            if (empty($promo_image_id)) {
                continue;
            }
            fn_add_image_link($promo_image_id, $bundle_image['pair_id']);
        }
    }

    /**
     * Calculates discount for product.
     *
     * @param string $type  Discount type.
     * @param float  $price Product price.
     * @param float  $value Discount value.
     *
     * @return float|int
     */
    public function calculateDiscount($type, $price, $value)
    {
        if (empty($value) || empty($price)) {
            return 0;
        }

        switch ($type) {
            case DiscountType::TO_PERCENTAGE:
                $discount = $price * (100 - $value) / 100;
                break;
            case DiscountType::BY_PERCENTAGE:
                $discount = $price * $value / 100;
                break;
            case DiscountType::TO_FIXED_AMOUNT:
                $discount = $price - $value;
                break;
            case DiscountType::BY_FIXED_AMOUNT:
            default:
                $discount = $value;
                break;
        }

        if ($discount < 0) {
            $discount = 0;
        }

        return $discount;
    }

    /**
     * Allows to change status of child product bundles accordingly to new status of parent product bundle.
     *
     * @param int    $parent_bundle_id Parent bundle identifier.
     * @param string $status           New parent bundle status.
     *
     * @return void
     */
    public function changeStatusOfChildBundles($parent_bundle_id, $status)
    {
        $child_bundles_promotions_ids = db_get_fields('SELECT linked_promotion_id FROM ?:product_bundles WHERE parent_bundle_id = ?i', $parent_bundle_id);
        if (empty($child_bundles_promotions_ids)) {
            return;
        }

        db_query('UPDATE ?:product_bundles SET status = ?s WHERE parent_bundle_id = ?i', $status, $parent_bundle_id);
        db_query('UPDATE ?:promotions SET status = ?s WHERE promotion_id IN (?a)', $status, $child_bundles_promotions_ids);
    }
}
