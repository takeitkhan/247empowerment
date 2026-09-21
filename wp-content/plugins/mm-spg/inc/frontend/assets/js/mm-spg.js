(function ($) {
    'use strict';

    /* =========================
       DIALOG DRAG ON SCROLL
    ========================= */
    const $dialog = $('.mm-spg-dialog');
    if (!$dialog.length) return;

    let translateY = 0;
    const MAX_UP   = -350;
    const MAX_DOWN = 1200;
    const SPEED    = 0.22;


    $(window).on('wheel.mmSpgDialog', function (e) {
        e.preventDefault(); // 🚫 block page scroll

        const deltaY = e.originalEvent.deltaY || 0;
        translateY -= deltaY * SPEED;

        translateY = Math.max(MAX_UP, Math.min(MAX_DOWN, translateY));

        $dialog.css('transform', `translateY(${translateY}px)`);
    });

    let startY = 0;

    $(document).on('touchstart.mmSpgDialog', function (e) {
        startY = e.originalEvent.touches[0].clientY;
    });

    $(document).on('touchmove.mmSpgDialog', function (e) {
        const currentY = e.originalEvent.touches[0].clientY;
        const delta = startY - currentY;

        translateY -= delta * 0.6;
        translateY = Math.max(MAX_UP, Math.min(MAX_DOWN, translateY));

        $dialog.css('transform', `translateY(${translateY}px)`);

        startY = currentY;
        e.preventDefault();
    });




    let steps = MM_SPG.steps || [];
    let currentStep = 0;
    let avatar = MM_SPG.avatar || '';

    function lockBodyScroll() {
        $('body').css({
            overflow: 'hidden',
            height: '100vh'
        });
    }

    function unlockBodyScroll() {
        $('body').css({
            overflow: '',
            height: ''
        });
    }


    /* =========================
       LAUNCHER
    ========================= */
    function updateLauncher(status) {
        // 🔒 FINAL TERMINAL STATE
        if (status === 'completed') {
            $('#mm-spg-launcher').addClass('mm-spg-hidden');
            return;
        }
        if (!avatar) {
            $('#mm-spg-launcher').addClass('mm-spg-hidden');
            return;
        }

        if (status === 'active') {
            $('#mm-spg-launcher').addClass('mm-spg-hidden');
        } else {
            $('#mm-spg-launcher')
                .removeClass('mm-spg-hidden')
                .text(status === 'paused' ? 'Resume Guide' : 'Start Guide');
        }
    }

    /* =========================
       RENDER STEP
    ========================= */
    function renderStep() {

        // -------- Phase 1: Avatar --------
        if (!avatar) {
            $('.mm-spg-title').text('Choose your avatar');
            $('.mm-spg-body').html(`                
                <div class="mm-spg-avatar-choice">
                    <div class="mm-spg-avatar-btn" data-avatar="male">
                        <img src="${MM_SPG.avatar_male_url}" alt="Male Avatar">
                    </div>
                    <div class="mm-spg-avatar-btn" data-avatar="female">
                        <img src="${MM_SPG.avatar_female_url}" alt="Female Avatar">
                    </div>
                </div>
                <p class="text-center">Please select a Guide to Empower Your Journey.</p>
            `);

            $('.mm-spg-avatar').html('');
            $('.mm-spg-next').hide();
            $('.mm-spg-pause').hide();
            clearHighlight();
            return;
        }

        // -------- Phase 2+ Steps --------
        let step = steps[currentStep];

        if (!step) {
            // Phase 2 just ended → auto start Phase 3
            if (steps[currentStep - 1]?.phase === 2) {

                $.post(MM_SPG.ajax_url, {
                    action: 'mm_spg_prepare_phase_3',
                    nonce: MM_SPG.nonce
                }, function () {
                    window.location.reload();
                });

                return;
            }

            hideModal();
            saveState('stopped');
            updateLauncher('stopped');
            return;
        }



        $('.mm-spg-title').text(step.title || '');
        if (step.blocks) {
            $('.mm-spg-body').html(renderBlocks(step.blocks));
        } else if (step.message) {
            // backward compatibility
            $('.mm-spg-body').html(`<p>${step.message}</p>`);
        }

        $('.mm-spg-avatar').html(`
                <img
                    src="${avatar === 'male' ? MM_SPG.avatar_male_url : MM_SPG.avatar_female_url}"
                    alt="${avatar === 'male' ? 'Male Avatar' : 'Female Avatar'}"
                    class="mm-spg-selected-avatar"
                >
            `);


        $('.mm-spg-next').show();
        $('.mm-spg-pause').show();
        $('.mm-spg-avatar-choice').remove();

        if (step.video) {
            $('.mm-spg-body').append(`
                <div class="mm-spg-video">
                    <iframe src="${step.video}" frameborder="0" allowfullscreen></iframe>
                </div>
            `);
        }

        if (step.highlight) {
            applyHighlight(step.highlight);
        } else {
            clearHighlight();
        }

        $('.mm-spg-next').text(step.redirect ? 'Go' : 'Next');

        /** Phase 3 */

        /* if (step.phase === 3) {
            $('.mm-spg-pause').hide();
        } else {
            $('.mm-spg-pause').show();
        } */

        // Pause is allowed unless guide is completed
        if (step.phase === 3 && currentStep >= steps.length - 1) {
            // Last step only (final screen)
            $('.mm-spg-pause').hide();
        } else {
            $('.mm-spg-pause').show();
        }

    }

    function nl2br(str) {
        if (!str) return '';
        return str
            .replace(/\r\n/g, '\n')
            .replace(/\n{2,}/g, '</p><p>')
            .replace(/\n/g, '<br>');
    }

    function renderBlocks(blocks) {
        let html = '';

        blocks.forEach(block => {

            switch (block.type) {

                case 'text':
                    html += `
                    <div class="mm-spg-text">
                        ${block.content}
                    </div>
                `;
                    break;

                case 'html':
                    html += `<div class="mm-spg-html">${block.content}</div>`;
                    break;

                case 'video':
                    html += `
                    <div class="mm-spg-video">
                        <iframe src="${block.src}" frameborder="0" allowfullscreen></iframe>
                    </div>
                `;
                    break;

                case 'shortcode':
                    const id = 'mm-spg-shortcode-' + Math.random().toString(36).substring(2);

                    html += `<div class="mm-spg-shortcode" id="${id}">Loading…</div>`;

                    $.post(MM_SPG.ajax_url, {
                        action: 'mm_spg_render_shortcode',
                        nonce: MM_SPG.nonce,
                        shortcode: block.shortcode
                    }, function (res) {
                        if (res.success) {
                            $('#' + id).html(res.data.html);
                        } else {
                            $('#' + id).html('<div class="alert alert-danger">Failed to load form.</div>');
                        }
                    });
                    break;
            }
        });

        return html;
    }





    /* =========================
       MODAL
    ========================= */
    function openModal() {
        $('#mm-spg-modal').removeClass('mm-spg-hidden');
    }

    function showModal() {
        $('#mm-spg-modal').removeClass('mm-spg-hidden');
        updateLauncher('active');
        renderStep();
    }

    function hideModal() {
        $('#mm-spg-modal').addClass('mm-spg-hidden');
    }

    /* =========================
       STATE
    ========================= */
    function saveState(status) {
        $.post(MM_SPG.ajax_url, {
            action: 'mm_spg_set_state',
            nonce: MM_SPG.nonce,
            status: status,
            step: currentStep
        });
    }

    function clearHighlight() {
        $('.mm-spg-highlighted').removeClass('mm-spg-highlighted');
        $('body').removeClass('mm-spg-highlighting');
    }

    function applyHighlight(selector) {
        clearHighlight();
        let $el = $(selector).first();
        if ($el.length) {
            $el.addClass('mm-spg-highlighted');
            $('body').addClass('mm-spg-highlighting');
        }
    }

    function findPhaseStart(phase) {
        return steps.findIndex(step => step.phase === phase);
    }

    function getPhaseStartIndex(phase) {
        for (let i = 0; i < steps.length; i++) {
            if (steps[i].phase === phase) {
                return i;
            }
        }
        return -1;
    }

    function validateInterestForm() {
        const $form = $('.mm-spg-interest-form');
        if (!$form.length) return true;

        const $error = $form.find('.mm-spg-error');
        const $checked = $form.find('input[name="user_categories[]"]:checked');

        $error.hide().text('');

        // RULE 1: At least one checkbox
        if (!$checked.length) {
            $error.text('Oops: Please prioritise your interest to proceed.').show();
            return false;
        }

        let hasFirstPriority = false;
        let allHavePriority = true;

        $checked.each(function () {
            const termId = $(this).val();
            const priority = $form.find(
                `select[name="user_categories_priority[${termId}]"]`
            ).val();

            if (!priority) {
                allHavePriority = false;
                return false;
            }

            if (priority === '1') {
                hasFirstPriority = true;
            }
        });

        if (!allHavePriority) {
            $error.text('Oops: Please assign a priority to each selected interest.').show();            
            return false;
        }

        if (!hasFirstPriority) {
            $error.text('Oops: Please mark at least one interest as 1st priority.').show();
            return false;
        }

        return true;
    }

    function validateAdditionalProfileForm() {
        const $form = $('.mm-spg-additional-profile-form');
        if (!$form.length) return true;

        let errorMsg = '';

        const title = $form.find('[name="designation"]').val().trim();
        const about = $form.find('[name="about_me_short"]').val().trim();
        const address = $form.find('[name="place_display_name"]').val().trim();

        // Collect keywords
        const keywords = [];
        $form.find('#keyword-tags .keyword-tag').each(function () {
            keywords.push(
                $(this).clone().children().remove().end().text().trim()
            );
        });

        // Collect hashtags
        const hashtags = [];
        $form.find('#hashtag-tags .hashtag-tag').each(function () {
            hashtags.push(
                $(this).clone().children().remove().end().text().trim()
            );
        });

        if (!title) {
            errorMsg = 'Please enter your title.';
        } else if (!about) {
            errorMsg = 'Please write something about yourself.';
        } else if (!address) {
            errorMsg = 'Please enter your address.';
        } else if (!keywords.length) {
            errorMsg = 'Please add at least one keyword.';
        } else if (!hashtags.length) {
            errorMsg = 'Please add at least one hashtag.';
        }

        if (errorMsg) {
            $form.find('.alert').remove();
            $form.prepend(
                '<div class="alert alert-danger mb-2">' + errorMsg + '</div>'
            );
            return false;
        }

        return true;
    }


    // 1️⃣ Comma → tag (keywords)
    /* $(document).on('keydown', '#keywordInput', function (e) {
        if (e.key === ',' || e.key === 'Enter') {
            e.preventDefault();

            const $input = $(this);
            let value = $input.val().replace(',', '').trim();

            if (!value) return;

            const exists = $('#keyword-tags .keyword-tag').filter(function () {
                return $(this).clone().children().remove().end().text().trim() === value;
            }).length;

            if (!exists) {
                $('#keyword-tags').prepend(`
                    <span class="bg-light border text-dark badge keyword-tag">
                        ${value}
                        <button type="button" class="btn-close remove-tag"></button>
                    </span>
                `);
            }

            $input.val('');
        }
    }); */

    // 2️⃣ Comma → tag (hashtags)
    /* $(document).on('keydown', '#hashtagInput', function (e) {
        if (e.key === ',' || e.key === 'Enter') {
            e.preventDefault();

            const $input = $(this);
            let value = $input.val().replace(',', '').trim();

            if (!value) return;

            if (!value.startsWith('#')) {
                value = '#' + value;
            }

            const exists = $('#hashtag-tags .hashtag-tag').filter(function () {
                return $(this).clone().children().remove().end().text().trim() === value;
            }).length;

            if (!exists) {
                $('#hashtag-tags').prepend(`
                    <span class="bg-light border text-dark badge hashtag-tag">
                        ${value}
                        <button type="button" class="btn-close remove-hashtag"></button>
                    </span>
                `);
            }

            $input.val('');
        }
    }); */

    function addTag($input, container, tagClass, removeClass, prefix = '') {
        let value = $input.val().replace(',', '').trim();
        if (!value) return;

        if (prefix && !value.startsWith(prefix)) {
            value = prefix + value;
        }

        const exists = $(container).find(`.${tagClass}`).filter(function () {
            return $(this).clone().children().remove().end().text().trim() === value;
        }).length;

        if (!exists) {
            $(container).prepend(`
                <span class="bg-light border text-dark badge ${tagClass}">
                    ${value}
                    <button type="button" class="btn-close ${removeClass}"></button>
                </span>
            `);
        }

        $input.val('');
    }

    $(document).on('input', '#keywordInput', function () {
        const $input = $(this);
        if ($input.val().includes(',')) {
            addTag($input, '#keyword-tags', 'keyword-tag', 'remove-tag');
        }
    });

    $(document).on('input', '#hashtagInput', function () {
        const $input = $(this);
        if ($input.val().includes(',')) {
            addTag($input, '#hashtag-tags', 'hashtag-tag', 'remove-hashtag', '#');
        }
    });

    
    // Remove keyword tag
    $(document).on('click', '.remove-tag', function () {
        $(this).closest('.keyword-tag').remove();
    });

    // Remove hashtag tag
    $(document).on('click', '.remove-hashtag', function () {
        $(this).closest('.hashtag-tag').remove();
    });




    /* =========================
       EVENTS
    ========================= */

    // Avatar select
    $(document).on('click', '.mm-spg-avatar-btn', function () {
        let selected = $(this).data('avatar');

        $.post(MM_SPG.ajax_url, {
            action: 'mm_spg_save_avatar',
            nonce: MM_SPG.nonce,
            avatar: selected
        }, function (res) {
            if (res.success) {
                avatar = selected;
                currentStep = 0;
                saveState('active');
                showModal();
            }
        });
    });

    $(document).on('click', '.mm-spg-next', function () {

        // Interests Form validation
        if (!validateInterestForm()) {
            return;
        }
        // 🔒 Additional Profile validation (THIS WAS MISSING)
        if (!validateAdditionalProfileForm()) {
            return;
        }
        let step = steps[currentStep];
        clearHighlight();



        /* =========================
           SAVE FORMS (NON-BLOCKING)
        ========================= */

        const $interestForm = $('.mm-spg-interest-form');
        if ($interestForm.length) {
            const formData = $interestForm.serialize();
            $.post(MM_SPG.ajax_url, {
                action: 'mm_spg_save_interests',
                nonce: $interestForm.find('[name="mm_spg_interest_nonce"]').val(),
                ...Object.fromEntries(new URLSearchParams(formData))
            });
        }

        const $profileForm = $('.mm-spg-additional-profile-form');
        if ($profileForm.length) {

            const keywords = [];
            $('#keyword-tags .keyword-tag').each(function () {
                keywords.push($(this).clone().children().remove().end().text().trim());
            });
            $('#keywords-hidden').val(keywords.join(', '));

            const hashtags = [];
            $('#hashtag-tags .hashtag-tag').each(function () {
                hashtags.push($(this).clone().children().remove().end().text().trim());
            });
            $('#hashtags-hidden').val(hashtags.join(', '));

            const formData = $profileForm.serialize();
            $.post(MM_SPG.ajax_url, {
                action: 'mm_spg_save_additional_profile',
                mm_spg_additional_nonce: $profileForm.find('[name="mm_spg_additional_nonce"]').val(),
                ...Object.fromEntries(new URLSearchParams(formData))
            });
        }

        const $socialForm = $('.mm-spg-social-links-form');
        if ($socialForm.length) {
            const formData = $socialForm.serialize();
            $.post(MM_SPG.ajax_url, {
                action: 'mm_spg_save_social_links',
                nonce: $socialForm.find('[name="mm_spg_links_nonce"]').val(),
                ...Object.fromEntries(new URLSearchParams(formData))
            });
        }

        /* =========================
           🔒 HARD GUARD: PHASE 2 IS DONE
        ========================= */
        if (
            step &&
            step.phase === 2 &&
            currentStep >= MM_SPG.phase_3_start_index
        ) {
            currentStep = MM_SPG.phase_3_start_index;
            renderStep();
            return;
        }

        /* =========================
           PHASE 2 → PHASE 3 (ONE-WAY)
        ========================= */
        if (
            step.phase === 2 &&
            currentStep === MM_SPG.phase_3_start_index - 1
        ) {
            $.post(MM_SPG.ajax_url, {
                action: 'mm_spg_complete_phase_2',
                nonce: MM_SPG.nonce
            }, function () {
                currentStep = MM_SPG.phase_3_start_index; // 🔐 lock Phase 2
                saveState('active');
                renderStep();
            });
            return;
        }

        /* =========================
           PHASE 3 COMPLETION
        ========================= */
        /* if (step.phase === 3 && currentStep >= steps.length - 1) {
            saveState('stopped');
            hideModal();
            updateLauncher('stopped');
            return;
        } */

        if (step.phase === 3 && currentStep >= steps.length - 1) {
            $.post(MM_SPG.ajax_url, {
                action: 'mm_spg_complete_phase_3',
                nonce: MM_SPG.nonce
            }, function () {
                hideModal();
                updateLauncher('completed');
            });
            return;
        }

        /* =========================
           REDIRECT
        ========================= */
        if (step.redirect) {
            window.location.href = step.redirect;
            return;
        }

        /* =========================
           NORMAL STEP ADVANCE
        ========================= */
        currentStep++;
        saveState('active');
        renderStep();
    });


    // Pause / Close
    $(document).on('click', '.mm-spg-pause, .mm-spg-close', function () {
        clearHighlight();
        saveState('paused');
        hideModal();
        updateLauncher('paused');
    });

    // Launcher click
    /* $(document).on('click', '#mm-spg-launcher', function () {

        if (MM_SPG.completed === true) {
            return;
        }

        // ✅ Avatar must exist
        if (!avatar) {
            currentStep = 0;
            showModal();
            return;
        }

        // ✅ Only jump to Phase 3 if Phase 2 completed
        if (
            MM_SPG.phase_3_start_index !== null &&
            MM_SPG.phase_3_start_index < steps.length
        ) {
            currentStep = MM_SPG.phase_3_start_index;
        } else {
            currentStep = 0; // Phase 2
        }
        // ✅ Resume from saved step
        //currentStep = currentStep || 0;


        saveState('active');
        showModal();
    }); */

    $(document).on('click', '#mm-spg-launcher', function () {

        if (MM_SPG.completed === true) {
            return;
        }

        // Avatar not selected → Phase 1
        if (!avatar) {
            currentStep = 0;
            showModal();
            return;
        }

        // ✅ Resume from last saved step
        saveState('active');
        showModal();
    });





    $(document).on('change', '.mm-spg-interest-form input[type=checkbox]', function () {
        $(this).closest('.d-flex').find('select')
            .prop('disabled', !this.checked);
    }).trigger('change');

    /* ========================
        INTERESTS SAVE
    ========================== */
    $(document).on('submit', '.mm-spg-interest-form', function (e) {
        e.preventDefault();

        // 🔹 STEP 2: আগের validation-এর জায়গায় এই এক লাইন
        if (!validateInterestForm()) {
            return;
        }

        const $form = $(this);
        const formData = $form.serialize();

        $.post(MM_SPG.ajax_url, {
            action: 'mm_spg_save_interests',
            nonce: $form.find('[name="mm_spg_interest_nonce"]').val(),
            ...Object.fromEntries(new URLSearchParams(formData))
        }, function (res) {

            $form.find('.alert').remove();

            if (res.success) {
                $form.prepend(
                    '<div class="alert alert-success mb-2">' + res.data + '</div>'
                );

                // OPTIONAL: auto-advance guide
                // currentStep++;
                // saveState('active');
                // renderStep();

            } else {
                $form.prepend(
                    '<div class="alert alert-danger mb-2">' + res.data + '</div>'
                );
            }
        });
    });

    /* =====================
    SOCIAL MANAGEMENT
    =================== */
    // Add new social link row
    $(document).on('click', '#mm-spg-add-social-link', function () {

        const index = $('#social-links-group .social-link-row').length;

        const row = `
            <div class="align-items-center mb-2 row g-2 social-link-row">
                <div class="col-md-3">
                    <select name="links[${index}][platform]" class="form-select">
                        <option value="facebook">Facebook</option>
                        <option value="instagram">Instagram</option>
                        <option value="linkedin">Linked In</option>
                        <option value="twitter">Twitter / X</option>
                        <option value="youtube">YouTube</option>
                        <option value="website">Website</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <input type="text"
                        name="links[${index}][label]"
                        class="form-control"
                        placeholder="Custom label">
                </div>

                <div class="col-md-4">
                    <input type="url"
                        name="links[${index}][url]"
                        class="form-control"
                        placeholder="https://example.com">
                </div>

                <div class="col-md-2">
                    <button type="button"
                            class="w-100 btn btn-danger remove-link">
                        Remove
                    </button>
                </div>
            </div>
        `;

        $('#social-links-group').append(row);
    });

    // Remove social link row
    $(document).on('click', '.remove-link', function () {
        $(this).closest('.social-link-row').remove();
    });


    $(document).on('submit', '.mm-spg-social-links-form', function (e) {
        e.preventDefault();

        const $form = $(this);
        const formData = $form.serialize();

        $.post(MM_SPG.ajax_url, {
            action: 'mm_spg_save_social_links',
            nonce: $form.find('[name="mm_spg_links_nonce"]').val(),
            ...Object.fromEntries(new URLSearchParams(formData))
        }, function (res) {

            $form.find('.alert').remove();

            if (res.success) {
                $form.prepend(
                    '<div class="alert alert-success mb-2">' + res.data + '</div>'
                );

                // OPTIONAL: auto-advance guide
                // currentStep++;
                // saveState('active');
                // renderStep();

            } else {
                $form.prepend(
                    '<div class="alert alert-danger mb-2">' + res.data + '</div>'
                );
            }
        });
    });

    /* =====================
    ADDITIONAL PROFILE
    =================== */
    $(document)
        .off('submit.mmSpgAdditionalProfile', '.mm-spg-additional-profile-form')
        .on('submit.mmSpgAdditionalProfile', '.mm-spg-additional-profile-form', function (e) {

        e.preventDefault();

        const $form = $(this);

        if (!validateAdditionalProfileForm()) {
            return;
        }

        // Keywords
        const keywords = [];
        $form.find('#keyword-tags .keyword-tag').each(function () {
            keywords.push(
                $(this).clone().children().remove().end().text().trim()
            );
        });
        $form.find('#keywords-hidden').val(keywords.join(', '));

        // Hashtags
        const hashtags = [];
        $form.find('#hashtag-tags .hashtag-tag').each(function () {
            hashtags.push(
                $(this).clone().children().remove().end().text().trim()
            );
        });
        $form.find('#hashtags-hidden').val(hashtags.join(', '));

        console.log('KEYWORDS:', $form.find('#keywords-hidden').val());
        console.log('HASHTAGS:', $form.find('#hashtags-hidden').val());

        $.post(MM_SPG.ajax_url, {
            action: 'mm_spg_save_additional_profile',
            nonce: $form.find('[name="mm_spg_additional_nonce"]').val(),
            designation: $form.find('[name="designation"]').val(),
            place_display_name: $form.find('[name="place_display_name"]').val(),
            show_full_address: $form.find('[name="show_full_address"]').is(':checked') ? '1' : '0',
            about_me_short: $form.find('[name="about_me_short"]').val(),
            user_keywords: $form.find('#keywords-hidden').val(),
            user_hashtags: $form.find('#hashtags-hidden').val()
        }, function (res) {

            $form.find('.alert').remove();

            if (res.success) {
                $form.prepend('<div class="alert alert-success mb-2">' + res.data + '</div>');
            } else {
                $form.prepend('<div class="alert alert-danger mb-2">' + res.data + '</div>');
            }
        });
    });






    /* =========================
       INITIAL LOAD
    ========================= */
    $(document).ready(function () {
        $.post(MM_SPG.ajax_url, {
            action: 'mm_spg_get_state',
            nonce: MM_SPG.nonce
        }, function (res) {
            if (!res.success) return;

            currentStep = res.data.step || 0;

            // 🔹 Phase 1 always blocks everything
            if (!avatar) {
                showModal();
                return;
            }

            // 🔹 WAITING STATE (⏱️ reading time enforcement)
            if (res.data.status === 'waiting' && res.data.wait_until) {
                const now = Math.floor(Date.now() / 1000);

                if (now >= res.data.wait_until) {
                    saveState('active');
                    showModal();
                } else {
                    setTimeout(() => {
                        saveState('active');
                        showModal();
                    }, (res.data.wait_until - now) * 1000);
                }

                return; // ⛔ stop further logic
            }

            // 🔹 Normal states
            if (res.data.status === 'active') {
                showModal();
            } else {
                updateLauncher(res.data.status);
            }
        });
    });


    $(document).on('submit', '.mm-spg-interest-form', function (e) {
        e.preventDefault();

        const $form = $(this);
        const $error = $form.find('.mm-spg-error');

        $error.hide().text('');

        const checked = $form.find('input[name="user_categories[]"]:checked');

        // RULE 1: At least one checkbox
        if (!checked.length) {
            $error.text('Oops: Please prioritise your interest to proceed.')
                .show();
            return;
        }

        // RULE 2: At least one 1st priority
        let hasFirstPriority = false;

        checked.each(function () {
            const termId = $(this).val();
            const priority = $form.find(
                `select[name="user_categories_priority[${termId}]"]`
            ).val();

            if (priority === '1') {
                hasFirstPriority = true;
            }
        });

        if (!hasFirstPriority) {
            $error.text('Oops: Please prioritise your interest to proceed.')
                .show();
            return;
        }

        /* -------------------------
           AJAX SUBMIT
        -------------------------- */
        $.post(MM_SPG.ajax_url, $form.serialize() + '&action=mm_spg_save_interests')
            .done(function (res) {
                if (!res.success) {
                    $error.text('❌ ' + res.data).show();
                } else {
                    $error.hide();
                    // continue guide flow here
                }
            });
    });
})(jQuery);
