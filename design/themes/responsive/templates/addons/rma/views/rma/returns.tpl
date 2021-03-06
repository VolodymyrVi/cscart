<div class="ty-rma-return">
{capture name="section"}
    {include file="addons/rma/views/rma/components/rma_search_form.tpl"}
{/capture}
{include file="common/section.tpl" section_title=__("search_options") section_content=$smarty.capture.section collapse=true}
<form action="{""|fn_url}" method="post" name="rma_list_form">
{include file="common/pagination.tpl"}

{assign var="c_url" value=$config.current_url|fn_query_remove:"sort_by":"sort_order"}
{if $search.sort_order == "asc"}
    {include_ext file="common/icon.tpl" class="ty-icon-down-dir" assign=sort_sign}
{else}
    {include_ext file="common/icon.tpl" class="ty-icon-up-dir" assign=sort_sign}
{/if}
<table class="ty-table ty-rma-return__table">
    <thead>
        <tr>
            <th style="width: 10%"><a class="{$ajax_class}" href="{"`$c_url`&sort_by=return_id&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("id")}</a>{if $search.sort_by === "return_id"}{$sort_sign nofilter}{/if}</th>
            <th style="width: 13%"><a class="{$ajax_class}" href="{"`$c_url`&sort_by=status&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("status")}</a>{if $search.sort_by === "status"}{$sort_sign nofilter}{/if}</th>
            <th style="width: 35%"><a class="{$ajax_class}" href="{"`$c_url`&sort_by=customer&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("customer")}</a>{if $search.sort_by === "customer"}{$sort_sign nofilter}{/if}</th>
            <th style="width: 20%"><a class="{$ajax_class}" href="{"`$c_url`&sort_by=timestamp&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("date")}</a>{if $search.sort_by === "timestamp"}{$sort_sign nofilter}{/if}</th>
            <th style="width: 5%"><a class="{$ajax_class}" href="{"`$c_url`&sort_by=order_id&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("order")}&nbsp;{__("id")}</a>{if $search.sort_by === "order_id"}{$sort_sign nofilter}{/if}</th>
            <th style="width: 5%"><a class="{$ajax_class}" href="{"`$c_url`&sort_by=amount&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("quantity")}</a>{if $search.sort_by === "amount"}{$sort_sign nofilter}{/if}</th>
        </tr>
    </thead>
    <tbody>
    {foreach from=$return_requests item="request"}
        <tr>
            <td><a href="{"rma.details?return_id=`$request.return_id`"|fn_url}"><strong>#{$request.return_id}</strong></a></td>
            <td>
                <input type="hidden" name="origin_statuses[{$request.return_id}]" value="{$request.status}">
                {include file="common/status.tpl" status=$request.status display="view" name="return_statuses[`$request.return_id`]" status_type=$smarty.const.STATUSES_RETURN}
            </td>
            <td>{$request.firstname} {$request.lastname}</td>
            <td><a href="{"rma.details?return_id=`$request.return_id`"|fn_url}">{$request.timestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}</a></td>
            <td class="ty-center"><a href="{"orders.details?order_id=`$request.order_id`"|fn_url}">#{$request.order_id}</a></td>
            <td class="ty-center">{$request.total_amount}</td>
        </tr>
    {foreachelse}
        <tr class="ty-table__no-items">
            <td colspan="6"><p class="ty-no-items">{__("no_return_requests_found")}</p></td>
        </tr>
    {/foreach}
    </tbody>
</table>
{include file="common/pagination.tpl"}
</form>
{capture name="mainbox_title"}{__("return_requests")}{/capture}
</div>
