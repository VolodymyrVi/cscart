{if !$product.company_id}
    {if $show_add_to_cart}
        {$show_view_offers_btn=true scope=parent}
    {/if}

    {$show_master_product_discount_label = $show_discount_label scope=parent}
    {$show_discount_label=false scope=parent}
    {$show_shipping_label=false scope=parent}
    {$show_product_amount=false scope=parent}
    {$show_add_to_cart=false scope=parent}
{/if}