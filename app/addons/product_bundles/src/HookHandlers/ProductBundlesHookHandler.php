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

use Tygh\Addons\ProductVariations\ServiceProvider;
use Tygh\Addons\ProductBundles\ServiceProvider as BundlesServiceProvider;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\SiteArea;
use Tygh\Registry;
use Tygh\Addons\MasterProducts\ServiceProvider as MasterProductsServiceProvider;

class ProductBundlesHookHandler
{
    /**
     * The `product_bundle_service_update_bundle_post` hook handler.
     *
     * Action performed:
     *     - Generate bundles for all variations if in bundle was parent variation product.
     *
     * @param array<string> $bundle_data         Bundle information.
     * @param int           $bundle_id           Bundle identifier.
     * @param array<string> $bundle_descriptions Bundle description.
     *
     * @return void
     */
    public function onPostUpdateBundle(array $bundle_data, $bundle_id, array $bundle_descriptions)
    {
        if (empty($bundle_data['products']) || isset($bundle_data['has_ignore_update_bundle_post'])) {
            return;
        }
        $bundle_service = BundlesServiceProvider::getService();
        $new_bundle_data = $bundle_data;
        $products = unserialize($bundle_data['products']);
        $new_bundle_data['products'] = $products;
        $product_id_map = ServiceProvider::getProductIdMap();
        foreach ($products as $product_key => $product) {
            if (empty($product['any_variation'])) {
                continue;
            }
            $product_id = $product['product_id'];

            $children_ids = $product_id_map->getProductChildrenIds($product_id);
            if (empty($children_ids)) {
                continue;
            }
            foreach ($children_ids as $children_id) {
                $new_bundle_data['products'][$product_key]['product_id'] = $children_id;
                unset($new_bundle_data['products'][$product_key]['any_variation']);
                $new_bundle_data['parent_bundle_id'] = $bundle_id;
                $bundle_service->updateBundle($new_bundle_data);
            }
        }
    }

    /**
     * The `product_bundle_service_update_bundle` hook handler.
     *
     * Action performed:
     *     - Deletes all child bundles at updating parent bundle process.
     *
     * @param array<string> $bundle_data Bundle information.
     * @param int           $bundle_id   Updating bundle identifier.
     *
     * @return void
     */
    public function onUpdateBundle(array $bundle_data, $bundle_id)
    {
        if (empty($bundle_id)) {
            return;
        }
        $this->deleteAllChildBundles($bundle_id);
    }

    /**
     * The `product_bundle_service_delete_bundle_pre` hook handler.
     *
     * Action performed:
     *     - Deletes all child bundles at deleting parent bundle process.
     *
     * @param int $bundle_id Updating bundle identifier.
     *
     * @return void
     */
    public function onPreDeleteBundle($bundle_id)
    {
        if (empty($bundle_id)) {
            return;
        }
        $this->deleteAllChildBundles($bundle_id);
    }

    /**
     * The `product_bundle_service_get_bundles_post` hook handlers.
     *
     * Action performed:
     *      - Adds information about existing product variations.
     *
     * @param array<string> $params  Parameters for selecting bundles.
     * @param array<string> $bundles Selected bundles.
     *
     * @return void
     */
    public function onPostGetBundles(array $params, array &$bundles)
    {
        if (empty($bundles)) {
            return;
        }

        $product_id_map = ServiceProvider::getProductIdMap();
        foreach ($bundles as &$bundle) {
            if (!is_array($bundle['products'])) {
                $bundle['products'] = unserialize($bundle['products']);
            }
            if (empty($bundle['products'])) {
                continue;
            }
            foreach ($bundle['products'] as &$product) {
                $product['parent_variation_product'] = (bool) $product_id_map->getProductChildrenIds($product['product_id']);

                // phpcs:ignore
                if (
                    !isset($product['any_variation'])
                    && isset($product['product_data']['variation_name'])
                ) {
                    $product['product_name'] = $product['product_data']['variation_name'];
                }
            }
            unset($product);
        }
        unset($bundle);
    }

    /**
     * The `product_bundles_service_update_hidden_promotion_before_update` hook handler.
     *
     * Action performed:
     *     - Change owner of hidden promotion assigned to product bundle.
     *
     * @param array<string> $bundle_data         Bundle information.
     * @param array<string> $bundle_descriptions Bundle descriptions.
     * @param int           $promotion_id        Promotion identifier.
     * @param array<string> $data                Promotion data.
     *
     * @return void
     */
    public function onPreUpdatePromotion(array $bundle_data, array $bundle_descriptions, $promotion_id, array &$data)
    {
        if (Registry::get('addons.direct_payments.status') !== ObjectStatuses::ACTIVE) {
            return;
        }

        $data['company_id'] = $bundle_data['company_id'];
    }

    /**
     * The `product_bundle_service_get_bundles_pre` hook handler.
     *
     * Action performed:
     *      - Allows to get child variation bundle if selected product is a variotion.
     *
     * @param array<string> $params Parameters for getting bundles.
     *
     * @return void
     */
    public function onPreGetBundles(array &$params)
    {
        if (empty($params['product_id'])) {
            return;
        }
        $product_id_map = ServiceProvider::getProductIdMap();
        $params['get_child_bundles'] = !$product_id_map->isParentProduct((int) $params['product_id']);
    }

    /**
     * The `product_bundle_service_get_bundles` hook handler.
     *
     * Action performed:
     *      - Allows getting bundles for child products.
     *
     * @param array<string|int>     $params     Parameters for bundles search.
     * @param string                $fields     Requesting product bundles fields.
     * @param array<string, string> $joins      Joining tables for request.
     * @param array<string, string> $conditions Conditions of request.
     * @param array<string, string> $limit      Limit conditions of request.
     *
     * @param-out array<string|int|array<int>> $params
     *
     * @return void
     */
    public function onGetBundles(array &$params, $fields, array $joins, array &$conditions, array $limit)
    {
        if (!isset($conditions['product_id'], $params['product_id']) || !SiteArea::isStorefront(AREA)) {
            return;
        }

        $vendor_product_ids = MasterProductsServiceProvider::getProductRepository()->findVendorProductIds((int) $params['product_id']);
        if (empty($vendor_product_ids)) {
            return;
        }

        $params['product_id'] = $vendor_product_ids;
        $conditions['product_id'] = db_quote(' AND links.product_id IN (?n)', $vendor_product_ids);
    }

    /**
     * Finds and deletes all child bundles at parent bundle.
     *
     * @param int $bundle_id Updating bundle identifier.
     *
     * @return void
     */
    private function deleteAllChildBundles($bundle_id)
    {
        $children_bundle_ids = db_get_fields('SELECT bundle_id FROM ?:product_bundles WHERE parent_bundle_id = ?i', $bundle_id);
        if (empty($children_bundle_ids)) {
            return;
        }
        $bundle_service = BundlesServiceProvider::getService();
        foreach ($children_bundle_ids as $children_bundle_id) {
            $bundle_service->deleteBundle($children_bundle_id);
        }
    }
}
