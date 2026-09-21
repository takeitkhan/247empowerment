<?php
/* Template Name: Participation Form */

if (!is_user_logged_in()) {
    wp_redirect(home_url('/signin'));
    exit;
}
get_header_based_on_login();
$current_user_id = get_current_user_id();
$current_user = wp_get_current_user();

// Get user profile if slug exists
$user_slug = get_query_var('user_profile');
if ($user_slug) {
    $user = get_user_by('slug', $user_slug);
} else {
    $user = get_user_by('ID', $current_user_id);
}

$profile = $user ? (new UserProfileData($user))->getProfile() : null;

$user_info = get_userdata($current_user_id);
$user_nicename = $user_info->user_nicename;
$referrer = get_user_meta($current_user_id, 'referrer', true);

$referred        = !empty($referrer_name) ? 'yes' : 'no';
$referrer_name   = $referrer_name ?? '';


// Unified mapping for easier processing
$field_map = [
    // Basic Fields
    'full_name'        => 'pa_participant_fullname',
    'email'            => 'pa_participant_email',
    'phone'            => 'pa_participant_phone',
    'emergency_contact' => 'pa_participant_emergency',
    'career'           => 'pa_participant_career',

    // Arrays
    'available_days'   => 'pa_available_days',
    'available_times'  => 'pa_available_times',

    // Textareas
    'goal_12_months'    => 'pa_goal_12_months',
    'learn_about_us'    => 'pa_learn_about_us',
    'suggestions'       => 'pa_suggestions',
    'differentiates_us' => 'pa_differentiates_us',
    'interested_parts'  => 'pa_interested_parts',
    'inspiration'       => 'pa_inspiration',
    'skills_gifts'      => 'pa_skills_gifts',
    'barriers'          => 'pa_barriers',
    'goals'             => 'pa_goals',

    // Radios
    'read_faq'      => 'pa_read_faq',
    'referred'      => 'pa_referred',
    'referrer_name' => 'pa_referrer_name',
    'media_consent' => 'pa_media_consent',

    // Checkboxes
    'declare_ready'            => 'pa_declare_ready',
    'declare_prepared'         => 'pa_declare_prepared',
    'declare_responsibility'   => 'pa_declare_responsibility',
    'declare_confidentiality'  => 'pa_declare_confidentiality',
    'declare_no_guarantees'    => 'pa_declare_no_guarantees',
    'declare_emotional_stable' => 'pa_declare_emotional_stable',

    // Signature
    'effective_date' => 'pa_effective_date',
    'printed_name'   => 'pa_printed_name',
    'pa_address'     => 'pa_address',
    'signature'      => 'pa_signature',
    'signature_date' => 'pa_signature_date',
];

// Extract all values
foreach ($field_map as $var => $meta_key) {
    if (in_array($var, ['available_days', 'available_times'])) {
        ${$var} = (array) get_user_meta($current_user_id, $meta_key, true);
    } else {
        ${$var} = get_user_meta($current_user_id, $meta_key, true);
    }
}

?>

<div class="container profile-page pt20">
    <div class="row">
        <div class="col-lg-3">
            <?php get_template_part('template-custom/auth/common-parts/editprofilemenu', null, ['profile' => $profile]); ?>
            <?php get_template_part('template-custom/auth/profile-parts/navlink', null, ['profile' => $profile]); ?>
        </div>
        <div class="mb-0 rounded-end-0 col-lg-9">
            <div class="bg-white custom-card post-search">
                <div class="gap-3 post-row">

                    <form method="post" id="participationForm">

                        <h5 class="pb-4 text-start portal-title">Participation Agreement Form</h5>

                        <p><img width="150" src="<?php echo esc_url(get_theme_mod('large_logo')); ?>"></p>

                        <h4 class="mb-3">PARTICIPATION AGREEMENT</h4>
                        <h6>Personal Empowerment Teams, Inc. (“Teams”)</h6>

                        <p><strong>Welcome to the Journey!</strong></p>
                        <p>This Participation Agreement represents your official threshold into a transformational journey through the programs of Personal Empowerment Teams, Inc. (“P.E.T”). Your signature signifies your willingness to engage with integrity, take full responsibility for your process, and honor yourself, your team, and the transformational legacy we’re building together.</p>

                        <!-- PARTICIPANT INFO -->
                        <h6>PARTICIPANT INFORMATION</h6>

                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="pa_participant_fullname" class="form-control"
                                value="<?php echo esc_attr($full_name); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="pa_participant_email" class="form-control"
                                value="<?php echo esc_attr($email); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="text" name="pa_participant_phone" class="form-control"
                                value="<?php echo esc_attr($phone); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Emergency Contact & Phone Number</label>
                            <input type="text" name="pa_participant_emergency" class="form-control"
                                value="<?php echo esc_attr($emergency_contact); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Career / Vocation</label>
                            <input type="text" name="pa_participant_career" class="form-control"
                                value="<?php echo esc_attr($career); ?>">
                        </div>

                        <!-- DAYS & TIMES -->
                        <div class="row">
                            <div class="col-md-12">
                                What days and times are you typically available to participate in your Empowerment and Transformation?
                            </div>
                            <div class="col-md-6">
                                <label>Days</label>
                                <select name="pa_available_days[]" class="form-control" multiple required>
                                    <?php
                                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                    foreach ($days as $d):
                                    ?>
                                        <option value="<?php echo $d; ?>"
                                            <?php selected(in_array($d, $available_days)); ?>>
                                            <?php echo $d; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label>Times</label>
                                <select name="pa_available_times[]" class="form-control" multiple required>
                                    <?php for ($i = 1; $i <= 24; $i++): ?>
                                        <option value="<?php echo $i; ?>"
                                            <?php selected(in_array($i, $available_times)); ?>>
                                            <?php echo $i; ?>:00
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                        <!-- TEXTAREAS -->
                        <?php
                        function textarea($label, $name, $value, $rows = 3)
                        {
                            echo "
                                <div class='form-group'>
                                    <label>{$label}</label>
                                    <textarea name='{$name}' class='form-control' rows='{$rows}'>" . esc_textarea($value) . "</textarea>
                                </div>
                            ";
                        }
                        ?>
                        <?php
                        textarea("Who and what do You Want to be within 12 months?", "pa_goal_12_months", $goal_12_months, 4);
                        textarea("How did you learn about 24/7 Empowerment?", "pa_learn_about_us", $learn_about_us, 2);
                        textarea("Please share any suggestions to improve our recruiting process to help more people.", "pa_suggestions", $suggestions, 3);
                        ?>
                        <!-- RADIOS -->
                        <div class="form-group">
                            <label>Did you read our FAQs Page or complete your own due diligence? </label><br>
                            <label><input type="radio" name="pa_read_faq" value="yes" <?php checked($read_faq, 'yes'); ?>> Yes</label>
                            <label class="ms-3"><input type="radio" name="pa_read_faq" value="no" <?php checked($read_faq, 'no'); ?>> No</label>
                        </div>

                        <?php
                        textarea("If so, what differentiates us from other empowerment programs?", "pa_differentiates_us", $differentiates_us, 2);
                        textarea("What parts of Personal Empowerment Teams, Inc. are you most interested in?", "pa_interested_parts", $interested_parts, 2);
                        textarea("What drove you here, and what inspired you to participate in Personal Empowerment Teams?", "pa_inspiration", $inspiration, 4);
                        textarea("What experience/s, gifts, and skills would you like to share to empower others?", "pa_skills_gifts", $skills_gifts, 4);
                        ?>

                        <h6>EMPOWERMENT PROGRAM COSTS & COMMITMENTS</h6>
                        <p>You are choosing to participate in a phased Empowerment Program that includes the following:</p>
                        <ul>
                            <li>Legacy Retreat: $500 (1 full day), time and place to be announced in advance.</li>
                            <li>Personal Empowerment Team meetings and Practitioner Training: $75 ($25 for the entire week for 3 weeks total).</li>
                            <li>Personal Empowerment Team meetings and Master’s Course: $375 ($25 for the whole week for 3 months total).</li>
                            <li>Certifications: $300 each.</li>
                            <li>Total Empowerment Package: $1,250.</li>
                            <li>Payments will be accepted ONLY online through 24/7 Empowerment’s Platform:<br>
                                <strong><?php echo esc_html(home_url('/u/' . $user_nicename)); ?></strong>
                            </li>
                            <li>Do not pay anyone directly!</li>
                            <li>PAYMENTS ARE ONLY ACCEPTED through the online wallets of 24/7’s Empowerment Platform:<br>
                                <strong><?php echo esc_html(home_url('/u/' . $user_nicename)); ?></strong>
                            </li>
                            <li>
                                <strong>Referral Appreciation (Program Enrollments Only):</strong><br>
                                Members may receive a one-time appreciation credit of up to $600 when a new participant they referred completes a paid enrollment in an eligible program and remains enrolled beyond the organization’s refund/cancellation period (and any applicable statutory cooling-off period).<br>
                                Single-level only; not available for donations or fundraising.<br>
                                Rewards may be issued as program credit or cash equivalent (e.g., check/ACH/electronic payment) at the organization’s option and may be taxable.<br>
                                Form W-9 required; Form 1099-NEC issued if a member receives $600 or more in cash rewards in a calendar year.<br>
                                Only one referring member may be credited per new participant.
                            </li>
                            <li>
                                Were you referred by a member?<br>

                                <label>
                                    <input type="radio" name="pa_referred" value="yes"
                                        <?php checked($referred, 'yes'); ?> id="refYes">
                                    Yes
                                </label>

                                <label class="ms-3">
                                    <input type="radio" name="pa_referred" value="no"
                                        <?php checked($referred, 'no'); ?> id="refNo">
                                    No
                                </label>
                            </li>

                            <li>
                                If yes, member’s full name:<br>

                                <input type="text"
                                    name="pa_referrer_name"
                                    id="referrerName"
                                    class="mt-2 form-control"
                                    placeholder="Referrer Name"
                                    value="<?php echo esc_attr($referrer_name); ?>"><br>

                                (One referring member only. Referral is verified and must meet program terms.)
                            </li>

                        </ul>

                        <ol>
                            <li>
                                <h6>The Legacy Retreat (1-Day Rite of Passage)</h6>
                                <p>
                                    You agree to show up fully—mentally, emotionally, spiritually, and physically—for a sacred, intensive day of breakthrough and reflection. You understand this experience is designed to help you confront limiting patterns and obstacles, embrace your “Birthright” Power Beyond Measure, and prepare to operate as a team.
                                </p>
                            </li>
                            <li>
                                <h6>The Team Training Empowerment Course (1 Two-Hour Meeting per Week for 3 Weeks)</h6>
                                <p>
                                    You agree to participate actively in team formation and function, based on the PET Code of Honor and Team Constitution. You commit to practicing clear communication, accountability, emotional presence, and collaboration.
                                </p>
                            </li>
                            <li>
                                <h6>The Master’s Course (1 Two-Hour Meeting per Week for 3 Months)</h6>
                                <p>
                                    This track is designed to challenge and support you as you apply your breakthroughs toward real-world transformation. You will work within a committed team of like-minded members to:
                                <ul>
                                    <li>Discover and break inner and outer barriers.</li>
                                    <li>Pursue your top goals and commitments.</li>
                                    <li>Practice living in your Power Beyond Measure</li>
                                    <li>Participate in feedback, mirroring, project management, coaching, and leadership opportunities.</li>
                                </ul>
                                </p>
                            </li>
                            <li>
                                <h6>Certifications</h6>
                                <p>
                                    Certifications are available upon successful completion of the entire program and can be awarded after completing our Mastery Course.
                                </p>
                            </li>
                            <li>
                                <h6>FAQs</h6>
                                <p>
                                    Please visit our website for due diligence and frequently asked questions about obstacles, empowerment, and profit sharing at <a href="https://personalempowermentteams.me/faqs/" target="_blank">https://personalempowermentteams.me/faqs/</a>
                                </p>
                            </li>
                            <li>
                                <h6>Personal Empowerment Teams, Inc. is a Non-Profit organization that shares revenue with members and causes.</h6>
                                <p>
                                    Visit our FAQs page for details <a href="https://personalempowermentteams.me/faqs/" target="_blank">https://personalempowermentteams.me/faqs/</a>
                                </p>
                            </li>
                        </ol>
                        <!-- CHECKBOXES -->
                        <h6>PARTICIPANT DECLARATIONS</h6>
                        <p>
                            By signing this agreement, you affirm:
                        </p>
                        <?php
                        function checkbox($label, $name, $value)
                        {
                            echo "<label><input type='checkbox' name='{$name}' value='yes' " . checked($value, 'yes', false) . "> {$label}</label><br>";
                        }

                        checkbox("I am ready to show up consistently and wholeheartedly.", "pa_declare_ready", $declare_ready);
                        checkbox("I am emotionally and mentally prepared to be challenged, supported, and transformed.", "pa_declare_prepared", $declare_prepared);
                        checkbox("I accept full responsibility for my decisions, growth, and actions.", "pa_declare_responsibility", $declare_responsibility);
                        checkbox("I will honor confidentiality and trust within my team.", "pa_declare_confidentiality", $declare_confidentiality);
                        checkbox("I understand no promises or guarantees are made—my results are up to me.", "pa_declare_no_guarantees", $declare_no_guarantees);
                        checkbox(" I affirm that I am emotionally stable and willing to take full responsibility for my personal growth journey.", "pa_declare_emotional_stable", $declare_emotional_stable);
                        ?>


                        <h6>PARTICIPATION TERMS & CONDITIONS</h6>

                        <ol>
                            <li>
                                <strong>Voluntary Engagement</strong><br>
                                You are voluntarily entering into this agreement. You may opt out at any time; however, your
                                departure may affect your standing with future team invitations or certification pathways.
                            </li>

                            <li>
                                <strong>Confidentiality & Respect</strong><br>
                                All conversations, shared stories, and personal breakthroughs within the team are confidential.
                                Breaching trust may result in removal.
                            </li>

                            <li>
                                <strong>No Guarantees</strong><br>
                                P.E.T. offers tools, team support, and mentorship—not therapy, legal advice, or guaranteed results.
                                You are responsible for what you do with what you receive.
                            </li>

                            <li>
                                <strong>Liability & Assumption of Risk</strong><br>
                                You acknowledge that deep personal work can be emotional, and you assume full responsibility for
                                your participation. P.E.T. is not liable for your choices, interpretations, or outcomes.
                            </li>

                            <li>
                                <strong>Code of Honor Agreement</strong><br>
                                You agree to uphold the PET Code of Honor in your communication, commitments, and conduct
                                within the team and during all phases of the program.
                            </li>

                            <li>
                                <div class="form-group">
                                    <label>Media Consent (Optional)</label><br>
                                    <label><input type="radio" name="pa_media_consent" value="yes" <?php checked($media_consent, 'yes'); ?>> I consent to my image, voice, or success story being used anonymously for promotional or training purposes.</label>
                                    <label><input type="radio" name="pa_media_consent" value="no" <?php checked($media_consent, 'no'); ?>> I decline media use. If neither box is selected, we will assume you do not consent to media use.</label>
                                </div>
                            </li>
                        </ol>

                        <!-- PERSONAL COMMITMENTS -->
                        <h6>PERSONAL COMMITMENTS (Required)</h6>

                        <strong>BARRIES</strong>
                        <?php textarea("List three personal barriers that you want to dissolve during your journey & why:", "pa_barriers", $barriers, 4); ?>
                        <strong>GOALS</strong>
                        <?php textarea("List three and date (3) breakthroughs, goals, or changes you commit to achieving during your journey:", "pa_goals", $goals, 4); ?>

                        <p>
                            <strong>Participation in the Teams offering</strong><br>
                            is subject to the following terms and conditions, whether or not you have read them. If at any time
                            Participant does not want to be subject to these terms and conditions, Participant shall refrain from
                            attending or participating in any Teams event.
                        </p>

                        <p>
                            <strong>Participation</strong><br>
                            Participant agrees that attendance and/or participation in any Teams offering is optional and at Participant’s own discretion. Participant may not transfer or assign participation to any other entity.
                        </p>

                        <p>
                            <strong>24/7 Team Member Constitution.</strong><br>
                            Participants need to maintain open communication with each other according to the team training,
                            during, leading up to, and throughout any Teams offering. Participant and Teams will maintain open,
                            honest communication about the status of any offering-related matters, including but not limited to
                            any tardiness, inability, or unwillingness to attend any offering event or session(s).
                        </p>

                        <p>
                            <strong>Trust and Confidentiality.</strong><br>
                            Trust and professionalism are necessary for the success of Teams' offering(s). The Participant agrees
                            that any nonpublic or personal information shared by any or all 4 participants and/or Teams shall be
                            treated and held as confidential. Participant shall not use any information provided by Teams,
                            including any formats, structure or business information, nor any participant’s information to create
                            any commercial offering.
                        </p>

                        <p>
                            <strong>Participant agrees that.</strong><br>
                            Teams may reasonably rely on the accuracy and completeness of any information Participant shares
                            with Teams. Teams cannot be held responsible for Participant’s inaccurate or incomplete information
                            or for its reliance on any Participant-supplied information.
                        </p>

                        <p>
                            <strong>Privacy Policy.</strong><br>
                            Teams agrees to use Participant’s information in accordance with Teams’ Privacy Policy located at <a href="https://personalempowermentteams.me/privacy-policy" target="_blank">https://personalempowermentteams.me/privacy-policy</a>
                        </p>
                        <p>
                            <strong>No Guarantees.</strong><br>
                            Except as expressly provided in these terms and conditions, Teams makes no guarantees,
                            representations or warranties of any kind or nature, express or implied, with respect to any Teams
                            offering or for any outcomes thereof. Teams makes no express guarantee or outcome, including
                            invitation to any ongoing or future Teams offering. Participant agrees that you are using your own
                            judgment in attending, participating or using any Teams offering, and you agree that you are doing so
                            at your own risk.
                        </p>
                        <p>
                            <strong>Any content</strong><br>
                            Information provided by Teams is not, nor intended to be, nor should it be construed as medical,
                            business, financial, religious, or legal advice. No pastor-parishioner, client-attorney, financial
                            advisor-client, doctor-client, or other professional relationship is established between Participant and
                            Teams or other participants except as may be explicitly provided for herein.
                        </p>
                        <p>
                            <strong>Participant agrees and understands that</strong><br>
                            Participant assumes all risks and no results are guaranteed in any way related to any Teams offering.
                            Teams' offerings are solely to provide participants with information and tools to help participants make
                            their own decisions. Participant is solely responsible for their actions, decisions, and results based on
                            the attendance, participation, reliance, actions, and inaction of any Teams offering.
                        </p>
                        <p>
                            <strong>Waiver</strong><br>
                            The outcome of any Teams offering is subject to inherent risks, personal decisions, and other factors
                            beyond Teams’s control. Therefore, Teams has not made, and cannot make, any guarantees or
                            promises concerning any outcome(s,) and Participant is responsible for, and accepts all risk, for the
                            words, action,s and inactions Participant may or may not take during or as a result of any Teams
                            offering.
                        </p>
                        <p>
                            <strong>Liability</strong><br>
                            Participant understands and agrees that participation in a Team offering includes other members of
                            the general public and Teams is not, and cannot be held responsible for the words, actions, or
                            inactions of any participant(s), including the Participant. Participant specifically acknowledges and
                            agrees that Teams is not liable for any defamatory, offensive, or illegal conduct of any other
                            participant or user, including you.
                        </p>
                        <p>
                            <strong>Damages</strong><br>
                            In no event shall Teams be liable for any indirect, consequential, or special damages. Access to and
                            participation in Teams offerings is made free of charge, and Participant’s participation and attendance
                            is at Participant’s own discretion. Team's entire liability under this Agreement, and the Client’s
                            exclusive remedy, shall be limited to actual damages or $100 whichever is less.
                        </p>
                        <p>
                            <strong>Choice of Law and Venue</strong><br>
                            These terms and conditions shall be governed by and construed in accordance with the laws of the
                            State of California, without giving effect to any choice or conflict of law provision or rule that would
                            result in the application of laws of any jurisdiction other than those of the 5 State of California. Each
                            Party hereby submits to the exclusive jurisdiction of Los Angeles, California, for any such action, suit,
                            or proceeding.
                        </p>
                        <p>
                            <strong>Entire Agreement</strong><br>
                            This document reflects the entire agreement between Teams and the Participant and reflects a
                            complete understanding of the parties with respect to the subject matter. These terms and conditions
                            supersede all prior written and oral representations and may not be amended, altered, or
                            supplemented except in writing signed by both the Teams and the Participant.
                        </p>

                        <!-- SIGNATURE -->
                        <h6>SIGNATURE & AGREEMENT</h6>
                        <p>
                            By signing below, I confirm that I have read, understood, and agree to the terms above. I understand
                            this journey will require courage, commitment, and consistency, and I am ready to begin.
                        </p>

                                                
                        <input type="hidden" name="pa_effective_date" class="form-control"
                            value="<?php echo esc_attr($effective_date ?: date('Y-m-d')); ?>">                        

                        <div class="form-group">
                            <label>Printed Name</label>
                            <input type="text" name="pa_printed_name" class="form-control"
                                value="<?php echo esc_attr($printed_name); ?>">
                        </div>

                        <div class="form-group">                            
                            <?php textarea("Address", "pa_address", $pa_address, 2); ?>                            
                        </div>

                        <div class="form-group">
                            <label>Type your full name. We will accept it as your e-signature</label>
                            <input type="text" name="pa_signature" class="form-control"
                                value="<?php echo esc_attr($signature); ?>">
                        </div>

                        <div class="form-group">
                            <label>Date</label>
                            <input type="date" name="pa_signature_date" class="form-control"
                                value="<?php echo esc_attr($signature_date ?: date('Y-m-d')); ?>">
                        </div>

                        <div class="form-group">
                            <strong>Personal Empowerment Teams, Inc.</strong>                            
                            <p>
                                Joseph@PersonalEmpowermentTeams.me<br/>
                                14227 Magnolia Blvd, Sherman Oaks, CA 91423<br/>
                                Website: https://personalempowermentteams.me
                            </p>
                            <p>
                                Signature: <u>Joshua Joseph</u><br>
                                Printed Name: <u>Joshua Joseph</u><br>
                                Date: <u><?php echo date('Y-m-d'); ?></u><br>
                            </p>
                        </div>

                        <button type="submit" name="save_participation_agreement" class="mt-3 w-auto custom-btn">
                            Submit Agreement
                        </button>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const refYes = document.getElementById("refYes");
        const refNo = document.getElementById("refNo");
        const refName = document.getElementById("referrerName");

        function toggleReferrer() {
            if (refYes.checked) {
                refName.style.display = "block";
            } else {
                refName.style.display = "none";
                refName.value = "";
            }
        }

        refYes.addEventListener("change", toggleReferrer);
        refNo.addEventListener("change", toggleReferrer);

        // Run once on load
        toggleReferrer();
    });
</script>


<?php get_footer_based_on_login(); ?>