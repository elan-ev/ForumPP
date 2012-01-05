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
            items: ">*.movable",
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
            items: ">*:not(.sort-disabled)",
            connectWith: 'tbody.sortable',
            helper: function(e, ui) {
                ui.children().each(function() {
                    $(this).width($(this).width());
                });
                return ui;
            },

            stop: function() {
                // iterate over each category and get the areas there
                var areas = {};
                areas['areas'] = {};
                jQuery('#sortable_areas').find('table').each(function() {
                    var category_id = jQuery(this).attr('data-category-id');
                    
                    areas['areas'][category_id] = {}
                    
                    jQuery(this).find('tr').each(function() {
                        var area_id = jQuery(this).attr('data-area-id');
                        areas['areas'][category_id][area_id] = area_id;
                    })
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
            // hide the area in the dom
            jQuery('tr[data-area-id='+ STUDIP.ForumPP.current_area_id +']').remove();
            jQuery('#question').hide();

            // ajax call to make the deletion permanent
            jQuery.ajax(STUDIP.URLHelper.getURL('plugins.php/forumpp/index/delete_entry/'
                + STUDIP.ForumPP.current_area_id), {
                success: function(html) {
                    jQuery('#message_area').html(html);
                }
            });

            STUDIP.ForumPP.current_area_id = null;
        }

        if (STUDIP.ForumPP.current_category_id) {
            // hide the table in the dom
            jQuery('table[data-category-id='+ STUDIP.ForumPP.current_category_id +']').fadeOut();
            jQuery('#question').hide();

            // move all areas to the default category
            jQuery('table[data-category-id='+ STUDIP.ForumPP.current_category_id +'] tr.movable').each(function() {
               jQuery('table[data-category-id=Allgemein]').append(jQuery(this));
            });

            // ajax call to make the deletion permanent
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
    },
    
    editCategoryName: function(category_id) {
        jQuery('table[data-category-id=' + category_id + '] span.heading').hide();
        jQuery('table[data-category-id=' + category_id + '] span.heading_edit').show();
    },

    cancelEditCategoryName: function(category_id) {
        jQuery('table[data-category-id=' + category_id + '] span.heading_edit').hide();
        jQuery('table[data-category-id=' + category_id + '] span.heading').show();

        // reset the input field with the unchanged name
        jQuery('table[data-category-id=' + category_id + '] span.heading_edit input[type=text]').val(
            jQuery('table[data-category-id=' + category_id + '] span.category_name').text().trim()
        );
    },
    
    saveCategoryName: function(category_id) {
        var name = {};
        name['name'] = jQuery('table[data-category-id=' + category_id + '] span.heading_edit input[type=text]').val();

        // display the new name immediately
        jQuery('table[data-category-id=' + category_id + '] span.category_name').text(name['name']);

        jQuery('table[data-category-id=' + category_id + '] span.heading_edit').hide();
        jQuery('table[data-category-id=' + category_id + '] span.heading').show();

        jQuery.ajax(STUDIP.URLHelper.getURL('plugins.php/forumpp/index/edit_category/' + category_id), {
            type: 'POST',
            data: name
        });
    },


    editAreaName: function(area_id) {
        jQuery('tr[data-area-id=' + area_id + '] span.areaname').parent().hide();
        jQuery('tr[data-area-id=' + area_id + '] span.areaname_edit').show();
    },

    cancelEditAreaName: function(area_id) {
        jQuery('tr[data-area-id=' + area_id + '] span.areaname_edit').hide();
        jQuery('tr[data-area-id=' + area_id + '] span.areaname').parent().show();
        
        // reset the input field with the unchanged name
        jQuery('tr[data-area-id=' + area_id + '] span.areaname_edit input[type=text]').val(
            jQuery('tr[data-area-id=' + area_id + '] span.areaname').text().trim()
        );
    },
    
    saveAreaName: function(area_id) {
        var name = {};
        name['name'] = jQuery('tr[data-area-id=' + area_id + '] span.areaname_edit input[type=text]').val();

        // display the new name immediately
        jQuery('tr[data-area-id=' + area_id + '] span.areaname').text(name['name']);

        jQuery('tr[data-area-id=' + area_id + '] span.areaname_edit').hide();
        jQuery('tr[data-area-id=' + area_id + '] span.areaname').parent().show();

        jQuery.ajax(STUDIP.URLHelper.getURL('plugins.php/forumpp/index/edit_area/' + area_id), {
            type: 'POST',
            data: name
        });
    }
}