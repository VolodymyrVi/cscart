{if $page.page_type === $smarty.const.PAGE_TYPE_TILDA_PAGE || $location_data.page_id}
    {if !$config.tweaks.dev_js}
        <script src="https://code.jquery.com/jquery-migrate-{$jquery_migrate_version}.min.js"
                integrity="sha256-wZ3vNXakH9k4P00fNGAlbN0PkpKSyhRa76IFy4V1PYE="
                crossorigin="anonymous"
                data-no-defer
        ></script>
        <script data-no-defer>
            if (!window.jQuery) {
                document.write('{script src="js/lib/jquery/jquery-migrate-$jquery_migrate_version}.min.js" no-defer=true escape=true}');
            }
        </script>
    {else}
        {script src="js/lib/jquery/jquery-migrate-{$jquery_migrate_version}.min.js"}
    {/if}

    {if $page.page_type == $smarty.const.PAGE_TYPE_TILDA_PAGE}
        {script src="`$tilda_page_upload_settings.js.http_path`/`$smarty.const.TILDA_PAGE_COMMON_SCRIPT_FILE_NAME`"}
    {/if}
{/if}