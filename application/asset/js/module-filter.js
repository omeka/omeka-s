$(document).ready(function() {

/**
 * Behavior for the modules page: the filter, its state chips, and the batch
 * actions.
 *
 * The filter narrows the module rows by text and by state. A row the filter
 * hides has its checkbox cleared and disabled, so Select all, unchecking
 * Select all, and the batch form all pass it by. Its selection comes back when
 * the row shows again.
 */

const bar = $('#module-filter');
const table = $('#modules');
if (!bar.length || !table.length) {
    return;
}
const input = bar.find('.o-filter-input');
const chips = bar.find('.o-filter-chip');
const announcer = bar.find('.o-filter-announcer');
const batch = $('#module-batch');
const noMatches = $('.o-filter-no-matches');
const batchSelect = $('.batch-actions-select');
let selectedState = chips.filter('[aria-pressed="true"]').attr('data-state-filter') || '';

// Text for matching: lowercase and without accents, so "evenement" finds
// "Événement".
const normalize = function(text) {
    return text.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
};

// What the filter reads from each row, gathered once. The text is the
// module's name, ID, author and description, not the row's text, which in the
// stacked layout includes the column labels.
const rows = table.find('tr.module').toArray().map(function(element) {
    const row = $(element);
    return {
        row: row,
        text: normalize([
            row.find('.module-name').text(),
            row.attr('data-module-id'),
            row.find('.module-author').text(),
            row.find('.module-description').text(),
        ].join(' ')),
        state: row.attr('data-state-filter'),
        checkbox: row.find('input[name="module_ids[]"]'),
        isShown: true,
        // The selection held while the filter hides the row.
        heldChecked: false,
    };
});

// Whether a version check has revealed an update notice in the row. This looks
// each time, because a module's check can add notices after the page loads,
// and reads the notice's own style, because a notice in a row the filter
// hides is never :visible.
const hasUpdate = function(row) {
    return row.find('.version-notification').toArray().some(function(notice) {
        return 'none' !== notice.style.display;
    });
};

/**
 * Enable the batch actions while a shown module is selected, as
 * Omeka.manageSelectedActions() does on other browse pages. That function can
 * be replaced by modules, and doesn't know this page's actions.
 */
const updateBatchActions = function() {
    const hasSelection = 0 < table.find('input[name="module_ids[]"]:checked:not(:disabled)').length;
    batchSelect.find('.batch-selected').prop('disabled', !hasSelection);
    if (!hasSelection) {
        batchSelect.val('default');
        $('.batch-actions .active').removeClass('active');
        $('.batch-actions .default').addClass('active');
    }
};

/**
 * Show and hide rows and chips for the current text and state, and update the
 * chips' counts of the modules that match the text.
 */
const refresh = function() {
    // A module matches when its text has every word, in any order.
    const words = normalize(input.val()).split(/\s+/).filter(Boolean);
    // Counts of the modules that match the text, for All, each state, and
    // Updates available.
    const counts = {};
    const count = function(state) {
        counts[state] = (counts[state] || 0) + 1;
    };
    let shownCount = 0;
    rows.forEach(function(data) {
        const isMatch = words.every(function(word) {
            return data.text.includes(word);
        });
        const isUpdate = hasUpdate(data.row);
        if (isMatch) {
            count('');
            count(data.state);
            if (isUpdate) {
                count('update_available');
            }
        }
        const isShown = isMatch && (
            '' === selectedState
            || ('update_available' === selectedState ? isUpdate : selectedState === data.state)
        );
        data.row.toggleClass('o-filter-hidden', !isShown);
        if (!isShown && data.isShown) {
            data.heldChecked = data.checkbox.prop('checked');
            data.checkbox.prop('checked', false);
        } else if (isShown && !data.isShown) {
            data.checkbox.prop('checked', data.heldChecked);
        }
        data.isShown = isShown;
        data.checkbox.prop('disabled', !isShown || data.checkbox.is('[data-batch-disabled]'));
        if (isShown) {
            shownCount++;
        }
    });
    // A state with no matching modules hides its chip, unless it's selected.
    chips.each(function() {
        const chip = $(this);
        const state = this.getAttribute('data-state-filter');
        const count = counts[state] || 0;
        chip.find('.o-filter-chip-count').text(count);
        chip.prop('hidden', '' !== state && 0 === count && state !== selectedState);
    });
    batch.toggleClass('o-filter-hidden', 0 === shownCount);
    noMatches.prop('hidden', 0 < shownCount);
    const filtering = 0 < words.length || '' !== selectedState;
    announcer.text(filtering ? Omeka.jsTranslate('Matching modules: %s').replace('%s', shownCount) : '');
    updateBatchActions();
};

// A change to the filter makes Select all stale, so it's cleared.
const clearSelectAll = function() {
    $('.select-all').prop('checked', false);
};

input.on('input', function() {
    clearSelectAll();
    refresh();
});

input.on('keydown', function(e) {
    if ('Escape' === e.key && '' !== input.val()) {
        input.val('').trigger('input');
    }
});

chips.on('click', function() {
    selectedState = this.getAttribute('data-state-filter');
    chips.attr('aria-pressed', 'false');
    $(this).attr('aria-pressed', 'true');
    clearSelectAll();
    refresh();
});

// admin.js handles these changes too, but its batch code only changes other
// pages' options, so it doesn't undo these in either order.
table.on('change', 'input[name="module_ids[]"]', updateBatchActions);
$('.select-all').on('change', updateBatchActions);

// The version check reveals update notices after the page loads, then fires
// this event, so the Updates available chip counts them. A module that reveals
// notices of its own can fire it too. If the check finishes before this page
// is ready, the refresh below counts its notices instead.
$(document).on('o:version-notifications-checked', refresh);

refresh();

});
