<?php
/* Template Name: Download Sales Agreement PDF */

use Mpdf\Mpdf;

// Clear accidental output
ob_start();

require_once get_template_directory() . '/vendor/autoload.php';

if (!is_user_logged_in()) {
    wp_redirect(home_url('/signin'));
    exit;
}

$user_id = get_current_user_id();

$effective_date = get_user_meta($user_id, 'agreement_effective_date', true);
$signature_date = get_user_meta($user_id, 'agreement_signature_date', true);
$printed_name   = get_user_meta($user_id, 'agreement_printed_name', true);
$signature      = get_user_meta($user_id, 'agreement_signature', true);

if (!$printed_name || !$signature) {
    wp_die("Please complete the agreement form first.");
}

$logo_url = get_theme_mod('large_logo') ?: '';

$mpdf = new Mpdf();
$mpdf->SetTitle("Sales Agreement");

// PDF HTML
$html = '
<div style="font-family: Arial, sans-serif; line-height: 1.5;">
'.($logo_url ? '<p style="text-align:center;"><img src="'.$logo_url.'" width="150" /></p>' : '').'

<h2 style="text-align:center;">Addendum to Participation Form</h2>
<h4 style="text-align:center;">Independent Sales and Marketing Contractors – Legal Notice and Terms</h4>

<p><strong>Effective Date:</strong> '.$effective_date.'</p>

<p>
    This Addendum supplements and forms an integral part of the Participation Form used by
    representatives of Personal Empowerment Teams, Inc. ("the Organization"). All individuals engaging
    in the solicitation or enrollment of members using the Participation Form are bound by the terms
    set forth below.
</p>

<h4>1. Independent Contractor Status</h4>
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

<h4>2. Limited Authority</h4>
<p>
    Independent contractors are authorized solely to present and distribute information and to assist
    prospects in completing the Participation Form. Contractors may not collect funds, make guarantees,
    or obligate the Organization in any manner.
</p>

<h4>3. No Guarantee of Earnings</h4>
<p>
    The Organization makes no guarantee of income or compensation. Contractor earnings are based
    entirely on performance, if applicable. Past performance is not indicative of future results.
</p>

<h4>4. Use of Participation Form and Member Information</h4>
<p>
    Contractors shall use the Participation Form solely for its intended purpose. All data collected from
    prospective members is the property of the Organization and must be handled in accordance with
    applicable privacy and data protection laws. Contractors may not retain, duplicate, or repurpose
    collected data for personal or external use.
</p>

<h4>5. Right to Cancel (California Buyer’s Remorse Law)</h4>
<p>
    Pursuant to California Civil Code Sections 1689.6–1689.14, individuals who enroll in any program or
    service through the Organization’s Participation Form outside of a regular place of business have the
    right to cancel the agreement within three (3) business days. A Notice of Cancellation form will be
    provided to each prospect. Cancellations must be submitted in writing within the allotted period.
</p>

<h4>6. Payment and Chargebacks</h4>
<p>
    Contractors are strictly prohibited from collecting payments on behalf of the Organization. All
    payments, if applicable, must be submitted directly by the member through Organization-approved
    channels. The Organization is not liable for any payments made outside these channels. Members
    acknowledge that they are voluntarily submitting payment for services or participation, and agree
    not to initiate a chargeback without first contacting the Organization for resolution. Improper
    chargebacks may result in suspension of benefits or services.
</p>

<h4>7. Commission Compensation (50%)</h4>
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

<h4>9. Acknowledgment and Agreement</h4>
<p>
    By signing below, the undersigned acknowledges receipt and understanding of this Addendum, agrees
    to abide by its terms, and confirms they have no authority to alter or negotiate the Organization’s
    official offerings.
</p>

<p><strong>Printed Name:</strong> '.$printed_name.'<br>
<strong>E-Signature:</strong> '.$signature.'<br>
<strong>Date:</strong> '.$signature_date.'</p>

<p>Personal Empowerment Teams, Inc.<br>
14227 Magnolia Blvd, Sherman Oaks, CA 91423<br>
Website: https://personalempowermentteams.me</p>

<p><strong>Printed Name:</strong> Joseph L. Flores<br>
<strong>Signature:</strong> Joseph L. Flores<br>
<strong>Date:</strong> '.$signature_date.'</p>

</div>
';

// Write PDF
$mpdf->WriteHTML($html);
ob_end_clean();
$mpdf->Output("sales-agreement.pdf", "D");
exit;
