<span class="news_category">{$preformat.category}</span>
<h3 class="news_title">{$info.catandtitle|safehtml}</h3>
<p class="news_meta z-sub">{gt text='Contributed'} {gt text='by %1$s on %2$s' tag1=$info.contributor tag2=$info.from|dateformat:'datetimebrief'}</p>
{if $links.searchtopic neq '' AND $info.topicimage neq ''}
<p id="news_topic" class="news_meta"><a href="{$links.searchtopic}"><img src="{$modvars.News.catimagepath}{$info.topicimage}" alt="{$info.topicname}" title="{$info.topicname}" /></a></p>
{/if}
<div id="news_body" class="news_body">
    <div class="news_hometext">
        {$preformat.hometext|notifyfilters:'news.filter_hooks.articles.filter'|safehtml|paragraph}
    </div>
    {$preformat.bodytext|notifyfilters:'news.filter_hooks.articles.filter'|safehtml|paragraph}
</div>
{if $preformat.notes neq ''}
<div id="news_notes" class="news_meta">{$preformat.notes|notifyfilters:'news.filter_hooks.articles.filter'|safehtml|paragraph}</div>
{/if}
{* display all associated pictures in full size in the footer of the pdf *}
{if $modvars.News.picupload_enabled AND $info.pictures gt 0}
<div class="news_pictures">
    <h4>{gt text='Article pictures'}</h4>
    {section name=counter start=0 loop=$info.pictures step=1}
    <img src="{$modvars.News.picupload_uploaddir}/pic_sid{$info.sid}-{$smarty.section.counter.index}-norm.jpg" alt="{gt text='Picture %1$s for %2$s' tag1=$smarty.section.counter.index tag2=$info.title}" /><br />
    {/section}
</div>
{/if}