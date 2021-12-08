<div id="product_bundle_features">
    <div id="product_bundle_features_update_{$bundle_id}_{$key}">
        {$purpose_create_variations = "\Tygh\Addons\ProductVariations\Product\FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM"|constant}

        {foreach $product.variation_features_variants as $feature}
            {if $feature.purpose !== $purpose_create_variations}
                {continue}
            {/if}
            <div class="ty-control-group ty-product-options__item clearfix">
                <label class="ty-control-group__label ty-product-options__item-label">{$feature.description}:</label>
                {if $feature.prefix}
                    <span>{$feature.prefix}</span>
                {/if}
                <select name="product_bundle_feature_variation_front" data-ca-product-id="{$product.product_id}">
                    {foreach $feature.variants as $variant}
                        {if $variant.product && $variant.product.amount}
                            <option {if $feature.variant_id === $variant.variant_id}selected="selected"{/if}
                                data-ca-product-id="{$variant.product_id}"
                                data-ca-target-id="product_bundle_features_update_{$bundle_id}_{$key}"
                                data-ca-bundle-id="{$bundle_id}"
                                data-ca-key="{$key}"
                                data-ca-change-url="{"product_bundles.get_feature_variants"|fn_url}"
                            >
                                {$variant.variant}
                            </option>
                        {elseif $addons.product_variations.variations_show_all_possible_feature_variants === "YesNo::YES"|enum}
                            <option disabled>{$variant.variant}</option>
                        {/if}
                    {/foreach}
                </select>
            </div>
        {/foreach}
    <!--product_bundle_features_update_{$bundle_id}_{$key}--></div>

    <div class="buttons-container">
        {include file="buttons/button.tpl"
            but_id="add_item_close"
            but_text=__("close")
            but_meta="ty-btn__secondary cm-dialog-closer"
            but_role="act"
        }
    </div>
</div>
