$(document).ready(function() {

/**
 * Behavior for the settings filter rendered by the settingsFilter view helper.
 *
 * The filter narrows the settings in its parent element by text and by group.
 * The groups are the .settings-group elements there, each given a chip cloned
 * from the template. Narrowing only hides settings, so all of them are still
 * submitted with the form.
 */

$('.settings-filter').each(function() {
    const bar = $(this);
    const scope = bar.parent();
    const input = bar.find('.settings-filter-input');
    const chips = bar.find('.settings-filter-chips');
    const groups = scope.find('.settings-group');
    const fields = scope.find('.field');
    const announcer = bar.find('.settings-filter-announcer');
    const noMatches = bar.next('.settings-filter-no-matches');
    let selectedGroup = null;

    // Add a chip for each group, with module groups after the Modules label.
    const template = bar.find('.settings-filter-chip-template')[0];
    const modulesLabel = chips.find('.settings-filter-modules-label');
    groups.each(function() {
        const label = $(this).find('.fieldsets-heading').first().text().trim();
        if ('' === label) {
            return;
        }
        const chip = $(template.content.firstElementChild.cloneNode(true));
        chip.text(label).data('group', this);
        if ($(this).hasClass('settings-group-module')) {
            chips.append(chip);
            modulesLabel.prop('hidden', false);
        } else {
            modulesLabel.before(chip);
        }
    });
    // A page without groups, such as a theme's settings without them, has
    // nothing to narrow to beyond All.
    if (chips.children('.settings-filter-chip').length < 2) {
        chips.addClass('settings-filter-hidden');
    }

    // The setting's name, without a fieldset's wrapping (user-settings[locale])
    // or a trailing [] for multiple values.
    const getName = function(field) {
        const name = $(field).find('[name]').first().attr('name') || '';
        const wrapped = name.match(/^[^\[]+\[([^\]]+)\]/);
        return wrapped ? wrapped[1] : name.replace(/\[\]$/, '');
    };

    // A setting's label, description and name, so a name from documentation
    // (media_type_whitelist) finds it too.
    const getText = function(field) {
        const meta = $(field).children('.field-meta');
        return (meta.children('label').text() + ' ' + meta.find('.field-description').text() + ' ' + getName(field)).toLowerCase();
    };

    /**
     * Show and hide settings, groups and chips for the current text and group.
     *
     * A setting with errors always shows, so it can be fixed.
     */
    const refresh = function() {
        const query = input.val().trim().toLowerCase();
        const filtering = '' !== query || null !== selectedGroup;
        // Groups with a setting that matches the text, whatever the chip, and
        // groups with a setting that shows.
        const matchedGroups = new Set();
        const shownGroups = new Set();
        let matchCount = 0;
        fields.each(function() {
            const field = $(this);
            const group = field.closest('.settings-group')[0];
            const hasErrors = 0 < field.children('ul.messages').length;
            const isMatch = '' === query || getText(this).includes(query) || hasErrors;
            const isShown = ((null === selectedGroup || group === selectedGroup) && isMatch) || hasErrors;
            field.toggleClass('settings-filter-hidden', !isShown);
            if (isMatch) {
                matchedGroups.add(group);
            }
            if (isShown) {
                shownGroups.add(group);
                matchCount++;
            }
        });
        groups.each(function() {
            // A selected group with no settings of its own still shows its
            // markup while there's no text to match.
            const isShown = !filtering || shownGroups.has(this) || (this === selectedGroup && '' === query);
            $(this).toggleClass('settings-filter-hidden', !isShown);
        });
        // While there's text, only the chips for groups with matches show, so
        // the chips say where the matches are. All and the selected chip
        // always show.
        let moduleChipShown = false;
        chips.children('.settings-filter-chip').each(function() {
            const group = $(this).data('group');
            if (!group) {
                return;
            }
            const isShown = '' === query || matchedGroups.has(group) || group === selectedGroup;
            $(this).prop('hidden', !isShown);
            if (isShown && group.classList.contains('settings-group-module')) {
                moduleChipShown = true;
            }
        });
        modulesLabel.prop('hidden', !moduleChipShown);
        // With no text, the selected group always shows, so there's always
        // something to see.
        noMatches.prop('hidden', '' === query || 0 < matchCount);
        announcer.text(filtering ? Omeka.jsTranslate('Matching settings: %s').replace('%s', matchCount) : '');
    };

    const selectGroup = function(group) {
        selectedGroup = group;
        chips.children('.settings-filter-chip').each(function() {
            const chip = $(this);
            chip.attr('aria-pressed', (chip.data('group') || null) === group ? 'true' : 'false');
        });
    };

    input.on('input', refresh);

    input.on('keydown', function(e) {
        if ('Enter' === e.key) {
            // Enter would submit the form, saving every setting.
            e.preventDefault();
        } else if ('Escape' === e.key && '' !== input.val()) {
            input.val('').trigger('input');
        }
    });

    chips.on('click', '.settings-filter-chip', function() {
        selectGroup($(this).data('group') || null);
        refresh();
    });

    // The browser can only focus an invalid setting that's visible, so clear
    // the filter when one is hidden. This is a capturing listener, as in
    // admin.js, because "invalid" doesn't bubble.
    document.body.addEventListener('invalid', function(e) {
        if ($.contains(scope[0], e.target) && $(e.target).closest('.settings-filter-hidden').length) {
            input.val('');
            selectGroup(null);
            refresh();
        }
    }, true);
});

});
