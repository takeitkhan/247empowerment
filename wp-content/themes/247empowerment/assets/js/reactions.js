jQuery(document).ready(function($) {
    // Handle reactions
    $(document).on('click', '.reaction-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const postId = $(this).data('post-id');
        const reaction = $(this).data('reaction');

        if (!postId || !reaction) return;

        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'add_reaction',
                post_id: postId,
                reaction: reaction,
                nonce: ajax_object.reaction_nonce
            },
            success: function(res) {
                if (res.success) {
                    // Update counts and active state in-place
                    const counts = res.data?.reactions || {};
                    const userReactions = res.data?.user_reaction || [];

                    // Update total reactions display
                    const total = Object.values(counts).reduce((s, v) => s + (parseInt(v) || 0), 0);
                    const $totalSpan = $(`#reaction-count-${postId}`);
                    if ($totalSpan.length) {
                        $totalSpan.text(total + ' Reaction' + (total !== 1 ? 's' : ''));
                    }

                    // Toggle active classes based on user_reaction (single or empty)
                    const $btns = $(`.reaction-btn[data-post-id="${postId}"]`);
                    $btns.removeClass('active');
                    if (Array.isArray(userReactions) && userReactions.length) {
                        userReactions.forEach(function(r) {
                            $btns.filter(`[data-reaction="${r}"]`).addClass('active');
                        });
                    }

                    // Optionally update per-button counts if present (span.reaction-type-count)
                    Object.keys(counts).forEach(function(r) {
                        const c = counts[r] || 0;
                        const $countEl = $(`.reaction-btn[data-post-id="${postId}"][data-reaction="${r}"]`).find('.reaction-type-count');
                        if ($countEl.length) {
                            $countEl.text(' ' + c);
                        }
                    });
                } else {
                    alert('Error: ' + (res.data?.message || 'Failed to add reaction'));
                }
            },
            error: function(xhr, status, err) {
                console.error('AJAX error:', status, err);
            }
        });
    });
});
