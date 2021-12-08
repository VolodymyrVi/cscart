{if $smarty.const.ACCOUNT_TYPE === "vendor"}
    {$navigation_accordion = true scope=parent}
    {$select_languages = false scope=parent}

    {$enable_onclick_menu = true scope=parent}
    {$enable_sticky_scroll = false scope=parent}
    {$enable_search_collapse = false scope=parent}

    {$show_company = false scope=parent}
    {$show_menu_descriptions = false scope=parent}
    {$show_menu_caret = false scope=parent}
    {$show_addon_icon = false scope=parent}
    {$show_languages_in_header_menu = false scope=parent}
    {$show_currencies_in_header_menu = false scope=parent}
    {$show_last_viewed_items = false scope=parent}
    {$show_pagination_open = false scope=parent}
    {$show_list_price_column = false scope=parent}

    {$image_width = "80" scope=parent}
    {$image_height = "80" scope=parent}

    {$is_open_state_sidebar_save = true scope=parent}

    {* Page: addons.update&addon=vendor_panel_configurator&selected_section=settings *}
    {$mainColor = $runtime.vendor_panel_style.element_color|default:"#024567" scope=parent}     {* Admin main color #0388cc + 20% darken *}
    {$menuSidebarColor = $runtime.vendor_panel_style.sidebar_color|default:"#eef1f3" scope=parent}
    {if $runtime.vendor_panel_style.main_pair.icon.image_path}
        {$menuSidebarBg = "url(`$runtime.vendor_panel_style.main_pair.icon.image_path`)" scope=parent}
    {else}
        {$menuSidebarBg = "none" scope=parent}
    {/if}
{/if}
