/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @fileoverview    function used for index manipulation pages
 * @name            Table Structure
 *
 * @requires    jQuery
 * @requires    jQueryUI
 * @required    js/functions.js
 */

/**
 * Returns the array of indexes based on the index choice
 *
 * @param index_choice index choice
 */
function PMA_getIndexArray(index_choice)
{
    var source_array = null;

    switch (index_choice.toLowerCase()) {
    case 'primary':
        source_array = primary_indexes;
        break;
    case 'unique':
        source_array = unique_indexes;
        break;
    case 'index':
        source_array = indexes;
        break;
    case 'fulltext':
        source_array = fulltext_indexes;
        break;
    case 'spatial':
        source_array = spatial_indexes;
        break;
    default:
        return null;
    }
    return source_array;
}

/**
 * Hides/shows the inputs and submits appropriately depending
 * on whether the index type chosen is 'SPATIAL' or not.
 */
function checkIndexType()
{
    /**
     * @var Object Dropdown to select the index choice.
     */
    var $select_index_choice = $('#select_index_choice');
    /**
     * @var Object Dropdown to select the index type.
     */
    var $select_index_type = $('#select_index_type');
    /**
     * @var Object Table header for the size column.
     */
    var $size_header = $('#index_columns').find('thead tr th:nth-child(2)');
    /**
     * @var Object Inputs to specify the columns for the index.
     */
    var $column_inputs = $('select[name="index[columns][names][]"]');
    /**
     * @var Object Inputs to specify sizes for columns of the index.
     */
    var $size_inputs = $('input[name="index[columns][sub_parts][]"]');
    /**
     * @var Object Footer containg the controllers to add more columns
     */
    var $add_more = $('#index_frm').find('.add_more');

    if ($select_index_choice.val() == 'SPATIAL') {
        // Disable and hide the size column
        $size_header.hide();
        $size_inputs.each(function () {
            $(this)
                .prop('disabled', true)
                .parent('td').hide();
        });

        // Disable and hide the columns of the index other than the first one
        var initial = true;
        $column_inputs.each(function () {
            $column_input = $(this);
            if (! initial) {
                $column_input
                    .prop('disabled', true)
                    .parent('td').hide();
            } else {
                initial = false;
            }
        });

        // Hide controllers to add more columns
        $add_more.hide();
    } else {
        // Enable and show the size column
        $size_header.show();
        $size_inputs.each(function () {
            $(this)
                .prop('disabled', false)
                .parent('td').show();
        });

        // Enable and show the columns of the index
        $column_inputs.each(function () {
            $(this)
                .prop('disabled', false)
                .parent('td').show();
        });

        // Show controllers to add more columns
        $add_more.show();
    }

    if ($select_index_choice.val() == 'SPATIAL' ||
            $select_index_choice.val() == 'FULLTEXT') {
        $select_index_type.val('').prop('disabled', true);
    } else {
        $select_index_type.prop('disabled', false)
    }
}

/**
 * Sets current index information into form parameters.
 *
 * @param array  source_array Array containing index columns
 * @param string index_choice Choice of index
 *
 * @return void
 */
function PMA_setIndexFormParameters(source_array, index_choice)
{
    if (index_choice == 'index') {
        $('input[name="indexes"]').val(JSON.stringify(source_array));
    } else {
        $('input[name="' + index_choice + '_indexes"]').val(JSON.stringify(source_array));
    }
}

/**
 * Removes a column from an Index.
 *
 * @param string col_index Index of column in form
 *
 * @return void
 */
function PMA_removeColumnFromIndex(col_index)
{
    // Get previous index details.
    var previous_index = $('select[name="field_key[' + col_index + ']"]')
        .attr('data-index');
    if (previous_index.length) {
        previous_index = previous_index.split(',');
        var source_array = PMA_getIndexArray(previous_index[0]);
        if (source_array == null) {
            return;
        }

        // Remove column from index array.
        var source_length = source_array[previous_index[1]].columns.length;
        for (var i=0; i<source_length; i++) {
            if (source_array[previous_index[1]].columns[i].col_index == col_index) {
                source_array[previous_index[1]].columns.splice(i, 1);
            }
        }

        // Remove index completely if no columns left.
        if (source_array[previous_index[1]].columns.length === 0) {
            source_array.splice(previous_index[1], 1);
        }

        // Update current index details.
        $('select[name="field_key[' + col_index + ']"]').attr('data-index', '');
        // Update form index parameters.
        PMA_setIndexFormParameters(source_array, previous_index[0].toLowerCase());
    }
}

/**
 * Adds a column to an Index.
 *
 * @param array  source_array Array holding corresponding indexes
 * @param string array_index  Index of an INDEX in array
 * @param string index_choice Choice of Index
 * @param string col_index    Index of column on form
 *
 * @return void
 */
function PMA_addColumnToIndex(source_array, array_index, index_choice, col_index)
{
    if (col_index >= 0) {
        // Remove column from other indexes (if any).
        PMA_removeColumnFromIndex(col_index);
    }
    var index_name = $('input[name="index[Key_name]"]').val();
    var index_comment = $('input[name="index[Index_comment]"]').val();
    var key_block_size = $('input[name="index[Key_block_size]"]').val();
    var parser = $('input[name="index[Parser]"]').val();
    var index_type = $('select[name="index[Index_type]"]').val();
    var columns = [];
    $('#index_columns').find('tbody').find('tr').each(function () {
        // Get columns in particular order.
        var col_index = $(this).find('select[name="index[columns][names][]"]').val();
        var size = $(this).find('input[name="index[columns][sub_parts][]"]').val();
        columns.push({
            'col_index': col_index,
            'size': size
        });
    });

    // Update or create an index.
    source_array[array_index] = {
        'Key_name': index_name,
        'Index_comment': index_comment,
        'Index_choice': index_choice.toUpperCase(),
        'Key_block_size': key_block_size,
        'Parser': parser,
        'Index_type': index_type,
        'columns': columns
    };

    // Display index name (or column list)
    var displayName = index_name;
    if (displayName == '') {
        var columnNames = [];
        $.each(columns, function () {
            columnNames.push($('input[name="field_name[' +  this.col_index + ']"]').val());
        });
        displayName = '[' + columnNames.join(', ') + ']';
    }
    $.each(columns, function () {
        var id = 'index_name_' + this.col_index + '_8';
        var $name = $('#' + id);
        if ($name.length == 0) {
            $name = $('<a id="' + id + '" href="#" class="ajax show_index_dialog"></a>');
            $name.insertAfter($('select[name="field_key[' + this.col_index + ']"]'));
        }
        var $text = $('<small>').text(displayName);
        $name.html($text);
    });

    if (col_index >= 0) {
        // Update index details on form.
        $('select[name="field_key[' + col_index + ']"]')
            .attr('data-index', index_choice + ',' + array_index);
    }
    PMA_setIndexFormParameters(source_array, index_choice.toLowerCase());
}

/**
 * Get choices list for a column to create a composite index with.
 *
 * @param string index_choice Choice of index
 * @param array  source_array Array hodling columns for particular index
 *
 * @return jQuery Object
 */
function PMA_getCompositeIndexList(source_array, col_index)
{
    // Remove any previous list.
    if ($('#composite_index_list').length) {
        $('#composite_index_list').remove();
    }

    // Html list.
    var $composite_index_list = $(
        '<ul id="composite_index_list">' +
        '<div>' + PMA_messages.strCompositeWith + '</div>' +
        '</ul>'
    );

    // Add each column to list available for composite index.
    var source_length = source_array.length;
    var already_present = false;
    for (var i=0; i<source_length; i++) {
        var sub_array_len = source_array[i].columns.length;
        var column_names = [];
        for (var j=0; j<sub_array_len; j++) {
            column_names.push(
                $('input[name="field_name[' + source_array[i].columns[j].col_index + ']"]').val()
            );

            if (col_index == source_array[i].columns[j].col_index) {
                already_present = true;
            }
        }

        $composite_index_list.append(
            '<li>' +
            '<input type="radio" name="composite_with" ' +
            (already_present ? 'checked="checked"' : '') +
            ' id="composite_index_' + i + '" value="' + i + '">' +
            '<label for="composite_index_' + i + '">' + column_names.join(', ') +
            '</lablel>' +
            '</li>'
        );
    }

    return $composite_index_list;
}

/**
 * Shows 'Add Index' dialog.
 *
 * @param array  source_array   Array holding particluar index
 * @param string array_index    Index of an INDEX in array
 * @param array  target_columns Columns for an INDEX
 * @param string col_index      Index of column on form
 * @param object index          Index detail object
 *
 * @return void
 */
function PMA_showAddIndexDialog(source_array, array_index, target_columns, col_index, index)
{
    // Prepare post-data.
    var $table = $('input[name="table"]');
    var table = $table.length > 0 ? $table.val() : '';
    var post_data = {
        server: PMA_commonParams.get('server'),
        token: PMA_commonParams.get('token'),
        db: $('input[name="db"]').val(),
        table: table,
        ajax_request: 1,
        create_edit_table: 1,
        index: index
    };

    var columns = {};
    for (var i=0; i<target_columns.length; i++) {
        var column_name = $('input[name="field_name[' + target_columns[i] + ']"]').val();
        var column_type = $('select[name="field_type[' + target_columns[i] + ']"]').val().toLowerCase();
        columns[column_name] = [column_type, target_columns[i]];
    }
    post_data.columns = JSON.stringify(columns);

    var button_options = {};
    button_options[PMA_messages.strGo] = function () {
        var is_missing_value = false;
        $('select[name="index[columns][names][]"]').each(function () {
            if ($(this).val() === '') {
                is_missing_value = true;
            }
        });

        if (! is_missing_value) {
            PMA_addColumnToIndex(
                source_array,
                array_index,
                index.Index_choice,
                col_index
            );
        } else {
            PMA_ajaxShowMessage(
                '<div class="error"><img src="themes/dot.gif" title="" alt=""' +
                ' class="icon ic_s_error" /> ' + PMA_messages.strMissingColumn +
                ' </div>', false
            );

            return false;
        }

        $(this).dialog('close');
    };
    button_options[PMA_messages.strCancel] = function () {
        if (col_index >= 0) {
            // Handle state on 'Cancel'.
            var $select_list = $('select[name="field_key[' + col_index + ']"]');
            if (! $select_list.attr('data-index').length) {
                $select_list.find('option[value*="none"]').attr('selected', 'selected');
            } else {
                var previous_index = $select_list.attr('data-index').split(',');
                $select_list.find('option[value*="' + previous_index[0].toLowerCase() + '"]')
                    .attr('selected', 'selected');
            }
        }
        $(this).dialog('close');
    };
    var $msgbox = PMA_ajaxShowMessage();
    $.post("tbl_indexes.php", post_data, function (data) {
        if (data.success === false) {
            //in the case of an error, show the error message returned.
            PMA_ajaxShowMessage(data.error, false);
        } else {
            PMA_ajaxRemoveMessage($msgbox);
            // Show dialog if the request was successful
            var $div = $('<div/>');
            $div
            .append(data.message)
            .dialog({
                title: PMA_messages.strAddIndex,
                width: 450,
                minHeight: 250,
                open: function () {
                    checkIndexName("index_frm");
                    PMA_showHints($div);
                    PMA_init_slider();
                    $('#index_columns').find('td').each(function () {
                        $(this).css("width", $(this).width() + 'px');
                    });
                    $('#index_columns').find('tbody').sortable({
                        axis: 'y',
                        containment: $("#index_columns").find("tbody"),
                        tolerance: 'pointer'
                    });
                    // We dont need the slider at this moment.
                    $(this).find('fieldset.tblFooters').remove();
                },
                modal: true,
                buttons: button_options,
                close: function () {
                    $(this).remove();
                }
            });
        }
    });
}

/**
 * Creates a advanced index type selection dialog.
 *
 * @param array  source_array Array holding a particular type of indexes
 * @param string index_choice Choice of index
 * @param string col_index    Index of new column on form
 *
 * @return void
 */
function PMA_indexTypeSelectionDialog(source_array, index_choice, col_index)
{
    var $single_column_radio = $('<input type="radio" id="single_column" name="index_choice"' +
        ' checked="checked">' +
        '<label for="single_column">' + PMA_messages.strCreateSingleColumnIndex + '</label>');
    var $composite_index_radio = $('<input type="radio" id="composite_index"' +
        ' name="index_choice">' +
        '<label for="composite_index">' + PMA_messages.strCreateCompositeIndex + '</label>');
    var $dialog_content = $('<fieldset id="advance_index_creator"></fieldset>');
    $dialog_content.append('<legend>' + index_choice.toUpperCase() + '</legend>');


    // For UNIQUE/INDEX type, show choice for single-column and composite index.
    $dialog_content.append($single_column_radio);
    $dialog_content.append($composite_index_radio);

    var button_options = {};
    // 'OK' operation.
    button_options[PMA_messages.strGo] = function () {
        if ($('#single_column').is(':checked')) {
            var index = {
                'Key_name': (index_choice == 'primary' ? 'PRIMARY' : ''),
                'Index_choice': index_choice.toUpperCase()
            };
            PMA_showAddIndexDialog(source_array, (source_array.length), [col_index], col_index, index);
        }

        if ($('#composite_index').is(':checked')) {
            if ($('input[name="composite_with"]').length !== 0 && $('input[name="composite_with"]:checked').length === 0
            ) {
                PMA_ajaxShowMessage(
                    '<div class="error"><img src="themes/dot.gif" title=""' +
                    ' alt="" class="icon ic_s_error" /> ' +
                    PMA_messages.strFormEmpty +
                    ' </div>',
                    false
                );
                return false;
            }

            var array_index = $('input[name="composite_with"]:checked').val();
            var source_length = source_array[array_index].columns.length;
            var target_columns = [];
            for (var i=0; i<source_length; i++) {
                target_columns.push(source_array[array_index].columns[i].col_index);
            }
            target_columns.push(col_index);

            PMA_showAddIndexDialog(source_array, array_index, target_columns, col_index,
             source_array[array_index]);
        }

        $(this).remove();
    };
    button_options[PMA_messages.strCancel] = function () {
        // Handle state on 'Cancel'.
        var $select_list = $('select[name="field_key[' + col_index + ']"]');
        if (! $select_list.attr('data-index').length) {
            $select_list.find('option[value*="none"]').attr('selected', 'selected');
        } else {
            var previous_index = $select_list.attr('data-index').split(',');
            $select_list.find('option[value*="' + previous_index[0].toLowerCase() + '"]')
                .attr('selected', 'selected');
        }
        $(this).remove();
    };
    var $dialog = $('<div/>').append($dialog_content).dialog({
        minWidth: 525,
        minHeight: 200,
        modal: true,
        title: PMA_messages.strAddIndex,
        resizable: false,
        buttons: button_options,
        open: function () {
            $('#composite_index').on('change', function () {
                if ($(this).is(':checked')) {
                    $dialog_content.append(PMA_getCompositeIndexList(source_array, col_index));
                }
            });
            $('#single_column').on('change', function () {
                if ($(this).is(':checked')) {
                    if ($('#composite_index_list').length) {
                        $('#composite_index_list').remove();
                    }
                }
            });
        },
        close: function () {
            $('#composite_index').off('change');
            $('#single_column').off('change');
            $(this).remove();
        }
    });
}

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('indexes.js', function () {
    $(document).off('click', '#save_index_frm');
    $(document).off('click', '#preview_index_frm');
    $(document).off('change', '#select_index_choice');
    $(document).off('click', 'a.drop_primary_key_index_anchor.ajax');
    $(document).off('click', "#table_index tbody tr td.edit_index.ajax, #indexes .add_index.ajax");
    $(document).off('click', '#index_frm input[type=submit]');
    $('body').off('change', 'select[name*="field_key"]');
    $(document).off('click', '.show_index_dialog');
});

/**
 * @description <p>Ajax scripts for table index page</p>
 *
 * Actions ajaxified here:
 * <ul>
 * <li>Showing/hiding inputs depending on the index type chosen</li>
 * <li>create/edit/drop indexes</li>
 * </ul>
 */
AJAX.registerOnload('indexes.js', function () {
    // Re-initialize variables.
    primary_indexes = [];
    unique_indexes = [];
    indexes = [];
    fulltext_indexes = [];
    spatial_indexes = [];

    // for table creation form
    var $engine_selector = $('.create_table_form select[name=tbl_storage_engine]');
    if ($engine_selector.length) {
        PMA_hideShowConnection($engine_selector);
    }

    var $form = $("#index_frm");
    if ($form.length > 0) {
        showIndexEditDialog($form);
    }

    $(document).on('click', '#save_index_frm', function (event) {
        event.preventDefault();
        var $form = $("#index_frm");
        var submitData = $form.serialize() + '&do_save_data=1&ajax_request=true&ajax_page_request=true';
        var $msgbox = PMA_ajaxShowMessage(PMA_messages.strProcessingRequest);
        AJAX.source = $form;
        $.post($form.attr('action'), submitData, AJAX.responseHandler);
    });

    $(document).on('click', '#preview_index_frm', function (event) {
        event.preventDefault();
        PMA_previewSQL($('#index_frm'));
    });

    $(document).on('change', '#select_index_choice', function (event) {
        event.preventDefault();
        checkIndexType();
        checkIndexName("index_frm");
    });

    /**
     * Ajax Event handler for 'Drop Index'
     */
    $(document).on('click', 'a.drop_primary_key_index_anchor.ajax', function (event) {
        event.preventDefault();
        var $anchor = $(this);
        /**
         * @var $curr_row    Object containing reference to the current field's row
         */
        var $curr_row = $anchor.parents('tr');
        /** @var    Number of columns in the key */
        var rows = $anchor.parents('td').attr('rowspan') || 1;
        /** @var    Rows that should be hidden */
        var $rows_to_hide = $curr_row;
        for (var i = 1, $last_row = $curr_row.next(); i < rows; i++, $last_row = $last_row.next()) {
            $rows_to_hide = $rows_to_hide.add($last_row);
        }

        var question = escapeHtml(
            $curr_row.children('td')
                .children('.drop_primary_key_index_msg')
                .val()
        );

        $anchor.PMA_confirm(question, $anchor.attr('href'), function (url) {
            var $msg = PMA_ajaxShowMessage(PMA_messages.strDroppingPrimaryKeyIndex, false);
            var params = {
                'is_js_confirmed': 1,
                'ajax_request': true,
                'token' : PMA_commonParams.get('token')
            };
            $.post(url, params, function (data) {
                if (typeof data !== 'undefined' && data.success === true) {
                    PMA_ajaxRemoveMessage($msg);
                    var $table_ref = $rows_to_hide.closest('table');
                    if ($rows_to_hide.length == $table_ref.find('tbody > tr').length) {
                        // We are about to remove all rows from the table
                        $table_ref.hide('medium', function () {
                            $('div.no_indexes_defined').show('medium');
                            $rows_to_hide.remove();
                        });
                        $table_ref.siblings('div.notice').hide('medium');
                    } else {
                        // We are removing some of the rows only
                        $rows_to_hide.hide("medium", function () {
                            $(this).remove();
                        });
                    }
                    if ($('.result_query').length) {
                        $('.result_query').remove();
                    }
                    if (data.sql_query) {
                        $('<div class="result_query"></div>')
                            .html(data.sql_query)
                            .prependTo('#structure_content');
                        PMA_highlightSQL($('#page_content'));
                    }
                    PMA_commonActions.refreshMain(false, function () {
                        $("a.ajax[href^=#indexes]").click();
                    });
                    PMA_reloadNavigation();
                } else {
                    PMA_ajaxShowMessage(PMA_messages.strErrorProcessingRequest + " : " + data.error, false);
                }
            }); // end $.post()
        }); // end $.PMA_confirm()
    }); //end Drop Primary Key/Index

    /**
     *Ajax event handler for index edit
    **/
    $(document).on('click', "#table_index tbody tr td.edit_index.ajax, #indexes .add_index.ajax", function (event) {
        event.preventDefault();
        var url, title;
        if ($(this).find("a").length === 0) {
            // Add index
            var valid = checkFormElementInRange(
                $(this).closest('form')[0],
                'added_fields',
                'Column count has to be larger than zero.'
            );
            if (! valid) {
                return;
            }
            url = $(this).closest('form').serialize();
            title = PMA_messages.strAddIndex;
        } else {
            // Edit index
            url = $(this).find("a").attr("href");
            if (url.substring(0, 16) == "tbl_indexes.php?") {
                url = url.substring(16, url.length);
            }
            title = PMA_messages.strEditIndex;
        }
        url += "&ajax_request=true";
        indexEditorDialog(url, title, function () {
            // refresh the page using ajax
            PMA_commonActions.refreshMain(false, function () {
                $("a.ajax[href^=#indexes]").click();
            });
        });
    });

    /**
     * Ajax event handler for advanced index creation during table creation
     * and column addition.
     */
    $('body').on('change', 'select[name*="field_key"]', function () {
        // Index of column on Table edit and create page.
        var col_index = /\d+/.exec($(this).attr('name'));
        col_index = col_index[0];
        // Choice of selected index.
        var index_choice = /[a-z]+/.exec($(this).val());
        index_choice = index_choice[0];
        // Array containing corresponding indexes.
        var source_array = null;

        if (index_choice == 'none') {
            PMA_removeColumnFromIndex(col_index);
            return false;
        }

        // Select a source array.
        source_array = PMA_getIndexArray(index_choice);
        if (source_array == null) {
            return;
        }

        if (source_array.length === 0) {
            var index = {
                'Key_name': (index_choice == 'primary' ? 'PRIMARY' : ''),
                'Index_choice': index_choice.toUpperCase()
            };
            PMA_showAddIndexDialog(source_array, 0, [col_index], col_index, index);
        } else {
            if (index_choice == 'primary') {
                var array_index = 0;
                var source_length = source_array[array_index].columns.length;
                var target_columns = [];
                for (var i=0; i<source_length; i++) {
                    target_columns.push(source_array[array_index].columns[i].col_index);
                }
                target_columns.push(col_index);

                PMA_showAddIndexDialog(source_array, array_index, target_columns, col_index,
                 source_array[array_index]);
            } else {
                // If there are multiple columns selected for an index, show advanced dialog.
                PMA_indexTypeSelectionDialog(source_array, index_choice, col_index);
            }
        }
    });

    $(document).on('click', '.show_index_dialog', function (e) {
        e.preventDefault();

        // Get index details.
        var previous_index = $(this).prev('select')
            .attr('data-index')
            .split(',');

        var index_choice = previous_index[0];
        var array_index  = previous_index[1];

        var source_array = PMA_getIndexArray(index_choice);
        var source_length = source_array[array_index].columns.length;

        var target_columns = [];
        for (var i = 0; i < source_length; i++) {
            target_columns.push(source_array[array_index].columns[i].col_index);
        }

        PMA_showAddIndexDialog(source_array, array_index, target_columns, -1, source_array[array_index]);
    })
});
;

/* vim: set expandtab sw=4 ts=4 sts=4: */

$(function () {
    checkNumberOfFields();
});

/**
 * Holds common parameters such as server, db, table, etc
 *
 * The content for this is normally loaded from Header.php or
 * Response.php and executed by ajax.js
 */
var PMA_commonParams = (function () {
    /**
     * @var hash params An associative array of key value pairs
     * @access private
     */
    var params = {};
    // The returned object is the public part of the module
    return {
        /**
         * Saves all the key value pair that
         * are provided in the input array
         *
         * @param obj hash The input array
         *
         * @return void
         */
        setAll: function (obj) {
            var reload = false;
            var updateNavigation = false;
            for (var i in obj) {
                if (params[i] !== undefined && params[i] !== obj[i]) {
                    if (i == 'db' || i == 'table') {
                        updateNavigation = true;
                    }
                    reload = true;
                }
                params[i] = obj[i];
            }
            if (updateNavigation &&
                    $('#pma_navigation_tree').hasClass('synced')
            ) {
                PMA_showCurrentNavigation();
            }
        },
        /**
         * Retrieves a value given its key
         * Returns empty string for undefined values
         *
         * @param name string The key
         *
         * @return string
         */
        get: function (name) {
            return params[name] || '';
        },
        /**
         * Saves a single key value pair
         *
         * @param name  string The key
         * @param value string The value
         *
         * @return self For chainability
         */
        set: function (name, value) {
            var updateNavigation = false;
            if (name == 'db' || name == 'table' &&
                params[name] !== value
            ) {
                updateNavigation = true;
            }
            params[name] = value;
            if (updateNavigation &&
                    $('#pma_navigation_tree').hasClass('synced')
            ) {
                PMA_showCurrentNavigation();
            }
            return this;
        },
        /**
         * Returns the url query string using the saved parameters
         *
         * @return string
         */
        getUrlQuery: function () {
            var common = this.get('common_query');
            var separator = '?';
            if (common.length > 0) {
                separator = '&';
            }
            return PMA_sprintf(
                '%s%sserver=%s&db=%s&table=%s',
                this.get('common_query'),
                separator,
                encodeURIComponent(this.get('server')),
                encodeURIComponent(this.get('db')),
                encodeURIComponent(this.get('table'))
            );
        }
    };
})();

/**
 * Holds common parameters such as server, db, table, etc
 *
 * The content for this is normally loaded from Header.php or
 * Response.php and executed by ajax.js
 */
var PMA_commonActions = {
    /**
     * Saves the database name when it's changed
     * and reloads the query window, if necessary
     *
     * @param new_db string new_db The name of the new database
     *
     * @return void
     */
    setDb: function (new_db) {
        if (new_db != PMA_commonParams.get('db')) {
            PMA_commonParams.setAll({'db': new_db, 'table': ''});
        }
    },
    /**
     * Opens a database in the main part of the page
     *
     * @param new_db string The name of the new database
     *
     * @return void
     */
    openDb: function (new_db) {
        PMA_commonParams
            .set('db', new_db)
            .set('table', '');
        this.refreshMain(
            PMA_commonParams.get('opendb_url')
        );
    },
    /**
     * Refreshes the main frame
     *
     * @param mixed url Undefined to refresh to the same page
     *                  String to go to a different page, e.g: 'index.php'
     *
     * @return void
     */
    refreshMain: function (url, callback) {
        if (! url) {
            url = $('#selflink').find('a').attr('href');
            url = url.substring(0, url.indexOf('?'));
        }
        url += PMA_commonParams.getUrlQuery();
        $('<a />', {href: url})
            .appendTo('body')
            .click()
            .remove();
        AJAX._callback = callback;
    }
};

/**
 * Class to handle PMA Drag and Drop Import
 *      feature
 */
PMA_DROP_IMPORT = {
    /**
     * @var int, count of total uploads in this view
     */
    uploadCount: 0,
    /**
     * @var int, count of live uploads
     */
    liveUploadCount: 0,
    /**
     * @var  string array, allowed extensions
     */
    allowedExtensions: ['sql', 'xml', 'ldi', 'mediawiki', 'shp'],
    /**
     * @var  string array, allowed extensions for compressed files
     */
    allowedCompressedExtensions: ['gz', 'bz2', 'zip'],
    /**
     * @var obj array to store message returned by import_status.php
     */
    importStatus: [],
    /**
     * Checks if any dropped file has valid extension or not
     *
     * @param file filename
     *
     * @return string, extension for valid extension, '' otherwise
     */
    _getExtension: function(file) {
        var arr = file.split('.');
        ext = arr[arr.length - 1];

        //check if compressed
        if (jQuery.inArray(ext.toLowerCase(),
            PMA_DROP_IMPORT.allowedCompressedExtensions) !== -1) {
            ext = arr[arr.length - 2];
        }

        //Now check for extension
        if (jQuery.inArray(ext.toLowerCase(),
            PMA_DROP_IMPORT.allowedExtensions) !== -1) {
            return ext;
        }
        return '';
    },
    /**
     * Shows upload progress for different sql uploads
     *
     * @param: hash (string), hash for specific file upload
     * @param: percent (float), file upload percentage
     *
     * @return void
     */
    _setProgress: function(hash, percent) {
        $('.pma_sql_import_status div li[data-hash="' +hash +'"]')
            .children('progress').val(percent);
    },
    /**
     * Function to upload the file asynchronously
     *
     * @param formData FormData object for a specific file
     * @param hash hash of the current file upload
     *
     * @return void
     */
    _sendFileToServer: function(formData, hash) {
        var uploadURL ="./import.php"; //Upload URL
        var extraData ={};
        var jqXHR = $.ajax({
            xhr: function() {
                var xhrobj = $.ajaxSettings.xhr();
                if (xhrobj.upload) {
                    xhrobj.upload.addEventListener('progress', function(event) {
                        var percent = 0;
                        var position = event.loaded || event.position;
                        var total = event.total;
                        if (event.lengthComputable) {
                            percent = Math.ceil(position / total * 100);
                        }
                        //Set progress
                        PMA_DROP_IMPORT._setProgress(hash, percent);
                    }, false);
                }
                return xhrobj;
            },
            url: uploadURL,
            type: "POST",
            contentType:false,
            processData: false,
            cache: false,
            data: formData,
            success: function(data){
                PMA_DROP_IMPORT._importFinished(hash, false, data.success);
                if (!data.success) {
                    PMA_DROP_IMPORT.importStatus[PMA_DROP_IMPORT.importStatus.length] = {
                        hash: hash,
                        message: data.error
                    };
                }
            }
        });

        // -- provide link to cancel the upload
        $('.pma_sql_import_status div li[data-hash="' + hash +
            '"] span.filesize').html('<span hash="' +
            hash + '" class="pma_drop_file_status" task="cancel">' +
            PMA_messages.dropImportMessageCancel + '</span>');

        // -- add event listener to this link to abort upload operation
        $('.pma_sql_import_status div li[data-hash="' + hash +
            '"] span.filesize span.pma_drop_file_status')
            .on('click', function() {
                if ($(this).attr('task') === 'cancel') {
                    jqXHR.abort();
                    $(this).html('<span>' +PMA_messages.dropImportMessageAborted +'</span>');
                    PMA_DROP_IMPORT._importFinished(hash, true, false);
                } else if ($(this).children("span").html() ===
                    PMA_messages.dropImportMessageFailed) {
                    // -- view information
                    var $this = $(this);
                    $.each( PMA_DROP_IMPORT.importStatus,
                    function( key, value ) {
                        if (value.hash === hash) {
                            $(".pma_drop_result:visible").remove();
                            var filename = $this.parent('span').attr('data-filename');
                            $("body").append('<div class="pma_drop_result"><h2>' +
                                PMA_messages.dropImportImportResultHeader + ' - ' +
                                filename +'<span class="close">x</span></h2>' +value.message +'</div>');
                            $(".pma_drop_result").draggable();  //to make this dialog draggable
                        }
                    });
                }
            });
    },
    /**
     * Triggered when an object is dragged into the PMA UI
     *
     * @param event obj
     *
     * @return void
     */
    _dragenter : function (event) {

        // We don't want to prevent users from using
        // browser's default drag-drop feature on some page(s)
        if ($(".noDragDrop").length !== 0) {
            return;
        }

        event.stopPropagation();
        event.preventDefault();
        if (!PMA_DROP_IMPORT._hasFiles(event)) {
            return;
        }
        if (PMA_commonParams.get('db') === '') {
            $(".pma_drop_handler").html(PMA_messages.dropImportSelectDB);
        } else {
            $(".pma_drop_handler").html(PMA_messages.dropImportDropFiles);
        }
        $(".pma_drop_handler").fadeIn();
    },
    /**
     * Check if dragged element contains Files
     *
     * @param event the event object
     *
     * @return bool
     */
    _hasFiles: function (event) {
        return !(typeof event.originalEvent.dataTransfer.types === 'undefined' ||
            $.inArray('Files', event.originalEvent.dataTransfer.types) < 0 ||
            $.inArray(
                'application/x-moz-nativeimage',
                event.originalEvent.dataTransfer.types
            ) >= 0);
    },
    /**
     * Triggered when dragged file is being dragged over PMA UI
     *
     * @param event obj
     *
     * @return void
     */
    _dragover: function (event) {
        // We don't want to prevent users from using
        // browser's default drag-drop feature on some page(s)
        if ($(".noDragDrop").length !== 0) {
            return;
        }

        event.stopPropagation();
        event.preventDefault();
        if (!PMA_DROP_IMPORT._hasFiles(event)) {
            return;
        }
        $(".pma_drop_handler").fadeIn();
    },
    /**
     * Triggered when dragged objects are left
     *
     * @param event obj
     *
     * @return void
     */
    _dragleave: function (event) {
        // We don't want to prevent users from using
        // browser's default drag-drop feature on some page(s)
        if ($(".noDragDrop").length !== 0) {
            return;
        }
        event.stopPropagation();
        event.preventDefault();
        var $pma_drop_handler = $(".pma_drop_handler");
        $pma_drop_handler.clearQueue().stop();
        $pma_drop_handler.fadeOut();
        $pma_drop_handler.html(PMA_messages.dropImportDropFiles);
    },
    /**
     * Called when upload has finished
     *
     * @param string, unique hash for a certain upload
     * @param bool, true if upload was aborted
     * @param bool, status of sql upload, as sent by server
     *
     * @return void
     */
    _importFinished: function(hash, aborted, status) {
        $('.pma_sql_import_status div li[data-hash="' +hash +'"]')
            .children("progress").hide();
        var icon = 'icon ic_s_success';
        // -- provide link to view upload status
        if (!aborted) {
            if (status) {
                $('.pma_sql_import_status div li[data-hash="' + hash +
                   '"] span.filesize span.pma_drop_file_status')
                   .html('<span>' +PMA_messages.dropImportMessageSuccess +'</a>');
            } else {
                $('.pma_sql_import_status div li[data-hash="' + hash +
                   '"] span.filesize span.pma_drop_file_status')
                   .html('<span class="underline">' + PMA_messages.dropImportMessageFailed +
                   '</a>');
                icon = 'icon ic_s_error';
            }
        } else {
            icon = 'icon ic_s_notice';
        }
        $('.pma_sql_import_status div li[data-hash="' + hash +
            '"] span.filesize span.pma_drop_file_status')
            .attr('task', 'info');

        // Set icon
        $('.pma_sql_import_status div li[data-hash="' +hash +'"]')
            .prepend('<img src="./themes/dot.gif" title="finished" class="' +
            icon +'"> ');

        // Decrease liveUploadCount by one
        $('.pma_import_count').html(--PMA_DROP_IMPORT.liveUploadCount);
        if (!PMA_DROP_IMPORT.liveUploadCount) {
            $('.pma_sql_import_status h2 .close').fadeIn();
        }
    },
    /**
     * Triggered when dragged objects are dropped to UI
     * From this function, the AJAX Upload operation is initiated
     *
     * @param event object
     *
     * @return void
     */
    _drop: function (event) {
        // We don't want to prevent users from using
        // browser's default drag-drop feature on some page(s)
        if ($(".noDragDrop").length !== 0) {
            return;
        }

        var dbname = PMA_commonParams.get('db');
        var server = PMA_commonParams.get('server');

        //if no database is selected -- no
        if (dbname !== '') {
            var files = event.originalEvent.dataTransfer.files;
            if (!files || files.length === 0) {
                // No files actually transferred
                $(".pma_drop_handler").fadeOut();
                event.stopPropagation();
                event.preventDefault();
                return;
            }
            $(".pma_sql_import_status").slideDown();
            for (var i = 0; i < files.length; i++) {
                var ext  = (PMA_DROP_IMPORT._getExtension(files[i].name));
                var hash = AJAX.hash(++PMA_DROP_IMPORT.uploadCount);

                var $pma_sql_import_status_div = $(".pma_sql_import_status div");
                $pma_sql_import_status_div.append('<li data-hash="' +hash +'">' +
                    ((ext !== '') ? '' : '<img src="./themes/dot.gif" title="invalid format" class="icon ic_s_notice"> ') +
                    escapeHtml(files[i].name) + '<span class="filesize" data-filename="' +
                    escapeHtml(files[i].name) +'">' +(files[i].size/1024).toFixed(2) +
                    ' kb</span></li>');

                //scroll the UI to bottom
                $pma_sql_import_status_div.scrollTop(
                    $pma_sql_import_status_div.scrollTop() + 50
                );  //50 hardcoded for now

                if (ext !== '') {
                    // Increment liveUploadCount by one
                    $('.pma_import_count').html(++PMA_DROP_IMPORT.liveUploadCount);
                    $('.pma_sql_import_status h2 .close').fadeOut();

                    $('.pma_sql_import_status div li[data-hash="' +hash +'"]')
                        .append('<br><progress max="100" value="2"></progress>');

                    //uploading
                    var fd = new FormData();
                    fd.append('import_file', files[i]);
                    fd.append('noplugin', Math.random().toString(36).substring(2, 12));
                    fd.append('db', dbname);
                    fd.append('server', server);
                    fd.append('token', PMA_commonParams.get('token'));
                    fd.append('import_type', 'database');
                    // todo: method to find the value below
                    fd.append('MAX_FILE_SIZE', '4194304');
                    // todo: method to find the value below
                    fd.append('charset_of_file','utf-8');
                    // todo: method to find the value below
                    fd.append('allow_interrupt', 'yes');
                    fd.append('skip_queries', '0');
                    fd.append('format',ext);
                    fd.append('sql_compatibility','NONE');
                    fd.append('sql_no_auto_value_on_zero','something');
                    fd.append('ajax_request','true');
                    fd.append('hash', hash);

                    // init uploading
                    PMA_DROP_IMPORT._sendFileToServer(fd, hash);
                } else if (!PMA_DROP_IMPORT.liveUploadCount) {
                    $('.pma_sql_import_status h2 .close').fadeIn();
                }
            }
        }
        $(".pma_drop_handler").fadeOut();
        event.stopPropagation();
        event.preventDefault();
    }
};

/**
 * Called when some user drags, dragover, leave
 *       a file to the PMA UI
 * @param object Event data
 * @return void
 */
$(document).on('dragenter', PMA_DROP_IMPORT._dragenter);
$(document).on('dragover', PMA_DROP_IMPORT._dragover);
$(document).on('dragleave', '.pma_drop_handler', PMA_DROP_IMPORT._dragleave);

//when file is dropped to PMA UI
$(document).on('drop', 'body', PMA_DROP_IMPORT._drop);

// minimizing-maximising the sql ajax upload status
$(document).on('click', '.pma_sql_import_status h2 .minimize', function() {
    if ($(this).attr('toggle') === 'off') {
        $('.pma_sql_import_status div').css('height','270px');
        $(this).attr('toggle','on');
        $(this).html('-');  // to minimize
    } else {
        $('.pma_sql_import_status div').css("height","0px");
        $(this).attr('toggle','off');
        $(this).html('+');  // to maximise
    }
});

// closing sql ajax upload status
$(document).on('click', '.pma_sql_import_status h2 .close', function() {
    $('.pma_sql_import_status').fadeOut(function() {
        $('.pma_sql_import_status div').html('');
        PMA_DROP_IMPORT.importStatus = [];  //clear the message array
    });
});

// Closing the import result box
$(document).on('click', '.pma_drop_result h2 .close', function(){
    $(this).parent('h2').parent('div').remove();
});
;

/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @fileoverview    function used for page-related settings
 * @name            Page-related settings
 *
 * @requires    jQuery
 * @requires    jQueryUI
 * @required    js/functions.js
 */

function showSettings(selector) {
    var buttons = {};
    buttons[PMA_messages.strApply] = function() {
        $('.config-form').submit();
    };

    buttons[PMA_messages.strCancel] = function () {
        $(this).dialog('close');
    };

    // Keeping a clone to restore in case the user cancels the operation
    var $clone = $(selector + ' .page_settings').clone(true);
    $(selector)
    .dialog({
        title: PMA_messages.strPageSettings,
        width: 700,
        minHeight: 250,
        modal: true,
        open: function() {
            $(this).dialog('option', 'maxHeight', $(window).height() - $(this).offset().top);
        },
        close: function() {
            $(selector + ' .page_settings').replaceWith($clone);
        },
        buttons: buttons
    });
}

function showPageSettings() {
    showSettings('#page_settings_modal');
}

function showNaviSettings() {
    showSettings('#pma_navigation_settings');
}

AJAX.registerTeardown('page_settings.js', function () {
    $('#page_settings_icon').css('display', 'none');
    $('#page_settings_icon').unbind('click');
    $('#pma_navigation_settings_icon').unbind('click');
});

AJAX.registerOnload('page_settings.js', function () {
    if ($('#page_settings_modal').length) {
        $('#page_settings_icon').css('display', 'inline');
        $('#page_settings_icon').bind('click', showPageSettings);
    }
    $('#pma_navigation_settings_icon').bind('click', showNaviSettings);
});
;

/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @fileoverview    Handle shortcuts in various pages
 * @name            Shortcuts handler
 *
 * @requires    jQuery
 * @requires    jQueryUI
 */

/**
 * Register key events on load
 */
$(document).ready(function() {
    var databaseOp = false;
    var tableOp = false;
    var keyD = 68;
    var keyT = 84;
    var keyK = 75;
    var keyS = 83;
    var keyF = 70;
    var keyE = 69;
    var keyH = 72;
    var keyC = 67;
    var keyBackSpace = 8;
    $(document).keyup(function(e) {
        if( e.target.nodeName === 'INPUT' || e.target.nodeName === 'TEXTAREA' || e.target.nodeName === 'SELECT' ) {
            return;
        }

        if(e.keyCode === keyD) {
            setTimeout(function() {
                databaseOp = false;
            }, 2000);
        }
        else if(e.keyCode === keyT) {
            setTimeout(function() {
                tableOp = false;
            }, 2000);
        }
    });
    $(document).keydown(function(e) {
        if ( e.ctrlKey && e.altKey && e.keyCode === keyC ) {
            PMA_console.toggle();
        }

        if( e.ctrlKey && e.keyCode == keyK ) {
            e.preventDefault();
            PMA_console.toggle();
        }

        if( e.target.nodeName === 'INPUT' || e.target.nodeName === 'TEXTAREA' || e.target.nodeName === 'SELECT' ) {
            return;
        }

        var isTable;
        var isDb;
        if(e.keyCode === keyD) {
            databaseOp = true;
        }
        else if(e.keyCode === keyK) {
            e.preventDefault();
            PMA_console.toggle();
        }
        else if(e.keyCode === keyS) {
            if(databaseOp === true) {
                isTable = PMA_commonParams.get('table');
                isDb = PMA_commonParams.get('db');
                if(isDb && ! isTable) {
                    $('.tab .ic_b_props').first().trigger('click');
                }
            }
            else if(tableOp === true) {
                isTable = PMA_commonParams.get('table');
                isDb = PMA_commonParams.get('db');
                if(isDb && isTable) {
                    $('.tab .ic_b_props').first().trigger('click');
                }
            }
            else{
                $('#pma_navigation_settings_icon').trigger('click');
            }
        }
        else if(e.keyCode === keyF) {
            if(databaseOp === true) {
                isTable = PMA_commonParams.get('table');
                isDb = PMA_commonParams.get('db');
                if(isDb && ! isTable) {
                    $('.tab .ic_b_search').first().trigger('click');
                }
            }
            else if(tableOp === true) {
                isTable = PMA_commonParams.get('table');
                isDb = PMA_commonParams.get('db');
                if(isDb && isTable) {
                    $('.tab .ic_b_search').first().trigger('click');
                }
            }
        }
        else if(e.keyCode === keyT) {
            tableOp = true;
        }
        else if(e.keyCode === keyE) {
            $('.ic_b_export').first().trigger('click');
        }
        else if(e.keyCode === keyBackSpace) {
            window.history.back();
        }
        else if(e.keyCode === keyH) {
            $('.ic_b_home').first().trigger('click');
        }
    });
});
;

/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Create advanced table (resize, reorder, and show/hide columns; and also grid editing).
 * This function is designed mainly for table DOM generated from browsing a table in the database.
 * For using this function in other table DOM, you may need to:
 * - add "draggable" class in the table header <th>, in order to make it resizable, sortable or hidable
 * - have at least one non-"draggable" header in the table DOM for placing column visibility drop-down arrow
 * - pass the value "false" for the parameter "enableGridEdit"
 * - adjust other parameter value, to select which features that will be enabled
 *
 * @param t the table DOM element
 * @param enableResize Optional, if false, column resizing feature will be disabled
 * @param enableReorder Optional, if false, column reordering feature will be disabled
 * @param enableVisib Optional, if false, show/hide column feature will be disabled
 * @param enableGridEdit Optional, if false, grid editing feature will be disabled
 */
function PMA_makegrid(t, enableResize, enableReorder, enableVisib, enableGridEdit) {
    var g = {
        /***********
         * Constant
         ***********/
        minColWidth: 15,


        /***********
         * Variables, assigned with default value, changed later
         ***********/
        actionSpan: 5,              // number of colspan in Actions header in a table
        tableCreateTime: null,      // table creation time, used for saving column order and visibility to server, only available in "Browse tab"

        // Column reordering variables
        colOrder: [],      // array of column order

        // Column visibility variables
        colVisib: [],      // array of column visibility
        showAllColText: '',         // string, text for "show all" button under column visibility list
        visibleHeadersCount: 0,     // number of visible data headers

        // Table hint variables
        reorderHint: '',            // string, hint for column reordering
        sortHint: '',               // string, hint for column sorting
        markHint: '',               // string, hint for column marking
        copyHint: '',               // string, hint for copy column name
        showReorderHint: false,
        showSortHint: false,
        showMarkHint: false,

        // Grid editing
        isCellEditActive: false,    // true if current focus is in edit cell
        isEditCellTextEditable: false,  // true if current edit cell is editable in the text input box (not textarea)
        currentEditCell: null,      // reference to <td> that currently being edited
        cellEditHint: '',           // hint shown when doing grid edit
        gotoLinkText: '',           // "Go to link" text
        wasEditedCellNull: false,   // true if last value of the edited cell was NULL
        maxTruncatedLen: 0,         // number of characters that can be displayed in a cell
        saveCellsAtOnce: false,     // $cfg[saveCellsAtOnce]
        isCellEdited: false,        // true if at least one cell has been edited
        saveCellWarning: '',        // string, warning text when user want to leave a page with unsaved edited data
        lastXHR : null,             // last XHR object used in AJAX request
        isSaving: false,            // true when currently saving edited data, used to handle double posting caused by pressing ENTER in grid edit text box in Chrome browser
        alertNonUnique: '',         // string, alert shown when saving edited nonunique table

        // Common hidden inputs
        token: null,
        server: null,
        db: null,
        table: null,


        /************
         * Functions
         ************/

        /**
         * Start to resize column. Called when clicking on column separator.
         *
         * @param e event
         * @param obj dragged div object
         */
        dragStartRsz: function (e, obj) {
            var n = $(g.cRsz).find('div').index(obj);    // get the index of separator (i.e., column index)
            $(obj).addClass('colborder_active');
            g.colRsz = {
                x0: e.pageX,
                n: n,
                obj: obj,
                objLeft: $(obj).position().left,
                objWidth: $(g.t).find('th.draggable:visible:eq(' + n + ') span').outerWidth()
            };
            $(document.body).css('cursor', 'col-resize').noSelect();
            if (g.isCellEditActive) {
                g.hideEditCell();
            }
        },

        /**
         * Start to reorder column. Called when clicking on table header.
         *
         * @param e event
         * @param obj table header object
         */
        dragStartReorder: function (e, obj) {
            // prepare the cCpy (column copy) and cPointer (column pointer) from the dragged column
            $(g.cCpy).text($(obj).text());
            var objPos = $(obj).position();
            $(g.cCpy).css({
                top: objPos.top + 20,
                left: objPos.left,
                height: $(obj).height(),
                width: $(obj).width()
            });
            $(g.cPointer).css({
                top: objPos.top
            });

            // get the column index, zero-based
            var n = g.getHeaderIdx(obj);

            g.colReorder = {
                x0: e.pageX,
                y0: e.pageY,
                n: n,
                newn: n,
                obj: obj,
                objTop: objPos.top,
                objLeft: objPos.left
            };

            $(document.body).css('cursor', 'move').noSelect();
            if (g.isCellEditActive) {
                g.hideEditCell();
            }
        },

        /**
         * Handle mousemove event when dragging.
         *
         * @param e event
         */
        dragMove: function (e) {
            if (g.colRsz) {
                var dx = e.pageX - g.colRsz.x0;
                if (g.colRsz.objWidth + dx > g.minColWidth) {
                    $(g.colRsz.obj).css('left', g.colRsz.objLeft + dx + 'px');
                }
            } else if (g.colReorder) {
                // dragged column animation
                var dx = e.pageX - g.colReorder.x0;
                $(g.cCpy)
                    .css('left', g.colReorder.objLeft + dx)
                    .show();

                // pointer animation
                var hoveredCol = g.getHoveredCol(e);
                if (hoveredCol) {
                    var newn = g.getHeaderIdx(hoveredCol);
                    g.colReorder.newn = newn;
                    if (newn != g.colReorder.n) {
                        // show the column pointer in the right place
                        var colPos = $(hoveredCol).position();
                        var newleft = newn < g.colReorder.n ?
                                      colPos.left :
                                      colPos.left + $(hoveredCol).outerWidth();
                        $(g.cPointer)
                            .css({
                                left: newleft,
                                visibility: 'visible'
                            });
                    } else {
                        // no movement to other column, hide the column pointer
                        $(g.cPointer).css('visibility', 'hidden');
                    }
                }
            }
        },

        /**
         * Stop the dragging action.
         *
         * @param e event
         */
        dragEnd: function (e) {
            if (g.colRsz) {
                var dx = e.pageX - g.colRsz.x0;
                var nw = g.colRsz.objWidth + dx;
                if (nw < g.minColWidth) {
                    nw = g.minColWidth;
                }
                var n = g.colRsz.n;
                // do the resizing
                g.resize(n, nw);

                g.reposRsz();
                g.reposDrop();
                g.colRsz = false;
                $(g.cRsz).find('div').removeClass('colborder_active');
                rearrangeStickyColumns($(t).prev('.sticky_columns'), $(t));
            } else if (g.colReorder) {
                // shift columns
                if (g.colReorder.newn != g.colReorder.n) {
                    g.shiftCol(g.colReorder.n, g.colReorder.newn);
                    // assign new position
                    var objPos = $(g.colReorder.obj).position();
                    g.colReorder.objTop = objPos.top;
                    g.colReorder.objLeft = objPos.left;
                    g.colReorder.n = g.colReorder.newn;
                    // send request to server to remember the column order
                    if (g.tableCreateTime) {
                        g.sendColPrefs();
                    }
                    g.refreshRestoreButton();
                }

                // animate new column position
                $(g.cCpy).stop(true, true)
                    .animate({
                        top: g.colReorder.objTop,
                        left: g.colReorder.objLeft
                    }, 'fast')
                    .fadeOut();
                $(g.cPointer).css('visibility', 'hidden');

                g.colReorder = false;
                rearrangeStickyColumns($(t).prev('.sticky_columns'), $(t));
            }
            $(document.body).css('cursor', 'inherit').noSelect(false);
        },

        /**
         * Resize column n to new width "nw"
         *
         * @param n zero-based column index
         * @param nw new width of the column in pixel
         */
        resize: function (n, nw) {
            $(g.t).find('tr').each(function () {
                $(this).find('th.draggable:visible:eq(' + n + ') span,' +
                             'td:visible:eq(' + (g.actionSpan + n) + ') span')
                       .css('width', nw);
            });
        },

        /**
         * Reposition column resize bars.
         */
        reposRsz: function () {
            $(g.cRsz).find('div').hide();
            var $firstRowCols = $(g.t).find('tr:first th.draggable:visible');
            var $resizeHandles = $(g.cRsz).find('div').removeClass('condition');
            $(g.t).find('table.pma_table').find('thead th:first').removeClass('before-condition');
            for (var n = 0, l = $firstRowCols.length; n < l; n++) {
                var $col = $($firstRowCols[n]);
                var colWidth;
                if (navigator.userAgent.toLowerCase().indexOf("safari") != -1) {
                    colWidth = $col.outerWidth();
                } else {
                    colWidth = $col.outerWidth(true);
                }
                $($resizeHandles[n]).css('left', $col.position().left + colWidth)
                   .show();
                if ($col.hasClass('condition')) {
                    $($resizeHandles[n]).addClass('condition');
                    if (n > 0) {
                        $($resizeHandles[n - 1]).addClass('condition');
                    }
                }
            }
            if ($($resizeHandles[0]).hasClass('condition')) {
                $(g.t).find('thead th:first').addClass('before-condition');
            }
            $(g.cRsz).css('height', $(g.t).height());
        },

        /**
         * Shift column from index oldn to newn.
         *
         * @param oldn old zero-based column index
         * @param newn new zero-based column index
         */
        shiftCol: function (oldn, newn) {
            $(g.t).find('tr').each(function () {
                if (newn < oldn) {
                    $(this).find('th.draggable:eq(' + newn + '),' +
                                 'td:eq(' + (g.actionSpan + newn) + ')')
                           .before($(this).find('th.draggable:eq(' + oldn + '),' +
                                                'td:eq(' + (g.actionSpan + oldn) + ')'));
                } else {
                    $(this).find('th.draggable:eq(' + newn + '),' +
                                 'td:eq(' + (g.actionSpan + newn) + ')')
                           .after($(this).find('th.draggable:eq(' + oldn + '),' +
                                               'td:eq(' + (g.actionSpan + oldn) + ')'));
                }
            });
            // reposition the column resize bars
            g.reposRsz();

            // adjust the column visibility list
            if (newn < oldn) {
                $(g.cList).find('.lDiv div:eq(' + newn + ')')
                          .before($(g.cList).find('.lDiv div:eq(' + oldn + ')'));
            } else {
                $(g.cList).find('.lDiv div:eq(' + newn + ')')
                          .after($(g.cList).find('.lDiv div:eq(' + oldn + ')'));
            }
            // adjust the colOrder
            var tmp = g.colOrder[oldn];
            g.colOrder.splice(oldn, 1);
            g.colOrder.splice(newn, 0, tmp);
            // adjust the colVisib
            if (g.colVisib.length > 0) {
                tmp = g.colVisib[oldn];
                g.colVisib.splice(oldn, 1);
                g.colVisib.splice(newn, 0, tmp);
            }
        },

        /**
         * Find currently hovered table column's header (excluding actions column).
         *
         * @param e event
         * @return the hovered column's th object or undefined if no hovered column found.
         */
        getHoveredCol: function (e) {
            var hoveredCol;
            $headers = $(g.t).find('th.draggable:visible');
            $headers.each(function () {
                var left = $(this).offset().left;
                var right = left + $(this).outerWidth();
                if (left <= e.pageX && e.pageX <= right) {
                    hoveredCol = this;
                }
            });
            return hoveredCol;
        },

        /**
         * Get a zero-based index from a <th class="draggable"> tag in a table.
         *
         * @param obj table header <th> object
         * @return zero-based index of the specified table header in the set of table headers (visible or not)
         */
        getHeaderIdx: function (obj) {
            return $(obj).parents('tr').find('th.draggable').index(obj);
        },

        /**
         * Reposition the columns back to normal order.
         */
        restoreColOrder: function () {
            // use insertion sort, since we already have shiftCol function
            for (var i = 1; i < g.colOrder.length; i++) {
                var x = g.colOrder[i];
                var j = i - 1;
                while (j >= 0 && x < g.colOrder[j]) {
                    j--;
                }
                if (j != i - 1) {
                    g.shiftCol(i, j + 1);
                }
            }
            if (g.tableCreateTime) {
                // send request to server to remember the column order
                g.sendColPrefs();
            }
            g.refreshRestoreButton();
        },

        /**
         * Send column preferences (column order and visibility) to the server.
         */
        sendColPrefs: function () {
            if ($(g.t).is('.ajax')) {   // only send preferences if ajax class
                var post_params = {
                    ajax_request: true,
                    db: g.db,
                    table: g.table,
                    token: g.token,
                    server: g.server,
                    set_col_prefs: true,
                    table_create_time: g.tableCreateTime
                };
                if (g.colOrder.length > 0) {
                    $.extend(post_params, {col_order: g.colOrder.toString()});
                }
                if (g.colVisib.length > 0) {
                    $.extend(post_params, {col_visib: g.colVisib.toString()});
                }
                $.post('sql.php', post_params, function (data) {
                    if (data.success !== true) {
                        var $temp_div = $(document.createElement('div'));
                        $temp_div.html(data.error);
                        $temp_div.addClass("error");
                        PMA_ajaxShowMessage($temp_div, false);
                    }
                });
            }
        },

        /**
         * Refresh restore button state.
         * Make restore button disabled if the table is similar with initial state.
         */
        refreshRestoreButton: function () {
            // check if table state is as initial state
            var isInitial = true;
            for (var i = 0; i < g.colOrder.length; i++) {
                if (g.colOrder[i] != i) {
                    isInitial = false;
                    break;
                }
            }
            // check if only one visible column left
            var isOneColumn = g.visibleHeadersCount == 1;
            // enable or disable restore button
            if (isInitial || isOneColumn) {
                $(g.o).find('div.restore_column').hide();
            } else {
                $(g.o).find('div.restore_column').show();
            }
        },

        /**
         * Update current hint using the boolean values (showReorderHint, showSortHint, etc.).
         *
         */
        updateHint: function () {
            var text = '';
            if (!g.colRsz && !g.colReorder) {     // if not resizing or dragging
                if (g.visibleHeadersCount > 1) {
                    g.showReorderHint = true;
                }
                if ($(t).find('th.marker').length > 0) {
                    g.showMarkHint = true;
                }
                if (g.showSortHint && g.sortHint) {
                    text += text.length > 0 ? '<br />' : '';
                    text += '- ' + g.sortHint;
                }
                if (g.showMultiSortHint && g.strMultiSortHint) {
                    text += text.length > 0 ? '<br />' : '';
                    text += '- ' + g.strMultiSortHint;
                }
                if (g.showMarkHint &&
                    g.markHint &&
                    ! g.showSortHint && // we do not show mark hint, when sort hint is shown
                    g.showReorderHint &&
                    g.reorderHint
                ) {
                    text += text.length > 0 ? '<br />' : '';
                    text += '- ' + g.reorderHint;
                    text += text.length > 0 ? '<br />' : '';
                    text += '- ' + g.markHint;
                    text += text.length > 0 ? '<br />' : '';
                    text += '- ' + g.copyHint;
                }
            }
            return text;
        },

        /**
         * Toggle column's visibility.
         * After calling this function and it returns true, afterToggleCol() must be called.
         *
         * @return boolean True if the column is toggled successfully.
         */
        toggleCol: function (n) {
            if (g.colVisib[n]) {
                // can hide if more than one column is visible
                if (g.visibleHeadersCount > 1) {
                    $(g.t).find('tr').each(function () {
                        $(this).find('th.draggable:eq(' + n + '),' +
                                     'td:eq(' + (g.actionSpan + n) + ')')
                               .hide();
                    });
                    g.colVisib[n] = 0;
                    $(g.cList).find('.lDiv div:eq(' + n + ') input').prop('checked', false);
                } else {
                    // cannot hide, force the checkbox to stay checked
                    $(g.cList).find('.lDiv div:eq(' + n + ') input').prop('checked', true);
                    return false;
                }
            } else {    // column n is not visible
                $(g.t).find('tr').each(function () {
                    $(this).find('th.draggable:eq(' + n + '),' +
                                 'td:eq(' + (g.actionSpan + n) + ')')
                           .show();
                });
                g.colVisib[n] = 1;
                $(g.cList).find('.lDiv div:eq(' + n + ') input').prop('checked', true);
            }
            return true;
        },

        /**
         * This must be called if toggleCol() returns is true.
         *
         * This function is separated from toggleCol because, sometimes, we want to toggle
         * some columns together at one time and do just one adjustment after it, e.g. in showAllColumns().
         */
        afterToggleCol: function () {
            // some adjustments after hiding column
            g.reposRsz();
            g.reposDrop();
            g.sendColPrefs();

            // check visible first row headers count
            g.visibleHeadersCount = $(g.t).find('tr:first th.draggable:visible').length;
            g.refreshRestoreButton();
        },

        /**
         * Show columns' visibility list.
         *
         * @param obj The drop down arrow of column visibility list
         */
        showColList: function (obj) {
            // only show when not resizing or reordering
            if (!g.colRsz && !g.colReorder) {
                var pos = $(obj).position();
                // check if the list position is too right
                if (pos.left + $(g.cList).outerWidth(true) > $(document).width()) {
                    pos.left = $(document).width() - $(g.cList).outerWidth(true);
                }
                $(g.cList).css({
                        left: pos.left,
                        top: pos.top + $(obj).outerHeight(true)
                    })
                    .show();
                $(obj).addClass('coldrop-hover');
            }
        },

        /**
         * Hide columns' visibility list.
         */
        hideColList: function () {
            $(g.cList).hide();
            $(g.cDrop).find('.coldrop-hover').removeClass('coldrop-hover');
        },

        /**
         * Reposition the column visibility drop-down arrow.
         */
        reposDrop: function () {
            var $th = $(t).find('th:not(.draggable)');
            for (var i = 0; i < $th.length; i++) {
                var $cd = $(g.cDrop).find('div:eq(' + i + ')');   // column drop-down arrow
                var pos = $($th[i]).position();
                $cd.css({
                        left: pos.left + $($th[i]).width() - $cd.width(),
                        top: pos.top
                    });
            }
        },

        /**
         * Show all hidden columns.
         */
        showAllColumns: function () {
            for (var i = 0; i < g.colVisib.length; i++) {
                if (!g.colVisib[i]) {
                    g.toggleCol(i);
                }
            }
            g.afterToggleCol();
        },

        /**
         * Show edit cell, if it can be shown
         *
         * @param cell <td> element to be edited
         */
        showEditCell: function (cell) {
            if ($(cell).is('.grid_edit') &&
                !g.colRsz && !g.colReorder)
            {
                if (!g.isCellEditActive) {
                    var $cell = $(cell);

                    if ('string' === $cell.attr('data-type') ||
                        'blob' === $cell.attr('data-type')
                    ) {
                        g.cEdit = g.cEditTextarea;
                    } else {
                        g.cEdit = g.cEditStd;
                    }

                    // remove all edit area and hide it
                    $(g.cEdit).find('.edit_area').empty().hide();
                    // reposition the cEdit element
                    $(g.cEdit).css({
                            top: $cell.position().top,
                            left: $cell.position().left
                        })
                        .show()
                        .find('.edit_box')
                        .css({
                            width: $cell.outerWidth(),
                            height: $cell.outerHeight()
                        });
                    // fill the cell edit with text from <td>
                    var value = PMA_getCellValue(cell);
                    $(g.cEdit).find('.edit_box').val(value);

                    g.currentEditCell = cell;
                    $(g.cEdit).find('.edit_box').focus();
                    moveCursorToEnd($(g.cEdit).find('.edit_box'));
                    $(g.cEdit).find('*').prop('disabled', false);
                }
            }

            function moveCursorToEnd(input) {
                var originalValue = input.val();
                var originallength = originalValue.length;
                input.val('');
                input.blur().focus().val(originalValue);
                input[0].setSelectionRange(originallength, originallength);
            }
        },

        /**
         * Remove edit cell and the edit area, if it is shown.
         *
         * @param force Optional, force to hide edit cell without saving edited field.
         * @param data  Optional, data from the POST AJAX request to save the edited field
         *              or just specify "true", if we want to replace the edited field with the new value.
         * @param field Optional, the edited <td>. If not specified, the function will
         *              use currently edited <td> from g.currentEditCell.
         * @param field Optional, this object contains a boolean named move (true, if called from move* functions)
         *              and a <td> to which the grid_edit should move
         */
        hideEditCell: function (force, data, field, options) {
            if (g.isCellEditActive && !force) {
                // cell is being edited, save or post the edited data
                if (options !== undefined) {
                    g.saveOrPostEditedCell(options);
                } else {
                    g.saveOrPostEditedCell();
                }
                return;
            }

            // cancel any previous request
            if (g.lastXHR !== null) {
                g.lastXHR.abort();
                g.lastXHR = null;
            }

            if (data) {
                if (g.currentEditCell) {    // save value of currently edited cell
                    // replace current edited field with the new value
                    var $this_field = $(g.currentEditCell);
                    var is_null = $this_field.data('value') === null;
                    if (is_null) {
                        $this_field.find('span').html('NULL');
                        $this_field.addClass('null');
                    } else {
                        $this_field.removeClass('null');
                        var value = data.isNeedToRecheck
                            ? data.truncatableFieldValue
                            : $this_field.data('value');

                        // Truncates the text.
                        $this_field.removeClass('truncated');
                        if (PMA_commonParams.get('pftext') === 'P' && value.length > g.maxTruncatedLen) {
                            $this_field.addClass('truncated');
                            value = value.substring(0, g.maxTruncatedLen) + '...';
                        }

                        //Add <br> before carriage return.
                        new_html = escapeHtml(value);
                        new_html = new_html.replace(/\n/g, '<br>\n');

                        //remove decimal places if column type not supported
                        if (($this_field.attr('data-decimals') == 0) && ( $this_field.attr('data-type').indexOf('time') != -1)) {
                            new_html = new_html.substring(0, new_html.indexOf('.'));
                        }

                        //remove addtional decimal places
                        if (($this_field.attr('data-decimals') > 0) && ( $this_field.attr('data-type').indexOf('time') != -1)){
                            new_html = new_html.substring(0, new_html.length - (6 - $this_field.attr('data-decimals')));
                        }

                        var selector = 'span';
                        if ($this_field.hasClass('hex') && $this_field.find('a').length) {
                            selector = 'a';
                        }

                        // Updates the code keeping highlighting (if any).
                        var $target = $this_field.find(selector);
                        if (!PMA_updateCode($target, new_html, value)) {
                            $target.html(new_html);
                        }
                    }
                    if ($this_field.is('.bit')) {
                        $this_field.find('span').text($this_field.data('value'));
                    }
                }
                if (data.transformations !== undefined) {
                    $.each(data.transformations, function (cell_index, value) {
                        var $this_field = $(g.t).find('.to_be_saved:eq(' + cell_index + ')');
                        $this_field.find('span').html(value);
                    });
                }
                if (data.relations !== undefined) {
                    $.each(data.relations, function (cell_index, value) {
                        var $this_field = $(g.t).find('.to_be_saved:eq(' + cell_index + ')');
                        $this_field.find('span').html(value);
                    });
                }

                // refresh the grid
                g.reposRsz();
                g.reposDrop();
            }

            // hide the cell editing area
            $(g.cEdit).hide();
            $(g.cEdit).find('.edit_box').blur();
            g.isCellEditActive = false;
            g.currentEditCell = null;
            // destroy datepicker in edit area, if exist
            var $dp = $(g.cEdit).find('.hasDatepicker');
            if ($dp.length > 0) {
                $(document).bind('mousedown', $.datepicker._checkExternalClick);
                $dp.datepicker('destroy');
                // change the cursor in edit box back to normal
                // (the cursor become a hand pointer when we add datepicker)
                $(g.cEdit).find('.edit_box').css('cursor', 'inherit');
            }
        },

        /**
         * Show drop-down edit area when edit cell is focused.
         */
        showEditArea: function () {
            if (!g.isCellEditActive) {   // make sure the edit area has not been shown
                g.isCellEditActive = true;
                g.isEditCellTextEditable = false;
                /**
                 * @var $td current edited cell
                 */
                var $td = $(g.currentEditCell);
                /**
                 * @var $editArea the editing area
                 */
                var $editArea = $(g.cEdit).find('.edit_area');
                /**
                 * @var where_clause WHERE clause for the edited cell
                 */
                var where_clause = $td.parent('tr').find('.where_clause').val();
                /**
                 * @var field_name  String containing the name of this field.
                 * @see getFieldName()
                 */
                var field_name = getFieldName($(t), $td);
                /**
                 * @var relation_curr_value String current value of the field (for fields that are foreign keyed).
                 */
                var relation_curr_value = $td.text();
                /**
                 * @var relation_key_or_display_column String relational key if in 'Relational display column' mode,
                 * relational display column if in 'Relational key' mode (for fields that are foreign keyed).
                 */
                var relation_key_or_display_column = $td.find('a').attr('title');
                /**
                 * @var curr_value String current value of the field (for fields that are of type enum or set).
                 */
                var curr_value = $td.find('span').text();

                // empty all edit area, then rebuild it based on $td classes
                $editArea.empty();

                // remember this instead of testing more than once
                var is_null = $td.is('.null');

                // add goto link, if this cell contains a link
                if ($td.find('a').length > 0) {
                    var gotoLink = document.createElement('div');
                    gotoLink.className = 'goto_link';
                    $(gotoLink).append(g.gotoLinkText + ' ').append($td.find('a').clone());
                    $editArea.append(gotoLink);
                }

                g.wasEditedCellNull = false;
                if ($td.is(':not(.not_null)')) {
                    // append a null checkbox
                    $editArea.append('<div class="null_div">Null:<input type="checkbox"></div>');

                    var $checkbox = $editArea.find('.null_div input');
                    // check if current <td> is NULL
                    if (is_null) {
                        $checkbox.prop('checked', true);
                        g.wasEditedCellNull = true;
                    }

                    // if the select/editor is changed un-check the 'checkbox_null_<field_name>_<row_index>'.
                    if ($td.is('.enum, .set')) {
                        $editArea.on('change', 'select', function () {
                            $checkbox.prop('checked', false);
                        });
                    } else if ($td.is('.relation')) {
                        $editArea.on('change', 'select', function () {
                            $checkbox.prop('checked', false);
                        });
                        $editArea.on('click', '.browse_foreign', function () {
                            $checkbox.prop('checked', false);
                        });
                    } else {
                        $(g.cEdit).on('keypress change paste', '.edit_box', function () {
                            $checkbox.prop('checked', false);
                        });
                        // Capture ctrl+v (on IE and Chrome)
                        $(g.cEdit).on('keydown', '.edit_box', function (e) {
                            if (e.ctrlKey && e.which == 86) {
                                $checkbox.prop('checked', false);
                            }
                        });
                        $editArea.on('keydown', 'textarea', function () {
                            $checkbox.prop('checked', false);
                        });
                    }

                    // if null checkbox is clicked empty the corresponding select/editor.
                    $checkbox.click(function () {
                        if ($td.is('.enum')) {
                            $editArea.find('select').val('');
                        } else if ($td.is('.set')) {
                            $editArea.find('select').find('option').each(function () {
                                var $option = $(this);
                                $option.prop('selected', false);
                            });
                        } else if ($td.is('.relation')) {
                            // if the dropdown is there to select the foreign value
                            if ($editArea.find('select').length > 0) {
                                $editArea.find('select').val('');
                            }
                        } else {
                            $editArea.find('textarea').val('');
                        }
                        $(g.cEdit).find('.edit_box').val('');
                    });
                }

                //reset the position of the edit_area div after closing datetime picker
                $(g.cEdit).find('.edit_area').css({'top' :'0','position':''});

                if ($td.is('.relation')) {
                    //handle relations
                    $editArea.addClass('edit_area_loading');

                    // initialize the original data
                    $td.data('original_data', null);

                    /**
                     * @var post_params Object containing parameters for the POST request
                     */
                    var post_params = {
                        'ajax_request' : true,
                        'get_relational_values' : true,
                        'server' : g.server,
                        'db' : g.db,
                        'table' : g.table,
                        'column' : field_name,
                        'token' : g.token,
                        'curr_value' : relation_curr_value,
                        'relation_key_or_display_column' : relation_key_or_display_column
                    };

                    g.lastXHR = $.post('sql.php', post_params, function (data) {
                        g.lastXHR = null;
                        $editArea.removeClass('edit_area_loading');
                        if ($(data.dropdown).is('select')) {
                            // save original_data
                            var value = $(data.dropdown).val();
                            $td.data('original_data', value);
                            // update the text input field, in case where the "Relational display column" is checked
                            $(g.cEdit).find('.edit_box').val(value);
                        }

                        $editArea.append(data.dropdown);
                        $editArea.append('<div class="cell_edit_hint">' + g.cellEditHint + '</div>');

                        // for 'Browse foreign values' options,
                        // hide the value next to 'Browse foreign values' link
                        $editArea.find('span.curr_value').hide();
                        // handle update for new values selected from new window
                        $editArea.find('span.curr_value').change(function () {
                            $(g.cEdit).find('.edit_box').val($(this).text());
                        });
                    }); // end $.post()

                    $editArea.show();
                    $editArea.on('change', 'select', function () {
                        $(g.cEdit).find('.edit_box').val($(this).val());
                    });
                    g.isEditCellTextEditable = true;
                }
                else if ($td.is('.enum')) {
                    //handle enum fields
                    $editArea.addClass('edit_area_loading');

                    /**
                     * @var post_params Object containing parameters for the POST request
                     */
                    var post_params = {
                        'ajax_request' : true,
                        'get_enum_values' : true,
                        'server' : g.server,
                        'db' : g.db,
                        'table' : g.table,
                        'column' : field_name,
                        'token' : g.token,
                        'curr_value' : curr_value
                    };
                    g.lastXHR = $.post('sql.php', post_params, function (data) {
                        g.lastXHR = null;
                        $editArea.removeClass('edit_area_loading');
                        $editArea.append(data.dropdown);
                        $editArea.append('<div class="cell_edit_hint">' + g.cellEditHint + '</div>');
                    }); // end $.post()

                    $editArea.show();
                    $editArea.on('change', 'select', function () {
                        $(g.cEdit).find('.edit_box').val($(this).val());
                    });
                }
                else if ($td.is('.set')) {
                    //handle set fields
                    $editArea.addClass('edit_area_loading');

                    /**
                     * @var post_params Object containing parameters for the POST request
                     */
                    var post_params = {
                        'ajax_request' : true,
                        'get_set_values' : true,
                        'server' : g.server,
                        'db' : g.db,
                        'table' : g.table,
                        'column' : field_name,
                        'token' : g.token,
                        'curr_value' : curr_value
                    };

                    // if the data is truncated, get the full data
                    if ($td.is('.truncated')) {
                        post_params.get_full_values = true;
                        post_params.where_clause = where_clause;
                    }

                    g.lastXHR = $.post('sql.php', post_params, function (data) {
                        g.lastXHR = null;
                        $editArea.removeClass('edit_area_loading');
                        $editArea.append(data.select);
                        $td.data('original_data', $(data.select).val().join());
                        $editArea.append('<div class="cell_edit_hint">' + g.cellEditHint + '</div>');
                    }); // end $.post()

                    $editArea.show();
                    $editArea.on('change', 'select', function () {
                        $(g.cEdit).find('.edit_box').val($(this).val());
                    });
                }
                else if ($td.is('.truncated, .transformed')) {
                    if ($td.is('.to_be_saved')) {   // cell has been edited
                        var value = $td.data('value');
                        $(g.cEdit).find('.edit_box').val(value);
                        $editArea.append('<textarea></textarea>');
                        $editArea.find('textarea').val(value);
                        $editArea
                            .on('keyup', 'textarea', function () {
                                $(g.cEdit).find('.edit_box').val($(this).val());
                            });
                        $(g.cEdit).on('keyup', '.edit_box', function () {
                            $editArea.find('textarea').val($(this).val());
                        });
                        $editArea.append('<div class="cell_edit_hint">' + g.cellEditHint + '</div>');
                    } else {
                        //handle truncated/transformed values values
                        $editArea.addClass('edit_area_loading');

                        // initialize the original data
                        $td.data('original_data', null);

                        /**
                         * @var sql_query   String containing the SQL query used to retrieve value of truncated/transformed data
                         */
                        var sql_query = 'SELECT `' + field_name + '` FROM `' + g.table + '` WHERE ' + where_clause;

                        // Make the Ajax call and get the data, wrap it and insert it
                        g.lastXHR = $.post('sql.php', {
                            'token' : g.token,
                            'server' : g.server,
                            'db' : g.db,
                            'ajax_request' : true,
                            'sql_query' : sql_query,
                            'grid_edit' : true
                        }, function (data) {
                            g.lastXHR = null;
                            $editArea.removeClass('edit_area_loading');
                            if (typeof data !== 'undefined' && data.success === true) {
                                $td.data('original_data', data.value);
                                $(g.cEdit).find('.edit_box').val(data.value);
                            } else {
                                PMA_ajaxShowMessage(data.error, false);
                            }
                        }); // end $.post()
                    }
                    g.isEditCellTextEditable = true;
                } else if ($td.is('.timefield, .datefield, .datetimefield, .timestampfield')) {
                    var $input_field = $(g.cEdit).find('.edit_box');

                    // remember current datetime value in $input_field, if it is not null
                    var datetime_value = !is_null ? $input_field.val() : '';

                    var showMillisec = false;
                    var showMicrosec = false;
                    var timeFormat = 'HH:mm:ss';
                    // check for decimal places of seconds
                    if (($td.attr('data-decimals') > 0) && ($td.attr('data-type').indexOf('time') != -1)){
                        if (datetime_value && datetime_value.indexOf('.') === false) {
                            datetime_value += '.';
                        }
                        if ($td.attr('data-decimals') > 3) {
                            showMillisec = true;
                            showMicrosec = true;
                            timeFormat = 'HH:mm:ss.lc';

                            if (datetime_value) {
                                datetime_value += '000000';
                                var datetime_value = datetime_value.substring(0, datetime_value.indexOf('.') + 7);
                                $input_field.val(datetime_value);
                            }
                        } else {
                            showMillisec = true;
                            timeFormat = 'HH:mm:ss.l';

                            if (datetime_value) {
                                datetime_value += '000';
                                var datetime_value = datetime_value.substring(0, datetime_value.indexOf('.') + 4);
                                $input_field.val(datetime_value);
                            }
                        }
                    }

                    // add datetime picker
                    PMA_addDatepicker($input_field, $td.attr('data-type'), {
                        showMillisec: showMillisec,
                        showMicrosec: showMicrosec,
                        timeFormat: timeFormat
                    });

                    $input_field.on('keyup', function (e) {
                        if (e.which == 13) {
                            // post on pressing "Enter"
                            e.preventDefault();
                            e.stopPropagation();
                            g.saveOrPostEditedCell();
                        } else if (e.which == 27) {
                        } else {
                            toggleDatepickerIfInvalid($td, $input_field);
                        }
                    });

                    $input_field.datepicker("show");
                    toggleDatepickerIfInvalid($td, $input_field);

                    // unbind the mousedown event to prevent the problem of
                    // datepicker getting closed, needs to be checked for any
                    // change in names when updating
                    $(document).unbind('mousedown', $.datepicker._checkExternalClick);

                    //move ui-datepicker-div inside cEdit div
                    var datepicker_div = $('#ui-datepicker-div');
                    datepicker_div.css({'top': 0, 'left': 0, 'position': 'relative'});
                    $(g.cEdit).append(datepicker_div);

                    // cancel any click on the datepicker element
                    $editArea.find('> *').click(function (e) {
                        e.stopPropagation();
                    });

                    g.isEditCellTextEditable = true;
                } else {
                    g.isEditCellTextEditable = true;
                    // only append edit area hint if there is a null checkbox
                    if ($editArea.children().length > 0) {
                        $editArea.append('<div class="cell_edit_hint">' + g.cellEditHint + '</div>');
                    }
                }
                if ($editArea.children().length > 0) {
                    $editArea.show();
                }
            }
        },

        /**
         * Post the content of edited cell.
         *
         * @param field Optional, this object contains a boolean named move (true, if called from move* functions)
         *              and a <td> to which the grid_edit should move
         */
        postEditedCell: function (options) {
            if (g.isSaving) {
                return;
            }
            g.isSaving = true;
            /**
             * @var relation_fields Array containing the name/value pairs of relational fields
             */
            var relation_fields = {};
            /**
             * @var relational_display string 'K' if relational key, 'D' if relational display column
             */
            var relational_display = $(g.o).find("input[name=relational_display]:checked").val();
            /**
             * @var transform_fields    Array containing the name/value pairs for transformed fields
             */
            var transform_fields = {};
            /**
             * @var transformation_fields   Boolean, if there are any transformed fields in the edited cells
             */
            var transformation_fields = false;
            /**
             * @var full_sql_query String containing the complete SQL query to update this table
             */
            var full_sql_query = '';
            /**
             * @var rel_fields_list  String, url encoded representation of {@link relations_fields}
             */
            var rel_fields_list = '';
            /**
             * @var transform_fields_list  String, url encoded representation of {@link transform_fields}
             */
            var transform_fields_list = '';
            /**
             * @var where_clause Array containing where clause for updated fields
             */
            var full_where_clause = [];
            /**
             * @var is_unique   Boolean, whether the rows in this table is unique or not
             */
            var is_unique = $(g.t).find('td.edit_row_anchor').is('.nonunique') ? 0 : 1;
            /**
             * multi edit variables
             */
            var me_fields_name = [];
            var me_fields_type = [];
            var me_fields = [];
            var me_fields_null = [];

            // alert user if edited table is not unique
            if (!is_unique) {
                alert(g.alertNonUnique);
            }

            // loop each edited row
            $(g.t).find('td.to_be_saved').parents('tr').each(function () {
                var $tr = $(this);
                var where_clause = $tr.find('.where_clause').val();
                if (typeof where_clause === 'undefined') {
                    where_clause = '';
                }
                full_where_clause.push(where_clause);
                var condition_array = JSON.parse($tr.find('.condition_array').val());

                /**
                 * multi edit variables, for current row
                 * @TODO array indices are still not correct, they should be md5 of field's name
                 */
                var fields_name = [];
                var fields_type = [];
                var fields = [];
                var fields_null = [];

                // loop each edited cell in a row
                $tr.find('.to_be_saved').each(function () {
                    /**
                     * @var $this_field    Object referring to the td that is being edited
                     */
                    var $this_field = $(this);

                    /**
                     * @var field_name  String containing the name of this field.
                     * @see getFieldName()
                     */
                    var field_name = getFieldName($(g.t), $this_field);

                    /**
                     * @var this_field_params   Array temporary storage for the name/value of current field
                     */
                    var this_field_params = {};

                    if ($this_field.is('.transformed')) {
                        transformation_fields =  true;
                    }
                    this_field_params[field_name] = $this_field.data('value');

                    /**
                     * @var is_null String capturing whether 'checkbox_null_<field_name>_<row_index>' is checked.
                     */
                    var is_null = this_field_params[field_name] === null;

                    fields_name.push(field_name);

                    if (is_null) {
                        fields_null.push('on');
                        fields.push('');
                    } else {
                        if ($this_field.is('.bit')) {
                            fields_type.push('bit');
                        } else if ($this_field.hasClass('hex')) {
                            fields_type.push('hex');
                        }
                        fields_null.push('');
                        // Convert \n to \r\n to be consistent with form submitted value.
                        // The internal browser representation has to be just \n
                        // while form submitted value \r\n, see specification:
                        // https://www.w3.org/TR/html5/forms.html#the-textarea-element
                        fields.push($this_field.data('value').replace(/\n/g, '\r\n'));

                        var cell_index = $this_field.index('.to_be_saved');
                        if ($this_field.is(":not(.relation, .enum, .set, .bit)")) {
                            if ($this_field.is('.transformed')) {
                                transform_fields[cell_index] = {};
                                $.extend(transform_fields[cell_index], this_field_params);
                            }
                        } else if ($this_field.is('.relation')) {
                            relation_fields[cell_index] = {};
                            $.extend(relation_fields[cell_index], this_field_params);
                        }
                    }
                    // check if edited field appears in WHERE clause
                    if (where_clause.indexOf(PMA_urlencode(field_name)) > -1) {
                        var field_str = '`' + g.table + '`.' + '`' + field_name + '`';
                        for (var field in condition_array) {
                            if (field.indexOf(field_str) > -1) {
                                condition_array[field] = is_null ? 'IS NULL' : "= '" + this_field_params[field_name].replace(/'/g, "''") + "'";
                                break;
                            }
                        }
                    }

                }); // end of loop for every edited cells in a row

                // save new_clause
                var new_clause = '';
                for (var field in condition_array) {
                    new_clause += field + ' ' + condition_array[field] + ' AND ';
                }
                new_clause = new_clause.substring(0, new_clause.length - 5); // remove the last AND
                $tr.data('new_clause', new_clause);
                // save condition_array
                $tr.find('.condition_array').val(JSON.stringify(condition_array));

                me_fields_name.push(fields_name);
                me_fields_type.push(fields_type);
                me_fields.push(fields);
                me_fields_null.push(fields_null);

            }); // end of loop for every edited rows

            rel_fields_list = $.param(relation_fields);
            transform_fields_list = $.param(transform_fields);

            // Make the Ajax post after setting all parameters
            /**
             * @var post_params Object containing parameters for the POST request
             */
            var post_params = {'ajax_request' : true,
                            'sql_query' : full_sql_query,
                            'token' : g.token,
                            'server' : g.server,
                            'db' : g.db,
                            'table' : g.table,
                            'clause_is_unique' : is_unique,
                            'where_clause' : full_where_clause,
                            'fields[multi_edit]' : me_fields,
                            'fields_name[multi_edit]' : me_fields_name,
                            'fields_type[multi_edit]' : me_fields_type,
                            'fields_null[multi_edit]' : me_fields_null,
                            'rel_fields_list' : rel_fields_list,
                            'do_transformations' : transformation_fields,
                            'transform_fields_list' : transform_fields_list,
                            'relational_display' : relational_display,
                            'goto' : 'sql.php',
                            'submit_type' : 'save'
                          };

            if (!g.saveCellsAtOnce) {
                $(g.cEdit).find('*').prop('disabled', true);
                $(g.cEdit).find('.edit_box').addClass('edit_box_posting');
            } else {
                $(g.o).find('div.save_edited').addClass('saving_edited_data')
                    .find('input').prop('disabled', true);    // disable the save button
            }

            $.ajax({
                type: 'POST',
                url: 'tbl_replace.php',
                data: post_params,
                success:
                    function (data) {
                        g.isSaving = false;
                        if (!g.saveCellsAtOnce) {
                            $(g.cEdit).find('*').prop('disabled', false);
                            $(g.cEdit).find('.edit_box').removeClass('edit_box_posting');
                        } else {
                            $(g.o).find('div.save_edited').removeClass('saving_edited_data')
                                .find('input').prop('disabled', false);  // enable the save button back
                        }
                        if (typeof data !== 'undefined' && data.success === true) {
                            if (typeof options === 'undefined' || ! options.move) {
                                PMA_ajaxShowMessage(data.message);
                            }

                            // update where_clause related data in each edited row
                            $(g.t).find('td.to_be_saved').parents('tr').each(function () {
                                var new_clause = $(this).data('new_clause');
                                var $where_clause = $(this).find('.where_clause');
                                var old_clause = $where_clause.val();
                                var decoded_old_clause = old_clause;
                                var decoded_new_clause = new_clause;

                                $where_clause.val(new_clause);
                                // update Edit, Copy, and Delete links also
                                $(this).find('a').each(function () {
                                    $(this).attr('href', $(this).attr('href').replace(old_clause, new_clause));
                                    // update delete confirmation in Delete link
                                    if ($(this).attr('href').indexOf('DELETE') > -1) {
                                        $(this).removeAttr('onclick')
                                            .unbind('click')
                                            .bind('click', function () {
                                                return confirmLink(this, 'DELETE FROM `' + g.db + '`.`' + g.table + '` WHERE ' +
                                                       decoded_new_clause + (is_unique ? '' : ' LIMIT 1'));
                                            });
                                    }
                                });
                                // update the multi edit checkboxes
                                $(this).find('input[type=checkbox]').each(function () {
                                    var $checkbox = $(this);
                                    var checkbox_name = $checkbox.attr('name');
                                    var checkbox_value = $checkbox.val();

                                    $checkbox.attr('name', checkbox_name.replace(old_clause, new_clause));
                                    $checkbox.val(checkbox_value.replace(decoded_old_clause, decoded_new_clause));
                                });
                            });
                            // update the display of executed SQL query command
                            if (typeof data.sql_query != 'undefined') {
                                //extract query box
                                var $result_query = $($.parseHTML(data.sql_query));
                                var sqlOuter = $result_query.find('.sqlOuter').wrap('<p>').parent().html();
                                var tools = $result_query.find('.tools').wrap('<p>').parent().html();
                                // sqlOuter and tools will not be present if 'Show SQL queries' configuration is off
                                if (typeof sqlOuter != 'undefined' && typeof tools != 'undefined') {
                                    $(g.o).find('.result_query:not(:last)').remove();
                                    var $existing_query = $(g.o).find('.result_query');
                                    // If two query box exists update query in second else add a second box
                                    if ($existing_query.find('div.sqlOuter').length > 1) {
                                        $existing_query.children(":nth-child(4)").remove();
                                        $existing_query.children(":nth-child(4)").remove();
                                        $existing_query.append(sqlOuter + tools);
                                    } else {
                                        $existing_query.append(sqlOuter + tools);
                                    }
                                    PMA_highlightSQL($existing_query);
                                }
                            }
                            // hide and/or update the successfully saved cells
                            g.hideEditCell(true, data);

                            // remove the "Save edited cells" button
                            $(g.o).find('div.save_edited').hide();
                            // update saved fields
                            $(g.t).find('.to_be_saved')
                                .removeClass('to_be_saved')
                                .data('value', null)
                                .data('original_data', null);

                            g.isCellEdited = false;
                        } else {
                            PMA_ajaxShowMessage(data.error, false);
                            if (!g.saveCellsAtOnce) {
                                $(g.t).find('.to_be_saved')
                                    .removeClass('to_be_saved');
                            }
                        }
                    }
            }).done(function(){
                if (options !== undefined && options.move) {
                    g.showEditCell(options.cell);
                }
            }); // end $.ajax()
        },

        /**
         * Save edited cell, so it can be posted later.
         */
        saveEditedCell: function () {
            /**
             * @var $this_field    Object referring to the td that is being edited
             */
            var $this_field = $(g.currentEditCell);
            var $test_element = ''; // to test the presence of a element

            var need_to_post = false;

            /**
             * @var field_name  String containing the name of this field.
             * @see getFieldName()
             */
            var field_name = getFieldName($(g.t), $this_field);

            /**
             * @var this_field_params   Array temporary storage for the name/value of current field
             */
            var this_field_params = {};

            /**
             * @var is_null String capturing whether 'checkbox_null_<field_name>_<row_index>' is checked.
             */
            var is_null = $(g.cEdit).find('input:checkbox').is(':checked');

            if ($(g.cEdit).find('.edit_area').is('.edit_area_loading')) {
                // the edit area is still loading (retrieving cell data), no need to post
                need_to_post = false;
            } else if (is_null) {
                if (!g.wasEditedCellNull) {
                    this_field_params[field_name] = null;
                    need_to_post = true;
                }
            } else {
                if ($this_field.is('.bit')) {
                    this_field_params[field_name] = $(g.cEdit).find('.edit_box').val();
                } else if ($this_field.is('.set')) {
                    $test_element = $(g.cEdit).find('select');
                    this_field_params[field_name] = $test_element.map(function () {
                        return $(this).val();
                    }).get().join(",");
                } else if ($this_field.is('.relation, .enum')) {
                    // for relation and enumeration, take the results from edit box value,
                    // because selected value from drop-down, new window or multiple
                    // selection list will always be updated to the edit box
                    this_field_params[field_name] = $(g.cEdit).find('.edit_box').val();
                } else if ($this_field.hasClass('hex')) {
                    if ($(g.cEdit).find('.edit_box').val().match(/^(0x)?[a-f0-9]*$/i) !== null) {
                        this_field_params[field_name] = $(g.cEdit).find('.edit_box').val();
                    } else {
                        var hexError = '<div class="error">' + PMA_messages.strEnterValidHex + '</div>';
                        PMA_ajaxShowMessage(hexError, false);
                        this_field_params[field_name] = PMA_getCellValue(g.currentEditCell);
                    }
                } else {
                    this_field_params[field_name] = $(g.cEdit).find('.edit_box').val();
                }
                if (g.wasEditedCellNull || this_field_params[field_name] != PMA_getCellValue(g.currentEditCell)) {
                    need_to_post = true;
                }
            }

            if (need_to_post) {
                $(g.currentEditCell).addClass('to_be_saved')
                    .data('value', this_field_params[field_name]);
                if (g.saveCellsAtOnce) {
                    $(g.o).find('div.save_edited').show();
                }
                g.isCellEdited = true;
            }

            return need_to_post;
        },

        /**
         * Save or post currently edited cell, depending on the "saveCellsAtOnce" configuration.
         *
         * @param field Optional, this object contains a boolean named move (true, if called from move* functions)
         *              and a <td> to which the grid_edit should move
         */
        saveOrPostEditedCell: function (options) {
            var saved = g.saveEditedCell();
            // Check if $cfg['SaveCellsAtOnce'] is false
            if (!g.saveCellsAtOnce) {
                // Check if need_to_post is true
                if (saved) {
                    // Check if this function called from 'move' functions
                    if (options !== undefined && options.move) {
                        g.postEditedCell(options);
                    } else {
                        g.postEditedCell();
                    }
                // need_to_post is false
                } else {
                    // Check if this function called from 'move' functions
                    if (options !== undefined && options.move) {
                        g.hideEditCell(true);
                        g.showEditCell(options.cell);
                    // NOT called from 'move' functions
                    } else {
                        g.hideEditCell(true);
                    }
                }
            // $cfg['SaveCellsAtOnce'] is true
            } else {
                // If need_to_post
                if (saved) {
                    // If this function called from 'move' functions
                    if (options !== undefined && options.move) {
                        g.hideEditCell(true, true, false, options);
                        g.showEditCell(options.cell);
                    // NOT called from 'move' functions
                    } else {
                        g.hideEditCell(true, true);
                    }
                } else {
                    // If this function called from 'move' functions
                    if (options !== undefined && options.move) {
                        g.hideEditCell(true, false, false, options);
                        g.showEditCell(options.cell);
                    // NOT called from 'move' functions
                    } else {
                        g.hideEditCell(true);
                    }
                }
            }
        },

        /**
         * Initialize column resize feature.
         */
        initColResize: function () {
            // create column resizer div
            g.cRsz = document.createElement('div');
            g.cRsz.className = 'cRsz';

            // get data columns in the first row of the table
            var $firstRowCols = $(g.t).find('tr:first th.draggable');

            // create column borders
            $firstRowCols.each(function () {
                var cb = document.createElement('div'); // column border
                $(cb).addClass('colborder')
                    .mousedown(function (e) {
                        g.dragStartRsz(e, this);
                    });
                $(g.cRsz).append(cb);
            });
            g.reposRsz();

            // attach to global div
            $(g.gDiv).prepend(g.cRsz);
        },

        /**
         * Initialize column reordering feature.
         */
        initColReorder: function () {
            g.cCpy = document.createElement('div');     // column copy, to store copy of dragged column header
            g.cPointer = document.createElement('div'); // column pointer, used when reordering column

            // adjust g.cCpy
            g.cCpy.className = 'cCpy';
            $(g.cCpy).hide();

            // adjust g.cPointer
            g.cPointer.className = 'cPointer';
            $(g.cPointer).css('visibility', 'hidden');  // set visibility to hidden instead of calling hide() to force browsers to cache the image in cPointer class

            // assign column reordering hint
            g.reorderHint = PMA_messages.strColOrderHint;

            // get data columns in the first row of the table
            var $firstRowCols = $(g.t).find('tr:first th.draggable');

            // initialize column order
            $col_order = $(g.o).find('.col_order');   // check if column order is passed from PHP
            if ($col_order.length > 0) {
                g.colOrder = $col_order.val().split(',');
                for (var i = 0; i < g.colOrder.length; i++) {
                    g.colOrder[i] = parseInt(g.colOrder[i], 10);
                }
            } else {
                g.colOrder = [];
                for (var i = 0; i < $firstRowCols.length; i++) {
                    g.colOrder.push(i);
                }
            }

            // register events
            $(g.t).find('th.draggable')
                .mousedown(function (e) {
                    $(g.o).addClass("turnOffSelect");
                    if (g.visibleHeadersCount > 1) {
                        g.dragStartReorder(e, this);
                    }
                })
                .mouseenter(function () {
                    if (g.visibleHeadersCount > 1) {
                        $(this).css('cursor', 'move');
                    } else {
                        $(this).css('cursor', 'inherit');
                    }
                })
                .mouseleave(function () {
                    g.showReorderHint = false;
                    $(this).tooltip("option", {
                        content: g.updateHint()
                    });
                })
                .dblclick(function (e) {
                    e.preventDefault();
                    $("<div/>")
                    .prop("title", PMA_messages.strColNameCopyTitle)
                    .addClass("modal-copy")
                    .text(PMA_messages.strColNameCopyText)
                    .append(
                        $("<input/>")
                        .prop("readonly", true)
                        .val($(this).data("column"))
                        )
                    .dialog({
                        resizable: false,
                        modal: true
                    })
                    .find("input").focus().select();
                });
            $(g.t).find('th.draggable a')
                .dblclick(function (e) {
                    e.stopPropagation();
                });
            // restore column order when the restore button is clicked
            $(g.o).find('div.restore_column').click(function () {
                g.restoreColOrder();
            });

            // attach to global div
            $(g.gDiv).append(g.cPointer);
            $(g.gDiv).append(g.cCpy);

            // prevent default "dragstart" event when dragging a link
            $(g.t).find('th a').bind('dragstart', function () {
                return false;
            });

            // refresh the restore column button state
            g.refreshRestoreButton();
        },

        /**
         * Initialize column visibility feature.
         */
        initColVisib: function () {
            g.cDrop = document.createElement('div');    // column drop-down arrows
            g.cList = document.createElement('div');    // column visibility list

            // adjust g.cDrop
            g.cDrop.className = 'cDrop';

            // adjust g.cList
            g.cList.className = 'cList';
            $(g.cList).hide();

            // assign column visibility related hints
            g.showAllColText = PMA_messages.strShowAllCol;

            // get data columns in the first row of the table
            var $firstRowCols = $(g.t).find('tr:first th.draggable');

            var i;
            // initialize column visibility
            var $col_visib = $(g.o).find('.col_visib');   // check if column visibility is passed from PHP
            if ($col_visib.length > 0) {
                g.colVisib = $col_visib.val().split(',');
                for (i = 0; i < g.colVisib.length; i++) {
                    g.colVisib[i] = parseInt(g.colVisib[i], 10);
                }
            } else {
                g.colVisib = [];
                for (i = 0; i < $firstRowCols.length; i++) {
                    g.colVisib.push(1);
                }
            }

            // make sure we have more than one column
            if ($firstRowCols.length > 1) {
                var $colVisibTh = $(g.t).find('th:not(.draggable)');
                PMA_tooltip(
                    $colVisibTh,
                    'th',
                    PMA_messages.strColVisibHint
                );

                // create column visibility drop-down arrow(s)
                $colVisibTh.each(function () {
                        var $th = $(this);
                        var cd = document.createElement('div'); // column drop-down arrow
                        var pos = $th.position();
                        $(cd).addClass('coldrop')
                            .click(function () {
                                if (g.cList.style.display == 'none') {
                                    g.showColList(this);
                                } else {
                                    g.hideColList();
                                }
                            });
                        $(g.cDrop).append(cd);
                    });

                // add column visibility control
                g.cList.innerHTML = '<div class="lDiv"></div>';
                var $listDiv = $(g.cList).find('div');

                var tempClick = function () {
                    if (g.toggleCol($(this).index())) {
                        g.afterToggleCol();
                    }
                };

                for (i = 0; i < $firstRowCols.length; i++) {
                    var currHeader = $firstRowCols[i];
                    var listElmt = document.createElement('div');
                    $(listElmt).text($(currHeader).text())
                        .prepend('<input type="checkbox" ' + (g.colVisib[i] ? 'checked="checked" ' : '') + '/>');
                    $listDiv.append(listElmt);
                    // add event on click
                    $(listElmt).click(tempClick);
                }
                // add "show all column" button
                var showAll = document.createElement('div');
                $(showAll).addClass('showAllColBtn')
                    .text(g.showAllColText);
                $(g.cList).append(showAll);
                $(showAll).click(function () {
                    g.showAllColumns();
                });
                // prepend "show all column" button at top if the list is too long
                if ($firstRowCols.length > 10) {
                    var clone = showAll.cloneNode(true);
                    $(g.cList).prepend(clone);
                    $(clone).click(function () {
                        g.showAllColumns();
                    });
                }
            }

            // hide column visibility list if we move outside the list
            $(g.t).find('td, th.draggable').mouseenter(function () {
                g.hideColList();
            });

            // attach to global div
            $(g.gDiv).append(g.cDrop);
            $(g.gDiv).append(g.cList);

            // some adjustment
            g.reposDrop();
        },

        /**
         * Move currently Editing Cell to Up
         */
        moveUp: function(e) {
            e.preventDefault();
            var $this_field = $(g.currentEditCell);
            var field_name = getFieldName($(g.t), $this_field);

            var where_clause = $this_field.parents('tr').first().find('.where_clause').val();
            if (typeof where_clause === 'undefined') {
                where_clause = '';
            }
            var found = false;
            var $found_row;
            var $prev_row;
            var j = 0;

            $this_field.parents('tr').first().parents('tbody').children().each(function(){
                if ($(this).find('.where_clause').val() == where_clause) {
                    found = true;
                    $found_row = $(this);
                }
                if (!found) {
                    $prev_row = $(this);
                }
            });

            var new_cell;

            if (found && $prev_row) {
                $prev_row.children('td').each(function(){
                    if (getFieldName($(g.t), $(this)) == field_name) {
                        new_cell = this;
                    }
                });
            }

            if (new_cell) {
                g.hideEditCell(false, false, false, {move : true, cell : new_cell});
            }
        },

        /**
         * Move currently Editing Cell to Down
         */
        moveDown: function(e) {
            e.preventDefault();

            var $this_field = $(g.currentEditCell);
            var field_name = getFieldName($(g.t), $this_field);

            var where_clause = $this_field.parents('tr').first().find('.where_clause').val();
            if (typeof where_clause === 'undefined') {
                where_clause = '';
            }
            var found = false;
            var $found_row;
            var $next_row;
            var j = 0;
            var next_row_found = false;
            $this_field.parents('tr').first().parents('tbody').children().each(function(){
                if ($(this).find('.where_clause').val() == where_clause) {
                    found = true;
                    $found_row = $(this);
                }
                if (found) {
                    if (j >= 1 && ! next_row_found) {
                        $next_row = $(this);
                        next_row_found = true;
                    } else {
                        j++;
                    }
                }
            });

            var new_cell;
            if (found && $next_row) {
                $next_row.children('td').each(function(){
                    if (getFieldName($(g.t), $(this)) == field_name) {
                        new_cell = this;
                    }
                });
            }

            if (new_cell) {
                g.hideEditCell(false, false, false, {move : true, cell : new_cell});
            }
        },

        /**
         * Move currently Editing Cell to Left
         */
        moveLeft: function(e) {
            e.preventDefault();

            var $this_field = $(g.currentEditCell);
            var field_name = getFieldName($(g.t), $this_field);

            var where_clause = $this_field.parents('tr').first().find('.where_clause').val();
            if (typeof where_clause === 'undefined') {
                where_clause = '';
            }
            var found = false;
            var $found_row;
            var j = 0;
            $this_field.parents('tr').first().parents('tbody').children().each(function(){
                if ($(this).find('.where_clause').val() == where_clause) {
                    found = true;
                    $found_row = $(this);
                }
            });

            var left_cell;
            var cell_found = false;
            if (found) {
                $found_row.children('td.grid_edit').each(function(){
                    if (getFieldName($(g.t), $(this)) === field_name) {
                        cell_found = true;
                    }
                    if (!cell_found) {
                        left_cell = this;
                    }
                });
            }

            if (left_cell) {
                g.hideEditCell(false, false, false, {move : true, cell : left_cell});
            }
        },

        /**
         * Move currently Editing Cell to Right
         */
        moveRight: function(e) {
            e.preventDefault();

            var $this_field = $(g.currentEditCell);
            var field_name = getFieldName($(g.t), $this_field);

            var where_clause = $this_field.parents('tr').first().find('.where_clause').val();
            if (typeof where_clause === 'undefined') {
                where_clause = '';
            }
            var found = false;
            var $found_row;
            var j = 0;
            $this_field.parents('tr').first().parents('tbody').children().each(function(){
                if ($(this).find('.where_clause').val() == where_clause) {
                    found = true;
                    $found_row = $(this);
                }
            });

            var right_cell;
            var cell_found = false;
            var next_cell_found = false;
            if (found) {
                $found_row.children('td.grid_edit').each(function(){
                    if (getFieldName($(g.t), $(this)) === field_name) {
                        cell_found = true;
                    }
                    if (cell_found) {
                        if (j >= 1 && ! next_cell_found) {
                            right_cell = this;
                            next_cell_found = true;
                        } else {
                            j++;
                        }
                    }
                });
            }

            if (right_cell) {
                g.hideEditCell(false, false, false, {move : true, cell : right_cell});
            }
        },

        /**
         * Initialize grid editing feature.
         */
        initGridEdit: function () {

            function startGridEditing(e, cell) {
                if (g.isCellEditActive) {
                    g.saveOrPostEditedCell();
                } else {
                    g.showEditCell(cell);
                }
                e.stopPropagation();
            }

            function handleCtrlNavigation(e) {
                if ((e.ctrlKey && e.which == 38 ) || (e.altKey && e.which == 38)) {
                    g.moveUp(e);
                } else if ((e.ctrlKey && e.which == 40)  || (e.altKey && e.which == 40)) {
                    g.moveDown(e);
                } else if ((e.ctrlKey && e.which == 37 ) || (e.altKey && e.which == 37)) {
                    g.moveLeft(e);
                } else if ((e.ctrlKey && e.which == 39)  || (e.altKey && e.which == 39)) {
                    g.moveRight(e);
                }
            }

            // create cell edit wrapper element
            g.cEditStd = document.createElement('div');
            g.cEdit = g.cEditStd;
            g.cEditTextarea = document.createElement('div');

            // adjust g.cEditStd
            g.cEditStd.className = 'cEdit';
            $(g.cEditStd).html('<input class="edit_box" rows="1" ></input><div class="edit_area" />');
            $(g.cEditStd).hide();

            // adjust g.cEdit
            g.cEditTextarea.className = 'cEdit';
            $(g.cEditTextarea).html('<textarea class="edit_box" rows="1" ></textarea><div class="edit_area" />');
            $(g.cEditTextarea).hide();

            // assign cell editing hint
            g.cellEditHint = PMA_messages.strCellEditHint;
            g.saveCellWarning = PMA_messages.strSaveCellWarning;
            g.alertNonUnique = PMA_messages.strAlertNonUnique;
            g.gotoLinkText = PMA_messages.strGoToLink;

            // initialize cell editing configuration
            g.saveCellsAtOnce = $(g.o).find('.save_cells_at_once').val();
            g.maxTruncatedLen = PMA_commonParams.get('LimitChars');

            // register events
            $(g.t).find('td.data.click1')
                .click(function (e) {
                    startGridEditing(e, this);
                    // prevent default action when clicking on "link" in a table
                    if ($(e.target).is('.grid_edit a')) {
                        e.preventDefault();
                    }
                });

            $(g.t).find('td.data.click2')
                .click(function (e) {
                    var $cell = $(this);
                    // In the case of relational link, We want single click on the link
                    // to goto the link and double click to start grid-editing.
                    var $link = $(e.target);
                    if ($link.is('.grid_edit.relation a')) {
                        e.preventDefault();
                        // get the click count and increase
                        var clicks = $cell.data('clicks');
                        clicks = (typeof clicks === 'undefined') ? 1 : clicks + 1;

                        if (clicks == 1) {
                            // if there are no previous clicks,
                            // start the single click timer
                            var timer = setTimeout(function () {
                                // temporarily remove ajax class so the page loader will not handle it,
                                // submit and then add it back
                                $link.removeClass('ajax');
                                AJAX.requestHandler.call($link[0]);
                                $link.addClass('ajax');
                                $cell.data('clicks', 0);
                            }, 700);
                            $cell.data('clicks', clicks);
                            $cell.data('timer', timer);
                        } else {
                            // this is a double click, cancel the single click timer
                            // and make the click count 0
                            clearTimeout($cell.data('timer'));
                            $cell.data('clicks', 0);
                            // start grid-editing
                            startGridEditing(e, this);
                        }
                    }
                })
                .dblclick(function (e) {
                    if ($(e.target).is('.grid_edit a')) {
                        e.preventDefault();
                    } else {
                        startGridEditing(e, this);
                    }
                });

            $(g.cEditStd).on('keydown', 'input.edit_box, select', handleCtrlNavigation);

            $(g.cEditStd).find('.edit_box').focus(function () {
                g.showEditArea();
            });
            $(g.cEditStd).on('keydown', '.edit_box, select', function (e) {
                if (e.which == 13) {
                    // post on pressing "Enter"
                    e.preventDefault();
                    g.saveOrPostEditedCell();
                }
            });
            $(g.cEditStd).keydown(function (e) {
                if (!g.isEditCellTextEditable) {
                    // prevent text editing
                    e.preventDefault();
                }
            });

            $(g.cEditTextarea).on('keydown', 'textarea.edit_box, select', handleCtrlNavigation);

            $(g.cEditTextarea).find('.edit_box').focus(function () {
                g.showEditArea();
            });
            $(g.cEditTextarea).on('keydown', '.edit_box, select', function (e) {
                if (e.which == 13 && !e.shiftKey) {
                    // post on pressing "Enter"
                    e.preventDefault();
                    g.saveOrPostEditedCell();
                }
            });
            $(g.cEditTextarea).keydown(function (e) {
                if (!g.isEditCellTextEditable) {
                    // prevent text editing
                    e.preventDefault();
                }
            });
            $('html').click(function (e) {
                // hide edit cell if the click is not fromDat edit area
                if ($(e.target).parents().index($(g.cEdit)) == -1 &&
                    !$(e.target).parents('.ui-datepicker-header').length &&
                    !$('.browse_foreign_modal.ui-dialog:visible').length &&
                    !$(e.target).closest('.dismissable').length
                ) {
                    g.hideEditCell();
                }
            }).keydown(function (e) {
                if (e.which == 27 && g.isCellEditActive) {

                    // cancel on pressing "Esc"
                    g.hideEditCell(true);
                }
            });
            $(g.o).find('div.save_edited').click(function () {
                g.hideEditCell();
                g.postEditedCell();
            });
            $(window).bind('beforeunload', function () {
                if (g.isCellEdited) {
                    return g.saveCellWarning;
                }
            });

            // attach to global div
            $(g.gDiv).append(g.cEditStd);
            $(g.gDiv).append(g.cEditTextarea);

            // add hint for grid editing feature when hovering "Edit" link in each table row
            if (PMA_messages.strGridEditFeatureHint !== undefined) {
                PMA_tooltip(
                    $(g.t).find('.edit_row_anchor a'),
                    'a',
                    PMA_messages.strGridEditFeatureHint
                );
            }
        }
    };

    /******************
     * Initialize grid
     ******************/

    // wrap all truncated data cells with span indicating the original length
    // todo update the original length after a grid edit
    $(t).find('td.data.truncated:not(:has(span))')
        .wrapInner(function() {
            return '<span title="' + PMA_messages.strOriginalLength + ' ' +
                $(this).data('originallength') + '"></span>';
        });

    // wrap remaining cells, except actions cell, with span
    $(t).find('th, td:not(:has(span))')
        .wrapInner('<span />');

    // create grid elements
    g.gDiv = document.createElement('div');     // create global div

    // initialize the table variable
    g.t = t;

    // enclosing .sqlqueryresults div
    g.o = $(t).parents('.sqlqueryresults');

    // get data columns in the first row of the table
    var $firstRowCols = $(t).find('tr:first th.draggable');

    // initialize visible headers count
    g.visibleHeadersCount = $firstRowCols.filter(':visible').length;

    // assign first column (actions) span
    if (! $(t).find('tr:first th:first').hasClass('draggable')) {  // action header exist
        g.actionSpan = $(t).find('tr:first th:first').prop('colspan');
    } else {
        g.actionSpan = 0;
    }

    // assign table create time
    // table_create_time will only available if we are in "Browse" tab
    g.tableCreateTime = $(g.o).find('.table_create_time').val();

    // assign the hints
    g.sortHint = PMA_messages.strSortHint;
    g.strMultiSortHint = PMA_messages.strMultiSortHint;
    g.markHint = PMA_messages.strColMarkHint;
    g.copyHint = PMA_messages.strColNameCopyHint;

    // assign common hidden inputs
    var $common_hidden_inputs = $(g.o).find('div.common_hidden_inputs');
    g.token = $common_hidden_inputs.find('input[name=token]').val();
    g.server = $common_hidden_inputs.find('input[name=server]').val();
    g.db = $common_hidden_inputs.find('input[name=db]').val();
    g.table = $common_hidden_inputs.find('input[name=table]').val();

    // add table class
    $(t).addClass('pma_table');

    // add relative position to global div so that resize handlers are correctly positioned
    $(g.gDiv).css('position', 'relative');

    // link the global div
    $(t).before(g.gDiv);
    $(g.gDiv).append(t);

    // FEATURES
    enableResize    = enableResize === undefined ? true : enableResize;
    enableReorder   = enableReorder === undefined ? true : enableReorder;
    enableVisib     = enableVisib === undefined ? true : enableVisib;
    enableGridEdit  = enableGridEdit === undefined ? true : enableGridEdit;
    if (enableResize) {
        g.initColResize();
    }
    if (enableReorder &&
        $(g.o).find('table.navigation').length > 0)    // disable reordering for result from EXPLAIN or SHOW syntax, which do not have a table navigation panel
    {
        g.initColReorder();
    }
    if (enableVisib) {
        g.initColVisib();
    }
    if (enableGridEdit &&
        $(t).is('.ajax'))   // make sure we have the ajax class
    {
        g.initGridEdit();
    }

    // create tooltip for each <th> with draggable class
    PMA_tooltip(
            $(t).find("th.draggable"),
            'th',
            g.updateHint()
    );

    // register events for hint tooltip (anchors inside draggable th)
    $(t).find('th.draggable a')
        .mouseenter(function () {
            g.showSortHint = true;
            g.showMultiSortHint = true;
            $(t).find("th.draggable").tooltip("option", {
                content: g.updateHint()
            });
        })
        .mouseleave(function () {
            g.showSortHint = false;
            g.showMultiSortHint = false;
            $(t).find("th.draggable").tooltip("option", {
                content: g.updateHint()
            });
        });

    // register events for dragging-related feature
    if (enableResize || enableReorder) {
        $(document).mousemove(function (e) {
            g.dragMove(e);
        });
        $(document).mouseup(function (e) {
            $(g.o).removeClass("turnOffSelect");
            g.dragEnd(e);
        });
    }

    // some adjustment
    $(t).removeClass('data');
    $(g.gDiv).addClass('data');
}

/**
 * jQuery plugin to cancel selection in HTML code.
 */
(function ($) {
    $.fn.noSelect = function (p) { //no select plugin by Paulo P.Marinas
        var prevent = (p === null) ? true : p;
        var is_msie = navigator.userAgent.indexOf('MSIE') > -1 || !!window.navigator.userAgent.match(/Trident.*rv\:11\./);
        var is_firefox = navigator.userAgent.indexOf('Firefox') > -1;
        var is_safari = navigator.userAgent.indexOf("Safari") > -1;
        var is_opera = navigator.userAgent.indexOf("Presto") > -1;
        if (prevent) {
            return this.each(function () {
                if (is_msie || is_safari) {
                    $(this).bind('selectstart', function () {
                        return false;
                    });
                } else if (is_firefox) {
                    $(this).css('MozUserSelect', 'none');
                    $('body').trigger('focus');
                } else if (is_opera) {
                    $(this).bind('mousedown', function () {
                        return false;
                    });
                } else {
                    $(this).attr('unselectable', 'on');
                }
            });
        } else {
            return this.each(function () {
                if (is_msie || is_safari) {
                    $(this).unbind('selectstart');
                } else if (is_firefox) {
                    $(this).css('MozUserSelect', 'inherit');
                } else if (is_opera) {
                    $(this).unbind('mousedown');
                } else {
                    $(this).removeAttr('unselectable');
                }
            });
        }
    }; //end noSelect
})(jQuery);
;

/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @fileoverview    functions used wherever an sql query form is used
 *
 * @requires    jQuery
 * @requires    js/functions.js
 *
 */

var $data_a;
var prevScrollX = 0;

/**
 * decode a string URL_encoded
 *
 * @param string str
 * @return string the URL-decoded string
 */
function PMA_urldecode(str)
{
    if (typeof str !== 'undefined') {
        return decodeURIComponent(str.replace(/\+/g, '%20'));
    }
}

/**
 * endecode a string URL_decoded
 *
 * @param string str
 * @return string the URL-encoded string
 */
function PMA_urlencode(str)
{
    if (typeof str !== 'undefined') {
        return encodeURIComponent(str).replace(/\%20/g, '+');
    }
}

/**
 * Saves SQL query in local storage or cooie
 *
 * @param string SQL query
 * @return void
 */
function PMA_autosaveSQL(query)
{
    if (query) {
        if (isStorageSupported('localStorage')) {
            window.localStorage.auto_saved_sql = query;
        } else {
            $.cookie('auto_saved_sql', query);
        }
    }
}

/**
 * Get the field name for the current field.  Required to construct the query
 * for grid editing
 *
 * @param $table_results enclosing results table
 * @param $this_field    jQuery object that points to the current field's tr
 */
function getFieldName($table_results, $this_field)
{

    var this_field_index = $this_field.index();
    // ltr or rtl direction does not impact how the DOM was generated
    // check if the action column in the left exist
    var left_action_exist = !$table_results.find('th:first').hasClass('draggable');
    // number of column span for checkbox and Actions
    var left_action_skip = left_action_exist ? $table_results.find('th:first').attr('colspan') - 1 : 0;

    // If this column was sorted, the text of the a element contains something
    // like <small>1</small> that is useful to indicate the order in case
    // of a sort on multiple columns; however, we dont want this as part
    // of the column name so we strip it ( .clone() to .end() )
    var field_name = $table_results
        .find('thead')
        .find('th:eq(' + (this_field_index - left_action_skip) + ') a')
        .clone()    // clone the element
        .children() // select all the children
        .remove()   // remove all of them
        .end()      // go back to the selected element
        .text();    // grab the text
    // happens when just one row (headings contain no a)
    if (field_name === '') {
        var $heading = $table_results.find('thead').find('th:eq(' + (this_field_index - left_action_skip) + ')').children('span');
        // may contain column comment enclosed in a span - detach it temporarily to read the column name
        var $tempColComment = $heading.children().detach();
        field_name = $heading.text();
        // re-attach the column comment
        $heading.append($tempColComment);
    }

    field_name = $.trim(field_name);

    return field_name;
}

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('sql.js', function () {
    $(document).off('click', 'a.delete_row.ajax');
    $(document).off('submit', '.bookmarkQueryForm');
    $('input#bkm_label').unbind('keyup');
    $(document).off('makegrid', ".sqlqueryresults");
    $(document).off('stickycolumns', ".sqlqueryresults");
    $("#togglequerybox").unbind('click');
    $(document).off('click', "#button_submit_query");
    $(document).off('change', '#id_bookmark');
    $("input[name=bookmark_variable]").unbind("keypress");
    $(document).off('submit', "#sqlqueryform.ajax");
    $(document).off('click', "input[name=navig].ajax");
    $(document).off('submit', "form[name='displayOptionsForm'].ajax");
    $(document).off('mouseenter', 'th.column_heading.pointer');
    $(document).off('mouseleave', 'th.column_heading.pointer');
    $(document).off('click', 'th.column_heading.marker');
    $(window).unbind('scroll');
    $(document).off("keyup", ".filter_rows");
    $(document).off('click', "#printView");
    if (codemirror_editor) {
        codemirror_editor.off('change');
    } else {
        $('#sqlquery').off('input propertychange');
    }
    $('body').off('click', '.navigation .showAllRows');
    $('body').off('click','a.browse_foreign');
    $('body').off('click', '#simulate_dml');
    $('body').off('keyup', '#sqlqueryform');
    $('body').off('click', 'form[name="resultsForm"].ajax button[name="submit_mult"], form[name="resultsForm"].ajax input[name="submit_mult"]');
});

/**
 * @description <p>Ajax scripts for sql and browse pages</p>
 *
 * Actions ajaxified here:
 * <ul>
 * <li>Retrieve results of an SQL query</li>
 * <li>Paginate the results table</li>
 * <li>Sort the results table</li>
 * <li>Change table according to display options</li>
 * <li>Grid editing of data</li>
 * <li>Saving a bookmark</li>
 * </ul>
 *
 * @name        document.ready
 * @memberOf    jQuery
 */
AJAX.registerOnload('sql.js', function () {

    $(function () {
        if (codemirror_editor) {
            codemirror_editor.on('change', function () {
                PMA_autosaveSQL(codemirror_editor.getValue());
            });
        } else {
            $('#sqlquery').on('input propertychange', function () {
                PMA_autosaveSQL($('#sqlquery').val());
            });
        }
    });

    // Delete row from SQL results
    $(document).on('click', 'a.delete_row.ajax', function (e) {
        e.preventDefault();
        var question =  PMA_sprintf(PMA_messages.strDoYouReally, escapeHtml($(this).closest('td').find('div').text()));
        var $link = $(this);
        $link.PMA_confirm(question, $link.attr('href'), function (url) {
            $msgbox = PMA_ajaxShowMessage();
            if ($link.hasClass('formLinkSubmit')) {
                submitFormLink($link);
            } else {
                var params = {
                    'ajax_request': true,
                    'is_js_confirmed': true,
                    'token': PMA_commonParams.get('token')
                };
                $.post(url, params, function (data) {
                    if (data.success) {
                        PMA_ajaxShowMessage(data.message);
                        $link.closest('tr').remove();
                    } else {
                        PMA_ajaxShowMessage(data.error, false);
                    }
                });
            }
        });
    });

    // Ajaxification for 'Bookmark this SQL query'
    $(document).on('submit', '.bookmarkQueryForm', function (e) {
        e.preventDefault();
        PMA_ajaxShowMessage();
        $.post($(this).attr('action'), 'ajax_request=1&' + $(this).serialize(), function (data) {
            if (data.success) {
                PMA_ajaxShowMessage(data.message);
            } else {
                PMA_ajaxShowMessage(data.error, false);
            }
        });
    });

    /* Hides the bookmarkoptions checkboxes when the bookmark label is empty */
    $('input#bkm_label').keyup(function () {
        $('input#id_bkm_all_users, input#id_bkm_replace')
            .parent()
            .toggle($(this).val().length > 0);
    }).trigger('keyup');

    /**
     * Attach Event Handler for 'Copy to clipbpard
     */
    $(document).on('click', "#copyToClipBoard", function (event) {
        event.preventDefault();

        var textArea = document.createElement("textarea");

        //
        // *** This styling is an extra step which is likely not required. ***
        //
        // Why is it here? To ensure:
        // 1. the element is able to have focus and selection.
        // 2. if element was to flash render it has minimal visual impact.
        // 3. less flakyness with selection and copying which **might** occur if
        //    the textarea element is not visible.
        //
        // The likelihood is the element won't even render, not even a flash,
        // so some of these are just precautions. However in IE the element
        // is visible whilst the popup box asking the user for permission for
        // the web page to copy to the clipboard.
        //

        // Place in top-left corner of screen regardless of scroll position.
        textArea.style.position = 'fixed';
        textArea.style.top = 0;
        textArea.style.left = 0;

        // Ensure it has a small width and height. Setting to 1px / 1em
        // doesn't work as this gives a negative w/h on some browsers.
        textArea.style.width = '2em';
        textArea.style.height = '2em';

        // We don't need padding, reducing the size if it does flash render.
        textArea.style.padding = 0;

        // Clean up any borders.
        textArea.style.border = 'none';
        textArea.style.outline = 'none';
        textArea.style.boxShadow = 'none';

        // Avoid flash of white box if rendered for any reason.
        textArea.style.background = 'transparent';

        textArea.value = '';

        $('#serverinfo a').each(function(){
            textArea.value += $(this).text().split(':')[1].trim() + '/';
        });
        textArea.value += '\t\t' + window.location.href;
        textArea.value += '\n';

        $('.notice,.success').each(function(){
            textArea.value += $(this).text() + '\n\n';
        });

        $('.sql pre').each(function() {
            textArea.value += $(this).text() + '\n\n';
        });

        $('.table_results .column_heading a').each(function() {
            textArea.value += $(this).text() + '\t';
        });

        textArea.value += '\n';
        $('.table_results tbody tr').each(function() {
            $(this).find('.data span').each(function(){
                textArea.value += $(this).text() + '\t';
            });
            textArea.value += '\n';
        });

        document.body.appendChild(textArea);

        textArea.select();

        try {
            document.execCommand('copy');
        } catch (err) {
            alert('Sorry! Unable to copy');
        }

        document.body.removeChild(textArea);

    }); //end of Copy to Clipboard action

    /**
     * Attach Event Handler for 'Print' link
     */
    $(document).on('click', "#printView", function (event) {
        event.preventDefault();

        // Take to preview mode
        printPreview();
    }); //end of 'Print' action

    /**
     * Attach the {@link makegrid} function to a custom event, which will be
     * triggered manually everytime the table of results is reloaded
     * @memberOf    jQuery
     */
    $(document).on('makegrid', ".sqlqueryresults", function () {
        $('.table_results').each(function () {
            PMA_makegrid(this);
        });
    });

    /*
     * Attach a custom event for sticky column headings which will be
     * triggered manually everytime the table of results is reloaded
     * @memberOf    jQuery
     */
    $(document).on('stickycolumns', ".sqlqueryresults", function () {
        $(".sticky_columns").remove();
        $(".table_results").each(function () {
            var $table_results = $(this);
            //add sticky columns div
            var $stick_columns = initStickyColumns($table_results);
            rearrangeStickyColumns($stick_columns, $table_results);
            //adjust sticky columns on scroll
            $(window).bind('scroll', function() {
                handleStickyColumns($stick_columns, $table_results);
            });
        });
    });

    /**
     * Append the "Show/Hide query box" message to the query input form
     *
     * @memberOf jQuery
     * @name    appendToggleSpan
     */
    // do not add this link more than once
    if (! $('#sqlqueryform').find('a').is('#togglequerybox')) {
        $('<a id="togglequerybox"></a>')
        .html(PMA_messages.strHideQueryBox)
        .appendTo("#sqlqueryform")
        // initially hidden because at this point, nothing else
        // appears under the link
        .hide();

        // Attach the toggling of the query box visibility to a click
        $("#togglequerybox").bind('click', function () {
            var $link = $(this);
            $link.siblings().slideToggle("fast");
            if ($link.text() == PMA_messages.strHideQueryBox) {
                $link.text(PMA_messages.strShowQueryBox);
                // cheap trick to add a spacer between the menu tabs
                // and "Show query box"; feel free to improve!
                $('#togglequerybox_spacer').remove();
                $link.before('<br id="togglequerybox_spacer" />');
            } else {
                $link.text(PMA_messages.strHideQueryBox);
            }
            // avoid default click action
            return false;
        });
    }


    /**
     * Event handler for sqlqueryform.ajax button_submit_query
     *
     * @memberOf    jQuery
     */
    $(document).on('click', "#button_submit_query", function (event) {
        $(".success,.error").hide();
        //hide already existing error or success message
        var $form = $(this).closest("form");
        // the Go button related to query submission was clicked,
        // instead of the one related to Bookmarks, so empty the
        // id_bookmark selector to avoid misinterpretation in
        // import.php about what needs to be done
        $form.find("select[name=id_bookmark]").val("");
        // let normal event propagation happen
    });

    /**
     * Event handler to show appropiate number of variable boxes
     * based on the bookmarked query
     */
    $(document).on('change', '#id_bookmark', function (event) {

        var varCount = $(this).find('option:selected').data('varcount');
        if (typeof varCount == 'undefined') {
            varCount = 0;
        }

        var $varDiv = $('#bookmark_variables');
        $varDiv.empty();
        for (var i = 1; i <= varCount; i++) {
            $varDiv.append($('<label for="bookmark_variable_' + i + '">' + PMA_sprintf(PMA_messages.strBookmarkVariable, i) + '</label>'));
            $varDiv.append($('<input type="text" size="10" name="bookmark_variable[' + i + ']" id="bookmark_variable_' + i + '"></input>'));
        }

        if (varCount == 0) {
            $varDiv.parent('.formelement').hide();
        } else {
            $varDiv.parent('.formelement').show();
        }
    });

    /**
     * Event handler for hitting enter on sqlqueryform bookmark_variable
     * (the Variable textfield in Bookmarked SQL query section)
     *
     * @memberOf    jQuery
     */
    $("input[name=bookmark_variable]").bind("keypress", function (event) {
        // force the 'Enter Key' to implicitly click the #button_submit_bookmark
        var keycode = (event.keyCode ? event.keyCode : (event.which ? event.which : event.charCode));
        if (keycode == 13) { // keycode for enter key
            // When you press enter in the sqlqueryform, which
            // has 2 submit buttons, the default is to run the
            // #button_submit_query, because of the tabindex
            // attribute.
            // This submits #button_submit_bookmark instead,
            // because when you are in the Bookmarked SQL query
            // section and hit enter, you expect it to do the
            // same action as the Go button in that section.
            $("#button_submit_bookmark").click();
            return false;
        } else  {
            return true;
        }
    });

    /**
     * Ajax Event handler for 'SQL Query Submit'
     *
     * @see         PMA_ajaxShowMessage()
     * @memberOf    jQuery
     * @name        sqlqueryform_submit
     */
    $(document).on('submit', "#sqlqueryform.ajax", function (event) {
        event.preventDefault();

        var $form = $(this);
        if (codemirror_editor) {
            $form[0].elements.sql_query.value = codemirror_editor.getValue();
        }
        if (! checkSqlQuery($form[0])) {
            return false;
        }

        // remove any div containing a previous error message
        $('div.error').remove();

        var $msgbox = PMA_ajaxShowMessage();
        var $sqlqueryresultsouter = $('#sqlqueryresultsouter');

        PMA_prepareForAjaxRequest($form);

        $.post($form.attr('action'), $form.serialize() + '&ajax_page_request=true', function (data) {
            if (typeof data !== 'undefined' && data.success === true) {
                // success happens if the query returns rows or not

                // show a message that stays on screen
                if (typeof data.action_bookmark != 'undefined') {
                    // view only
                    if ('1' == data.action_bookmark) {
                        $('#sqlquery').text(data.sql_query);
                        // send to codemirror if possible
                        setQuery(data.sql_query);
                    }
                    // delete
                    if ('2' == data.action_bookmark) {
                        $("#id_bookmark option[value='" + data.id_bookmark + "']").remove();
                        // if there are no bookmarked queries now (only the empty option),
                        // remove the bookmark section
                        if ($('#id_bookmark option').length == 1) {
                            $('#fieldsetBookmarkOptions').hide();
                            $('#fieldsetBookmarkOptionsFooter').hide();
                        }
                    }
                }
                $sqlqueryresultsouter
                    .show()
                    .html(data.message);
                PMA_highlightSQL($sqlqueryresultsouter);

                if (data._menu) {
                    if (history && history.pushState) {
                        history.replaceState({
                                menu : data._menu
                            },
                            null
                        );
                        AJAX.handleMenu.replace(data._menu);
                    } else {
                        PMA_MicroHistory.menus.replace(data._menu);
                        PMA_MicroHistory.menus.add(data._menuHash, data._menu);
                    }
                } else if (data._menuHash) {
                    if (! (history && history.pushState)) {
                        PMA_MicroHistory.menus.replace(PMA_MicroHistory.menus.get(data._menuHash));
                    }
                }

                if (data._params) {
                    PMA_commonParams.setAll(data._params);
                }

                if (typeof data.ajax_reload != 'undefined') {
                    if (data.ajax_reload.reload) {
                        if (data.ajax_reload.table_name) {
                            PMA_commonParams.set('table', data.ajax_reload.table_name);
                            PMA_commonActions.refreshMain();
                        } else {
                            PMA_reloadNavigation();
                        }
                    }
                } else if (typeof data.reload != 'undefined') {
                    // this happens if a USE or DROP command was typed
                    PMA_commonActions.setDb(data.db);
                    var url;
                    if (data.db) {
                        if (data.table) {
                            url = 'table_sql.php';
                        } else {
                            url = 'db_sql.php';
                        }
                    } else {
                        url = 'server_sql.php';
                    }
                    PMA_commonActions.refreshMain(url, function () {
                        $('#sqlqueryresultsouter')
                            .show()
                            .html(data.message);
                        PMA_highlightSQL($('#sqlqueryresultsouter'));
                    });
                }

                $('.sqlqueryresults').trigger('makegrid').trigger('stickycolumns');
                $('#togglequerybox').show();
                PMA_init_slider();

                if (typeof data.action_bookmark == 'undefined') {
                    if ($('#sqlqueryform input[name="retain_query_box"]').is(':checked') !== true) {
                        if ($("#togglequerybox").siblings(":visible").length > 0) {
                            $("#togglequerybox").trigger('click');
                        }
                    }
                }
            } else if (typeof data !== 'undefined' && data.success === false) {
                // show an error message that stays on screen
                $sqlqueryresultsouter
                    .show()
                    .html(data.error);
            }
            PMA_ajaxRemoveMessage($msgbox);
        }); // end $.post()
    }); // end SQL Query submit

    /**
     * Ajax Event handler for the display options
     * @memberOf    jQuery
     * @name        displayOptionsForm_submit
     */
    $(document).on('submit', "form[name='displayOptionsForm'].ajax", function (event) {
        event.preventDefault();

        $form = $(this);

        var $msgbox = PMA_ajaxShowMessage();
        $.post($form.attr('action'), $form.serialize() + '&ajax_request=true', function (data) {
            PMA_ajaxRemoveMessage($msgbox);
            var $sqlqueryresults = $form.parents(".sqlqueryresults");
            $sqlqueryresults
             .html(data.message)
             .trigger('makegrid')
             .trigger('stickycolumns');
            PMA_init_slider();
            PMA_highlightSQL($sqlqueryresults);
        }); // end $.post()
    }); //end displayOptionsForm handler

    // Filter row handling. --STARTS--
    $(document).on("keyup", ".filter_rows", function () {
        var unique_id = $(this).data("for");
        var $target_table = $(".table_results[data-uniqueId='" + unique_id + "']");
        var $header_cells = $target_table.find("th[data-column]");
        var target_columns = Array();
        // To handle colspan=4, in case of edit,copy etc options.
        var dummy_th = ($(".edit_row_anchor").length !== 0 ?
            '<th class="hide dummy_th"></th><th class="hide dummy_th"></th><th class="hide dummy_th"></th>'
            : '');
        // Selecting columns that will be considered for filtering and searching.
        $header_cells.each(function () {
            target_columns.push($.trim($(this).text()));
        });

        var phrase = $(this).val();
        // Set same value to both Filter rows fields.
        $(".filter_rows[data-for='" + unique_id + "']").not(this).val(phrase);
        // Handle colspan.
        $target_table.find("thead > tr").prepend(dummy_th);
        $.uiTableFilter($target_table, phrase, target_columns);
        $target_table.find("th.dummy_th").remove();
    });
    // Filter row handling. --ENDS--

    // Prompt to confirm on Show All
    $('body').on('click', '.navigation .showAllRows', function (e) {
        e.preventDefault();
        var $form = $(this).parents('form');

        if (! $(this).is(':checked')) { // already showing all rows
            submitShowAllForm();
        } else {
            $form.PMA_confirm(PMA_messages.strShowAllRowsWarning, $form.attr('action'), function (url) {
                submitShowAllForm();
            });
        }

        function submitShowAllForm() {
            var submitData = $form.serialize() + '&ajax_request=true&ajax_page_request=true';
            PMA_ajaxShowMessage();
            AJAX.source = $form;
            $.post($form.attr('action'), submitData, AJAX.responseHandler);
        }
    });

    $('body').on('keyup', '#sqlqueryform', function () {
        PMA_handleSimulateQueryButton();
    });

    /**
     * Ajax event handler for 'Simulate DML'.
     */
    $('body').on('click', '#simulate_dml', function () {
        var $form = $('#sqlqueryform');
        var query = '';
        var delimiter = $('#id_sql_delimiter').val();
        var db_name = $form.find('input[name="db"]').val();

        if (codemirror_editor) {
            query = codemirror_editor.getValue();
        } else {
            query = $('#sqlquery').val();
        }

        if (query.length === 0) {
            alert(PMA_messages.strFormEmpty);
            $('#sqlquery').focus();
            return false;
        }

        var $msgbox = PMA_ajaxShowMessage();
        $.ajax({
            type: 'POST',
            url: $form.attr('action'),
            data: {
                token: PMA_commonParams.get('token'),
                server: PMA_commonParams.get('server'),
                db: db_name,
                ajax_request: '1',
                simulate_dml: '1',
                sql_query: query,
                sql_delimiter: delimiter
            },
            success: function (response) {
                PMA_ajaxRemoveMessage($msgbox);
                if (response.success) {
                    var dialog_content = '<div class="preview_sql">';
                    if (response.sql_data) {
                        var len = response.sql_data.length;
                        for (var i=0; i<len; i++) {
                            dialog_content += '<strong>' + PMA_messages.strSQLQuery +
                                '</strong>' + response.sql_data[i].sql_query +
                                PMA_messages.strMatchedRows +
                                ' <a href="' + response.sql_data[i].matched_rows_url +
                                '">' + response.sql_data[i].matched_rows + '</a><br>';
                            if (i<len-1) {
                                dialog_content += '<hr>';
                            }
                        }
                    } else {
                        dialog_content += response.message;
                    }
                    dialog_content += '</div>';
                    var $dialog_content = $(dialog_content);
                    var button_options = {};
                    button_options[PMA_messages.strClose] = function () {
                        $(this).dialog('close');
                    };
                    var $response_dialog = $('<div />').append($dialog_content).dialog({
                        minWidth: 540,
                        maxHeight: 400,
                        modal: true,
                        buttons: button_options,
                        title: PMA_messages.strSimulateDML,
                        open: function () {
                            PMA_highlightSQL($(this));
                        },
                        close: function () {
                            $(this).remove();
                        }
                    });
                } else {
                    PMA_ajaxShowMessage(response.error);
                }
            },
            error: function (response) {
                PMA_ajaxShowMessage(PMA_messages.strErrorProcessingRequest);
            }
        });
    });

    /**
     * Handles multi submits of results browsing page such as edit, delete and export
     */
    $('body').on('click', 'form[name="resultsForm"].ajax button[name="submit_mult"], form[name="resultsForm"].ajax input[name="submit_mult"]', function (e) {
        e.preventDefault();
        var $button = $(this);
        var $form = $button.closest('form');
        var submitData = $form.serialize() + '&ajax_request=true&ajax_page_request=true&submit_mult=' + $button.val();
        PMA_ajaxShowMessage();
        AJAX.source = $form;
        $.post($form.attr('action'), submitData, AJAX.responseHandler);
    });
}); // end $()

/**
 * Starting from some th, change the class of all td under it.
 * If isAddClass is specified, it will be used to determine whether to add or remove the class.
 */
function PMA_changeClassForColumn($this_th, newclass, isAddClass)
{
    // index 0 is the th containing the big T
    var th_index = $this_th.index();
    var has_big_t = $this_th.closest('tr').children(':first').hasClass('column_action');
    // .eq() is zero-based
    if (has_big_t) {
        th_index--;
    }
    var $table = $this_th.parents('.table_results');
    if (! $table.length) {
        $table = $this_th.parents('table').siblings('.table_results');
    }
    var $tds = $table.find('tbody tr').find('td.data:eq(' + th_index + ')');
    if (isAddClass === undefined) {
        $tds.toggleClass(newclass);
    } else {
        $tds.toggleClass(newclass, isAddClass);
    }
}

/**
 * Handles browse foreign values modal dialog
 *
 * @param object $this_a reference to the browse foreign value link
 */
function browseForeignDialog($this_a)
{
    var formId = '#browse_foreign_form';
    var showAllId = '#foreign_showAll';
    var tableId = '#browse_foreign_table';
    var filterId = '#input_foreign_filter';
    var $dialog = null;
    $.get($this_a.attr('href'), {'ajax_request': true}, function (data) {
        // Creates browse foreign value dialog
        $dialog = $('<div>').append(data.message).dialog({
            title: PMA_messages.strBrowseForeignValues,
            width: Math.min($(window).width() - 100, 700),
            maxHeight: $(window).height() - 100,
            dialogClass: 'browse_foreign_modal',
            close: function (ev, ui) {
                // remove event handlers attached to elements related to dialog
                $(tableId).off('click', 'td a.foreign_value');
                $(formId).off('click', showAllId);
                $(formId).off('submit');
                // remove dialog itself
                $(this).remove();
            },
            modal: true
        });
    }).done(function () {
        var showAll = false;
        $(tableId).on('click', 'td a.foreign_value', function (e) {
            e.preventDefault();
            var $input = $this_a.prev('input[type=text]');
            // Check if input exists or get CEdit edit_box
            if ($input.length === 0 ) {
                $input = $this_a.closest('.edit_area').prev('.edit_box');
            }
            // Set selected value as input value
            $input.val($(this).data('key'));
            $dialog.dialog('close');
        });
        $(formId).on('click', showAllId, function () {
            showAll = true;
        });
        $(formId).on('submit', function (e) {
            e.preventDefault();
            // if filter value is not equal to old value
            // then reset page number to 1
            if ($(filterId).val() != $(filterId).data('old')) {
                $(formId).find('select[name=pos]').val('0');
            }
            var postParams = $(this).serializeArray();
            // if showAll button was clicked to submit form then
            // add showAll button parameter to form
            if (showAll) {
                postParams.push({
                    name: $(showAllId).attr('name'),
                    value: $(showAllId).val()
                });
            }
            // updates values in dialog
            $.post($(this).attr('action') + '?ajax_request=1', postParams, function (data) {
                var $obj = $('<div>').html(data.message);
                $(formId).html($obj.find(formId).html());
                $(tableId).html($obj.find(tableId).html());
            });
            showAll = false;
        });
    });
}

AJAX.registerOnload('sql.js', function () {
    $('body').on('click', 'a.browse_foreign', function (e) {
        e.preventDefault();
        browseForeignDialog($(this));
    });

    /**
     * vertical column highlighting in horizontal mode when hovering over the column header
     */
    $(document).on('mouseenter', 'th.column_heading.pointer', function (e) {
        PMA_changeClassForColumn($(this), 'hover', true);
    });
    $(document).on('mouseleave', 'th.column_heading.pointer', function (e) {
        PMA_changeClassForColumn($(this), 'hover', false);
    });

    /**
     * vertical column marking in horizontal mode when clicking the column header
     */
    $(document).on('click', 'th.column_heading.marker', function () {
        PMA_changeClassForColumn($(this), 'marked');
    });

    /**
     * create resizable table
     */
    $(".sqlqueryresults").trigger('makegrid').trigger('stickycolumns');
});

/*
 * Profiling Chart
 */
function makeProfilingChart()
{
    if ($('#profilingchart').length === 0 ||
        $('#profilingchart').html().length !== 0 ||
        !$.jqplot || !$.jqplot.Highlighter || !$.jqplot.PieRenderer
    ) {
        return;
    }

    var data = [];
    $.each(JSON.parse($('#profilingChartData').html()), function (key, value) {
        data.push([key, parseFloat(value)]);
    });

    // Remove chart and data divs contents
    $('#profilingchart').html('').show();
    $('#profilingChartData').html('');

    PMA_createProfilingChart('profilingchart', data);
}

/*
 * initialize profiling data tables
 */
function initProfilingTables()
{
    if (!$.tablesorter) {
        return;
    }

    $('#profiletable').tablesorter({
        widgets: ['zebra'],
        sortList: [[0, 0]],
        textExtraction: function (node) {
            if (node.children.length > 0) {
                return node.children[0].innerHTML;
            } else {
                return node.innerHTML;
            }
        }
    });

    $('#profilesummarytable').tablesorter({
        widgets: ['zebra'],
        sortList: [[1, 1]],
        textExtraction: function (node) {
            if (node.children.length > 0) {
                return node.children[0].innerHTML;
            } else {
                return node.innerHTML;
            }
        }
    });
}

/*
 * Set position, left, top, width of sticky_columns div
 */
function setStickyColumnsPosition($sticky_columns, $table_results, position, top, left, margin_left) {
    $sticky_columns
        .css("position", position)
        .css("top", top)
        .css("left", left ? left : "auto")
        .css("margin-left", margin_left ? margin_left : "0px")
        .css("width", $table_results.width());
}

/*
 * Initialize sticky columns
 */
function initStickyColumns($table_results) {
    return $('<table class="sticky_columns"></table>')
            .insertBefore($table_results)
            .css("position", "fixed")
            .css("z-index", "99")
            .css("width", $table_results.width())
            .css("margin-left", $('#page_content').css("margin-left"))
            .css("top", $('#floating_menubar').height())
            .css("display", "none");
}

/*
 * Arrange/Rearrange columns in sticky header
 */
function rearrangeStickyColumns($sticky_columns, $table_results) {
    var $originalHeader = $table_results.find("thead");
    var $originalColumns = $originalHeader.find("tr:first").children();
    var $clonedHeader = $originalHeader.clone();
    // clone width per cell
    $clonedHeader.find("tr:first").children().width(function(i,val) {
        var width = $originalColumns.eq(i).width();
        var is_firefox = navigator.userAgent.indexOf('Firefox') > -1;
        if (! is_firefox) {
            width += 1;
        }
        return width;
    });
    $sticky_columns.empty().append($clonedHeader);
}

/*
 * Adjust sticky columns on horizontal/vertical scroll for all tables
 */
function handleAllStickyColumns() {
    $('.sticky_columns').each(function () {
        handleStickyColumns($(this), $(this).next('.table_results'));
    });
}

/*
 * Adjust sticky columns on horizontal/vertical scroll
 */
function handleStickyColumns($sticky_columns, $table_results) {
    var currentScrollX = $(window).scrollLeft();
    var windowOffset = $(window).scrollTop();
    var tableStartOffset = $table_results.offset().top;
    var tableEndOffset = tableStartOffset + $table_results.height();
    if (windowOffset >= tableStartOffset && windowOffset <= tableEndOffset) {
        //for horizontal scrolling
        if(prevScrollX != currentScrollX) {
            prevScrollX = currentScrollX;
            setStickyColumnsPosition($sticky_columns, $table_results, "absolute", $('#floating_menubar').height() + windowOffset - tableStartOffset);
        //for vertical scrolling
        } else {
            setStickyColumnsPosition($sticky_columns, $table_results, "fixed", $('#floating_menubar').height(), $("#pma_navigation").width() - currentScrollX, $('#page_content').css("margin-left"));
        }
        $sticky_columns.show();
    } else {
        $sticky_columns.hide();
    }
}

AJAX.registerOnload('sql.js', function () {
    makeProfilingChart();
    initProfilingTables();
});
;

/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @fileoverview    functions used in GIS data editor
 *
 * @requires    jQuery
 *
 */

var gisEditorLoaded = false;

/**
 * Closes the GIS data editor and perform necessary clean up work.
 */
function closeGISEditor() {
    $("#popup_background").fadeOut("fast");
    $("#gis_editor").fadeOut("fast", function () {
        $(this).empty();
    });
}

/**
 * Prepares the HTML received via AJAX.
 */
function prepareJSVersion() {
    // Change the text on the submit button
    $("#gis_editor").find("input[name='gis_data[save]']")
        .val(PMA_messages.strCopy)
        .insertAfter($('#gis_data_textarea'))
        .before('<br/><br/>');

    // Add close and cancel links
    $('#gis_data_editor').prepend('<a class="close_gis_editor" href="#">' + PMA_messages.strClose + '</a>');
    $('<a class="cancel_gis_editor" href="#"> ' + PMA_messages.strCancel + '</a>')
        .insertAfter($("input[name='gis_data[save]']"));

    // Remove the unnecessary text
    $('div#gis_data_output p').remove();

    // Remove 'add' buttons and add links
    $('#gis_editor').find('input.add').each(function (e) {
        var $button = $(this);
        $button.addClass('addJs').removeClass('add');
        var classes = $button.attr('class');
        $button.replaceWith(
            '<a class="' + classes +
            '" name="' + $button.attr('name') +
            '" href="#">+ ' + $button.val() + '</a>'
        );
    });
}

/**
 * Returns the HTML for a data point.
 *
 * @param pointNumber point number
 * @param prefix      prefix of the name
 * @returns the HTML for a data point
 */
function addDataPoint(pointNumber, prefix) {
    return '<br/>' +
        PMA_sprintf(PMA_messages.strPointN, (pointNumber + 1)) + ': ' +
        '<label for="x">' + PMA_messages.strX + '</label>' +
        '<input type="text" name="' + prefix + '[' + pointNumber + '][x]" value=""/>' +
        '<label for="y">' + PMA_messages.strY + '</label>' +
        '<input type="text" name="' + prefix + '[' + pointNumber + '][y]" value=""/>';
}

/**
 * Initialize the visualization in the GIS data editor.
 */
function initGISEditorVisualization() {
    // Loads either SVG or OSM visualization based on the choice
    selectVisualization();
    // Adds necessary styles to the div that coontains the openStreetMap
    styleOSM();
    // Loads the SVG element and make a reference to it
    loadSVG();
    // Adds controllers for zooming and panning
    addZoomPanControllers();
    zoomAndPan();
}

/**
 * Loads JavaScript files and the GIS editor.
 *
 * @param value      current value of the geometry field
 * @param field      field name
 * @param type       geometry type
 * @param input_name name of the input field
 * @param token      token
 */
function loadJSAndGISEditor(value, field, type, input_name, token) {
    var head = document.getElementsByTagName('head')[0];
    var script;

    // Loads a set of small JS file needed for the GIS editor
    var smallScripts = [ 'js/jquery/jquery.svg.js',
                     'js/jquery/jquery.mousewheel.js',
                     'js/jquery/jquery.event.drag-2.2.js',
                     'js/tbl_gis_visualization.js' ];

    for (var i = 0; i < smallScripts.length; i++) {
        script = document.createElement('script');
        script.type = 'text/javascript';
        script.src = smallScripts[i];
        head.appendChild(script);
    }

    // OpenLayers.js is BIG and takes time. So asynchronous loading would not work.
    // Load the JS and do a callback to load the content for the GIS Editor.
    script = document.createElement('script');
    script.type = 'text/javascript';

    script.onreadystatechange = function () {
        if (this.readyState == 'complete') {
            loadGISEditor(value, field, type, input_name, token);
        }
    };
    script.onload = function () {
        loadGISEditor(value, field, type, input_name, token);
    };
    script.onerror = function() {
        loadGISEditor(value, field, type, input_name, token);
    }

    script.src = 'js/openlayers/OpenLayers.js';
    head.appendChild(script);

    gisEditorLoaded = true;
}

/**
 * Loads the GIS editor via AJAX
 *
 * @param value      current value of the geometry field
 * @param field      field name
 * @param type       geometry type
 * @param input_name name of the input field
 * @param token      token
 */
function loadGISEditor(value, field, type, input_name, token) {

    var $gis_editor = $("#gis_editor");
    $.post('gis_data_editor.php', {
        'field' : field,
        'value' : value,
        'type' : type,
        'input_name' : input_name,
        'get_gis_editor' : true,
        'token' : token,
        'ajax_request': true
    }, function (data) {
        if (typeof data !== 'undefined' && data.success === true) {
            $gis_editor.html(data.gis_editor);
            initGISEditorVisualization();
            prepareJSVersion();
        } else {
            PMA_ajaxShowMessage(data.error, false);
        }
    }, 'json');
}

/**
 * Opens up the dialog for the GIS data editor.
 */
function openGISEditor() {

    // Center the popup
    var windowWidth = document.documentElement.clientWidth;
    var windowHeight = document.documentElement.clientHeight;
    var popupWidth = windowWidth * 0.9;
    var popupHeight = windowHeight * 0.9;
    var popupOffsetTop = windowHeight / 2 - popupHeight / 2;
    var popupOffsetLeft = windowWidth / 2 - popupWidth / 2;

    var $gis_editor = $("#gis_editor");
    var $backgrouond = $("#popup_background");

    $gis_editor.css({"top": popupOffsetTop, "left": popupOffsetLeft, "width": popupWidth, "height": popupHeight});
    $backgrouond.css({"opacity" : "0.7"});

    $gis_editor.append(
        '<div id="gis_data_editor">' +
        '<img class="ajaxIcon" id="loadingMonitorIcon" src="' +
        pmaThemeImage + 'ajax_clock_small.gif" alt=""/>' +
        '</div>'
    );

    // Make it appear
    $backgrouond.fadeIn("fast");
    $gis_editor.fadeIn("fast");
}

/**
 * Prepare and insert the GIS data in Well Known Text format
 * to the input field.
 */
function insertDataAndClose() {
    var $form = $('form#gis_data_editor_form');
    var input_name = $form.find("input[name='input_name']").val();

    $.post('gis_data_editor.php', $form.serialize() + "&generate=true&ajax_request=true", function (data) {
        if (typeof data !== 'undefined' && data.success === true) {
            $("input[name='" + input_name + "']").val(data.result);
        } else {
            PMA_ajaxShowMessage(data.error, false);
        }
    }, 'json');
    closeGISEditor();
}

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('gis_data_editor.js', function () {
    $(document).off('click', "#gis_editor input[name='gis_data[save]']");
    $(document).off('submit', '#gis_editor');
    $(document).off('change', "#gis_editor input[type='text']");
    $(document).off('change', "#gis_editor select.gis_type");
    $(document).off('click', '#gis_editor a.close_gis_editor, #gis_editor a.cancel_gis_editor');
    $(document).off('click', '#gis_editor a.addJs.addPoint');
    $(document).off('click', '#gis_editor a.addLine.addJs');
    $(document).off('click', '#gis_editor a.addJs.addPolygon');
    $(document).off('click', '#gis_editor a.addJs.addGeom');
});

AJAX.registerOnload('gis_data_editor.js', function () {

    // Remove the class that is added due to the URL being too long.
    $('span.open_gis_editor a').removeClass('formLinkSubmit');

    /**
     * Prepares and insert the GIS data to the input field on clicking 'copy'.
     */
    $(document).on('click', "#gis_editor input[name='gis_data[save]']", function (event) {
        event.preventDefault();
        insertDataAndClose();
    });

    /**
     * Prepares and insert the GIS data to the input field on pressing 'enter'.
     */
    $(document).on('submit', '#gis_editor', function (event) {
        event.preventDefault();
        insertDataAndClose();
    });

    /**
     * Trigger asynchronous calls on data change and update the output.
     */
    $(document).on('change', "#gis_editor input[type='text']", function () {
        var $form = $('form#gis_data_editor_form');
        $.post('gis_data_editor.php', $form.serialize() + "&generate=true&ajax_request=true", function (data) {
            if (typeof data !== 'undefined' && data.success === true) {
                $('#gis_data_textarea').val(data.result);
                $('#placeholder').empty().removeClass('hasSVG').html(data.visualization);
                $('#openlayersmap').empty();
                /* TODO: the gis_data_editor should rather return JSON than JS code to eval */
                eval(data.openLayers);
                initGISEditorVisualization();
            } else {
                PMA_ajaxShowMessage(data.error, false);
            }
        }, 'json');
    });

    /**
     * Update the form on change of the GIS type.
     */
    $(document).on('change', "#gis_editor select.gis_type", function (event) {
        var $gis_editor = $("#gis_editor");
        var $form = $('form#gis_data_editor_form');

        $.post('gis_data_editor.php', $form.serialize() + "&get_gis_editor=true&ajax_request=true", function (data) {
            if (typeof data !== 'undefined' && data.success === true) {
                $gis_editor.html(data.gis_editor);
                initGISEditorVisualization();
                prepareJSVersion();
            } else {
                PMA_ajaxShowMessage(data.error, false);
            }
        }, 'json');
    });

    /**
     * Handles closing of the GIS data editor.
     */
    $(document).on('click', '#gis_editor a.close_gis_editor, #gis_editor a.cancel_gis_editor', function () {
        closeGISEditor();
    });

    /**
     * Handles adding data points
     */
    $(document).on('click', '#gis_editor a.addJs.addPoint', function () {
        var $a = $(this);
        var name = $a.attr('name');
        // Eg. name = gis_data[0][MULTIPOINT][add_point] => prefix = gis_data[0][MULTIPOINT]
        var prefix = name.substr(0, name.length - 11);
        // Find the number of points
        var $noOfPointsInput = $("input[name='" + prefix + "[no_of_points]" + "']");
        var noOfPoints = parseInt($noOfPointsInput.val(), 10);
        // Add the new data point
        var html = addDataPoint(noOfPoints, prefix);
        $a.before(html);
        $noOfPointsInput.val(noOfPoints + 1);
    });

    /**
     * Handles adding linestrings and inner rings
     */
    $(document).on('click', '#gis_editor a.addLine.addJs', function () {
        var $a = $(this);
        var name = $a.attr('name');

        // Eg. name = gis_data[0][MULTILINESTRING][add_line] => prefix = gis_data[0][MULTILINESTRING]
        var prefix = name.substr(0, name.length - 10);
        var type = prefix.slice(prefix.lastIndexOf('[') + 1, prefix.lastIndexOf(']'));

        // Find the number of lines
        var $noOfLinesInput = $("input[name='" + prefix + "[no_of_lines]" + "']");
        var noOfLines = parseInt($noOfLinesInput.val(), 10);

        // Add the new linesting of inner ring based on the type
        var html = '<br/>';
        var noOfPoints;
        if (type == 'MULTILINESTRING') {
            html += PMA_messages.strLineString + ' ' + (noOfLines + 1) + ':';
            noOfPoints = 2;
        } else {
            html += PMA_messages.strInnerRing + ' ' + noOfLines + ':';
            noOfPoints = 4;
        }
        html += '<input type="hidden" name="' + prefix + '[' + noOfLines + '][no_of_points]" value="' + noOfPoints + '"/>';
        for (var i = 0; i < noOfPoints; i++) {
            html += addDataPoint(i, (prefix + '[' + noOfLines + ']'));
        }
        html += '<a class="addPoint addJs" name="' + prefix + '[' + noOfLines + '][add_point]" href="#">+ ' +
            PMA_messages.strAddPoint + '</a><br/>';

        $a.before(html);
        $noOfLinesInput.val(noOfLines + 1);
    });

    /**
     * Handles adding polygons
     */
    $(document).on('click', '#gis_editor a.addJs.addPolygon', function () {
        var $a = $(this);
        var name = $a.attr('name');
        // Eg. name = gis_data[0][MULTIPOLYGON][add_polygon] => prefix = gis_data[0][MULTIPOLYGON]
        var prefix = name.substr(0, name.length - 13);
        // Find the number of polygons
        var $noOfPolygonsInput = $("input[name='" + prefix + "[no_of_polygons]" + "']");
        var noOfPolygons = parseInt($noOfPolygonsInput.val(), 10);

        // Add the new polygon
        var html = PMA_messages.strPolygon + ' ' + (noOfPolygons + 1) + ':<br/>';
        html += '<input type="hidden" name="' + prefix + '[' + noOfPolygons + '][no_of_lines]" value="1"/>' +
            '<br/>' + PMA_messages.strOuterRing + ':' +
            '<input type="hidden" name="' + prefix + '[' + noOfPolygons + '][0][no_of_points]" value="4"/>';
        for (var i = 0; i < 4; i++) {
            html += addDataPoint(i, (prefix + '[' + noOfPolygons + '][0]'));
        }
        html += '<a class="addPoint addJs" name="' + prefix + '[' + noOfPolygons + '][0][add_point]" href="#">+ ' +
            PMA_messages.strAddPoint + '</a><br/>' +
            '<a class="addLine addJs" name="' + prefix + '[' + noOfPolygons + '][add_line]" href="#">+ ' +
            PMA_messages.strAddInnerRing + '</a><br/><br/>';

        $a.before(html);
        $noOfPolygonsInput.val(noOfPolygons + 1);
    });

    /**
     * Handles adding geoms
     */
    $(document).on('click', '#gis_editor a.addJs.addGeom', function () {
        var $a = $(this);
        var prefix = 'gis_data[GEOMETRYCOLLECTION]';
        // Find the number of geoms
        var $noOfGeomsInput = $("input[name='" + prefix + "[geom_count]" + "']");
        var noOfGeoms = parseInt($noOfGeomsInput.val(), 10);

        var html1 = PMA_messages.strGeometry + ' ' + (noOfGeoms + 1) + ':<br/>';
        var $geomType = $("select[name='gis_data[" + (noOfGeoms - 1) + "][gis_type]']").clone();
        $geomType.attr('name', 'gis_data[' + noOfGeoms + '][gis_type]').val('POINT');
        var html2 = '<br/>' + PMA_messages.strPoint + ' :' +
            '<label for="x"> ' + PMA_messages.strX + ' </label>' +
            '<input type="text" name="gis_data[' + noOfGeoms + '][POINT][x]" value=""/>' +
            '<label for="y"> ' + PMA_messages.strY + ' </label>' +
            '<input type="text" name="gis_data[' + noOfGeoms + '][POINT][y]" value=""/>' +
            '<br/><br/>';

        $a.before(html1);
        $geomType.insertBefore($a);
        $a.before(html2);
        $noOfGeomsInput.val(noOfGeoms + 1);
    });
});
;

/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @fileoverview    function used in table data manipulation pages
 *
 * @requires    jQuery
 * @requires    jQueryUI
 * @requires    js/functions.js
 *
 */

/**
 * Modify form controls when the "NULL" checkbox is checked
 *
 * @param theType     string   the MySQL field type
 * @param urlField    string   the urlencoded field name - OBSOLETE
 * @param md5Field    string   the md5 hashed field name
 * @param multi_edit  string   the multi_edit row sequence number
 *
 * @return boolean  always true
 */
function nullify(theType, urlField, md5Field, multi_edit)
{
    var rowForm = document.forms.insertForm;

    if (typeof(rowForm.elements['funcs' + multi_edit + '[' + md5Field + ']']) != 'undefined') {
        rowForm.elements['funcs' + multi_edit + '[' + md5Field + ']'].selectedIndex = -1;
    }

    // "ENUM" field with more than 20 characters
    if (theType == 1) {
        rowForm.elements['fields' + multi_edit + '[' + md5Field +  ']'][1].selectedIndex = -1;
    }
    // Other "ENUM" field
    else if (theType == 2) {
        var elts     = rowForm.elements['fields' + multi_edit + '[' + md5Field + ']'];
        // when there is just one option in ENUM:
        if (elts.checked) {
            elts.checked = false;
        } else {
            var elts_cnt = elts.length;
            for (var i = 0; i < elts_cnt; i++) {
                elts[i].checked = false;
            } // end for

        } // end if
    }
    // "SET" field
    else if (theType == 3) {
        rowForm.elements['fields' + multi_edit + '[' + md5Field +  '][]'].selectedIndex = -1;
    }
    // Foreign key field (drop-down)
    else if (theType == 4) {
        rowForm.elements['fields' + multi_edit + '[' + md5Field +  ']'].selectedIndex = -1;
    }
    // foreign key field (with browsing icon for foreign values)
    else if (theType == 6) {
        rowForm.elements['fields' + multi_edit + '[' + md5Field + ']'].value = '';
    }
    // Other field types
    else /*if (theType == 5)*/ {
        rowForm.elements['fields' + multi_edit + '[' + md5Field + ']'].value = '';
    } // end if... else if... else

    return true;
} // end of the 'nullify()' function


/**
 * javascript DateTime format validation.
 * its used to prevent adding default (0000-00-00 00:00:00) to database when user enter wrong values
 * Start of validation part
 */
//function checks the number of days in febuary
function daysInFebruary(year)
{
    return (((year % 4 === 0) && (((year % 100 !== 0)) || (year % 400 === 0))) ? 29 : 28);
}
//function to convert single digit to double digit
function fractionReplace(num)
{
    num = parseInt(num, 10);
    return num >= 1 && num <= 9 ? '0' + num : '00';
}

/* function to check the validity of date
* The following patterns are accepted in this validation (accepted in mysql as well)
* 1) 2001-12-23
* 2) 2001-1-2
* 3) 02-12-23
* 4) And instead of using '-' the following punctuations can be used (+,.,*,^,@,/) All these are accepted by mysql as well. Therefore no issues
*/
function isDate(val, tmstmp)
{
    val = val.replace(/[.|*|^|+|//|@]/g, '-');
    var arrayVal = val.split("-");
    for (var a = 0; a < arrayVal.length; a++) {
        if (arrayVal[a].length == 1) {
            arrayVal[a] = fractionReplace(arrayVal[a]);
        }
    }
    val = arrayVal.join("-");
    var pos = 2;
    var dtexp = new RegExp(/^([0-9]{4})-(((01|03|05|07|08|10|12)-((0[0-9])|([1-2][0-9])|(3[0-1])))|((02|04|06|09|11)-((0[0-9])|([1-2][0-9])|30))|((00)-(00)))$/);
    if (val.length == 8) {
        pos = 0;
    }
    if (dtexp.test(val)) {
        var month = parseInt(val.substring(pos + 3, pos + 5), 10);
        var day = parseInt(val.substring(pos + 6, pos + 8), 10);
        var year = parseInt(val.substring(0, pos + 2), 10);
        if (month == 2 && day > daysInFebruary(year)) {
            return false;
        }
        if (val.substring(0, pos + 2).length == 2) {
            year = parseInt("20" + val.substring(0, pos + 2), 10);
        }
        if (tmstmp === true) {
            if (year < 1978) {
                return false;
            }
            if (year > 2038 || (year > 2037 && day > 19 && month >= 1) || (year > 2037 && month > 1)) {
                return false;
            }
        }
    } else {
        return false;
    }
    return true;
}

/* function to check the validity of time
* The following patterns are accepted in this validation (accepted in mysql as well)
* 1) 2:3:4
* 2) 2:23:43
* 3) 2:23:43.123456
*/
function isTime(val)
{
    var arrayVal = val.split(":");
    for (var a = 0, l = arrayVal.length; a < l; a++) {
        if (arrayVal[a].length == 1) {
            arrayVal[a] = fractionReplace(arrayVal[a]);
        }
    }
    val = arrayVal.join(":");
    var tmexp = new RegExp(/^(-)?(([0-7]?[0-9][0-9])|(8[0-2][0-9])|(83[0-8])):((0[0-9])|([1-5][0-9])):((0[0-9])|([1-5][0-9]))(\.[0-9]{1,6}){0,1}$/);
    return tmexp.test(val);
}

/**
 * To check whether insert section is ignored or not
 */
function checkForCheckbox(multi_edit)
{
    if($("#insert_ignore_"+multi_edit).length) {
        return $("#insert_ignore_"+multi_edit).is(":unchecked");
    }
    return true;
}

function verificationsAfterFieldChange(urlField, multi_edit, theType)
{
    var evt = window.event || arguments.callee.caller.arguments[0];
    var target = evt.target || evt.srcElement;
    var $this_input = $(":input[name^='fields[multi_edit][" + multi_edit + "][" +
        urlField + "]']");
    // the function drop-down that corresponds to this input field
    var $this_function = $("select[name='funcs[multi_edit][" + multi_edit + "][" +
        urlField + "]']");
    var function_selected = false;
    if (typeof $this_function.val() !== 'undefined' &&
        $this_function.val() !== null &&
        $this_function.val().length > 0
    ) {
        function_selected = true;
    }

    //To generate the textbox that can take the salt
    var new_salt_box = "<br><input type=text name=salt[multi_edit][" + multi_edit + "][" + urlField + "]" +
        " id=salt_" + target.id + " placeholder='" + PMA_messages.strEncryptionKey + "'>";

    //If encrypting or decrypting functions that take salt as input is selected append the new textbox for salt
    if (target.value === 'AES_ENCRYPT' ||
            target.value === 'AES_DECRYPT' ||
            target.value === 'DES_ENCRYPT' ||
            target.value === 'DES_DECRYPT' ||
            target.value === 'ENCRYPT') {
        if (!($("#salt_" + target.id).length)) {
            $this_input.after(new_salt_box);
        }
    } else {
        //Remove the textbox for salt
        $('#salt_' + target.id).prev('br').remove();
        $("#salt_" + target.id).remove();
    }

    if (target.value === 'AES_DECRYPT'
            || target.value === 'AES_ENCRYPT'
            || target.value === 'MD5') {
        $('#' + target.id).rules("add", {
            validationFunctionForFuns: {
                param: $this_input,
                depends: function() {
                    return checkForCheckbox(multi_edit);
                }
            }
        });
    }

    // Unchecks the corresponding "NULL" control
    $("input[name='fields_null[multi_edit][" + multi_edit + "][" + urlField + "]']").prop('checked', false);

    // Unchecks the Ignore checkbox for the current row
    $("input[name='insert_ignore_" + multi_edit + "']").prop('checked', false);

    var charExceptionHandling;
    if (theType.substring(0,4) === "char") {
        charExceptionHandling = theType.substring(5,6);
    }
    else if (theType.substring(0,7) === "varchar") {
        charExceptionHandling = theType.substring(8,9);
    }
    if (function_selected) {
        $this_input.removeAttr('min');
        $this_input.removeAttr('max');
        // @todo: put back attributes if corresponding function is deselected
    }

    if ($this_input.data('rulesadded') == null && ! function_selected) {

        //call validate before adding rules
        $($this_input[0].form).validate();
        // validate for date time
        if (theType == "datetime" || theType == "time" || theType == "date" || theType == "timestamp") {
            $this_input.rules("add", {
                validationFunctionForDateTime: {
                    param: theType,
                    depends: function() {
                        return checkForCheckbox(multi_edit);
                    }
                }
            });
        }
        //validation for integer type
        if ($this_input.data('type') === 'INT') {
            var mini = parseInt($this_input.attr('min'));
            var maxi = parseInt($this_input.attr('max'));
            $this_input.rules("add", {
                number: {
                    param : true,
                    depends: function() {
                        return checkForCheckbox(multi_edit);
                    }
                },
                min: {
                    param: mini,
                    depends: function() {
                        if (isNaN($this_input.val())) {
                            return false;
                        } else {
                            return checkForCheckbox(multi_edit);
                        }
                    }
                },
                max: {
                    param: maxi,
                    depends: function() {
                        if (isNaN($this_input.val())) {
                            return false;
                        } else {
                            return checkForCheckbox(multi_edit);
                        }
                    }
                }
            });
            //validation for CHAR types
        } else if ($this_input.data('type') === 'CHAR') {
            var maxlen = $this_input.data('maxlength');
            if (typeof maxlen !== 'undefined') {
                if (maxlen <=4) {
                    maxlen=charExceptionHandling;
                }
                $this_input.rules("add", {
                    maxlength: {
                        param: maxlen,
                        depends: function() {
                            return checkForCheckbox(multi_edit);
                        }
                    }
                });
            }
            // validate binary & blob types
        } else if ($this_input.data('type') === 'HEX') {
            $this_input.rules("add", {
                validationFunctionForHex: {
                    param: true,
                    depends: function() {
                        return checkForCheckbox(multi_edit);
                    }
                }
            });
        }
        $this_input.data('rulesadded', true);
    } else if ($this_input.data('rulesadded') == true && function_selected) {
        // remove any rules added
        $this_input.rules("remove");
        // remove any error messages
        $this_input
            .removeClass('error')
            .removeAttr('aria-invalid')
            .siblings('.error')
            .remove();
        $this_input.data('rulesadded', null);
    }
}
/* End of fields validation*/

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('tbl_change.js', function () {
    $(document).off('click', 'span.open_gis_editor');
    $(document).off('click', "input[name^='insert_ignore_']");
    $(document).off('click', "input[name='gis_data[save]']");
    $(document).off('click', 'input.checkbox_null');
    $('select[name="submit_type"]').unbind('change');
    $(document).off('change', "#insert_rows");
});

/**
 * Ajax handlers for Change Table page
 *
 * Actions Ajaxified here:
 * Submit Data to be inserted into the table.
 * Restart insertion with 'N' rows.
 */
AJAX.registerOnload('tbl_change.js', function () {

    if($("#insertForm").length) {
        // validate the comment form when it is submitted
        $("#insertForm").validate();
        jQuery.validator.addMethod("validationFunctionForHex", function(value, element) {
            return value.match(/^[a-f0-9]*$/i) !== null;
        });

        jQuery.validator.addMethod("validationFunctionForFuns", function(value, element, options) {
            if (value.substring(0, 3) === "AES" && options.data('type') !== 'HEX') {
                return false;
            }

            return !(value.substring(0, 3) === "MD5" &&
                typeof options.data('maxlength') !== 'undefined' &&
                options.data('maxlength') < 32);
        });

        jQuery.validator.addMethod("validationFunctionForDateTime", function(value, element, options) {
            var dt_value = value;
            var theType = options;
            if (theType == "date") {
                return isDate(dt_value);

            } else if (theType == "time") {
                return isTime(dt_value);

            } else if (theType == "datetime" || theType == "timestamp") {
                var tmstmp = false;
                dt_value = dt_value.trim();
                if (dt_value == "CURRENT_TIMESTAMP") {
                    return true;
                }
                if (theType == "timestamp") {
                    tmstmp = true;
                }
                if (dt_value == "0000-00-00 00:00:00") {
                    return true;
                }
                var dv = dt_value.indexOf(" ");
                if (dv == -1) { // Only the date component, which is valid
                    return isDate(dt_value, tmstmp);
                }

                return isDate(dt_value.substring(0, dv), tmstmp) &&
                    isTime(dt_value.substring(dv + 1));
            }
        });
        /*
         * message extending script must be run
         * after initiation of functions
         */
        extendingValidatorMessages();
    }

    $.datepicker.initialized = false;

    $(document).on('click', 'span.open_gis_editor', function (event) {
        event.preventDefault();

        var $span = $(this);
        // Current value
        var value = $span.parent('td').children("input[type='text']").val();
        // Field name
        var field = $span.parents('tr').children('td:first').find("input[type='hidden']").val();
        // Column type
        var type = $span.parents('tr').find('span.column_type').text();
        // Names of input field and null checkbox
        var input_name = $span.parent('td').children("input[type='text']").attr('name');
        //Token
        var token = $("input[name='token']").val();

        openGISEditor();
        if (!gisEditorLoaded) {
            loadJSAndGISEditor(value, field, type, input_name, token);
        } else {
            loadGISEditor(value, field, type, input_name, token);
        }
    });

    /**
     * Forced validation check of fields
     */
    $(document).on('click',"input[name^='insert_ignore_']", function (event) {
        $("#insertForm").valid();
    });

    /**
     * Uncheck the null checkbox as geometry data is placed on the input field
     */
    $(document).on('click', "input[name='gis_data[save]']", function (event) {
        var input_name = $('form#gis_data_editor_form').find("input[name='input_name']").val();
        var $null_checkbox = $("input[name='" + input_name + "']").parents('tr').find('.checkbox_null');
        $null_checkbox.prop('checked', false);
    });

    /**
     * Handles all current checkboxes for Null; this only takes care of the
     * checkboxes on currently displayed rows as the rows generated by
     * "Continue insertion" are handled in the "Continue insertion" code
     *
     */
    $(document).on('click', 'input.checkbox_null', function () {
        nullify(
            // use hidden fields populated by tbl_change.php
            $(this).siblings('.nullify_code').val(),
            $(this).closest('tr').find('input:hidden').first().val(),
            $(this).siblings('.hashed_field').val(),
            $(this).siblings('.multi_edit').val()
        );
    });

    /**
     * Reset the auto_increment column to 0 when selecting any of the
     * insert options in submit_type-dropdown. Only perform the reset
     * when we are in edit-mode, and not in insert-mode(no previous value
     * available).
     */
    $('select[name="submit_type"]').bind('change', function () {
        var thisElemSubmitTypeVal = $(this).val();
        var $table = $('table.insertRowTable');
        var auto_increment_column = $table.find('input[name^="auto_increment"]');
        auto_increment_column.each(function () {
            var $thisElemAIField = $(this);
            var thisElemName = $thisElemAIField.attr('name');

            var prev_value_field = $table.find('input[name="' + thisElemName.replace('auto_increment', 'fields_prev') + '"]');
            var value_field = $table.find('input[name="' + thisElemName.replace('auto_increment', 'fields') + '"]');
            var previous_value = $(prev_value_field).val();
            if (previous_value !== undefined) {
                if (thisElemSubmitTypeVal == 'insert'
                    || thisElemSubmitTypeVal == 'insertignore'
                    || thisElemSubmitTypeVal == 'showinsert'
                ) {
                    $(value_field).val(0);
                } else {
                    $(value_field).val(previous_value);
                }
            }
        });

    });

    /**
     * Continue Insertion form
     */
    $(document).on('change', "#insert_rows", function (event) {
        event.preventDefault();
        /**
         * @var columnCount   Number of number of columns table has.
         */
        var columnCount = $("table.insertRowTable:first").find("tr").has("input[name*='fields_name']").length;
        /**
         * @var curr_rows   Number of current insert rows already on page
         */
        var curr_rows = $("table.insertRowTable").length;
        /**
         * @var target_rows Number of rows the user wants
         */
        var target_rows = $("#insert_rows").val();

        // remove all datepickers
        $('input.datefield, input.datetimefield').each(function () {
            $(this).datepicker('destroy');
        });

        if (curr_rows < target_rows) {

            var tempIncrementIndex = function () {

                var $this_element = $(this);
                /**
                 * Extract the index from the name attribute for all input/select fields and increment it
                 * name is of format funcs[multi_edit][10][<long random string of alphanum chars>]
                 */

                /**
                 * @var this_name   String containing name of the input/select elements
                 */
                var this_name = $this_element.attr('name');
                /** split {@link this_name} at [10], so we have the parts that can be concatenated later */
                var name_parts = this_name.split(/\[\d+\]/);
                /** extract the [10] from  {@link name_parts} */
                var old_row_index_string = this_name.match(/\[\d+\]/)[0];
                /** extract 10 - had to split into two steps to accomodate double digits */
                var old_row_index = parseInt(old_row_index_string.match(/\d+/)[0], 10);

                /** calculate next index i.e. 11 */
                new_row_index = old_row_index + 1;
                /** generate the new name i.e. funcs[multi_edit][11][foobarbaz] */
                var new_name = name_parts[0] + '[' + new_row_index + ']' + name_parts[1];

                var hashed_field = name_parts[1].match(/\[(.+)\]/)[1];
                $this_element.attr('name', new_name);

                /** If element is select[name*='funcs'], update id */
                if ($this_element.is("select[name*='funcs']")) {
                    var this_id = $this_element.attr("id");
                    var id_parts = this_id.split(/\_/);
                    var old_id_index = id_parts[1];
                    var prevSelectedValue = $("#field_" + old_id_index + "_1").val();
                    var new_id_index = parseInt(old_id_index) + columnCount;
                    var new_id = 'field_' + new_id_index + '_1';
                    $this_element.attr('id', new_id);
                    $this_element.find("option").filter(function () {
                        return $(this).text() === prevSelectedValue;
                    }).attr("selected","selected");

                    // If salt field is there then update its id.
                    var nextSaltInput = $this_element.parent().next("td").next("td").find("input[name*='salt']");
                    if (nextSaltInput.length !== 0) {
                        nextSaltInput.attr("id", "salt_" + new_id);
                    }
                }

                // handle input text fields and textareas
                if ($this_element.is('.textfield') || $this_element.is('.char') || $this_element.is('textarea')) {
                    // do not remove the 'value' attribute for ENUM columns
                    // special handling for radio fields after updating ids to unique - see below
                    if ($this_element.closest('tr').find('span.column_type').html() != 'enum') {
                        $this_element.val($this_element.closest('tr').find('span.default_value').html());
                    }
                    $this_element
                        .unbind('change')
                        // Remove onchange attribute that was placed
                        // by tbl_change.php; it refers to the wrong row index
                        .attr('onchange', null)
                        // Keep these values to be used when the element
                        // will change
                        .data('hashed_field', hashed_field)
                        .data('new_row_index', new_row_index)
                        .bind('change', function () {
                            var $changed_element = $(this);
                            verificationsAfterFieldChange(
                                $changed_element.data('hashed_field'),
                                $changed_element.data('new_row_index'),
                                $changed_element.closest('tr').find('span.column_type').html()
                            );
                        });
                }

                if ($this_element.is('.checkbox_null')) {
                    $this_element
                        // this event was bound earlier by jQuery but
                        // to the original row, not the cloned one, so unbind()
                        .unbind('click')
                        // Keep these values to be used when the element
                        // will be clicked
                        .data('hashed_field', hashed_field)
                        .data('new_row_index', new_row_index)
                        .bind('click', function () {
                            var $changed_element = $(this);
                            nullify(
                                $changed_element.siblings('.nullify_code').val(),
                                $this_element.closest('tr').find('input:hidden').first().val(),
                                $changed_element.data('hashed_field'),
                                '[multi_edit][' + $changed_element.data('new_row_index') + ']'
                            );
                        });
                }
            };

            var tempReplaceAnchor = function () {
                var $anchor = $(this);
                var new_value = 'rownumber=' + new_row_index;
                // needs improvement in case something else inside
                // the href contains this pattern
                var new_href = $anchor.attr('href').replace(/rownumber=\d+/, new_value);
                $anchor.attr('href', new_href);
            };

            while (curr_rows < target_rows) {

                /**
                 * @var $last_row    Object referring to the last row
                 */
                var $last_row = $("#insertForm").find(".insertRowTable:last");

                // need to access this at more than one level
                // (also needs improvement because it should be calculated
                //  just once per cloned row, not once per column)
                var new_row_index = 0;

                //Clone the insert tables
                $last_row
                .clone(true, true)
                .insertBefore("#actions_panel")
                .find('input[name*=multi_edit],select[name*=multi_edit],textarea[name*=multi_edit]')
                .each(tempIncrementIndex)
                .end()
                .find('.foreign_values_anchor')
                .each(tempReplaceAnchor);

                //Insert/Clone the ignore checkboxes
                if (curr_rows == 1) {
                    $('<input id="insert_ignore_1" type="checkbox" name="insert_ignore_1" checked="checked" />')
                    .insertBefore("table.insertRowTable:last")
                    .after('<label for="insert_ignore_1">' + PMA_messages.strIgnore + '</label>');
                } else {

                    /**
                     * @var $last_checkbox   Object reference to the last checkbox in #insertForm
                     */
                    var $last_checkbox = $("#insertForm").children('input:checkbox:last');

                    /** name of {@link $last_checkbox} */
                    var last_checkbox_name = $last_checkbox.attr('name');
                    /** index of {@link $last_checkbox} */
                    var last_checkbox_index = parseInt(last_checkbox_name.match(/\d+/), 10);
                    /** name of new {@link $last_checkbox} */
                    var new_name = last_checkbox_name.replace(/\d+/, last_checkbox_index + 1);

                    $('<br/><div class="clearfloat"></div>')
                    .insertBefore("table.insertRowTable:last");

                    $last_checkbox
                    .clone()
                    .attr({'id': new_name, 'name': new_name})
                    .prop('checked', true)
                    .insertBefore("table.insertRowTable:last");

                    $('label[for^=insert_ignore]:last')
                    .clone()
                    .attr('for', new_name)
                    .insertBefore("table.insertRowTable:last");

                    $('<br/>')
                    .insertBefore("table.insertRowTable:last");
                }
                curr_rows++;
            }
            // recompute tabindex for text fields and other controls at footer;
            // IMO it's not really important to handle the tabindex for
            // function and Null
            var tabindex = 0;
            $('.textfield, .char, textarea')
            .each(function () {
                tabindex++;
                $(this).attr('tabindex', tabindex);
                // update the IDs of textfields to ensure that they are unique
                $(this).attr('id', "field_" + tabindex + "_3");

                // special handling for radio fields after updating ids to unique
                if ($(this).closest('tr').find('span.column_type').html() === 'enum') {
                    if ($(this).val() === $(this).closest('tr').find('span.default_value').html()) {
                        $(this).prop('checked', true);
                    } else {
                        $(this).prop('checked', false);
                    }
                }
            });
            $('.control_at_footer')
            .each(function () {
                tabindex++;
                $(this).attr('tabindex', tabindex);
            });
        } else if (curr_rows > target_rows) {
            while (curr_rows > target_rows) {
                $("input[id^=insert_ignore]:last")
                .nextUntil("fieldset")
                .addBack()
                .remove();
                curr_rows--;
            }
        }
        // Add all the required datepickers back
        addDateTimePicker();
    });
});

function changeValueFieldType(elem, searchIndex)
{
    var fieldsValue = $("select#fieldID_" + searchIndex);
    if (0 === fieldsValue.size()) {
        return;
    }

    var type = $(elem).val();
    if ('IN (...)' == type ||
        'NOT IN (...)' == type ||
        'BETWEEN' == type ||
        'NOT BETWEEN' == type
    ) {
        $("#fieldID_" + searchIndex).attr('multiple', '');
    } else {
        $("#fieldID_" + searchIndex).removeAttr('multiple');
    }
}
;

/*
 * Copyright (c) 2008 Greg Weber greg at gregweber.info
 * Dual licensed under the MIT and GPLv2 licenses just as jQuery is:
 * http://jquery.org/license
 *
 * Multi-columns fork by natinusala
 *
 * documentation at http://gregweber.info/projects/uitablefilter
 *                  https://github.com/natinusala/jquery-uitablefilter
 *
 * allows table rows to be filtered (made invisible)
 * <code>
 * t = $('table')
 * $.uiTableFilter( t, phrase )
 * </code>
 * arguments:
 *   jQuery object containing table rows
 *   phrase to search for
 *   optional arguments:
 *     array of columns to limit search too (the column title in the table header)
 *     ifHidden - callback to execute if one or more elements was hidden
 *     tdElem - specific element within <td> to be considered for searching or to limit search to,
 *     default:whole <td>. useful if <td> has more than one elements inside but want to
 *     limit search within only some of elements or only visible elements. eg tdElem can be "td span"
 */
(function($) {
  $.uiTableFilter = function(jq, phrase, column, ifHidden, tdElem){
    if(!tdElem) tdElem = "td";
    var new_hidden = false;
    if( this.last_phrase === phrase ) return false;

    var phrase_length = phrase.length;
    var words = phrase.toLowerCase().split(" ");

    // these function pointers may change
    var matches = function(elem) { elem.show() }
    var noMatch = function(elem) { elem.hide(); new_hidden = true }
    var getText = function(elem) { return elem.text() }

    if( column )
    {
      if (!$.isArray(column))
      {
        column = new Array(column);
      }

      var index = new Array();

      jq.find("thead > tr:last > th").each(function(i)
      {
          for (var j = 0; j < column.length; j++)
          {
              if ($.trim($(this).text()) == column[j])
              {
                  index[j] = i;
                  break;
              }
          }

      });

      getText = function(elem) {
          var selector = "";
          for (var i = 0; i < index.length; i++)
          {
              if (i != 0) {selector += ",";}
              selector += tdElem + ":eq(" + index[i] + ")";
          }
          return $(elem.find((selector))).text();
      }
    }

    // if added one letter to last time,
    // just check newest word and only need to hide
    if( (words.size > 1) && (phrase.substr(0, phrase_length - 1) ===
          this.last_phrase) ) {

      if( phrase[-1] === " " )
      { this.last_phrase = phrase; return false; }

      var words = words[-1]; // just search for the newest word

      // only hide visible rows
      matches = function(elem) {;}
      var elems = jq.find("tbody:first > tr:visible")
    }
    else {
      new_hidden = true;
      var elems = jq.find("tbody:first > tr")
    }

    elems.each(function(){
      var elem = $(this);
      $.uiTableFilter.has_words( getText(elem), words, false ) ?
        matches(elem) : noMatch(elem);
    });

    last_phrase = phrase;
    if( ifHidden && new_hidden ) ifHidden();
    return jq;
  };

  // caching for speedup
  $.uiTableFilter.last_phrase = ""

  // not jQuery dependent
  // "" [""] -> Boolean
  // "" [""] Boolean -> Boolean
  $.uiTableFilter.has_words = function( str, words, caseSensitive )
  {
    var text = caseSensitive ? str : str.toLowerCase();
    for (var i=0; i < words.length; i++) {
      if (text.indexOf(words[i]) === -1) return false;
    }
    return true;
  }
}) (jQuery);
;

/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @fileoverview    Implements the shiftkey + click remove column
 *                  from order by clause funcationality
 * @name            columndelete
 *
 * @requires    jQuery
 */

function captureURL(url)
{
    var URL = {};
    url = '' + url;
    // Exclude the url part till HTTP
    url = url.substr(url.search("sql.php"), url.length);
    // The url part between ORDER BY and &session_max_rows needs to be replaced.
    URL.head = url.substr(0, url.indexOf('ORDER+BY') + 9);
    URL.tail = url.substr(url.indexOf("&session_max_rows"), url.length);
    return URL;
}

/**
 * This function is for navigating to the generated URL
 *
 * @param object   target HTMLAnchor element
 * @param object   parent HTMLDom Object
 */

function removeColumnFromMultiSort(target, parent)
{
    var URL = captureURL(target);
    var begin = target.indexOf('ORDER+BY') + 8;
    var end = target.indexOf('&session_max_rows');
    // get the names of the columns involved
    var between_part = target.substr(begin, end-begin);
    var columns = between_part.split('%2C+');
    // If the given column is not part of the order clause exit from this function
    var index = parent.find('small').length ? parent.find('small').text() : '';
    if (index === ''){
        return '';
    }
    // Remove the current clicked column
    columns.splice(index-1, 1);
    // If all the columns have been removed dont submit a query with nothing
    // After order by clause.
    if (columns.length === 0) {
        var head = URL.head;
        head = head.slice(0,head.indexOf('ORDER+BY'));
        URL.head = head;
        // removing the last sort order should have priority over what
        // is remembered via the RememberSorting directive
        URL.tail += '&discard_remembered_sort=1';
    }
    var middle_part = columns.join('%2C+');
    url = URL.head + middle_part + URL.tail;
    return url;
}

AJAX.registerOnload('keyhandler.js', function () {
    $("th.draggable.column_heading.pointer.marker a").on('click', function (event) {
        var url = $(this).parent().find('input').val();
        if (event.ctrlKey || event.altKey) {
            event.preventDefault();
            url = removeColumnFromMultiSort(url, $(this).parent());
            if (url) {
                AJAX.source = $(this);
                PMA_ajaxShowMessage();
                $.get(url, {'ajax_request' : true, 'ajax_page_request' : true}, AJAX.responseHandler);
            }
        } else if (event.shiftKey) {
            event.preventDefault();
            AJAX.source = $(this);
            PMA_ajaxShowMessage();
            $.get(url, {'ajax_request' : true, 'ajax_page_request' : true}, AJAX.responseHandler);
        }
    });
});

AJAX.registerTeardown('keyhandler.js', function () {
    $(document).off('click', "th.draggable.column_heading.pointer.marker a");
});
;

