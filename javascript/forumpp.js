/*global window, $, jQuery, document, _ */
/* ------------------------------------------------------------------------
 * the global STUDIP namespace
 * ------------------------------------------------------------------------ */
var STUDIP = STUDIP || {};

STUDIP.ForumPP = {
    deleteAreaTemplate : null,
    deleteCategoryTemplate: null,
    current_area_id: null,
    current_category_id: null,
    seminar_id: null,

    init: function () {
        // bind click events on add-area at bottom row of each category
        jQuery('div.add_area').bind('click', function () {
            STUDIP.ForumPP.addArea(this);
        });
        
        jQuery('#new_entry_button button').bind('click', function() {
            STUDIP.ForumPP.newEntry();
            return false;
        });

        // make categories and areas sortable
        jQuery('#sortable_areas').sortable({
            axis: 'y',
            items: ">*.movable",
            handle: 'td.handle',
            stop: function () {
                var categories = {};
                categories.categories = {};
                jQuery(this).find('table').each(function () {
                    var name = jQuery(this).attr('data-category-id');
                    categories.categories[name] = name;
                });

                jQuery.ajax({
                    type: 'POST',
                    url: STUDIP.URLHelper.getURL('plugins.php/forumpp/index/savecats?cid=' + STUDIP.ForumPP.seminar_id),
                    data: categories
                });
            }
        });

        jQuery('tbody.sortable').sortable({
            axis: 'y',
            items: ">*:not(.sort-disabled)",
            connectWith: 'tbody.sortable',
            handle: 'img.handle',
            helper: function (e, ui) {
                ui.children().each(function () {
                    jQuery(this).width(jQuery(this).width());
                });
                return ui;
            },

            stop: function () {
                // iterate over each category and get the areas there
                var areas = {};
                areas.areas = {};
                jQuery('#sortable_areas').find('table').each(function () {
                    var category_id = jQuery(this).attr('data-category-id');

                    areas.areas[category_id] = {};

                    jQuery(this).find('tr').each(function () {
                        var area_id = jQuery(this).attr('data-area-id');
                        areas.areas[category_id][area_id] = area_id;
                    });
                });

                jQuery.ajax({
                    type: 'POST',
                    url: STUDIP.URLHelper.getURL('plugins.php/forumpp/index/saveareas?cid=' + STUDIP.ForumPP.seminar_id),
                    data: areas
                });
            }
        });

        // compile template
        STUDIP.ForumPP.deleteAreaTemplate     = _.template(jQuery('#question_delete_area').text());
        STUDIP.ForumPP.deleteCategoryTemplate = _.template(jQuery('#question_delete_category').text());
    },

    insertSmiley: function(textarea_id, element) {
        jQuery('textarea[data-textarea=' + textarea_id + ']').insertAtCaret(jQuery(element).attr('data-smiley'));
    },
    
    newEntry: function() {
        jQuery('#new_entry_button').hide();
        jQuery('#new_entry_box').show();
        jQuery('body').animate({scrollTop: jQuery(document).height()}, 'slow');
        jQuery('html').animate({scrollTop: jQuery(document).height()}, 'slow');
    },

    approveDelete: function () {
        if (STUDIP.ForumPP.current_area_id) {
            // hide the area in the dom
            jQuery('tr[data-area-id=' + STUDIP.ForumPP.current_area_id + ']').remove();
            jQuery('#question').hide();

            // ajax call to make the deletion permanent
            jQuery.ajax(STUDIP.URLHelper.getURL('plugins.php/forumpp/index/delete_entry/'
                + STUDIP.ForumPP.current_area_id + '?cid=' + STUDIP.ForumPP.seminar_id), {
                success: function (html) {
                    jQuery('#message_area').html(html);
                }
            });

            STUDIP.ForumPP.current_area_id = null;
        }

        if (STUDIP.ForumPP.current_category_id) {
            // hide the table in the dom
            jQuery('table[data-category-id=' + STUDIP.ForumPP.current_category_id + ']').fadeOut();
            jQuery('#question').hide();

            // move all areas to the default category
            jQuery('table[data-category-id=' + STUDIP.ForumPP.current_category_id + '] tr.movable').each(function () {
                jQuery('table[data-category-id=' + STUDIP.ForumPP.seminar_id + ']').append(jQuery(this));
            });

            // ajax call to make the deletion permanent
            jQuery.ajax(STUDIP.URLHelper.getURL('plugins.php/forumpp/index/remove_category/'
                + STUDIP.ForumPP.current_category_id + '?cid=' + STUDIP.ForumPP.seminar_id), {
                success: function (html) {
                    jQuery('#message_area').html(html);
                }
            });

            STUDIP.ForumPP.current_category_id = null;
        }
    },

    disapproveDelete: function () {
        jQuery('#question').hide();
    },

    deleteArea: function (element, area_id) {
        jQuery('#modalquestion').text(STUDIP.ForumPP.deleteAreaTemplate({
            area: jQuery(element).parent().parent().find('span.areaname').text()
        }));

        STUDIP.ForumPP.current_area_id = area_id;

        jQuery('#question').show();
    },

    addArea: function (element) {
        this.cancelAddArea();
        jQuery(element).parent().parent().hide().parent().find("tr.new_area").show();
    },

    cancelAddArea: function () {
        jQuery('tr.new_area').hide();
        jQuery('tr.add_area').show();
    },

    deleteCategory: function (category_id, name) {
        jQuery('#modalquestion').text(STUDIP.ForumPP.deleteCategoryTemplate({
            category: name
        }));

        STUDIP.ForumPP.current_category_id = category_id;
        jQuery('#question').show();
    },

    editCategoryName: function (category_id) {
        jQuery('table[data-category-id=' + category_id + '] span.heading').hide();
        jQuery('table[data-category-id=' + category_id + '] span.heading_edit').show();
    },

    cancelEditCategoryName: function (category_id) {
        jQuery('table[data-category-id=' + category_id + '] span.heading_edit').hide();
        jQuery('table[data-category-id=' + category_id + '] span.heading').show();

        // reset the input field with the unchanged name
        jQuery('table[data-category-id=' + category_id + '] span.heading_edit input[type=text]').val(
            jQuery('table[data-category-id=' + category_id + '] span.category_name').text().trim()
        );
    },

    saveCategoryName: function (category_id) {
        var name = {};
        name.name = jQuery('table[data-category-id=' + category_id + '] span.heading_edit input[type=text]').val();

        // display the new name immediately
        jQuery('table[data-category-id=' + category_id + '] span.category_name').text(name.name);

        jQuery('table[data-category-id=' + category_id + '] span.heading_edit').hide();
        jQuery('table[data-category-id=' + category_id + '] span.heading').show();

        jQuery.ajax(STUDIP.URLHelper.getURL('plugins.php/forumpp/index/edit_category/' + category_id + '?cid=' + STUDIP.ForumPP.seminar_id), {
            type: 'POST',
            data: name
        });
    },


    editArea: function (area_id) {
        jQuery('tr[data-area-id=' + area_id + '] span.areadata').hide();
        jQuery('tr[data-area-id=' + area_id + '] span.areaname_edit').show();
        jQuery('tr[data-area-id=' + area_id + '] span.areadata').parent().css('height', 'auto');
    },

    cancelEditArea: function (area_id) {
        jQuery('tr[data-area-id=' + area_id + '] span.areaname_edit').hide();
        jQuery('tr[data-area-id=' + area_id + '] span.areadata').show();

        // reset the input field with the unchanged name
        jQuery('tr[data-area-id=' + area_id + '] span.areaname_edit input[name=name]').val(
            jQuery('tr[data-area-id=' + area_id + '] span.areaname').text().trim()
        );

        jQuery('tr[data-area-id=' + area_id + '] span.areaname_edit textarea[name=content]').val(
            jQuery('tr[data-area-id=' + area_id + '] div.areacontent').text().trim()
        );
            
            jQuery('tr[data-area-id=' + area_id + '] span.areadata').parent().css('height', '');
    },

    saveArea: function (area_id) {
        var name = {};
        name.name = jQuery('tr[data-area-id=' + area_id + '] span.areaname_edit input[type=text]').val();
        name.content = jQuery('tr[data-area-id=' + area_id + '] span.areaname_edit textarea').val();

        // display the new name immediately
        jQuery('tr[data-area-id=' + area_id + '] span.areaname').text(name.name);
        jQuery('tr[data-area-id=' + area_id + '] div.areacontent').text(name.content);

        jQuery('tr[data-area-id=' + area_id + '] span.areaname_edit').hide();
        jQuery('tr[data-area-id=' + area_id + '] span.areaname').parent().parent().show();

        jQuery.ajax(STUDIP.URLHelper.getURL('plugins.php/forumpp/index/edit_area/' + area_id + '?cid=' + STUDIP.ForumPP.seminar_id), {
            type: 'POST',
            data: name
        });
    },

    saveEntry: function(topic_id) {
        jQuery('span[data-edit-topic=' + topic_id +'] input[name=name]').attr('data-reset',
            jQuery('span[data-edit-topic=' + topic_id +'] input[name=name]').val()
        );
            
        jQuery('span[data-edit-topic=' + topic_id +'] textarea[name=content]').attr('data-reset',
            jQuery('span[data-edit-topic=' + topic_id +'] textarea[name=content]').val()
        );

        jQuery.ajax(STUDIP.URLHelper.getURL('plugins.php/forumpp/index/update_entry/' + topic_id + '?cid=' + STUDIP.ForumPP.seminar_id), {
            type: 'POST',
            data: jQuery('form[data-topicid='+ topic_id +']').serializeObject(),
            success: function (data) {
                var json = jQuery.parseJSON(data);
                // set the new name and content
                jQuery('span[data-topic-name=' + topic_id +']').html(json.name);
                jQuery('span[data-topic-content=' + topic_id +']').html(json.content);
                
                // hide the other stuff
                jQuery('div[id*=preview]').parent().hide();
                jQuery('span[data-edit-topic*=]').hide();
                jQuery('span[data-show-topic*=]').show();
                
            }
        });
    },
    
    editEntry: function (topic_id) {
        jQuery('div[id*=preview]').parent().hide();
        jQuery('span[data-edit-topic*=]').hide();
        jQuery('span[data-show-topic*=]').show();
        
        jQuery('span[data-edit-topic=' + topic_id +']').show();
        jQuery('span[data-show-topic=' + topic_id +']').hide();
    },
    
    cancelEditEntry: function (topic_id) {
        jQuery('div[id*=preview]').parent().hide();

        jQuery('span[data-edit-topic=' + topic_id +'] input[name=name]').val(
            jQuery('span[data-edit-topic=' + topic_id +'] input[name=name]').attr('data-reset')
        );

        jQuery('span[data-edit-topic=' + topic_id +'] textarea[name=content]').val(
            jQuery('span[data-edit-topic=' + topic_id +'] textarea[name=content]').attr('data-reset')
        );

        jQuery('span[data-edit-topic=' + topic_id +']').hide();
        jQuery('span[data-show-topic=' + topic_id +']').show();  
    },

    cancelNewEntry: function() {
        jQuery('#new_entry_button').show();
        jQuery('#new_entry_box').hide();
        
        jQuery('#new_entry_box textarea, #new_entry_box input[name=name]').val('');
        jQuery('#forumpp_new_entry').data('validator').reset();
        return false;
    },
    
    citeEntry: function(topic_id) {
        var name    = jQuery('span.username[data-profile=' + topic_id +']').text().trim();
        var title   = jQuery('span[data-edit-topic=' + topic_id +'] input[name=name]').val();

        if (title) {
            title = 'Re: ' + title;
            // sum the Re's and display them as Re^x:
            var count   = title.match(/Re:/g).length;       // number of Re: occurrences
            var matches = title.match(/Re:?\^(\d+):?/);     // check for occurrence of Re^x

            title = title.replace(/Re:\ ?/g, '');           // remove all simple Re:

            if (matches) {                                  // add the x of Re^x if any
                title = title.replace(matches[0], 'Re^' + (count + parseInt(matches[1])) + ':');
            } else {                                        // otherwise create a new one
                if (count > 1) {
                    title = 'Re^' + count + ': ' + title;
                } else {
                    title = 'Re: ' + title;
                }
            }
        }
      
        // add content from cited posting in [quote]-tags
        var content = '[quote=' + name + ']' + "\n"
            + jQuery('span[data-edit-topic=' + topic_id +'] textarea[name=content]').val()
            + "\n[/quote]"
        
        jQuery('#new_entry_box textarea').val(content);
        jQuery('#new_entry_box input[name=name]').val(title);
        STUDIP.ForumPP.newEntry();
    },
    
    forwardEntry: function(topic_id) {
        var title   = 'Re: ' + jQuery('span[data-edit-topic=' + topic_id +'] input[name=name]').val();
        var content = jQuery('span[data-edit-topic=' + topic_id +'] textarea[name=content]').val();
        var text    = 'Die Senderin/der Sender dieser Nachricht möchte Sie auf den folgenden Beitrag aufmerksam machen. '
                    + "\n\n" + 'Link zum Beitrag: ';
        
        STUDIP.ForumPP.postToUrl(STUDIP.URLHelper.getURL('sms_send.php'), {
            'message' :  text.toLocaleString()
                + STUDIP.URLHelper.getURL('plugins.php/forumpp/index/index/'
                + topic_id + '?cid=' + STUDIP.ForumPP.seminar_id + '&again=yes#' + topic_id)
                + "\n\n" + '**' + title + "**\n\n" + content + "\n\n",
            'sms_source_page' : 'plugins.php/forumpp/index/index/'
                + topic_id + '?cid=' + STUDIP.ForumPP.seminar_id + '#' + topic_id,
            'messagesubject': 'WG: ' + title
        });
    },

    postToUrl: function(path, params) {
        // create a form
        var form = jQuery('<form method="post" action="' + path + '" style="display: none">');
        for (var key in params) {
            jQuery(form).append('<textarea name="' + key + '">' + params[key] + '</textarea>');
        }

        // append it to the body-element
        jQuery('body').append(form);
        
        // submit it
        jQuery(form).submit();
    },

    moveThreadDialog: function (topic_id) {
        jQuery('#dialog_' + topic_id).dialog();
    },

    preview: function (text_element_id, preview_id) {
        var posting = {};
        posting.posting = jQuery('textarea[data-textarea=' + text_element_id + ']').val();

        jQuery.ajax(STUDIP.URLHelper.getURL('plugins.php/forumpp/index/preview?cid=' + STUDIP.ForumPP.seminar_id), {
            type: 'POST',
            data: posting,
            success: function (html) {
                jQuery('#' + preview_id).html(html);
                jQuery('#' + preview_id).parent().show();
            }
        });
    },
    
    loadAction: function(element, action) {
        jQuery(element).load(STUDIP.URLHelper.getURL('plugins.php/forumpp/index/'
            + action + '?cid=' + STUDIP.ForumPP.seminar_id))
    },
    
    disableTour: function() {
        jQuery.ajax(STUDIP.URLHelper.getURL('plugins.php/forumpp/index/disable_tour'));
        jQuery('a.joyride-close-tip').click();
    },
    
    hideTour: function(id) {
        jQuery.ajax(STUDIP.URLHelper.getURL('plugins.php/forumpp/index/hide_tour/' + id));
    },
    
    closeTour: function() {
        jQuery('a.joyride-close-tip:visible').click();
    }
};


// TODO: make TIC and add this to the Stud.IP-Core
/**
 * found at stackoverflow.com
 * http://stackoverflow.com/questions/946534/insert-text-into-textarea-with-jquery/946556#946556
 */
jQuery.fn.extend({
    insertAtCaret: function (myValue) {
        return this.each(function (i) {
            if (document.selection) {
                //For browsers like Internet Explorer
                this.focus();
                var sel = document.selection.createRange();
                sel.text = myValue;
                this.focus();
            } else if (this.selectionStart || this.selectionStart === '0') {
                //For browsers like Firefox and Webkit based
                var startPos = this.selectionStart;
                var endPos = this.selectionEnd;
                var scrollTop = this.scrollTop;
                this.value = this.value.substring(0, startPos) + myValue
                    + this.value.substring(endPos, this.value.length);
                this.focus();
                this.selectionStart = startPos + myValue.length;
                this.selectionEnd = startPos + myValue.length;
                this.scrollTop = scrollTop;
            } else {
                this.value += myValue;
                this.focus();
            }
        });
    }
});

/** 
 * Thanks to Tobias Cohen for this function
 * http://stackoverflow.com/questions/1184624/convert-form-data-to-js-object-with-jquery
 */
jQuery.fn.serializeObject = function() {
    var o = {};
    var a = this.serializeArray();
    jQuery.each(a, function() {
        if (o[this.name] !== undefined) {
            if (!o[this.name].push) {
                o[this.name] = [o[this.name]];
            }
            o[this.name].push(this.value || '');
        } else {
            o[this.name] = this.value || '';
        }
    });
    return o;
};