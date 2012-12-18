<h3 class="news_title">{$preview.title|safehtml}</h3>

<div id="news_body" class="news_body">
    {$preview.hometext|notifyfilters:'news.filter_hooks.articles.filter'|safehtml|paragraph}
    <hr />
    {$preview.bodytext|notifyfilters:'news.filter_hooks.articles.filter'|safehtml|paragraph}
</div>

{if $preview.notes neq ''}
<div id="news_notes" class="news_meta">{$preview.notes|notifyfilters:'news.filter_hooks.articles.filter'|safehtml|paragraph}</div>
{/if}
{if $preview.pictures > 0}
{include file='user/preview_pics.tpl'}
{/if}