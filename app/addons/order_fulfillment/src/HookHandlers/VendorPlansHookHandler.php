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

namespace Tygh\Addons\OrderFulfillment\HookHandlers;


use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\YesNo;
use Tygh\Models\VendorPlan;
use Tygh\Shippings\Shippings;

class VendorPlansHookHandler
{
    /**
     * The 'vendor_plan_update' hook handler.
     *
     * Action performed:
     *     - Updates shippings at companies with currently updating vendor plan. Accordingly to new fulfillment status.
     *
     * @param VendorPlan $plan      Instance of Vendor plan.
     * @param bool       $result    Can save flag.
     * @param int[]      $companies Companies.
     *
     * @see VendorPlan::update()
     *
     * @return void
     */
    public function onVendorPlanUpdate(VendorPlan $plan, $result, array $companies)
    {
        if (!$result) {
            return;
        }
        $extended_plan = VendorPlan::model()->find($plan->plan_id);
        if (!($extended_plan instanceof VendorPlan && isset($extended_plan->is_fulfillment_by_marketplace))) {
            return;
        }

        $fulfillment_status = $extended_plan->is_fulfillment_by_marketplace;
        $all_shippings = fn_get_shippings(false);
        $marketplace_shippings = array_filter($all_shippings, static function ($shipping) {
            return !$shipping['company_id'] && $shipping['status'] === ObjectStatuses::ACTIVE;
        });
        foreach ($companies as $company_id) {
            /** @var false|array<string, string|array<string>> $company_data */
            $company_data = fn_get_company_data($company_id);
            if (!$company_data) {
                continue;
            }
            $company_current_shippings = isset($company_data['shippings_ids']) ? $company_data['shippings_ids'] : [];
            $company_shippings = array_filter($marketplace_shippings, static function ($shipping) use ($fulfillment_status) {
                $is_shipping_sent_by_marketplace = Shippings::isSentByMarketplace($shipping);
                return $is_shipping_sent_by_marketplace === YesNo::toBool($fulfillment_status);
            });
            $company_shippings = array_column($company_shippings, 'shipping_id');

            if ($company_shippings === $company_current_shippings) {
                continue;
            }
            $company_data['shippings'] = $company_shippings;
            fn_update_company($company_data, $company_id);
        }
    }

    /**
     * The `vendor_plans_calculate_commission_for_payout_post` hook hander.
     *
     * Action performed:
     *     - Adds marketplace shipping expenses to marketplace profit and updates commission payouts.
     *
     * @param array<string>        $order_info   Order information.
     * @param array<string>        $company_data Company information.
     * @param array<string, float> $payout_data  Payout information.
     *
     * @see \fn_calculate_commission_for_payout()
     *
     * @return void
     */
    public function onVendorPlansCalculateCommissionForPayoutPost(array $order_info, array $company_data, array &$payout_data)
    {
        if (!isset($payout_data['marketplace_shipping_amount'])) {
            return;
        }
        $payout_data['marketplace_profit'] += $payout_data['marketplace_shipping_amount'];
        $payout_data['commission_amount'] += $payout_data['marketplace_shipping_amount'];
    }

    /**
     * The `vendor_plans_calculate_commission_for_payout_before` hook handler.
     *
     * Action performed:
     *   - Removes from payout to vendor shipping taxes that was not included into shipping cost.
     *
     * @codingStandardsIgnoreStart
     * @param array{
     *     taxes: array{
     *              array{
     *                  price_includes_tax: string,
     *                  applies: array<string, float>
     *              }
     *            }
     * }                    $order_info              Order information
     * @param array<string> $company_data            Company to which order belongs to
     * @param array<float>  $payout_data             Payout data to be written to database
     * @param float         $total                   Order total amount
     * @param float         $shipping_cost           Order shipping cost amount
     * @param float         $surcharge_from_total    Order payment surcharge to be subtracted from total
     * @param float         $surcharge_to_commission Order payment surcharge to be added to commission amount
     * @param float         $commission              The transaction percent value
     * @param float         $taxes                   Order taxes amount
     *
     * @codingStandardsIgnoreEnd
     *
     * @param-out array<float> $payout_data
     *
     * @see \fn_calculate_commission_for_payout()
     *
     * @return void
     */
    public function onVendorPlansCalculateCommissionForPayoutBefore(
        array $order_info,
        array $company_data,
        array &$payout_data,
        &$total,
        $shipping_cost,
        $surcharge_from_total,
        $surcharge_to_commission,
        $commission,
        &$taxes
    ) {
        if (
            !isset($payout_data['marketplace_shipping_amount'])
            || empty($taxes)
            || empty($order_info['taxes'])
            || !is_array($order_info['taxes'])
        ) {
            return;
        }

        foreach ($order_info['taxes'] as $tax) {
            if (
                /** @var string $tax['price_includes_tax'] */
                YesNo::toBool($tax['price_includes_tax'])
            ) {
                continue;
            }
            if (!empty($tax['applies']['S'])) {
                $total -= $tax['applies']['S'];
                $taxes -= $tax['applies']['S'];
                $payout_data['marketplace_shipping_amount'] += $tax['applies']['S'];
            } elseif (is_array($tax['applies'])) {
                foreach ($tax['applies'] as $tax_code => $tax_value) {
                    //phpcs:ignore
                    if (strpos($tax_code, 'S_0') === 0) {
                        $taxes -= $tax_value;
                    }
                }
            }
        }
    }
}
