$(document).ready(function () {
    const serializeRows = function ($container) {
        const rows = [];
        $container.find('.repeatable-rows > li').each(function () {
            const row = {};
            $(this).find('[data-field-name]').each(function () {
                row[$(this).data('fieldName')] = $(this).val();
            });
            rows.push(row);
        });
        $container.find('input[type="hidden"]').val(JSON.stringify(rows));
    };

    const updateButtons = function ($container) {
        const minRows = $container.data('minRows') || 0;
        const maxRows = $container.data('maxRows') || null;
        const count = $container.find('.repeatable-rows > li').length;

        $container.find('.repeatable-add').prop('disabled', maxRows !== null && count >= maxRows);
        $container.find('.repeatable-remove').prop('disabled', count <= minRows);
    };

    $('.repeatable-form-element').each(function () {
        const $container = $(this);
        if ($container.is('[data-sortable]')) {
            new Sortable($container.find('.repeatable-rows')[0], {
                draggable: 'li',
                handle: '.sortable-handle',
            });
        }
        updateButtons($container);
    });

    $('#content').on('click', '.repeatable-add', function () {
        const $container = $(this).closest('.repeatable-form-element');
        // template.content is a DocumentFragment, which jQuery cannot clone — importNode is required.
        const template = $container.find('template.repeatable-row-template')[0];
        $container.find('.repeatable-rows').append(document.importNode(template.content, true));
        updateButtons($container);
    });

    $('#content').on('click', '.repeatable-remove', function () {
        const $container = $(this).closest('.repeatable-form-element');
        $(this).closest('li').remove();
        updateButtons($container);
    });

    // Serialize all repeatable elements immediately before submit.
    $('#content').on('submit', 'form', function () {
        $(this).find('.repeatable-form-element').each(function () {
            serializeRows($(this));
        });
    });
});
