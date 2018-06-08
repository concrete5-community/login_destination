/* jshint unused:vars, undef:true, browser:true, jquery:true */
;(function($) {
'use strict';
var tokenName, actions, $tbody;


function setupSortable() {
    $tbody.sortable({
        items: 'tr.cpl-rule',
        handle: '.cpl-rule-move-drag',
        cursor: 'move',
        axis: 'y',
        tolerance: 'pointer',
        containment: $tbody,
        stop: function() {
            saveSortedList(function() {
                $tbody.sortable('cancel');
            });
        }
    });
    $tbody.find('a[data-move-delta]').each(function() {
       $(this).on('click', function(e) {
           e.preventDefault();
           var $a = $(this),
               delta = parseInt($a.data('move-delta')),
               $myRow = $a.closest('tr'),
               $otherRow = delta < 0 ? $myRow.prev('tr[data-rule-id]') : $myRow.next('tr[data-rule-id]')
           ;
           if ($otherRow.length !== 1 || $tbody.sortable('option', 'disabled')) {
               return;
           }
           if (delta < 0) {
               $myRow.after($otherRow);
           } else {
               $myRow.before($otherRow);
           }
           saveSortedList(function() {
               if (delta > 0) {
                   $myRow.after($otherRow);
               } else {
                   $myRow.before($otherRow);
               }
           });
       });
    });
}

function saveSortedList(onFail) {
    $tbody.sortable('disable');
    var data = {ruleIds: []};
    data[tokenName] = actions.sort.token;
    $tbody.find('tr[data-rule-id]').each(function() {
        data.ruleIds.push($(this).data('rule-id'));
    });
    $.ajax({
        cache: false,
        data: data,
        dataType: 'json',
        method: 'POST',
        url: actions.sort.url
    }).always(function() {
        $tbody.sortable('enable');
    }).fail(function (xhr, status, error) {
        if (xhr && xhr.responseJSON && xhr.responseJSON.error && xhr.responseJSON.errors) {
            error = xhr.responseJSON.errors.join('\n');
        }
        setTimeout(function() { window.alert(error); }, 0);
        onFail();
    }).done(function (data) {
        if (data && data.error) {
            setTimeout(function() { window.alert(data.errors.join('\n')); }, 0);
            onFail();
            return;
        }
    });
}

window.initializeRuleList = function(data) {
    tokenName = data.tokenName;
    actions = data.actions;
    var isTouch;
    try {
        document.createEvent('TouchEvent');
        isTouch = true;
    } catch (e) {
        isTouch = false;
    }
    $tbody = $('#cpl-rules');
    setupSortable();
    $('#cpl-rules-table')
        .addClass(isTouch ? 'cpl-touch-yes' : 'cpl-touch-no')
        .show()
    ;
};

})(jQuery);
