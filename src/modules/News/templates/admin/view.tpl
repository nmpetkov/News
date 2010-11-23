{ajaxheader modname='News' filename='news.js' nobehaviour=true noscriptaculous=true ui=true}
{gt text='News articles list' assign='templatetitle'}

{include file='admin/menu.tpl'}

<div class="z-admincontainer">
    <div class="z-adminpageicon">{img modname='core' src='windowlist.gif' set='icons/large' alt=$templatetitle}</div>

    <h2>{$templatetitle}</h2>

    <p>
        <a href="{$alllink.url|safetext}">{$alllink.title} <span class="z-sub">({$alllink.count})</span></a>
        {foreach from=$statuslinks item='statuslink'}
        {if $statuslink.count gt 0}&nbsp;|&nbsp;<a href="{$statuslink.url|safetext}">{$statuslink.title} <span class="z-sub">({$statuslink.count})</span></a>{/if}
        {/foreach}
    </p>

    {if $enablecategorization && $numproperties > 0}
    <form class="z-form" id="news_filter" action="{modurl modname='News' type='admin' func='view'}" method="post" enctype="application/x-www-form-urlencoded">
        <fieldset id="news_multicategory_filter">
            <legend>{gt text="Filter"}</legend>
            <label for="news_property">{gt text='Category'}</label>
            {gt text='All' assign='lblDef'}
            {nocache}
            {if $numproperties gt 1}
            {html_options id='news_property' name='news_property' options=$properties selected=$property}
            {else}
            <input type="hidden" id="news_property" name="news_property" value="{$property}" />
            {/if}
            <div id="news_category_selectors">
                {foreach from=$catregistry key='prop' item='cat'}
                {assign var='propref' value=$prop|string_format:'news_%s_category'}
                {if $property eq $prop}
                {assign var='selectedValue' value=$category}
                {else}
                {assign var='selectedValue' value=0}
                {/if}
                <noscript>
                    <div class="property_selector_noscript"><label for="{$propref}">{$prop}</label>:</div>
                </noscript>
                {selector_category category=$cat name=$propref selectedValue=$selectedValue allValue=0 allText=$lblDef editLink=false}
                {/foreach}
            </div>
            {if $modvars.ZConfig.multilingual}
            &nbsp;
            <label for="news_language">{gt text='Language'}</label>
            {html_select_languages id="news_language" name="story[language]" installed=1 all=1 selected=$language|default:''}
            {/if}
            {/nocache}
            &nbsp;
            <label for="news_status">{gt text='Status'}</label>
            {html_options name='news_status' id='news_status' options=$itemstatus selected=$news_status}
            &nbsp;
            <label for="order">{gt text='Order articles by'}</label>
            {html_options name='order' id='order' options=$orderoptions selected=$order}
            &nbsp;&nbsp;
            <span class="z-nowrap z-buttons">
                <input class="z-bt-small" name="submit" type="submit" value="{gt text='Filter'}" />
                <input class="z-bt-small" name="clear" type="submit" value="{gt text='Clear'}" />
            </span>
        </fieldset>
    </form>
    {elseif $modvars.ZConfig.multilingual}
    <form action="{modurl modname='News' type='admin' func='view'}" method="post" enctype="application/x-www-form-urlencoded">
        <div id="news_multicategory_filter">
            <label for="news_language">{gt text='Language'}</label>
            {nocache}
            {html_select_languages id="news_language" name="story[language]" installed=1 all=1 selected=$language|default:''}
            {/nocache}
            <label for="news_status">{gt text='Status'}</label>
            {html_options name='news_status' id='news_status' options=$itemstatus selected=$status}
            <input name="submit" type="submit" value="{gt text='Apply filter'}" />
            <input name="clear" type="submit" value="{gt text='Reset'}" />
        </div>
    </form>
    {/if}

    <table id="news_admintable" class="z-datatable">
        <thead>
            <tr>
                <th>{gt text='ID'}</th>
                <th>{gt text='Title'}</th>
                <th>{gt text='Contributor'}</th>
                {if $enablecategorization}
                <th>{gt text='Category'}</th>
                {/if}
                {if $picupload_enabled}
                <th>{gt text='Pictures'}</th>
                {/if}
                <th>{gt text='Index page<br />listing / Weight'}</th>
                <th>{gt text='Date'}</th>
                <th>{gt text='Actions'}</th>
            </tr>
        </thead>
        <tbody>
            {foreach from=$newsitems item='newsitem'}
            <tr class="{cycle values='z-odd,z-even'}">
                <td>{$newsitem.sid|safetext}</td>
                <td>
                    {$newsitem.title|strip_tags|safetext}
                    {if $newsitem.published_status eq 2}<strong><em> - {gt text='Pending Review'}</em></strong>{/if}
                    {if $newsitem.published_status eq 4}<strong><em> - {gt text='Draft'}</em></strong>{/if}
                </td>
                <td>{$newsitem.contributor|safetext}</td>
                {if $enablecategorization}
                <td class="news_categorieslist">
                    {assignedcategorieslist item=$newsitem}
                </td>
                {/if}
                {if $picupload_enabled}
                <td>
                    {$newsitem.pictures|safetext}
                </td>
                {/if}
                <td>{$newsitem.hideonindex|safetext} / {$newsitem.weight|safetext}</td>
                <td>
                    {if $newsitem.published_status eq '2'}
                    {gt text='Last edited %s' tag1=$newsitem.lu_date|dateformatHuman:'%x':'3'}
                    {else}
                    {if $newsitem.infuture}{gt text='Scheduled for'}{else}{$newsitem.published_status|news_getstatustext}{/if}
                    {$newsitem.from|dateformatHuman:'%x':'2'}.
                    {if $newsitem.to neq null}<br />{gt text='until %s' tag1=$newsitem.to|dateformatHuman:'%x':'3'}{/if}
                    {/if}
                    {if $newsitem.disallowcomments eq '1'}
                    <br /><em>{gt text='No comments allowed.'}</em>
                    {/if}
                </td>
                <td>
                    {assign var='options' value=$newsitem.options}
                    {section name='options' loop=$options}
                    <a href="{$options[options].url|safetext}">{img modname='core' set='icons/extrasmall' src=$options[options].image title=$options[options].title alt=$options[options].title class='tooltips'}</a>
                    {/section}
                </td>
            </tr>
            {foreachelse}
            <tr class="z-datatableempty"><td colspan="{if $enablecategorization}7{else}6{/if}">{gt text='No articles currently in database.'}</td></tr>
            {/foreach}
        </tbody>
    </table>

    <form class="z-form" action="{modurl modname='News' type='admin' func='modify'}" method="post">
        <fieldset>
            <label for="directsid">{gt text='Access a past article via its ID'}:</label>
            <input type="text" id="directsid" name="sid" value="" size="5" maxlength="8" />
            <span class="z-nowrap z-buttons">
                <input class="z-bt-small" name="submit" type="submit" value="{gt text='Go retrieve'}" />
                <input class="z-bt-small" name="reset" type="reset" value="{gt text='Reset'}" />
            </span>
        </fieldset>
    </form>

    {pager rowcount=$pager.numitems limit=$pager.itemsperpage posvar='startnum' shift=1}
</div>

<script type="text/javascript">
    Zikula.UI.Tooltips($$('.tooltips'));
</script>
