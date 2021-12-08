{if $page.page_type == $smarty.const.PAGE_TYPE_TILDA_PAGE && $page.is_only_content === "YesNo::NO"|enum}
    <div id="{$smarty.const.TILDA_PAGE_CONTAINER_ID}">
        <link type="text/css" rel="stylesheet" href="{$tilda_page_upload_settings.css.http_path}/{$smarty.const.TILDA_PAGE_COMMON_STYLE_FILE_NAME}" />

        {$page.description nofilter}
    </div>
{elseif $location_data.page_id && $location_data.is_only_content === "YesNo::NO"|enum}
    <div id="{$smarty.const.TILDA_PAGE_CONTAINER_ID}">
        <link type="text/css" rel="stylesheet" href="{$location_data.tilda_page_upload_settings.css.http_path}/{$smarty.const.TILDA_PAGE_COMMON_STYLE_FILE_NAME}" />

        {$location_data.description nofilter}

        <script src="{$location_data.tilda_page_upload_settings.js.http_path}/{$smarty.const.TILDA_PAGE_COMMON_SCRIPT_FILE_NAME}"></script>
    </div>
{/if}