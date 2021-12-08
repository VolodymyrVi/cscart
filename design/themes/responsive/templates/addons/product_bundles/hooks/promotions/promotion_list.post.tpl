{if $bundles}
    <p><h1>{__("product_bundles.active_bundles")}</h1></p>
    {foreach $bundles as $bundle}
        {include file="addons/product_bundles/components/bundle_promotion.tpl"}
    {/foreach}
{/if}