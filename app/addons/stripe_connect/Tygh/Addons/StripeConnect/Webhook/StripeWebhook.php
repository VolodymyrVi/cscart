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

namespace Tygh\Addons\StripeConnect\Webhook;

use Tygh;
use Tygh\Addons\StripeConnect\Payments\StripeConnect;
use Tygh\Enum\SiteArea;
use Stripe\Stripe;
use Stripe\Event;
use Stripe\WebhookEndpoint;

final class StripeWebhook
{
    const WEBHOOK_SECRET_STORAGE_KEY = 'stripe_connect_webhook_secret_key';
    const WEBHOOK_ID_STORAGE_KEY = 'stripe_connect_webhook_id';

    /**
     * Creates webhook event handler
     *
     * @param Event $event Event
     *
     * @return void
     */
    public static function handle(Event $event)
    {
        /** @var Handler|false $handler */
        $handler = self::getHandler($event->type);

        if (!$handler) {
            return;
        }

        $handler->handle($event);
    }

    /**
     * Registers webhook on the Stripe Connect side
     *
     * @return void
     *
     * @throws \Stripe\Exception\ApiErrorException Stripe exception.
     */
    public static function register()
    {
        $endpoint = WebhookEndpoint::create([
            'url' => fn_url('', SiteArea::STOREFRONT) . 'stripe-connect/webhook',
            'connect' => true,
            'enabled_events' => [
                'account.application.deauthorized'
            ],
            'description' => 'This webhook is created automatically. Please do not delete it.',
            'api_version' => StripeConnect::API_VERSION
        ]);

        fn_set_storage_data(self::WEBHOOK_SECRET_STORAGE_KEY, $endpoint->secret);
        fn_set_storage_data(self::WEBHOOK_ID_STORAGE_KEY, $endpoint->id);
    }

    /**
     * Retrieves webhook data
     *
     * @param string $id Webhook ID
     *
     * @return WebhookEndpoint
     *
     * @throws \Stripe\Exception\ApiErrorException Stripe exception.
     */
    public static function retrieve($id)
    {
        return WebhookEndpoint::retrieve($id);
    }

    /**
     * Gets webhook secret key
     *
     * @return string
     */
    public static function getSecretKey()
    {
        return (string) fn_get_storage_data(self::WEBHOOK_SECRET_STORAGE_KEY);
    }

    /**
     * Gets webhook ID
     *
     * @return string
     */
    public static function getId()
    {
        return (string) fn_get_storage_data(self::WEBHOOK_ID_STORAGE_KEY);
    }

    /**
     * Sets config
     *
     * @param string $api_key Api key
     *
     * @return void
     */
    public static function setConfig($api_key)
    {
        Stripe::setApiKey($api_key);
    }

    /**
     * Converts event type to class name
     *
     * @param string $event_type Event type
     *
     * @return Handler|false
     */
    private static function getHandler($event_type)
    {
        $handler_key = 'addons.stripe_connect.webhook_handler.' . $event_type;

        if (isset(Tygh::$app[$handler_key])) {
            return Tygh::$app[$handler_key];
        }

        return false;
    }
}
