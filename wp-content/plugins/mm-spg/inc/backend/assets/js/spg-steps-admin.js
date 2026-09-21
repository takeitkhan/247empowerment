jQuery(function ($) {

    function reindexBlocks() {
        $('#spg-blocks .spg-block').each(function (index) {

            $(this).attr('data-index', index);
            $(this).find('.spg-block-header strong')
                .text('Block #' + (index + 1));

            $(this).find('[name]').each(function () {
                this.name = this.name.replace(/spg_blocks\[\d+]/, 'spg_blocks[' + index + ']');
            });
        });
    }

    $('#spg-blocks').sortable({
        placeholder: 'spg-placeholder',
        update: reindexBlocks
    });

    $('#spg-blocks').on('click', '.spg-remove', function () {
        if (!confirm('Remove this block?')) return;
        $(this).closest('.spg-block').remove();
        reindexBlocks();
    });

    $('#spg-add-block').on('click', function () {

        const index = $('#spg-blocks .spg-block').length;

        const html = `
        <div class="spg-block" data-index="${index}">
            <div class="spg-block-header">
                <strong>Block #${index + 1}</strong>
                <span class="spg-remove">Remove</span>
            </div>

            <div class="spg-grid">
                <div class="spg-field">
                    <label>Block Type</label>
                    <select name="spg_blocks[${index}][type]">
                        <option value="text">Text</option>
                        <option value="video">Video</option>
                        <option value="button">Button</option>
                        <option value="shortcode">Shortcode</option>
                        <option value="redirect">Redirect</option>
                    </select>
                </div>

                <div class="spg-field">
                    <label>Button Label</label>
                    <input type="text" name="spg_blocks[${index}][label]">
                </div>

                <div class="spg-field spg-full">
                    <label>Text Content</label>
                    <textarea name="spg_blocks[${index}][content]"></textarea>
                </div>

                <div class="spg-field">
                    <label>Video Source</label>
                    <input type="text" name="spg_blocks[${index}][src]">
                </div>

                <div class="spg-field">
                    <label>Redirect URL</label>
                    <input type="text" name="spg_blocks[${index}][url]">
                </div>

                <div class="spg-field spg-full">
                    <label>Shortcode</label>
                    <input type="text" name="spg_blocks[${index}][shortcode]">
                </div>
            </div>
        </div>`;

        $('#spg-blocks').append(html);
    });

});
