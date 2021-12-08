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

use Tygh\Addons\StripeConnect\Webhook\StripeWebhook;
use Stripe\Webhook;

defined('BOOTSTRAP') or die('Access denied');

if (!isset($_SERVER['HTTP_STRIPE_SIGNATURE'])) {
    die('Access denied');
}

$payload = @file_get_contents('php://input');
$stripe_signature = $_SERVER['HTTP_STRIPE_SIGNATURE'];
$event = null;

try {
    $event = Webhook::constructEvent(
        $payload,
        $stripe_signature,
        StripeWebhook::getSecretKey()
    );
} catch (\Exception $e) {
    return [CONTROLLER_STATUS_NO_CONTENT];
}

StripeWebhook::handle($event);

return [CONTROLLER_STATUS_NO_CONTENT];
