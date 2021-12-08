{$product_index=$product_index|default:0}
{$id_postfix=($product_index > 0) ? "_`$product_index`" : ""}

{$product_amount = $amount|default:$product.amount}

<div class="ty-product-bundle__product">
    <input type="hidden" name="product_data[{$product.product_id}{$id_postfix}][product_id]" value="{$product.product_id}" />
    <input type="hidden" name="product_data[{$product.product_id}{$id_postfix}][amount]" value="{$product_amount}" />

    <div class="ty-product-bundle__product-image cm-reload-{$obj_prefix}{$product.product_id}" id="pb_product_image_{$bundle.bundle_id}_{$product.product_id}">
        <a href="{"products.view?product_id=`$product.product_id`"|fn_url}">
            {include file="common/image.tpl" 
                image_width=$settings.Thumbnails.product_lists_thumbnail_width 
                image_height=$settings.Thumbnails.product_lists_thumbnail_height 
                obj_id="`$bundle.bundle_id`_`$product.product_id`" 
                images=$product.main_pair 
                class="ty-product-bundle__product-image"
            }
        </a>
    <!--pb_product_image_{$bundle.bundle_id}_{$product.product_id}--></div>

    <div class="ty-product-bundle__product-name">
        <a href="{"products.view?product_id=`$product.product_id`"|fn_url}">{$product.product_name}</a>
    </div>

    {if $product.product_options}
        {foreach $product.product_options as $option}
            <div class="ty-product-bundle-option">
                <input type="hidden" name="product_data[{$product.product_id}{$id_postfix}][product_options][{$option.option_id}]" value="{$option.value}" />
                <span class="ty-product-bundle-option__name">{$option.option_name}</span>: {$option.variant_name}
            </div>
        {/foreach}
    {elseif $product.aoc}
        {capture name="product_bundle_product_options"}
            <div id="product_bundle_options_{$bundle.bundle_id}_{$product.product_id}" class="ty-product-bundle-box">
                <div class="{$product_bundle_options_class}" id="product_bundle_options_update_{$bundle.bundle_id}_{$product.product_id}">
                    {include file="views/products/components/product_options.tpl" 
                        product=$product 
                        id="`$product.product_id``$id_postfix`"
                        product_options=$product.options 
                        name="product_data" 
                        no_script=true 
                        extra_id="`$product.product_id`_`$bundle.bundle_id`"
                    }
                    <!--product_bundle_options_update_{$bundle.bundle_id}_{$product.product_id}--></div>

                <div class="buttons-container">
                    {include file="buttons/button.tpl" but_id="add_item_close" but_name="" but_text=__("save_and_close") but_role="action" but_meta="ty-btn__secondary cm-dialog-closer"}
                </div>
            </div>
        {/capture}
        <div class="ty-product-bundle__product-options">
            {include file="common/popupbox.tpl" id="product_bundle_options_`$bundle.bundle_id`_`$product.product_id``$product_index`" link_meta="ty-btn ty-btn__primary" text=__("product_bundles.specify_options") content=$smarty.capture.product_bundle_product_options link_text=__("product_bundles.specify_options") act="general"}
        </div>
    {/if}

    <div class="ty-product-bundle-product-info">
        {hook name="products:product_info"}
        {/hook}
    </div>

    <div class="ty-product-bundle__product-price cm-reload-{$obj_prefix}{$product.product_id}" id="pb_product_price_{$bundle.bundle_id}_{$product.product_id}">
        {$product_amount}&nbsp;x
        {if !(!$auth.user_id && $settings.Checkout.allow_anonymous_shopping == "hide_price_and_add_to_cart")}
            {if $product.price != $product.discounted_price}
                <span class="ty-strike">
                    {include file="common/price.tpl" value=$product.price}
                </span>
            {/if}
            {include file="common/price.tpl" value=$product.discounted_price}
        {/if}
        <!--pb_product_price_{$bundle.bundle_id}_{$product.product_id}--></div>
</div>
{if ($bundle.products[$_id] !== $bundle.products|end) || ($product_index > 0 && ($product_index != $product.amount))}
    <span class="ty-product-bundle__plus chain-plus">+</span>
{/if}