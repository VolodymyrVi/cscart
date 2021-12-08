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

use Tygh\Addons\StripeConnect\ServiceProvider;
use Tygh\Common\OperationResult;
use Tygh\Addons\StripeConnect\Logger;

defined('BOOTSTRAP') or die('Access denied');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'check_confirmation') {
        /** @var \Tygh\Ajax $ajax */
        $ajax = Tygh::$app['ajax'];
        /** @var array $cart */
        $cart = Tygh::$app['session']['cart'];

        if (!empty($cart['user_data']['user_exists'])) {
            return [CONTROLLER_STATUS_NO_CONTENT];
        }

        $params = array_merge([
            'payment_intent_id' => null,
            'order_id' => null,
            'payment_id' => null,
        ], $_REQUEST);

        $total = 0;
        if ($params['order_id']) {
            $order_info = fn_get_order_info($params['order_id']);
            $total = $order_info['total'];
        } else {
            $total = $cart['total'];
            if (!empty($cart['payment_surcharge'])) {
                $total += $cart['payment_surcharge'];
            }
        }

        $payment_id = $params['payment_id'];
        if (!$payment_id) {
            $payment_id = $cart['payment_id'];
        }
        $processor = ServiceProvider::getProcessorFactory()->getByPaymentId($payment_id);

        $confirmation_result = new OperationResult(false);
        try {
            $confirmation_result = $processor->getPaymentConfirmationDetails($params['payment_intent_id'], $total);
        } catch (Exception $e) {
            Logger::log(Logger::ACTION_FAILURE, __('stripe_connect.payment_intent_error', [
                '[payment_id]' => $payment_id,
                '[error]' => $e->getMessage(),
            ]));
        }

        if ($confirmation_result->isSuccess()) {
            foreach ($confirmation_result->getData() as $field => $value) {
                $ajax->assign($field, $value);
            }
        } else {
            $ajax->assign('error', [
                'message' => __('text_order_placed_error'),
            ]);
        }

        return [CONTROLLER_STATUS_NO_CONTENT];
    }

    if ($mode === 'update_payments_description') {
        $session = & Tygh::$app['session'];

        if (
            empty($session['stripe_connect_order_id'])
            && empty($_REQUEST['order_id'])
        ) {
            return [CONTROLLER_STATUS_NO_CONTENT];
        }

        $order_id = isset($session['stripe_connect_order_id'])
            ? $session['stripe_connect_order_id']
            : $_REQUEST['order_id'];
        unset($session['stripe_connect_order_id']);

        $order_info = fn_get_order_info($order_id);

        if (!$order_info) {
            return [CONTROLLER_STATUS_NO_CONTENT];
        }

        $processor = ServiceProvider::getProcessorFactory()->getByPaymentId($order_info['payment_id']);

        if (!$processor) {
            return [CONTROLLER_STATUS_NO_CONTENT];
        }

        $processor->updatePaymentsDescriptions($order_info);

        return [CONTROLLER_STATUS_NO_CONTENT];
    }
}

return [CONTROLLER_STATUS_NO_PAGE];
