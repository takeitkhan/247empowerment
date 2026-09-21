<?php
/**
 * Template Name: Zoom Documentation Guide
 * Description: Complete Zoom app integration guide - Adding, Using, and Removing
 */

get_header_based_on_login();
?>

<div class="py-5 container">
    <div class="row">
        <div class="mx-auto col-lg-8">
            
            <div class="mb-5">
                <h1 class="mb-3">Zoom Integration Guide</h1>
                <p class="text-muted fs-5">Complete instructions for setting up, using, and managing Zoom integration on our platform</p>
                <hr>
            </div>

            <!-- Table of Contents -->
            <div class="mb-5 p-4" style="background-color: #f8f9fa; border-radius: 4px;">
                <h5 class="mb-3">Contents</h5>
                <ul class="mb-0 list-unstyled">
                    <li><a href="#adding">Adding Zoom to Your Account</a></li>
                    <li><a href="#using">Using Zoom Integration</a></li>
                    <li><a href="#managing">Managing Your Zoom Account</a></li>
                    <li><a href="#removing">Removing Zoom Integration</a></li>
                    <li><a href="#troubleshooting">Troubleshooting</a></li>
                    <li><a href="#security">Security and Privacy</a></li>
                </ul>
            </div>

            <!-- Section 1: Adding Zoom -->
            <section id="adding" class="mb-5">
                <h2 class="mb-4">Adding Zoom to Your Account</h2>
                
                <p>Connect your Zoom account to our platform to access integrated meeting management features.</p>
                
                <h5 class="mt-4 mb-3">Prerequisites</h5>
                <ul>
                    <li>Active account on our platform</li>
                    <li>Valid Zoom account</li>
                    <li>Admin access to your Zoom account (recommended)</li>
                </ul>

                <h5 class="mt-4 mb-3">Step-by-Step Instructions</h5>
                
                <div class="mb-4">
                    <h6>Step 1: Navigate to Account Settings</h6>
                    <ul>
                        <li>Log in to your account</li>
                        <li>Go to your Profile or Account Settings</li>
                        <li>Find the "Connected Apps" or "Integrations" section</li>
                    </ul>
                </div>

                <div class="mb-4">
                    <h6>Step 2: Locate Zoom Integration</h6>
                    <ul>
                        <li>Look for "Zoom" in the list of available integrations</li>
                        <li>Click on the Zoom option to expand it</li>
                        <li>You will see a "Connect" button</li>
                    </ul>
                </div>

                <div class="mb-4">
                    <h6>Step 3: Click Connect Button</h6>
                    <ul>
                        <li>Click the "Connect Zoom Account" button</li>
                        <li>You will be redirected to Zoom's login page</li>
                        <li>Do not close this browser window</li>
                    </ul>
                </div>

                <div class="mb-4">
                    <h6>Step 4: Authorize Our App</h6>
                    <ul>
                        <li>Log in to your Zoom account if not already logged in</li>
                        <li>Review the permissions requested by our app</li>
                        <li>Click "Authorize" to grant permission</li>
                        <li>You may be asked to confirm your password</li>
                    </ul>
                </div>

                <div class="mb-4">
                    <h6>Step 5: Confirm Connection</h6>
                    <ul>
                        <li>You will be redirected back to our platform</li>
                        <li>You should see a confirmation message: "Zoom Account Connected"</li>
                        <li>Your Zoom account display name will appear on the page</li>
                        <li>The connection timestamp will be shown</li>
                    </ul>
                </div>

                <div class="mt-4 alert alert-info" role="alert">
                    <strong>Tip:</strong> Connection typically takes less than 30 seconds. If you are stuck on the Zoom login page for more than 2 minutes, check your internet connection and try again.
                </div>
            </section>

            <!-- Section 2: Using Zoom -->
            <section id="using" class="mb-5">
                <h2 class="mb-4">Using Zoom Integration</h2>
                
                <p>Once connected, you can use various Zoom features throughout our platform.</p>

                <h5 class="mt-4 mb-3">Available Features</h5>

                <div class="mb-4">
                    <h6>Meeting Visibility</h6>
                    <ul>
                        <li>View all your upcoming Zoom meetings</li>
                        <li>See meeting titles, times, and participants</li>
                        <li>Access meeting links directly from our platform</li>
                        <li>Quick access to recorded meetings</li>
                    </ul>
                </div>

                <div class="mb-4">
                    <h6>Quick Access</h6>
                    <ul>
                        <li>One-click access to join scheduled meetings</li>
                        <li>Meeting information displayed in your dashboard</li>
                        <li>Calendar integration with your scheduled meetings</li>
                        <li>Meeting status updates in real-time</li>
                    </ul>
                </div>

                <div class="mb-4">
                    <h6>Account Information</h6>
                    <ul>
                        <li>Your connected Zoom account display name is shown</li>
                        <li>Connection status displays as "Connected"</li>
                        <li>Last connection date and time is recorded</li>
                        <li>Account type information is available</li>
                    </ul>
                </div>

                <h5 class="mt-4 mb-3">Daily Usage</h5>
                <p>To use Zoom integration in your daily workflow:</p>
                <ul>
                    <li>Log in to your account on our platform</li>
                    <li>Navigate to any page that displays Zoom features</li>
                    <li>Your meetings automatically sync and display</li>
                    <li>No additional setup required after initial connection</li>
                    <li>All data updates automatically every few minutes</li>
                </ul>

                <h5 class="mt-4 mb-3">Data Accessed</h5>
                <p>When connected, our app accesses the following from your Zoom account:</p>
                <ul>
                    <li>Your user profile information (name, email, user ID)</li>
                    <li>List of your scheduled meetings</li>
                    <li>Meeting details (titles, times, participant count)</li>
                    <li>Meeting access links</li>
                    <li>Recording status and access information</li>
                </ul>

                <div class="mt-4 alert alert-info" role="alert">
                    <strong>Note:</strong> We only access information necessary to display your meetings. We never modify your Zoom settings or schedule meetings on your behalf.
                </div>
            </section>

            <!-- Section 3: Managing -->
            <section id="managing" class="mb-5">
                <h2 class="mb-4">Managing Your Zoom Account</h2>
                
                <p>After connecting your Zoom account, you have several management options.</p>

                <h5 class="mt-4 mb-3">View Connection Status</h5>
                <ul>
                    <li>Go to your Profile or Account Settings</li>
                    <li>Navigate to "Connected Apps" or "Integrations"</li>
                    <li>Look for the Zoom section</li>
                    <li>You will see your connection status and Zoom account name</li>
                    <li>Connection date and time are displayed</li>
                </ul>

                <h5 class="mt-4 mb-3">Update Connection</h5>
                <ul>
                    <li>If you change your Zoom password, no action needed on our platform</li>
                    <li>If you change your Zoom account, disconnect and reconnect</li>
                    <li>If connection becomes inactive, reconnect using the Connect button</li>
                    <li>Automatic token refresh occurs periodically</li>
                </ul>

                <h5 class="mt-4 mb-3">Verify Permissions</h5>
                <ul>
                    <li>Review what data our app can access in your Zoom account</li>
                    <li>Permissions include: read access to meetings and user profile</li>
                    <li>We do not have permission to modify your Zoom settings</li>
                    <li>You can revoke permissions at any time from your Zoom app authorizations</li>
                </ul>

                <h5 class="mt-4 mb-3">Check Active Sessions</h5>
                <ul>
                    <li>Your Zoom account remains active and usable</li>
                    <li>Connection does not affect your direct Zoom usage</li>
                    <li>You can use Zoom independently as usual</li>
                    <li>Our platform integration runs alongside your normal Zoom activity</li>
                </ul>

                <div class="mt-4 alert alert-warning" role="alert">
                    <strong>Important:</strong> If you suspect unauthorized access, change your Zoom password immediately and revoke all connected app permissions from your Zoom security settings.
                </div>
            </section>

            <!-- Section 4: Removing Zoom -->
            <section id="removing" class="mb-5">
                <h2 class="mb-4">Removing Zoom Integration</h2>
                
                <p>You can disconnect your Zoom account at any time. This process is simple and reversible.</p>

                <h5 class="mt-4 mb-3">When to Disconnect</h5>
                <ul>
                    <li>You no longer need Zoom integration features</li>
                    <li>You want to use a different Zoom account</li>
                    <li>You are concerned about privacy or permissions</li>
                    <li>You are switching to a different platform</li>
                    <li>Your account security may be compromised</li>
                </ul>

                <h5 class="mt-4 mb-3">Disconnection Steps</h5>

                <div class="mb-4">
                    <h6>Method 1: From Our Platform</h6>
                    <ul>
                        <li>Log in to your account</li>
                        <li>Navigate to Profile or Account Settings</li>
                        <li>Go to "Connected Apps" or "Integrations" section</li>
                        <li>Find the Zoom integration entry</li>
                        <li>Click the "Disconnect" button</li>
                        <li>Confirm the disconnection when prompted</li>
                        <li>You should see "Zoom Account Disconnected" message</li>
                    </ul>
                </div>

                <div class="mb-4">
                    <h6>Method 2: From Zoom App Settings (Additional Security)</h6>
                    <ul>
                        <li>Log in to your Zoom account</li>
                        <li>Go to Account Management</li>
                        <li>Navigate to "App Marketplace"</li>
                        <li>Find our app in "Installed Apps"</li>
                        <li>Click on the app name</li>
                        <li>Click "Uninstall" or "Revoke Access"</li>
                        <li>Confirm uninstallation</li>
                    </ul>
                </div>

                <h5 class="mt-4 mb-3">After Disconnection</h5>
                <ul>
                    <li>Zoom meetings will no longer appear on our platform</li>
                    <li>All stored connection tokens are deleted</li>
                    <li>Cached meeting data is cleared</li>
                    <li>No Zoom features will be available</li>
                    <li>You can reconnect anytime by following the "Adding Zoom" section</li>
                    <li>Previous connection history may be retained in logs (for security)</li>
                </ul>

                <h5 class="mt-4 mb-3">Data Deletion</h5>
                <ul>
                    <li>Your Zoom access token is immediately deleted</li>
                    <li>Your Zoom refresh token is immediately deleted</li>
                    <li>Cached meeting list is cleared</li>
                    <li>Connection timestamp is retained for audit purposes only</li>
                    <li>Your Zoom account itself is not affected</li>
                    <li>Your Zoom meetings and recordings remain intact</li>
                </ul>

                <div class="mt-4 alert alert-success" role="alert">
                    <strong>Good to know:</strong> Disconnecting is instant and does not affect your Zoom account or our platform account in any other way.
                </div>
            </section>

            <!-- Section 5: Troubleshooting -->
            <section id="troubleshooting" class="mb-5">
                <h2 class="mb-4">Troubleshooting</h2>
                
                <p>Common issues and their solutions.</p>

                <div class="mb-4">
                    <h6>Connection Button Not Showing</h6>
                    <ul>
                        <li>Verify you are logged in to your account</li>
                        <li>Clear your browser cache and refresh the page</li>
                        <li>Check if your browser supports the required features</li>
                        <li>Try a different browser if available</li>
                        <li>Contact support if the button is still not visible</li>
                    </ul>
                </div>

                <div class="mb-4">
                    <h6>Authentication Failed</h6>
                    <ul>
                        <li>Verify your Zoom email address and password</li>
                        <li>Check if two-factor authentication is enabled on your Zoom account</li>
                        <li>Ensure Zoom app authorizations haven't been revoked</li>
                        <li>Try disconnecting and reconnecting</li>
                        <li>Wait a few minutes and try again if Zoom servers are busy</li>
                    </ul>
                </div>

                <div class="mb-4">
                    <h6>Meetings Not Displaying</h6>
                    <ul>
                        <li>Verify connection is active (check Connected Apps section)</li>
                        <li>Ensure you have scheduled meetings in your Zoom calendar</li>
                        <li>Refresh the page to sync latest data</li>
                        <li>Try disconnecting and reconnecting</li>
                        <li>Check that past meetings are not being displayed (only upcoming meetings show)</li>
                    </ul>
                </div>

                <div class="mb-4">
                    <h6>Meetings Not Updating</h6>
                    <ul>
                        <li>Our platform updates meetings automatically every few minutes</li>
                        <li>Refresh the page to manually trigger an update</li>
                        <li>If just scheduled a meeting, wait a few minutes for sync</li>
                        <li>Check if meeting is in the correct Zoom calendar/account</li>
                        <li>Disconnect and reconnect if updates stop working</li>
                    </ul>
                </div>

                <div class="mb-4">
                    <h6>Permission Errors</h6>
                    <ul>
                        <li>Go to your Zoom account settings</li>
                        <li>Check if our app permissions were revoked</li>
                        <li>Re-authorize the app from our platform by reconnecting</li>
                        <li>Verify your Zoom account has admin access to meeting data</li>
                        <li>Contact your Zoom admin if you have limited permissions</li>
                    </ul>
                </div>

                <div class="mb-4">
                    <h6>Still Having Issues?</h6>
                    <ul>
                        <li>Check your internet connection</li>
                        <li>Verify both our platform and Zoom services are operational</li>
                        <li>Try using a different browser</li>
                        <li>Clear browser cookies for our domain</li>
                        <li>Wait 24 hours in case of temporary system issues</li>
                        <li>Contact our support team with details of the problem</li>
                    </ul>
                </div>

                <div class="mt-4 alert alert-info" role="alert">
                    <strong>Tip:</strong> When reporting issues, include your browser type, operating system, and the exact error message you received.
                </div>
            </section>

            <!-- Section 6: Security -->
            <section id="security" class="mb-5">
                <h2 class="mb-4">Security and Privacy</h2>
                
                <p>Your security and privacy are our top priorities.</p>

                <h5 class="mt-4 mb-3">How We Protect Your Data</h5>
                <ul>
                    <li>Access tokens are encrypted using industry-standard AES-256-CBC encryption</li>
                    <li>Tokens are stored securely in our database</li>
                    <li>We use HTTPS to encrypt all data in transit</li>
                    <li>Your Zoom password is never stored or transmitted</li>
                    <li>OAuth 2.0 protocol ensures secure authentication</li>
                    <li>Tokens automatically refresh to maintain security</li>
                </ul>

                <h5 class="mt-4 mb-3">What Information We Access</h5>
                <ul>
                    <li>Your Zoom user profile (name, email, user ID)</li>
                    <li>Scheduled meeting information</li>
                    <li>Meeting links and join URLs</li>
                    <li>Recording availability status</li>
                    <li>Participant count and meeting times</li>
                </ul>

                <h5 class="mt-4 mb-3">What We Do NOT Access</h5>
                <ul>
                    <li>Your Zoom password</li>
                    <li>Your Zoom account settings</li>
                    <li>Recording content or files</li>
                    <li>Chat messages or transcripts</li>
                    <li>Participant personal information</li>
                    <li>Zoom account billing information</li>
                </ul>

                <h5 class="mt-4 mb-3">Your Control and Rights</h5>
                <ul>
                    <li>You can disconnect at any time</li>
                    <li>You control which permissions you grant</li>
                    <li>You can revoke access from Zoom settings</li>
                    <li>You can request data deletion</li>
                    <li>You can export your data anytime</li>
                    <li>We do not share your data with third parties</li>
                </ul>

                <h5 class="mt-4 mb-3">Data Retention</h5>
                <ul>
                    <li>Connection tokens are deleted immediately upon disconnection</li>
                    <li>Cached meeting data is cleared when you disconnect</li>
                    <li>Connection logs may be retained for audit purposes</li>
                    <li>Deletion requests are processed within 30 days</li>
                    <li>Backup copies follow our standard retention policy</li>
                </ul>

                <h5 class="mt-4 mb-3">Security Best Practices</h5>
                <ul>
                    <li>Keep your Zoom password strong and unique</li>
                    <li>Enable two-factor authentication on your Zoom account</li>
                    <li>Regularly review connected apps in your Zoom settings</li>
                    <li>Disconnect if you no longer use the integration</li>
                    <li>Report suspicious activity immediately</li>
                    <li>Never share your Zoom credentials with anyone</li>
                </ul>

                <div class="mt-4 alert alert-warning" role="alert">
                    <strong>Security Alert:</strong> If you believe your account has been compromised, change your Zoom password immediately and revoke all app permissions from your Zoom account settings.
                </div>
            </section>

            <!-- Footer -->
            <div class="mt-5 pt-4 border-top">
                <p class="text-muted">Last updated: April 27, 2026</p>
                <p class="text-muted">For additional support, contact our help desk or visit our main support page.</p>
            </div>

        </div>
    </div>
</div>

<style>
    h2 {
        font-size: 1.75rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 1.5rem;
    }
    
    h5 {
        font-size: 1.1rem;
        font-weight: 600;
        color: #333;
    }
    
    h6 {
        font-size: 1rem;
        font-weight: 600;
        color: #444;
    }
    
    ul {
        line-height: 1.8;
    }
    
    li {
        margin-bottom: 0.5rem;
    }
    
    .alert {
        border-left: 4px solid;
        border-radius: 4px;
    }
    
    .alert-info {
        background-color: #e7f3ff;
        border-left-color: #0066cc;
        color: #003366;
    }
    
    .alert-warning {
        background-color: #fff3cd;
        border-left-color: #ffc107;
        color: #664d00;
    }
    
    .alert-success {
        background-color: #d4edda;
        border-left-color: #28a745;
        color: #155724;
    }
    
    section {
        scroll-margin-top: 20px;
    }
</style>

<?php get_footer_based_on_login(); ?>
