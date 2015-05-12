<h3>{$title}</h3>
<style>
.newsarticles_stories_odd {
    background: #fff;
    padding: 8px;
}
.newsarticles_stories_even {
    background: #eee;
    padding: 8px;
}
.newsarticles_stories_img {
    width:75px;
    text-align:center;
    float:{{$News.picupload_index_float}};
}
.newsarticles_stories_img img {
    max-width:95%;
    max-height:75px;
    border:1px solid #333;
    border-radius:5px; 
    -moz-border-radius:5px; 
    -webkit-border-radius:5px;
}
.newsarticles_stories_story {
    /* margin is width of img block + 10px */
    margin-{{$News.picupload_index_float}}:85px;
}
</style>
{if $stories}
    {foreach from=$stories item='story'}
    <div class="z-clearfix {cycle values='newsarticles_stories_odd,newsarticles_stories_even'}">
        {if $story.readperm}
            {if $useshorturls}
                {modurl modname='News' type='user' func='display' sid=$story.sid from=$story.from urltitle=$story.urltitle assign='storylink'}
            {else}
                {modurl modname='News' type='user' func='display' sid=$story.sid assign='storylink'}
            {/if}
        {/if}
        {if $displayStoryImage AND $News.picupload_enabled AND $story.pictures gt 0}
        <div class="newsarticles_stories_img">
            <a href="{$storylink}"><img src="{$News.picupload_uploaddir}/pic_sid{$story.sid}-0-thumb.jpg" alt="{gt text='Picture %1$s for %2$s' tag1='0' tag2=$story.title}" /></a>
        </div>
        {/if}
        <div class="newsarticles_stories_story">
        <strong>
        {if $story.readperm}<a href="{$storylink}">{/if}
        {if $story.dispnewimage}{img modname='core' set=$newimageset src=$newimagesrc __alt='Create new article' __title='Create new article'}{/if}
        {$story.title|safehtml}
        {if $story.titlewrapped}{$titlewraptext|safehtml}{/if}
        {if $story.readperm}</a>{/if}
        </strong>

        {* Optional additional info on the story *}
        <div class="z-sub">
        {if $dispinfo}
            {if $dispdate}<img src='modules/News/images/calendar_777777_12.png' /> {$story.from|dateformat:$dateformat} {/if}
            {if $dispuname}<img src='modules/News/images/pencil_777777_12.png' /> {$story.uname|profilelinkbyuname} {/if}
            {if $dispreads}<img src='modules/News/images/eye_777777_12.png' /> {gt text='%s pageview' plural='%s pageviews' count=$story.counter tag1=$story.counter} {/if}
            {if $dispcomments}<img src='modules/News/images/comments_777777_12.png' /> {gt text='%s comment' plural='%s comments' count=$story.comments tag1=$story.comments}{/if}
            {if $story.topicsearchurl neq ''} {* only display category link if other items are also displayed *}
            <img src='modules/News/images/tags_777777_12.png' /> <a href="{$story.topicsearchurl}">{$story.topicname|safehtml}</a>
            {/if}
        {/if}
        </div>

        {* Optional hometext display *}
        {if $disphometext}
        <div class="content_newsarticles_hometext">
            {if $story.hometextwrapped}
                {$story.hometext|notifyfilters:'news.filter_hooks.articles.filter'|truncatehtml:$maxhometextlength:''|safehtml|paragraph}
            {else}
                {$story.hometext|notifyfilters:'news.filter_hooks.articles.filter'|safehtml|paragraph}
            {/if}
            {if ($story.hometextwrapped || strlen($story.bodytext) > 0) && $story.readperm}
            <div class="z-buttons">
                <a href="{$storylink}" class="z-btblue">{$hometextwraptext|safehtml}</a>
            </div>
            {/if}
        </div>
        {/if}

        </div>
    </div>
    {/foreach}
{else}
<p>{gt text='No articles published recently.'}</p>
{/if}

{checkpermission component='News::' instance='::' level=ACCESS_COMMENT assign='submitauth'}
{if $linktosubmit && $submitauth}
<p><a href="{modurl modname='News' type='user' func='newitem'}">{img modname='core' set='icons/extrasmall' src='edit_add.png' __alt='Submit an article'}&nbsp;{gt text='Submit an article'}</a></p>
{/if}