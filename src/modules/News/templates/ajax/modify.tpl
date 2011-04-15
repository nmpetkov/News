{checkpermission component='News::' instance="$item.cr_uid::$item.sid" level='ACCESS_DELETE' assign='mayDelete'}
{if $modvars.News.enableattribution}{pageaddvar name="javascript" value="javascript/helpers/Zikula.itemlist.js"}{/if}

<h2>{gt text='Edit news article'}: {$item.title|safetext}</h2>

<form id="news_user_newform" class="z-form" action="{modurl modname='News' type='admin' func='update'}" method="post" enctype="{if $accesspicupload AND $modvars.News.picupload_enabled}multipart/form-data{else}application/x-www-form-urlencoded{/if}">
    <div >
        <input type="hidden" name="csrftoken" value="{insert name='csrftoken'}" />
        <input type="hidden" name="page" value="{$page|safetext}" />
        <input type="hidden" name="story[sid]" value="{$item.sid|safetext}" />
        <input type="hidden" name="story[published_status]" value="{$item.published_status|safetext}" />
        <input type="hidden" name="story[pictures]" value="{$item.pictures}" />
        {if $formattedcontent eq 1}
        <input type="hidden" name="story[hometextcontenttype]" value="1" />
        <input type="hidden" name="story[bodytextcontenttype]" value="1" />
        {/if}

        <fieldset>
            <legend>{gt text='Title' domain='module_news'}</legend>

            <div class="z-formrow">
                <label for="news_title">{gt text='Title text' domain='module_news'}<span class="z-mandatorysym">*</span></label>
                <input id="news_title" class="z-form-text" name="story[title]" type="text" size="32" maxlength="255" value="{$item.title|safetext}" />
            </div>

            <div class="z-formrow">
                <label for="news_urltitle">{gt text='Permalink URL' domain='module_news'}</label>
                <input id="news_urltitle" class="z-form-text" name="story[urltitle]" type="text" size="32" maxlength="255" value="{$item.urltitle|safetext}" />
                <em class="z-sub z-formnote">{gt text='(Generated automatically if left blank)' domain='module_news'}</em>
            </div>

            {if $modvars.News.enablecategorization}
            <div class="z-formrow">
                <label>{gt text='Category' domain='module_news'}</label>
                {gt text='Choose category' assign='lblDef'}
                {nocache}
                {foreach from=$catregistry key='property' item='category'}
                {array_field_isset array=$item.__CATEGORIES__ field=$property assign='catExists'}
                {if $catExists}
                {array_field_isset array=$item.__CATEGORIES__.$property field='id' returnValue=1 assign='selectedValue'}
                {else}
                {assign var='selectedValue' value='0'}
                {/if}
                <div class="z-formnote">{selector_category category=$category name="story[__CATEGORIES__][$property]" field='id' selectedValue=$selectedValue defaultValue='0' defaultText=$lblDef}</div>
                {/foreach}
                {/nocache}
            </div>
            {/if}

            {if $modvars.ZConfig.multilingual}
            <div class="z-formrow">
                <label for="news_language">{gt text='Language(s) for which article should be displayed' domain='module_news'}</label>
                {html_select_languages id="news_language" name="story[language]" installed=1 all=1 selected=$item.language|default:''}
            </div>
            {/if}
        </fieldset>

        <fieldset class="z-linear">
            <legend>{gt text='Article' domain='module_news'}</legend>
            <div class="z-formrow">
                {if $formattedcontent eq 0}
                <div class="z-warningmsg">{gt text='Permitted HTML tags' domain='module_news'}: {news_allowedhtml}</div>
                {/if}
                <div class="z-informationmsg" style='margin-bottom:0 !important;'><span class="z-mandatorysym">*</span> {gt text='You must enter either <strong>teaser text</strong> or <strong>body text</strong>.' domain='module_news'}</div>
            </div>
            <div class="z-formrow">
                <label for="news_hometext"><strong>{gt text='Index page teaser text' domain='module_news'}</strong></label>
                <textarea id="news_hometext" class="z-form-text" name="story[hometext]" cols="40" rows="10">{$item.hometext|safetext}</textarea>
                <span id="news_hometext_remaining" class="z-formnote z-sub">{gt text='(Limit: %s characters)' tag1='4,294,967,295' domain='module_news'}</span>
            </div>

            {if $formattedcontent eq 0}
            <div class="z-formrow">
                <label for="news_hometextcontenttype">{gt text='Index page teaser format' domain='module_news'}</label>
                <select id="news_hometextcontenttype" name="story[hometextcontenttype]">
                    <option value="0"{if $item.hometextcontenttype eq 0} selected="selected"{/if}>{gt text='Plain text' domain='module_news'}</option>
                    <option value="1"{if $item.hometextcontenttype eq 1} selected="selected"{/if}>{gt text='Text formatted with mark-up language' domain='module_news'}</option>
                </select>
            </div>
            {/if}

            <div class="z-formrow">
                <label for="news_bodytext"><strong>{gt text='Article body text' domain='module_news'}</strong></label>
                <textarea id="news_bodytext" class="z-form-text" name="story[bodytext]" cols="40" rows="10">{$item.bodytext|safetext}</textarea>
                <span id="news_bodytext_remaining" class="z-formnote z-sub">{gt text='(Limit: %s characters)' tag1='4,294,967,295' domain='module_news'}</span>
            </div>

            {if $formattedcontent eq 0}
            <div class="z-formrow">
                <label for="news_bodytextcontenttype">{gt text='Article body format' domain='module_news'}</label>
                <select id="news_bodytextcontenttype" name="story[bodytextcontenttype]">
                    <option value="0"{if $item.bodytextcontenttype eq 0} selected="selected"{/if}>{gt text='Plain text' domain='module_news'}</option>
                    <option value="1"{if $item.bodytextcontenttype eq 1} selected="selected"{/if}>{gt text='Text formatted with mark-up language' domain='module_news'}</option>
                </select>
            </div>
            {/if}

            <div class="z-formrow">
                <label for="news_notes"><a id="news_notes_collapse" href="javascript:void(0);"><span id="news_notes_showhide">{gt text='Show' domain='module_news'}</span> {gt text='Footnote' domain='module_news'}</a></label>
                <p id="news_notes_details">
                    <textarea id="news_notes" class="z-form-text" name="story[notes]" cols="40" rows="10">{$item.notes|safetext}</textarea>
                    <span id="news_notes_remaining" class="z-formnote z-sub">{gt text='(Limit: %s characters)' tag1='65,536' domain='module_news'}</span>
                </p>
            </div>
        </fieldset>

        <fieldset>
            <legend><a id="news_publication_collapse" href="javascript:void(0);"><span id="news_publication_showhide">{gt text='Show' domain='module_news'}</span> {gt text='Publishing options' domain='module_news'}</a></legend>
            <div id="news_publication_details">
                <div class="z-formrow">
                    <label for="news_hideonindex">{gt text='Publish on news index page' domain='module_news'}</label>
                    <input id="news_hideonindex" name="story[hideonindex]" type="checkbox" value="1" {if $item.hideonindex eq 0}checked="checked" {/if}/>
                </div>
                <div class="z-formrow">
                    <label for="news_weight">{gt text='Article weight' domain='module_news'}</label>
                    <div>
                        <input id="news_weight" name="story[weight]" type="text" size="10" maxlength="10" value="{$item.weight|safetext}" />
                    </div>
                </div>
                <div class="z-formrow">
                    <label for="news_unlimited">{gt text='No time limit' domain='module_news'}</label>
                    <input id="news_unlimited" name="story[unlimited]" type="checkbox" value="1" {if $item.unlimited eq 1}checked="checked" {/if}/>
                </div>

                <div id="news_expiration_details">
                    <div class="z-formrow">
                        <label>{gt text='Start date' domain='module_news'}</label>
                        <div>
                            <input id="news_from" class="datepicker" name="story[from]" type="text" size="18" value="{$item.from}" />
                        </div>
                    </div>
                    <div class="z-formrow">
                        <label for="news_tonolimit">{gt text='No end date' domain='module_news'}</label>
                        <input id="news_tonolimit" name="story[tonolimit]" type="checkbox" value="1" {if $item.tonolimit eq 1}checked="checked" {/if}/>
                    </div>
                    <div id="news_expiration_date">
                        <div class="z-formrow">
                            <label>{gt text='End date' domain='module_news'}</label>
                            <div>
                                <input id="news_to" class="datepicker" name="story[to]" type="text" size="18" value="{$item.to}" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="z-formrow">
                    <label for="news_disallowcomments">{gt text='Allow comments on this article' domain='module_news'}</label>
                    <input id="news_disallowcomments" name="story[disallowcomments]" type="checkbox" value="1" {if $item.disallowcomments eq 0}checked="checked" {/if}/>
                </div>
            </div>
        </fieldset>
        <script type="text/javascript">
            // <![CDATA[
            var thisbaseurl='{{$baseurl}}';
            var dpPars = {
                use24hrs:true,
                icon:thisbaseurl+'modules/News/images/calendar.png',
                timePicker:true,
                timePickerAdjacent:true
            }
            var lang = '{{$lang}}';
            if (Control.DatePicker.Language[lang]) {
                if (!Control.DatePicker.Locale[lang+'_iso8601']) {
                    with (Control.DatePicker) Locale[lang+'_iso8601'] = i18n.createLocale('iso8601', lang);
                }
                dpPars.locale=lang+'_iso8601';
            } else {
                dpPars.locale='en_iso8601';
            }
            new Control.DatePicker('news_from', dpPars);
            new Control.DatePicker('news_to', dpPars);
            // ]]>
        </script>
        {if $modvars.News.enableattribution}
        <fieldset>
            <legend><a id="news_attributes_collapse" href="javascript:void(0);"><span id="news_attributes_showhide">{gt text='Show'}</span> {gt text='Article attributes'}</a></legend>
            <div id="news_attributes_details">
                {include file='user/attribute_subform.tpl'}
            </div>
        </fieldset>
        {/if}

        {notifydisplayhooks eventname='news.hook.articles.ui.edit' area='modulehook_area.news.articles' subject=$item id=$item.sid caller="News"}

        <div class="z-buttonrow z-buttons z-center">
            <a href="javascript:void(0);" onclick="editnews_save('update');" class="z-btgreen">{img src='button_ok.png' modname='core' set='icons/extrasmall' __alt='Save' __title='Save your changes' } {gt text='Save' domain='module_news'}</a>
            <a href="javascript:void(0);" onclick="editnews_save('pending');">{img modname='core' src='clock.png' set='icons/extrasmall' __alt='Mark as pending' __title='Mark this article as pending'} {gt text='Mark as pending' domain='module_news'}</a>
            {if $mayDelete}
            <a href="javascript:void(0);" onclick="editnews_save('delete');" class="z-btred">{img modname='core' src='editdelete.png' set='icons/extrasmall' __alt='Delete' __title='Delete this article'} {gt text='Delete' domain='module_news'}</a>
            {/if}
            <a href="javascript:void(0);" onclick="editnews_cancel();" class="z-btred">{img modname='core' src='button_cancel.png' set='icons/extrasmall' __alt='Cancel' __title='Cancel'} {gt text='Cancel' domain='module_news'}</a>
            &nbsp;<img id="news_savenews" src="{$baseurl}images/ajax/circle-ball-dark-antialiased.gif" alt="" />
        </div>
    </div>
</form>