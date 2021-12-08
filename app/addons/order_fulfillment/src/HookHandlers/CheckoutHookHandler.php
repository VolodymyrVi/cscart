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

use Tygh\Tygh;

class CheckoutHookHandler
{
    /**
     * The 'checkout_update_steps_pre' hook handler.
     *
     * Action performed:
     *    - Adds parameter to session for another add-ons hook about current request.
     *    - Parameter blocks creation the temporary product group at process of calculation suborders.
     *
     * @param array<string>         $cart            Cart content
     * @param array<string>         $auth            Customer's authentication data
     * @param array<string, string> $params          Step update parameters
     * @param array<string>         $redirect_params Redirection parameters
     *
     * @see \fn_checkout_update_steps()
     *
     * @return void
     */
    public function onCheckoutUpdateStepsPre(array $cart, array $auth, array $params, array $redirect_params)
    {
        if (!isset($params['update_steps']) || $params['update_steps'] !== '1') {
            return;
        }
        Tygh::$app['session']['place_order'] = true;
    }

    /**
     * The `checkout_place_orders_pre_route` hook handler.
     *
     * Action performed:
     *     - Removes specified parameter for session for blocking creation the temporary product group.
     *
     * @param array<string> $cart   Cart information.
     * @param array<string> $auth   Authentication data.
     * @param array<string> $params Request parameters.
     *
     * @see \fn_checkout_place_order()
     *
     * @return void
     */
    public function onCheckoutPlaceOrdersPreRoute(array $cart, array $auth, array $params)
    {
        unset(Tygh::$app['session']['place_order']);
    }
}
