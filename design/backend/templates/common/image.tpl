{strip}

{$image_data = $image|fn_image_to_display:$image_width:$image_height}
{$show_detailed_link = $show_detailed_link|default:true}

{$width = $image_data.width * 2}
{$height = $image_data.height * 2}
{$image_data2x = $image|fn_image_to_display:$width:$height}

{if $show_detailed_link && ($image || $href)}
    <a class="{$link_css_class}" href="{$href|default:$image.image_path}" {if !$href}target="_blank"{/if}>
{/if}
{if $image_data.image_path}
    <img {if $image_id}id="image_{$image_id}"{/if} srcset="{$image_data2x.image_path} 2x" src="{$image_data.image_path}" width="{$image_data.width}" height="{$image_data.height}" alt="{$image_data.alt}" {if $image_data.generate_image}    class="spinner {$image_css_class}"    data-ca-image-path="{$image_data.image_path}"{else}    class="{$image_css_class}"{/if} title="{$image_data.alt}" />
{else}
    <div class="no-image {$no_image_css_class}" style="width: {$image_width|default:$image_height}px; height: {$image_height|default:$image_width}px;">{include_ext file="common/icon.tpl" class="glyph-image" title=__("no_image")}</div>
{/if}
{if $show_detailed_link && ($image || $href)}</a>{/if}

{/strip}
