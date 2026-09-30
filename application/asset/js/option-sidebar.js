$(document).ready(function() {

/**
 * Behavior for "add" sidebars rendered by the optionSidebar view helper.
 *
 * Group rows carry the arrangement: the is-hidden class marks hidden options,
 * and the Pinned section holds clones of the pinned group rows. refresh()
 * works out what is visible from that state, the filter, and whether the
 * sidebar is in customize mode.
 */

const getGroupRows = function(sidebar) {
    return sidebar.find('.option-sidebar-group:not(.option-sidebar-pinned) .option-sidebar-row');
};

const getPinnedList = function(sidebar) {
    return sidebar.find('.option-sidebar-pinned .option-sidebar-list');
};

const withName = function(rows, name) {
    return rows.filter(function() {
        return this.dataset.name === name;
    });
};

const isCustomizing = function(sidebar) {
    return sidebar.hasClass('customizing');
};

/**
 * Announce a message to screen readers, filling %s or %1$s, %2$s, ...
 */
const announce = function(sidebar, messageKey, ...args) {
    let message = sidebar.data('messages')[messageKey];
    // Replacer functions keep "$&" and similar in a label from being read as
    // replacement patterns.
    args.forEach(function(arg, index) {
        message = message.replace('%' + (index + 1) + '$s', () => arg);
    });
    if (args.length) {
        message = message.replace('%s', () => args[0]);
    }
    sidebar.find('.option-sidebar-announcer').text(message);
};

/**
 * Show and hide rows, groups, and headings for the current state.
 *
 * Hidden options stay visible, dimmed, in customize mode so they can be
 * shown again, and outside it while "Show hidden options" is on, so they can
 * still be added. The Pinned section is hidden while filtering so matches
 * aren't listed twice.
 */
const refresh = function(sidebar) {
    const customizing = isCustomizing(sidebar);
    const revealing = sidebar.hasClass('revealing');
    const query = sidebar.find('.option-sidebar-filter').val().trim().toLowerCase();
    let matchCount = 0;
    let hiddenMatchCount = 0;

    sidebar.find('.option-sidebar-group:not(.option-sidebar-pinned)').each(function() {
        const group = $(this);
        let visibleCount = 0;
        group.find('.option-sidebar-row').each(function() {
            const row = $(this);
            const isHidden = row.hasClass('is-hidden') && !customizing && !revealing;
            const text = (this.dataset.label + ' ' + this.dataset.module).toLowerCase();
            const isMatch = '' === query || text.includes(query);
            row.prop('hidden', isHidden || !isMatch);
            if (!isHidden && isMatch) {
                visibleCount++;
            }
            if (row.hasClass('is-hidden') && isMatch) {
                hiddenMatchCount++;
            }
        });
        group.prop('hidden', 0 === visibleCount);
        matchCount += visibleCount;
    });

    const pinnedCount = getPinnedList(sidebar).children().length;
    sidebar.find('.option-sidebar-pinned').prop('hidden', '' !== query || (!customizing && 0 === pinnedCount));
    sidebar.find('.option-sidebar-pinned-hint').prop('hidden', !customizing || 0 < pinnedCount);
    const visibleSections = sidebar.find('.option-sidebar-group').filter(function() {
        return !this.hidden;
    }).length;
    sidebar.find('.option-sidebar-group > h4').prop('hidden', !customizing && 2 > visibleSections);
    sidebar.find('.option-sidebar-no-matches').prop('hidden', '' === query || 0 < matchCount);

    // Offer hidden options that match, so an option hidden for the site
    // isn't mistaken for missing.
    const messages = sidebar.data('messages');
    sidebar.find('.option-sidebar-reveal')
        .prop('hidden', customizing || (!revealing && 0 === hiddenMatchCount))
        .text(revealing ? messages.hideHidden : messages.showHidden.replace('%s', () => hiddenMatchCount));

    return {query: query, matchCount: matchCount};
};

const getState = function(sidebar) {
    const names = function() {
        return this.dataset.name;
    };
    return {
        pinned: getPinnedList(sidebar).children('.option-sidebar-row').map(names).get(),
        hidden: getGroupRows(sidebar).filter('.is-hidden').map(names).get(),
    };
};

const setPinned = function(sidebar, name, isPinned) {
    if (isPinned) {
        const groupRow = withName(getGroupRows(sidebar), name);
        if (!groupRow.length) {
            return;
        }
        setHidden(sidebar, name, false);
        const clone = groupRow.clone().prop('hidden', false);
        getPinnedList(sidebar).append(clone);
    } else {
        withName(getPinnedList(sidebar).children(), name).remove();
    }
    withName(sidebar.find('.option-sidebar-row'), name)
        .find('.option-sidebar-pin')
        .attr('aria-pressed', isPinned ? 'true' : 'false');
};

const setHidden = function(sidebar, name, isHidden) {
    const groupRow = withName(getGroupRows(sidebar), name);
    groupRow.toggleClass('is-hidden', isHidden);
    groupRow.find('.option-sidebar-hide').attr('aria-pressed', isHidden ? 'true' : 'false');
    if (isHidden) {
        setPinned(sidebar, name, false);
    }
};

const applyState = function(sidebar, state) {
    getPinnedList(sidebar).empty();
    sidebar.find('.option-sidebar-pin').attr('aria-pressed', 'false');
    getGroupRows(sidebar).each(function() {
        setHidden(sidebar, this.dataset.name, state.hidden.includes(this.dataset.name));
    });
    state.pinned.forEach(function(name) {
        setPinned(sidebar, name, true);
    });
};

/**
 * Show where the arrangement came from, and which resets apply.
 */
const setSource = function(sidebar, source) {
    sidebar.find('.option-sidebar-source').text(sidebar.data('sourceLabels')[source]);
    sidebar.find('.option-sidebar-reset').each(function() {
        this.hidden = this.dataset.level !== source;
    });
};

const enterCustomize = function(sidebar) {
    sidebar.removeClass('revealing');
    sidebar.find('.option-sidebar-reveal').attr('aria-pressed', 'false');
    sidebar.addClass('customizing');
    sidebar.data('snapshot', getState(sidebar));
    sidebar.find('.option-sidebar-customize').attr('aria-pressed', 'true');
    sidebar.find('.option-sidebar-panel').prop('hidden', false);
    sidebar.find('.option-sidebar-error').prop('hidden', true).text('');
    sidebar.find('.option-sidebar-notice').prop('hidden', true).text('');
    // Start from customizing for yourself, so a site or global customization
    // is a choice.
    sidebar.find('.option-sidebar-scope input[value="user"]').prop('checked', true);
    setScope(sidebar, 'user');
    sidebar.find('button.option').prop('disabled', true);
    sidebar.data('sortable', new Sortable(getPinnedList(sidebar)[0], {
        draggable: '.option-sidebar-row',
        handle: '.sortable-handle',
        onEnd: function(e) {
            if (e.oldIndex === e.newIndex) {
                return;
            }
            const row = $(e.item);
            announce(sidebar, 'moved', row[0].dataset.label, row.index() + 1, row.parent().children().length);
        }
    }));
    refresh(sidebar);
    announce(sidebar, 'customizing');
};

const leaveCustomize = function(sidebar) {
    sidebar.removeClass('customizing');
    sidebar.find('.option-sidebar-customize').attr('aria-pressed', 'false');
    sidebar.find('.option-sidebar-panel').prop('hidden', true);
    sidebar.find('button.option').prop('disabled', false);
    const sortable = sidebar.data('sortable');
    if (sortable) {
        sortable.destroy();
        sidebar.removeData('sortable');
    }
    refresh(sidebar);
};

const cancelCustomize = function(sidebar) {
    applyState(sidebar, sidebar.data('snapshot'));
    setSource(sidebar, sidebar.data('arrangements').user.source);
    leaveCustomize(sidebar);
    announce(sidebar, 'canceled');
};

/**
 * Show the arrangement for a scope: the user's own view, or the shared one.
 *
 * What's shown is what gets saved, so choosing "This site" edits the site's
 * arrangement, never the user's own. The loaded state is kept to tell later
 * whether there are unsaved changes.
 */
const setScope = function(sidebar, scope) {
    const arrangement = sidebar.data('arrangements')[scope];
    applyState(sidebar, arrangement);
    setSource(sidebar, arrangement.source);
    sidebar.data('scope', scope);
    sidebar.data('loaded', JSON.stringify(getState(sidebar)));
};

const hasChanges = function(sidebar) {
    return JSON.stringify(getState(sidebar)) !== sidebar.data('loaded');
};

/**
 * After a save or reset, show the user's own view again.
 *
 * After a shared save or reset, the user may still see their own
 * arrangement, so a visible notice says what happened.
 */
const finish = function(sidebar, arrangements, level, isReset) {
    sidebar.data('arrangements', arrangements);
    applyState(sidebar, arrangements.user);
    setSource(sidebar, arrangements.user.source);
    leaveCustomize(sidebar);
    const messages = sidebar.data('messages');
    if ('user' === level) {
        announce(sidebar, isReset ? 'reset' : 'saved');
        return;
    }
    let notice = (isReset ? messages.resetShared : messages.savedShared)[level];
    if ('user' === arrangements.user.source) {
        notice += ' ' + messages.ownStillApplies;
    }
    sidebar.find('.option-sidebar-notice').text(notice).prop('hidden', false);
    sidebar.find('.option-sidebar-announcer').text(notice);
};

/**
 * Save or reset an arrangement, then show the user's own view again.
 *
 * The response must be JSON: when the session has expired, the request is
 * redirected to the login page, and that HTML must count as a failure.
 */
const post = function(sidebar, data) {
    const panelButtons = sidebar.find('.option-sidebar-panel button');
    panelButtons.prop('disabled', true);
    return $.ajax({
        url: sidebar.data('saveUrl'),
        method: 'POST',
        dataType: 'json',
        data: Object.assign({
            sidebar: sidebar.attr('data-sidebar'),
            site_id: sidebar.attr('data-site-id'),
            option_sidebar_csrf: sidebar.attr('data-csrf'),
        }, data),
    }).done(function(arrangements) {
        finish(sidebar, arrangements, data.level, Boolean(data.reset));
    }).fail(function(jqXHR) {
        const message = (jqXHR.responseJSON && jqXHR.responseJSON.error) || sidebar.data('messages').error;
        sidebar.find('.option-sidebar-error').text(message).prop('hidden', false);
        sidebar.find('.option-sidebar-announcer').text(message);
    }).always(function() {
        panelButtons.prop('disabled', false);
    });
};

$(document).on('input', '.option-sidebar-filter', function() {
    const sidebar = $(this).closest('.option-sidebar');
    const result = refresh(sidebar);
    if ('' === result.query) {
        sidebar.find('.option-sidebar-announcer').text('');
    } else {
        announce(sidebar, 'matches', result.matchCount);
    }
});

// The media sidebar sits inside the item form, where Enter in the filter would
// submit the item.
$(document).on('keydown', '.option-sidebar-filter', function(e) {
    if ('Enter' === e.key) {
        e.preventDefault();
    }
});

$(document).on('click', '.option-sidebar-customize', function() {
    const sidebar = $(this).closest('.option-sidebar');
    isCustomizing(sidebar) ? cancelCustomize(sidebar) : enterCustomize(sidebar);
});

$(document).on('click', '.option-sidebar-pin', function() {
    const sidebar = $(this).closest('.option-sidebar');
    const row = $(this).closest('.option-sidebar-row');
    const name = row[0].dataset.name;
    const label = row[0].dataset.label;
    if ('true' === this.getAttribute('aria-pressed')) {
        setPinned(sidebar, name, false);
        refresh(sidebar);
        announce(sidebar, 'unpinned', label);
    } else {
        setPinned(sidebar, name, true);
        refresh(sidebar);
        const count = getPinnedList(sidebar).children().length;
        announce(sidebar, 'pinned', label, count, count);
    }
});

$(document).on('click', '.option-sidebar-hide', function() {
    const sidebar = $(this).closest('.option-sidebar');
    const row = $(this).closest('.option-sidebar-row');
    const isHidden = 'true' !== this.getAttribute('aria-pressed');
    setHidden(sidebar, row[0].dataset.name, isHidden);
    refresh(sidebar);
    announce(sidebar, isHidden ? 'hidden' : 'shown', row[0].dataset.label);
});

$(document).on('click', '.option-sidebar-save', function() {
    const sidebar = $(this).closest('.option-sidebar');
    const state = getState(sidebar);
    post(sidebar, {level: sidebar.data('scope'), pinned: state.pinned, hidden: state.hidden});
});

// Switching what's being customized discards unsaved changes, so ask first.
$(document).on('change', '.option-sidebar-scope input', function() {
    const sidebar = $(this).closest('.option-sidebar');
    const previous = sidebar.data('scope');
    if (hasChanges(sidebar) && !window.confirm(sidebar.data('messages').discard)) {
        sidebar.find(`.option-sidebar-scope input[value="${previous}"]`).prop('checked', true);
        return;
    }
    setScope(sidebar, this.value);
    refresh(sidebar);
    sidebar.find('.option-sidebar-announcer').text(sidebar.find('.option-sidebar-source').text());
});

$(document).on('click', '.option-sidebar-reset', function() {
    const sidebar = $(this).closest('.option-sidebar');
    const level = this.dataset.level;
    // A shared arrangement is what everyone sees, so ask first.
    if ('user' !== level && !window.confirm(sidebar.data('messages').confirmReset[level])) {
        return;
    }
    post(sidebar, {level: level, reset: 1});
});

$(document).on('click', '.option-sidebar-reveal', function() {
    const sidebar = $(this).closest('.option-sidebar');
    const revealing = !sidebar.hasClass('revealing');
    sidebar.toggleClass('revealing', revealing);
    this.setAttribute('aria-pressed', revealing ? 'true' : 'false');
    refresh(sidebar);
});

$(document).on('click', '.option-sidebar-cancel', function() {
    cancelCustomize($(this).closest('.option-sidebar'));
});

});
