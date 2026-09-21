jQuery(function ($) {

    const $tbody = $('#the-list');
    if (!$tbody.length) return;

    // Assign group keys
    $tbody.find('tr').each(function () {
        const $row = $(this);
        const phase = ($row.find('.column-spg_phase').text() || '').trim();
        const interest = ($row.find('.column-spg_interest').text() || '').trim();

        const groupKey = interest
            ? `phase-${phase}__interest-${interest}`
            : `phase-${phase}`;

        $row.attr('data-sort-group', groupKey);
    });

    let activeGroup = null;
    let originalIndex = null;

    $tbody.sortable({
        axis: 'y',
        handle: '.spg-drag-handle',
        tolerance: 'pointer',
        items: '> tr',

        start: function (e, ui) {
            activeGroup = ui.item.data('sort-group');
            originalIndex = ui.item.index();
        },

        stop: function (e, ui) {
            const $prev = ui.item.prev();
            const $next = ui.item.next();

            // ❌ Invalid move → revert
            if (
                ($prev.length && $prev.data('sort-group') !== activeGroup) ||
                ($next.length && $next.data('sort-group') !== activeGroup)
            ) {
                $tbody.sortable('cancel');
                return;
            }
        },

        update: function () {

            if (typeof ajaxurl === 'undefined') {
                console.error('ajaxurl is undefined');
                return;
            }

            if (!window.SPG_STEP_SORT || !SPG_STEP_SORT.nonce) {
                console.error('SPG_STEP_SORT nonce missing');
                return;
            }

            const order = [];

            $tbody.find('tr').each(function (index) {
                if (!this.id) return;

                order.push({
                    id: this.id.replace('post-', ''),
                    position: index
                });
            });

            $.post(ajaxurl, {
                action: 'spg_save_step_order',
                nonce: SPG_STEP_SORT.nonce,
                order: order
            })
            .done(function (res) {
                if (!res.success) {
                    console.error('Save failed:', res);
                }
            })
            .fail(function (xhr) {
                console.error('AJAX error:', xhr.responseText);
            });
        }
    });

    $tbody.disableSelection();
});
