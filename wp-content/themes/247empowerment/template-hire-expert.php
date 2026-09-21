<?php
/**
 * Template Name: Hire an Expert - Complete Wizard
 * Complete multi-journey expert intake form with all journey-specific questions
 */

get_header_based_on_login();

$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$user_email = $current_user->user_email ?? '';
$user_name = $current_user->display_name ?? '';

// Check for success
$success = isset($_GET['success']) ? true : false;
?>

<div class="py-5 container">
    <div class="row">
        <div class="mx-auto col-lg-10">
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <h4 class="alert-heading">✅ Thank You!</h4>
                    <p>Your expert hire request has been successfully submitted. Our team will review your detailed information and get back to you shortly at the email and phone number you provided.</p>
                    <hr>
                    <p class="mb-0">We appreciate your interest and look forward to working with you!</p>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <div style="margin-top: 30px;">
                    <a href="/" class="btn btn-primary">← Return Home</a>
                </div>
            <?php else: ?>
                <div id="wizard-root"></div>
            <?php endif; ?>

        </div>
    </div>
</div>

<style>
/* Complete wizard styles (scoped under #wizard-root) */
#wizard-root{
  --bg:#F6F3EC;
  --surface:#FFFFFF;
  --surface-2:#FBF8F2;
  --ink:#1B2430;
  --ink-soft:#5B6472;
  --ink-faint:#8A9099;
  --border:#E4DFD3;
  --accent:#1F6F64;
  --accent-ink:#FFFFFF;
  --accent-soft:#E3EEEA;
  --accent-hover:#195A51;
  --shadow: 0 1px 2px rgba(27,36,48,0.06), 0 8px 24px -12px rgba(27,36,48,0.18);
  --focus:#1F6F64;
  font-family: inherit;
}

@media (prefers-color-scheme: dark){
  #wizard-root{
    --bg:#12161C;
    --surface:#181D25;
    --surface-2:#1D232C;
    --ink:#EBEEF2;
    --ink-soft:#9BA4B2;
    --ink-faint:#6E7684;
    --border:#2A313C;
    --accent:#4FBFAE;
    --accent-ink:#0B1512;
    --accent-soft:#1B2E2B;
    --accent-hover:#6ACEC0;
    --shadow: 0 1px 2px rgba(0,0,0,0.3), 0 12px 28px -14px rgba(0,0,0,0.6);
    --focus:#4FBFAE;
  }
}

#wizard-root, #wizard-root *{ box-sizing:border-box; }
#wizard-root{ line-height:1.5; }
#wizard-root h3{ font-family:inherit; font-weight:600; text-wrap:balance; letter-spacing:-0.01em; margin:0; color:var(--ink); }
#wizard-root h4{ font-family:inherit; font-weight:600; margin:12px 0 8px; color:var(--ink); }
#wizard-root p{ font-family:inherit; margin:0; }
#wizard-root a{ font-family:inherit; color:inherit; text-decoration:none; }
#wizard-root button{ font-family:inherit; color:inherit; }
#wizard-root input, #wizard-root textarea{ font-family:inherit; }
#wizard-root :focus-visible{ outline:2px solid var(--focus); outline-offset:2px; }
#wizard-root ::selection{ background:var(--accent-soft); }

#wizard-root .btn{
  appearance:none; border:none; cursor:pointer; border-radius:9px;
  font-size:14.5px; font-weight:600; padding:13px 22px;
  display:inline-flex; align-items:center; gap:8px; font-family:inherit;
}
#wizard-root .btn-primary{ background:var(--accent); color:var(--accent-ink); }
#wizard-root .btn-primary:hover{ background:var(--accent-hover); }
#wizard-root .btn-ghost{ background:var(--surface); color:var(--ink); border:1px solid var(--border); }
#wizard-root .btn-ghost:hover{ background:var(--accent-soft); }
#wizard-root .btn[disabled]{ opacity:0.55; cursor:not-allowed; }

#wizard-root .wizard-card{ background:var(--surface); border:1px solid var(--border); border-radius:14px; padding:32px; box-shadow:var(--shadow); max-width:740px; margin:0 auto; color:var(--ink); }
@media (max-width:640px){ #wizard-root .wizard-card{ padding:22px; } }
#wizard-root .wizard-block-tag{ font-size:11px; letter-spacing:0.07em; text-transform:uppercase; font-weight:600; color:var(--accent); margin:0 0 8px; }
#wizard-root .wizard-card h3{ font-size:19px; font-weight:600; margin:0 0 6px; }
#wizard-root .wizard-card .sub{ font-size:13.5px; color:var(--ink-soft); line-height:1.55; margin:0 0 20px; }
#wizard-root .wizard-progress{ height:6px; background:var(--border); border-radius:999px; margin-bottom:28px; overflow:hidden; }
#wizard-root .wizard-progress > span{ display:block; height:100%; background:var(--accent); border-radius:999px; transition:width .2s ease; }
#wizard-root .wizard-options{ display:grid; gap:10px; }
#wizard-root .wizard-option{
  text-align:left; cursor:pointer; background:var(--surface); border:1px solid var(--border); border-radius:10px;
  padding:14px 16px; font:inherit; font-size:14.5px; color:var(--ink);
  display:flex; align-items:center; justify-content:space-between; gap:10px;
}
#wizard-root .wizard-option:hover{ border-color:var(--accent); background:var(--accent-soft); }
#wizard-root .wizard-option.selected{ border:2px solid var(--accent); background:var(--accent-soft); color:var(--accent); font-weight:600; padding:13px 15px; }
#wizard-root .wizard-check{ color:var(--accent); flex:none; }
#wizard-root .wizard-actions{ display:flex; gap:12px; margin-top:20px; }
#wizard-root .wizard-actions .btn-primary{ flex:1; justify-content:center; }
#wizard-root .wizard-field{ margin-bottom:14px; }
#wizard-root .wizard-field label{ display:block; font-size:12.5px; font-weight:600; color:var(--ink-soft); margin-bottom:6px; }
#wizard-root .wizard-field input, #wizard-root .wizard-field textarea, #wizard-root .wizard-field-input{
  font-family:inherit; font-size:14px; color:var(--ink); background:var(--surface-2);
  border:1px solid var(--border); border-radius:8px; padding:11px 12px; width:100%;
}
#wizard-root .wizard-field textarea{ resize:vertical; min-height:90px; }
#wizard-root .wizard-field input:focus, #wizard-root .wizard-field textarea:focus{ border-color:var(--accent); }
#wizard-root .wizard-note-card{ background:var(--surface-2); border:1px solid var(--border); border-radius:10px; padding:14px 16px; margin-bottom:20px; font-size:13.5px; color:var(--ink-soft); line-height:1.6; }
#wizard-root .wizard-note-card strong{ color:var(--ink); }
#wizard-root .wizard-badge-row{ display:flex; gap:8px; flex-wrap:wrap; }
#wizard-root .wizard-badge{
  font-size:12.5px; font-weight:600; padding:7px 14px; border-radius:999px; border:1px solid var(--border);
  background:var(--surface); cursor:pointer; color:var(--ink-soft); font-family:inherit;
}
#wizard-root .wizard-badge.selected{ border-color:var(--accent); color:var(--accent); background:var(--accent-soft); }
#wizard-root .wizard-error{ color:#b91c1c; font-size:13px; margin-top:6px; }
#wizard-root .wizard-loading{ text-align:center; padding:20px; }
#wizard-root .wizard-loading::after{ content:''; display:inline-block; width:20px; height:20px; border:3px solid var(--accent-soft); border-top-color:var(--accent); border-radius:50%; animation:spin 0.8s linear infinite; }
@keyframes spin{ to { transform:rotate(360deg); } }
</style>

<script>
// Store user data for prefilling
window.userFormData = {
    name: '<?php echo esc_js($user_name); ?>',
    email: '<?php echo esc_js($user_email); ?>',
    isLoggedIn: <?php echo $user_id ? 'true' : 'false'; ?>
};

// Store AJAX data
window.expertRequestData = {
    ajaxurl: '<?php echo admin_url('admin-ajax.php'); ?>',
    nonce: '<?php echo wp_create_nonce('expert_request_nonce'); ?>'
};
</script>

<?php
// Load the complete wizard JavaScript
$wizard_file = get_template_directory() . '/assets/js/hire-expert-wizard-complete.js';
if (file_exists($wizard_file)) {
    echo '<script>';
    include $wizard_file;
    echo '</script>';
} else {
    echo '<p style="color:red; text-align:center;">Wizard script not found. Please contact support.</p>';
}
?>

<?php
get_footer_based_on_login();
?>
