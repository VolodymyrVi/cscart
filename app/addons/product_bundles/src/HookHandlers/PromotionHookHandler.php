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

namespace Tygh\Addons\ProductBundles\HookHandlers;

use Tygh\Addons\ProductBundles\ServiceProvider;

class PromotionHookHandler
{
    /**
     * The `get_promotions` hook handler.
     *
     * Action performed:
     *     - Filtering promotions created for bundles from all promotions list.
     *
     * @param array<string> $params    Search params.
     * @param array<string> $fields    Selected fields.
     * @param array<string> $sortings  Sorting condition.
     * @param string        $condition Condition for selecting.
     * @param string        $join      Join tables.
     * @param string        $group     Grouping condition.
     * @param string        $lang_code Language code.
     *
     * @return void
     */
    public function onGetPromotions(array $params, array $fields, array $sortings, &$condition, $join, $group, $lang_code)
    {
        if (!empty($params['zone']) || !empty($params['promotion_id'])) {
            return;
        }
        $bundle_promotions = db_get_fields('SELECT linked_promotion_id FROM ?:product_bundles');
        if (empty($bundle_promotions)) {
            return;
        }
        $condition .= db_quote(' AND ?:promotions.promotion_id NOT IN (?n)', $bundle_promotions);
    }

    /**
     * The `promotions_apply_pre` hook handler.
     *
     * Action performed:
     *     - Multiply promotion bonuses for multiple bundles at the cart.
     *
     * @param array<string>      $promotions    List of promotions
     * @param string             $zone          Promotion zone (catalog, cart)
     * @param array<string>      $data          Data array (product - for catalog rules, cart - for cart rules)
     * @param array<string>|null $auth          Auth array (for car rules)
     * @param array<string>|null $cart_products Cart products array (for car rules)
     *
     * @return void
     */
    public function onPrePromotionApply(array &$promotions, $zone, array $data, $auth, $cart_products)
    {
        if (empty($cart_products)) {
            return;
        }

        $bundle_promotions = db_get_hash_array('SELECT bundle_id, linked_promotion_id FROM ?:product_bundles', 'bundle_id');
        $cart_promotions = array_filter($promotions['cart'], static function ($promotion) use ($bundle_promotions) {
            return in_array($promotion['promotion_id'], array_column($bundle_promotions, 'linked_promotion_id'));
        });
        if (empty($cart_promotions)) {
            return;
        }
        uasort($cart_promotions, static function ($first, $second) {
            $first_bonus = reset($first['bonuses']);
            $second_bonus = reset($second['bonuses']);
            return $first_bonus['discount_value'] < $second_bonus['discount_value'];
        });


        $bundle_service = ServiceProvider::getService();
        $bundle_ids = $bundle_service->checkForCompleteBundles(array_column($cart_products, 'amount', 'product_id'), array_keys($cart_promotions));
        foreach ($bundle_ids as $bundle_id => $bundle_amount) {
            $promotion_id = $bundle_promotions[$bundle_id]['linked_promotion_id'];
            if ($bundle_amount) {
                list($bundle_data,) = $bundle_service->getBundles(['bundle_id' => $bundle_id, 'get_child_bundles' => true]);
                $bundle_data = reset($bundle_data);
                $promotions['cart'][$promotion_id]['bonuses'][0]['discount_value'] = $bundle_amount *
                    $bundle_service->calculatePromotionBonus($bundle_data, array_column($cart_products, 'price', 'product_id'));
            }
        }
    }

    /**
     * The `pre_validate_promotion_attribute` hook handler.
     *
     * Action performed:
     *     - Modifies data before attribute validation.
     *
     * @param array|int|string $value     Value to compare with (can be one-dimensional array, in this case, every item will be checked)
     * @param array|int|string $condition Value to compare to
     *
     * @return void
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
     */
    public function onValidatePromotionAttributePre($value, &$condition)
    {
        if (!is_array($value) || !is_array($condition)) {
            return;
        }

        $new_value = reset($value);
        $new_condition = reset($condition);

        // phpcs:ignore
        if (
            !isset($new_value['product_options'])
            && isset($new_condition['product_options'])
        ) {
            unset($new_condition['product_options']);
            $condition = [];
            $condition[] = $new_condition;
        }
    }
}
