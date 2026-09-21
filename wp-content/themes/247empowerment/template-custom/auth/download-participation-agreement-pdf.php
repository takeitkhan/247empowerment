<?php
/* Template Name: Download Participation Agreement PDF */

use Mpdf\Mpdf;

require_once get_template_directory() . '/vendor/autoload.php';

if (!is_user_logged_in()) {
    wp_redirect(home_url('/signin'));
    exit;
}

$current_user_id = get_current_user_id();
ob_start();

/*
|------------------------------------------------------------------
| FETCH ALL USER META
|------------------------------------------------------------------
*/

// BASIC INFO
$participant_fullname  = get_user_meta($current_user_id, 'pa_participant_fullname', true);
$participant_email     = get_user_meta($current_user_id, 'pa_participant_email', true);
$participant_phone     = get_user_meta($current_user_id, 'pa_participant_phone', true);
$participant_emergency = get_user_meta($current_user_id, 'pa_participant_emergency', true);
$participant_career    = get_user_meta($current_user_id, 'pa_participant_career', true);

// ARRAYS
$available_days_list  = get_user_meta($current_user_id, 'pa_available_days', true);
$available_times_list = get_user_meta($current_user_id, 'pa_available_times', true);

// Format available days with underline + new line
$available_days = 'N/A';

if (!empty($available_days_list) && is_array($available_days_list)) {

    $formatted_days = [];

    foreach ($available_days_list as $day) {
        $formatted_days[] = '<u style="padding: 2px 6px; display:inline-block;">' . esc_html($day) . '</u>';
    }

    // Comma spaced
    $available_days = implode(', ', $formatted_days);
}


// Format times like "02:00", "12:00"
$available_times = 'N/A';

if (!empty($available_times_list) && is_array($available_times_list)) {

    $formatted_times = [];

    foreach ($available_times_list as $t) {

        $time_label = str_pad(intval($t), 2, '0', STR_PAD_LEFT) . ':00';

        $formatted_times[] = '<u style="padding: 2px 6px; display:inline-block;">' . esc_html($time_label) . '</u>';
    }

    $available_times = implode(', ', $formatted_times);
}


// TEXT FIELDS
$goal_12_months     = get_user_meta($current_user_id, 'pa_goal_12_months', true);
$learn_about_us     = get_user_meta($current_user_id, 'pa_learn_about_us', true);
$suggestions        = get_user_meta($current_user_id, 'pa_suggestions', true);
$differentiates_us  = get_user_meta($current_user_id, 'pa_differentiates_us', true);
$interested_parts   = get_user_meta($current_user_id, 'pa_interested_parts', true);
$inspiration        = get_user_meta($current_user_id, 'pa_inspiration', true);
$skills_gifts       = get_user_meta($current_user_id, 'pa_skills_gifts', true);
$barriers           = get_user_meta($current_user_id, 'pa_barriers', true);
$goals              = get_user_meta($current_user_id, 'pa_goals', true);

// RADIO
$read_faq           = get_user_meta($current_user_id, 'pa_read_faq', true);
$referred           = get_user_meta($current_user_id, 'pa_referred', true);
$referrer_name      = get_user_meta($current_user_id, 'pa_referrer_name', true);

// CHECKBOXES
$declare_ready           = get_user_meta($current_user_id, 'pa_declare_ready', true);
$declare_prepared           = get_user_meta($current_user_id, 'pa_declare_prepared', true);
$declare_responsibility           = get_user_meta($current_user_id, 'pa_declare_responsibility', true);
$declare_confidentiality           = get_user_meta($current_user_id, 'pa_declare_confidentiality', true);
$declare_no_guarantees           = get_user_meta($current_user_id, 'pa_declare_no_guarantees', true);
$declare_emotional_stable           = get_user_meta($current_user_id, 'pa_declare_emotional_stable', true);

// CONSENT
$media_consent      = get_user_meta($current_user_id, 'pa_media_consent', true);

// SIGNATURE
$effective_date_value   = get_user_meta($current_user_id, 'pa_effective_date', true);
$printed_name_value     = get_user_meta($current_user_id, 'pa_printed_name', true);
$signature_value        = get_user_meta($current_user_id, 'pa_signature', true);
$pa_address             = get_user_meta($current_user_id, 'pa_address', true);
$signature_date_value   = get_user_meta($current_user_id, 'pa_signature_date', true);

$user_info = get_userdata($current_user_id);
$user_nicename = $user_info->user_nicename;
$referrer = get_user_meta($current_user_id, 'referrer', true);

$referred        = !empty($referrer_name) ? 'yes' : 'no';
$referrer_name   = $referrer_name ?? '';

/*
|------------------------------------------------------------------
| REQUIRED VALIDATION
|------------------------------------------------------------------
*/
if (!$printed_name_value || !$signature_value) {
    wp_die("Please complete the Participation Agreement form first.");
}

// Logo
$logo_url = get_theme_mod('large_logo') ?: '';

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

$mpdf = new Mpdf();
$mpdf->SetTitle("Participation Agreement");

/*
|------------------------------------------------------------------
| PDF HTML CONTENT
|------------------------------------------------------------------
*/

$html = '<div style="font-family: Arial, sans-serif; line-height: 1.5;">';

if ($logo_url) {
    $html .= '<p style="text-align:center;"><img src="' . $logo_url . '" width="150" /></p>';
}

function pdf_checkbox_line($label, $value)
{
    $mark = ($value === 'yes') ? '✓' : '□'; // Checked or unchecked symbol
    return "<p><strong>{$mark}</strong> {$label}</p>";
}

function pdf_media_line($label, $checked)
{
    $mark = $checked ? "✓" : "□";
    return "<p><strong>{$mark}</strong> {$label}</p>";
}

$is_yes = ($media_consent === "yes");
$is_no  = ($media_consent === "no" || empty($media_consent));


$html .= '
<h2 style="text-align:center;">PARTICIPATION AGREEMENT</h2>
<h4 style="text-align:center;">Personal Empowerment Teams, Inc. (“Teams”)</h4>

<p><strong>Welcome to the Journey!</strong><p>
<p>This Participation Agreement represents your official threshold into a transformational journey
through the programs of Personal Empowerment Teams, Inc. (“P.E.T”). Your signature signifies your
willingness to engage with integrity, take full responsibility for your process, and honor yourself, your
team, and the transformational legacy we’re building together.</p>

<h3>PARTICIPANT INFORMATION</h3>
<p><strong>Full Name: </strong><u>' . esc_html($participant_fullname) . '</u></p>
<p><strong>Email: </strong><u>' . esc_html($participant_email) . '</u></p>
<p><strong>Phone: </strong><u> ' . esc_html($participant_phone) . '</u></p>
<p><strong>Emergency Contact: </strong><u> ' . esc_html($participant_emergency) . '</u></p>
<p><strong>Career / Vocation: </strong><u> ' . esc_html($participant_career) . '</u></p>

<p>What days and times are you typically available to participate in your Empowerment and Transformation?</p>
<p><strong>Days: </strong> ' . $available_days . '</p>
<p><strong>Times: </strong> ' . $available_times . '</p>

<p><strong>Who and what do You Want to be within 12 months?</strong><br><u>' . nl2br(esc_html($goal_12_months)) . '</u></p>
<p><strong>How did you learn about 24/7 Empowerment?</strong><br><u>' . nl2br(esc_html($learn_about_us)) . '</u></p>
<p><strong>Please share any suggestions to improve our recruiting process to help more people</strong><br><u>' . nl2br(esc_html($suggestions)) . '</u></p>
<p><strong>Did you read our FAQs Page or complete your own due diligence?</strong><br><u>' . ($read_faq === "yes" ? "Yes" : "No") . '</u></p>
<p><strong>If so, what differentiates us from other empowerment programs?</strong><br><u>' . nl2br(esc_html($differentiates_us)) . '</u></p>
<p><strong>What parts of Personal Empowerment Teams, Inc. are you most interested in?</strong><br><u>' . nl2br(esc_html($interested_parts)) . '</u></p>
<p><strong>What drove you here, and what inspired you to participate in Personal Empowerment Teams?</strong><br><u>' . nl2br(esc_html($inspiration)) . '</u></p>
<p><strong>What experience/s, gifts, and skills would you like to share to empower others?</strong><br><u>' . nl2br(esc_html($skills_gifts)) . '</u></p>

<h3>EMPOWERMENT PROGRAM COSTS & COMMITMENTS</h3>
<p>You are choosing to participate in a phased Empowerment Program that includes the following:</p>
<ul>
    <li>Legacy Retreat: $500 (1 full day), time and place to be announced in advance.</li>
    <li>Personal Empowerment Team meetings and Practitioner Training: $75 ($25 for the entire week for 3 weeks total).</li>
    <li>Personal Empowerment Team meetings and Master’s Course: $375 ($25 for the whole week for 3 months total).</li>
    <li>Certifications: $300 each.</li>
    <li>Total Empowerment Package: $1,250.</li>
    <li>Payments will be accepted ONLY online through 24/7 Empowerment’s Platform:<br>
        <strong>
            <a href="'. esc_url(home_url('/u/' . $user_nicename)) .'" target="_blank">
                '. esc_html(home_url('/u/' . $user_nicename)) .'
            </a>
        </strong>
    </li>
    <li>Do not pay anyone directly!</li>
    <li>PAYMENTS ARE ONLY ACCEPTED through the online wallets of 24/7’s Empowerment Platform:<br>
        <strong>
            <a href="'. esc_url(home_url('/u/' . $user_nicename)) .'" target="_blank">
                '. esc_html(home_url('/u/' . $user_nicename)) .'
            </a>
        </strong>
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
        ' . ($referred === "yes" ? "<u>Yes</u>" : "<u>No</u>") . '
    </li>';

$html .= '
<li>
    If yes, member’s full name:<br>
    ' . (
    $referred === "yes"
    ? "<u>" . esc_html($referrer_name) . "</u>"
    : "<u>N/A</u>"
) . '
    <br>
    (One referring member only. Referral is verified and must meet program terms.)
</li>';

$html .= '
<ol>
    <li>
        <h4>The Legacy Retreat (1-Day Rite of Passage)</h4>
        <p>
            You agree to show up fully—mentally, emotionally, spiritually, and physically—for a sacred, intensive day of breakthrough and reflection. You understand this experience is designed to help you confront limiting patterns and obstacles, embrace your “Birthright” Power Beyond Measure, and prepare to operate as a team.
        </p>
    </li>
    <li>
        <h4>The Team Training Empowerment Course (1 Two-Hour Meeting per Week for 3 Weeks)</h4>
        <p>
            You agree to participate actively in team formation and function, based on the PET Code of Honor and Team Constitution. You commit to practicing clear communication, accountability, emotional presence, and collaboration.
        </p>
    </li>
    <li>
        <h4>The Master’s Course (1 Two-Hour Meeting per Week for 3 Months)</h4>
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
        <h4>Certifications</h4>
        <p>
            Certifications are available upon successful completion of the entire program and can be awarded after completing our Mastery Course.
        </p>
    </li>
    <li>
        <h4>FAQs</h4>
        <p>
            Please visit our website for due diligence and frequently asked questions about obstacles, empowerment, and profit sharing at <a href="https://personalempowermentteams.me/faqs/" target="_blank">https://personalempowermentteams.me/faqs/</a>
        </p>
    </li>
    <li>
        <h4>Personal Empowerment Teams, Inc. is a Non-Profit organization that shares revenue with members and causes.</h4>
        <p>
            Visit our FAQs page for details <a href="https://personalempowermentteams.me/faqs/" target="_blank">https://personalempowermentteams.me/faqs/</a>
        </p>
    </li>
</ol>';

$html .= '<h4>PARTICIPANT DECLARATIONS</h4>
<p>By signing this agreement, you affirm:</p>';

$html .= pdf_checkbox_line(
    "I am ready to show up consistently and wholeheartedly.",
    $declare_ready
);

$html .= pdf_checkbox_line(
    "I am emotionally and mentally prepared to be challenged, supported, and transformed.",
    $declare_prepared
);

$html .= pdf_checkbox_line(
    "I accept full responsibility for my decisions, growth, and actions.",
    $declare_responsibility
);

$html .= pdf_checkbox_line(
    "I will honor confidentiality and trust within my team.",
    $declare_confidentiality
);

$html .= pdf_checkbox_line(
    "I understand no promises or guarantees are made—my results are up to me.",
    $declare_no_guarantees
);

$html .= pdf_checkbox_line(
    "I affirm that I am emotionally stable and willing to take full responsibility for my personal growth journey.",
    $declare_emotional_stable
);

$html .= '<h3>PARTICIPATION TERMS & CONDITIONS</h3>';
$html .= '<ol>
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
        <strong>Media Consent (Optional)</strong><br>';
        $html .= pdf_media_line(
            "I consent to my image, voice, or success story being used anonymously for promotional or training purposes.",
            $is_yes
        );

        $html .= pdf_media_line(
            "I decline media use. If neither box is selected, we will assume you do not consent to media use.",
            $is_no
        );
        $html .= '
    </li>
</ol>';

$html .= '<h3>PERSONAL COMMITMENTS (Required)</h3>

<strong>BARRIES</strong>
<p>' . nl2br(esc_html($barriers)) . '</p>

<strong>GOALS</strong>
<p>' . nl2br(esc_html($goals)) . '</p>

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
    Trust and professionalism are necessary for the success of Teams\' offering(s). The Participant agrees
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
    Teams\' offerings are solely to provide participants with information and tools to help participants make
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
    is at Participant’s own discretion. Team\'s entire liability under this Agreement, and the Client’s
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
<h3>SIGNATURE & AGREEMENT</h3>
<p>
    By signing below, I confirm that I have read, understood, and agree to the terms above. I understand
    this journey will require courage, commitment, and consistency, and I am ready to begin.
</p>';

$html .= '<p>
<strong>E-Signature: </strong><u>' . $signature_value . '</u><br>
<strong>Printed Name: </strong><u>' . $printed_name_value . '</u> <small>Printed your </small><br>
<strong>Address: </strong><u>' . esc_html($pa_address) . '</u><br>
<strong>Phone: </strong><u>' . $participant_phone . '</u><br>
<strong>Email: </strong><u>' . $participant_email . '</u><br>
<strong>Date: </strong><u>' . $signature_date_value . '</u></p>';

$html .= '<p>Personal Empowerment Teams, Inc.<br>
14227 Magnolia Blvd, Sherman Oaks, CA 91423<br>
Website: https://personalempowermentteams.me</p>';

$html .= '<p><strong>Printed Name: </strong><u>Joshua Joseph</u><br>
<strong>Signature: </strong><u>Joshua Joseph</u><br>
<strong>Date: </strong><u>' . $signature_date_value . '</u></p>';


/*
|------------------------------------------------------------------
| OUTPUT PDF
|------------------------------------------------------------------
*/

ob_end_clean();
$mpdf->WriteHTML($html);
$mpdf->Output("participation-agreement.pdf", "D");
exit;
