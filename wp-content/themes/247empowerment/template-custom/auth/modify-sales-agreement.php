<?php
/* Template Name: Sales Agreement Form */

if (!is_user_logged_in()) {
    wp_redirect(home_url('/signin'));
    exit;
}
get_header_based_on_login();

$current_user = wp_get_current_user();

// Get current logged-in user ID (used as a fallback if no slug is provided)
$current_user_id = get_current_user_id();

// 1. Get the user slug from the query variable
$user_slug = get_query_var('user_profile');

// 2. Determine the target user
if ($user_slug) {
    // If a slug is present, try to get the user by their slug (login or nicename)
    $user = get_user_by('slug', $user_slug);
} else {
    // If no slug, fall back to the currently logged-in user
    $user = get_user_by('ID', $current_user_id);
}

// 3. Instantiate the UserProfileData class and get the profile array
if ($user) {
    // We pass the WP_User object to the class constructor, or the ID/slug depending on the class's constructor.
    // Given your original line: $profile = (new UserProfileData($user_slug))->getProfile();
    // We'll update it to pass the $user object for better data handling, assuming the class supports it.
    // If the class REQUIRES a slug, use $user_slug or $user->user_login.

    // Option A: If UserProfileData takes a WP_User object (Recommended)
    $profile_data_instance = new UserProfileData($user);

    // Option B: If UserProfileData only takes the slug (Sticking closer to your original code)
    // Use the slug if present, otherwise use the current user's login.
    $target_identifier = $user_slug ? $user_slug : $user->user_login;
    $profile_data_instance = new UserProfileData($target_identifier);

    // Get the profile array
    $profile = $profile_data_instance->getProfile();
} else {
    // Set variables to null if no user could be determined
    $user = null;
    $profile = null;
}

// Fetch saved meta
$effective_date_value = get_user_meta($current_user_id, 'agreement_effective_date', true);
$signature_date_value = get_user_meta($current_user_id, 'agreement_signature_date', true);
$printed_name_value   = get_user_meta($current_user_id, 'agreement_printed_name', true);
$signature_value      = get_user_meta($current_user_id, 'agreement_signature', true);

// Lock dates if already submitted
$effective_readonly = $effective_date_value ? 'readonly' : '';
$signature_readonly = $signature_date_value ? 'readonly' : '';

?>

<div class="container profile-page pt20">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3">
            <?php get_template_part('template-custom/auth/common-parts/editprofilemenu', null, ['profile' => $profile]); ?>
            <?php get_template_part('template-custom/auth/profile-parts/navlink', null, ['profile' => $profile]); ?>
        </div>
        <div class="mb-0 rounded-end-0 col-lg-9">
            <div class="bg-white custom-card post-search">
                <div class="gap-3 post-row">
                    <h5 class="pb-4 text-start portal-title">Sales Agreement Form</h5>
                    <p>
                        <img
                            width="150px"
                            src="<?php echo esc_url(get_theme_mod('large_logo')); ?>"
                            alt="<?php echo esc_attr(get_bloginfo('name')); ?>">
                    </p>
                    <h4 class="mb-3">Addendum to Participation Form</h4>
                    <h6>Independent Sales and Marketing Contractors – Legal Notice and Terms</h6>

                    <p><strong>Effective Date:</strong> __________________</p>

                    <p>
                        This Addendum supplements and forms an integral part of the Participation Form used by
                        representatives of Personal Empowerment Teams, Inc. ("the Organization"). All individuals engaging
                        in the solicitation or enrollment of members using the Participation Form are bound by the terms
                        set forth below.
                    </p>

                    <h6>1. Independent Contractor Status</h6>
                    <p>
                        The undersigned (“Contractor”) is an independent contractor—not an employee or agent—and is
                        responsible for all taxes/benefits. Contractor must hold any required local business registration
                        (e.g., City of Los Angeles BTRC), operate an independent business (markets to the public, serves
                        multiple clients), set their own hours/rates, and use their own tools. Contractor has no authority
                        to bind the Organization and may not collect payments; all payments must go through
                        Organization-approved channels. Contractor will follow applicable laws and written policies, and if
                        these requirements aren’t met, the Organization may suspend work or reclassify the role to comply
                        with the law.
                    </p>

                    <h6>2. Limited Authority</h6>
                    <p>
                        Independent contractors are authorized solely to present and distribute information and to assist
                        prospects in completing the Participation Form. Contractors may not collect funds, make guarantees,
                        or obligate the Organization in any manner.
                    </p>

                    <h6>3. No Guarantee of Earnings</h6>
                    <p>
                        The Organization makes no guarantee of income or compensation. Contractor earnings are based
                        entirely on performance, if applicable. Past performance is not indicative of future results.
                    </p>

                    <h6>4. Use of Participation Form and Member Information</h6>
                    <p>
                        Contractors shall use the Participation Form solely for its intended purpose. All data collected from
                        prospective members is the property of the Organization and must be handled in accordance with
                        applicable privacy and data protection laws. Contractors may not retain, duplicate, or repurpose
                        collected data for personal or external use.
                    </p>

                    <h6>5. Right to Cancel (California Buyer’s Remorse Law)</h6>
                    <p>
                        Pursuant to California Civil Code Sections 1689.6–1689.14, individuals who enroll in any program or
                        service through the Organization’s Participation Form outside of a regular place of business have the
                        right to cancel the agreement within three (3) business days. A Notice of Cancellation form will be
                        provided to each prospect. Cancellations must be submitted in writing within the allotted period.
                    </p>

                    <h6>6. Payment and Chargebacks</h6>
                    <p>
                        Contractors are strictly prohibited from collecting payments on behalf of the Organization. All
                        payments, if applicable, must be submitted directly by the member through Organization-approved
                        channels. The Organization is not liable for any payments made outside these channels. Members
                        acknowledge that they are voluntarily submitting payment for services or participation, and agree
                        not to initiate a chargeback without first contacting the Organization for resolution. Improper
                        chargebacks may result in suspension of benefits or services.
                    </p>

                    <h6>7. Commission Compensation (50%)</h6>
                    <p>
                        Contractor earns 50% of Net Program Fees per Qualified Enrollment that are paid online through
                        24/7 Empowerment’s platform. Qualified Enrollment means a first-time participant attributed to
                        Contractor at enrollment (name or verified link) who completes a paid enrollment and remains past
                        the refund/cancellation period; only one Contractor may be credited per participant. Net Program
                        Fees means program fees actually received minus taxes, discounts, refunds/chargebacks, and
                        third-party processing/platform fees; donations/grants/sponsorships are not commissionable.
                        Commissions are paid within 7 days after the refund period, are reversed on refunds/chargebacks,
                        are single-level only (no downlines), with no advances and Contractor covering their own expenses;
                        W-9 required, and 1099-NEC issued as required; terms may be changed prospectively by written
                        notice.
                    </p>

                    <h6>9. Acknowledgment and Agreement</h6>
                    <p>
                        By signing below, the undersigned acknowledges receipt and understanding of this Addendum, agrees
                        to abide by its terms, and confirms they have no authority to alter or negotiate the Organization’s
                        official offerings.
                    </p>

                    <p>
                        Personal Empowerment Teams, Inc.<br>
                        Joseph@PersonalEmpowermentTeams.me<br>
                        14227 Magnolia Blvd, Sherman Oaks, CA 91423<br>
                        Website: https://personalempowermentteams.me
                    </p>

                    <hr class="my-4">

                    <!-- BEGIN FORM -->
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Effective Date</label>
                            <input type="date"
                                name="effective_date"
                                class="form-control"
                                value="<?php echo $effective_date_value ?: date('Y-m-d'); ?>"
                                <?php echo $effective_readonly; ?>
                                required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Printed Name</label>
                            <input type="text"
                                name="printed_name"
                                class="form-control"
                                value="<?php echo esc_attr($printed_name_value); ?>"
                                required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Type your full name. We will accept it as your e-signature</label>
                            <input type="text"
                                name="signature"
                                class="form-control"
                                value="<?php echo esc_attr($signature_value); ?>"
                                required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Date</label>
                            <input type="date"
                                name="signature_date"
                                class="form-control"
                                value="<?php echo $signature_date_value ?: date('Y-m-d'); ?>"
                                <?php echo $signature_readonly; ?>
                                required>
                        </div>


                        <button type="submit" name="save_agreement" class="w-auto custom-btn">
                            Submit Agreement
                        </button>
                    </form>
                    <!-- END FORM -->

                </div>


            </div>
        </div>
    </div>
</div>

<?php get_footer_based_on_login(); ?>