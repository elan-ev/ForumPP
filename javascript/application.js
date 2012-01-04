STUDIP.ForumPP = {
    deleteAreaTemplate : null,
    deleteCategoryTemplate: null,
    current_area_id: null,
    current_category_id: null,

    initAreas: function() {
        // show icons if mouse is over td
        jQuery('td.areaentry').bind('mouseover', function() {
            jQuery(this).find('span.action-icons').show();
        });

        jQuery('td.areaentry').bind('mouseout', function() {
            jQuery(this).find('span.action-icons').hide();
        });

        // bind icons
        jQuery('img.edit-area').bind('click', function() {
            //jQuery(this).siblings().css('border', '6px solid red');
        });

        jQuery('img.delete-area').bind('click', function() {
            STUDIP.ForumPP.showDialog(this);
        });

        // bind click events on add-area at bottom row of each category
        jQuery('div.add_area').bind('click', function() {
            STUDIP.ForumPP.addArea(this);
        })

        // make categories and areas sortable
        jQuery('#sortable_areas').sortable({
            axis: 'y',
            stop: function() {
                var categories = {};
                categories['categories'] = {};
                jQuery(this).find('table').each(function() {
                    var name = jQuery(this).attr('data-category-id');
                    categories['categories'][name] = name;
                });

                jQuery.ajax({
                    type: 'POST',
                    url: STUDIP.URLHelper.getURL('plugins.php/forumpp/index/savecats'),
                    data: categories
                });
            }            
        });
        
        jQuery('tbody.sortable').sortable({
            axis: 'y',
            helper: function(e, ui) {
                ui.children().each(function() {
                    $(this).width($(this).width());
                });
                return ui;
            },

            stop: function() {
                var areas = {};
                areas['areas'] = {};
                jQuery(this).find('tr').each(function() {
                    var name = jQuery(this).attr('data-area-id');
                    areas['areas'][name] = name;
                });

                jQuery.ajax({
                    type: 'POST',
                    url: STUDIP.URLHelper.getURL('plugins.php/forumpp/index/saveareas'),
                    data: areas
                });
            }            
        }).disableSelection();        

        // compile template
        STUDIP.ForumPP.deleteAreaTemplate     = _.template(jQuery('#question_delete_area').text());
        STUDIP.ForumPP.deleteCategoryTemplate = _.template(jQuery('#question_delete_category').text());
    },

    approveDelete: function() {
        if (STUDIP.ForumPP.current_area_id) {
            jQuery('tr[data-area-id='+ STUDIP.ForumPP.current_area_id +']').remove();
            jQuery('#question').hide();

            jQuery.ajax(STUDIP.URLHelper.getURL('plugins.php/forumpp/index/delete_entry/'
                + STUDIP.ForumPP.current_area_id), {
                success: function(html) {
                    jQuery('#message_area').html(html);
                }
            });

            STUDIP.ForumPP.current_area_id = null;
        }

        if (STUDIP.ForumPP.current_category_id) {
            jQuery('table[data-category-id='+ STUDIP.ForumPP.current_category_id +']').fadeOut();
            jQuery('#question').hide();
            
            jQuery.ajax(STUDIP.URLHelper.getURL('plugins.php/forumpp/index/remove_category/'
                + STUDIP.ForumPP.current_category_id), {
                success: function(html) {
                    jQuery('#message_area').html(html);
                }
            });

            STUDIP.ForumPP.current_category_id = null;
        }
    },

    disapproveDelete: function() {
        jQuery('#question').hide();
    },

    showDialog: function(element) {
        jQuery('#modalquestion').text(STUDIP.ForumPP.deleteAreaTemplate({
            area: jQuery(element).parent().parent().find('span.areaname').text()
        }));

        STUDIP.ForumPP.current_area_id = jQuery(element).attr('data-area-id');
        jQuery('#question').show();
    },

    addArea: function(element) {
        this.cancelAddArea();
        jQuery(element).hide().parent().find("form.add_area_form").show();
    },

    cancelAddArea: function() {
        jQuery('form.add_area_form').hide();
        jQuery('div.add_area').show();
    },

    deleteCategory: function(category_id, name) {
        jQuery('#modalquestion').text(STUDIP.ForumPP.deleteCategoryTemplate({
            category: name
        }));

        STUDIP.ForumPP.current_category_id = category_id;
        jQuery('#question').show();
    }
}