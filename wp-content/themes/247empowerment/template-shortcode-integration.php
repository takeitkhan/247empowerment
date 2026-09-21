<?php
/**
 * Template Name: Shortcode Integration Documentation
 * Description: Display all available shortcodes with usage and documentation
 * 
 * This page shows a comprehensive list of all shortcodes available in the system.
 * Each shortcode includes usage examples, parameters, and descriptions.
 */

get_header_based_on_login();
?>

<div class="py-5 container-fluid">
    <div class="container">
        
        <!-- Page Header -->
        <div class="mb-5">
            <h1 class="mb-2 display-4 fw-bold">Shortcode Integration Documentation</h1>
            <p class="text-muted lead">Complete list of all available shortcodes and their usage</p>
            <hr class="my-4">
        </div>

        <!-- Table of Contents -->
        <div class="mb-5">
            <h3>📑 Quick Navigation</h3>
            <div class="row g-2">
                <div class="col-md-6 col-lg-4">
                    <a href="#mm_spg_interest_form" class="btn-outline-primary w-100 text-start btn">
                        <strong>Interest Form</strong><br>
                        <small class="text-muted">User interest selection & prioritization</small>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4">
                    <a href="#mm_spg_social_links_form" class="btn-outline-primary w-100 text-start btn">
                        <strong>Social Links Form</strong><br>
                        <small class="text-muted">Social media profile management</small>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4">
                    <a href="#mm_spg_additional_profile_details" class="btn-outline-primary w-100 text-start btn">
                        <strong>Profile Details</strong><br>
                        <small class="text-muted">Additional user profile information</small>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4">
                    <a href="#zoom_connect_button" class="btn-outline-info w-100 text-start btn">
                        <strong>Zoom Connect Button</strong><br>
                        <small class="text-muted">OAuth connection for Zoom integration</small>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4">
                    <a href="#zoom_callback_thankyou" class="btn-outline-success w-100 text-start btn">
                        <strong>Zoom Callback Message</strong><br>
                        <small class="text-muted">OAuth success confirmation display</small>
                    </a>
                </div>
            </div>
        </div>

        <!-- Shortcode Documentation -->
        <div class="row g-4">

            <!-- Shortcode 1: mm_spg_interest_form -->
            <div class="col-12">
                <div class="shadow-sm border-4 border-left border-primary card" id="mm_spg_interest_form">
                    <div class="bg-light card-header">
                        <h3 class="mb-0">
                            <code class="text-primary">[mm_spg_interest_form]</code>
                        </h3>
                    </div>
                    <div class="card-body">
                        
                        <div class="mb-4 row">
                            <div class="col-md-6">
                                <h5>📋 Description</h5>
                                <p>A dynamic form that allows users to select and prioritize their interests (1st priority, 2nd priority, etc.). 
                                Users can select multiple categories and assign priority levels (1-5 scale).</p>
                            </div>
                            <div class="col-md-6">
                                <h5>👤 User Role</h5>
                                <p><span class="bg-info badge">Login Required</span></p>
                                <p>Only logged-in users can access and use this form. Used during Phase 2 of the onboarding guide.</p>
                            </div>
                        </div>

                        <h5>🔧 Usage</h5>
                        <div class="bg-dark mb-3 p-3 rounded text-light" style="overflow-x: auto;">
                            <code>[mm_spg_interest_form]</code>
                        </div>

                        <h5>📊 Validation Rules</h5>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item"><strong>✓ At least one interest must be selected</strong></li>
                            <li class="list-group-item"><strong>✓ At least one interest must be set as 1st priority</strong></li>
                            <li class="list-group-item"><strong>✓ No duplicate priority values allowed</strong></li>
                            <li class="list-group-item"><strong>✓ All selected interests must have a priority assigned</strong></li>
                        </ul>

                        <h5 class="mt-4">💾 Data Saved</h5>
                        <table class="table table-hover table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Meta Key</th>
                                    <th>Value Type</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>user_categories</code></td>
                                    <td>Array (Integer IDs)</td>
                                    <td>Selected category term IDs</td>
                                </tr>
                                <tr>
                                    <td><code>user_categories_priority</code></td>
                                    <td>Array (1-5 values)</td>
                                    <td>Priority mapping: term_id => priority</td>
                                </tr>
                                <tr>
                                    <td><code>mm_spg_phase_2_completed</code></td>
                                    <td>Boolean (1)</td>
                                    <td>Marks Phase 2 as completed</td>
                                </tr>
                                <tr>
                                    <td><code>mm_spg_interest_completed</code></td>
                                    <td>Boolean (1)</td>
                                    <td>Interest selection completed flag</td>
                                </tr>
                            </tbody>
                        </table>

                        <h5 class="mt-4">⚙️ Parameters</h5>
                        <p><em>This shortcode does not accept any parameters.</em></p>

                        <h5 class="mt-4">🎯 Use Cases</h5>
                        <ul>
                            <li>Phase 2 of user onboarding guide</li>
                            <li>Interest-based content personalization</li>
                            <li>User preference settings</li>
                            <li>Category-based user segmentation</li>
                        </ul>

                        <h5 class="mt-4">📝 Example</h5>
                        <div class="alert alert-info">
                            <strong>Page Content:</strong><br>
                            <code>[mm_spg_interest_form]</code><br><br>
                            <strong>Result:</strong> A form with all available blog categories as checkboxes, allowing users to select and assign priorities.
                        </div>

                    </div>
                </div>
            </div>

            <!-- Shortcode 2: mm_spg_social_links_form -->
            <div class="col-12">
                <div class="shadow-sm border-4 border-left border-success card" id="mm_spg_social_links_form">
                    <div class="bg-light card-header">
                        <h3 class="mb-0">
                            <code class="text-success">[mm_spg_social_links_form]</code>
                        </h3>
                    </div>
                    <div class="card-body">
                        
                        <div class="mb-4 row">
                            <div class="col-md-6">
                                <h5>📋 Description</h5>
                                <p>A dynamic form for managing social media and business links. Users can add, edit, and remove social platform links including Facebook, Instagram, LinkedIn, Twitter/X, YouTube, and custom websites.</p>
                            </div>
                            <div class="col-md-6">
                                <h5>👤 User Role</h5>
                                <p><span class="bg-info badge">Login Required</span></p>
                                <p>Only logged-in users can manage their social links. Users cannot modify other users' links.</p>
                            </div>
                        </div>

                        <h5>🔧 Usage</h5>
                        <div class="bg-dark mb-3 p-3 rounded text-light" style="overflow-x: auto;">
                            <code>[mm_spg_social_links_form]</code>
                        </div>

                        <h5>📊 Supported Platforms</h5>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <span class="bg-secondary badge">Facebook</span>
                            </div>
                            <div class="col-md-4">
                                <span class="bg-secondary badge">Instagram</span>
                            </div>
                            <div class="col-md-4">
                                <span class="bg-secondary badge">LinkedIn</span>
                            </div>
                            <div class="col-md-4">
                                <span class="bg-secondary badge">Twitter / X</span>
                            </div>
                            <div class="col-md-4">
                                <span class="bg-secondary badge">YouTube</span>
                            </div>
                            <div class="col-md-4">
                                <span class="bg-secondary badge">Website</span>
                            </div>
                        </div>

                        <h5 class="mt-4">💾 Data Saved</h5>
                        <table class="table table-hover table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Meta Key</th>
                                    <th>Value Type</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>custom_social_links</code></td>
                                    <td>Array (Serialized)</td>
                                    <td>Array of link objects with: platform, label, url</td>
                                </tr>
                            </tbody>
                        </table>

                        <h5 class="mt-4">📝 Link Structure</h5>
                        <div class="bg-light p-3 rounded">
                            <pre><code class="language-php">[
    'platform' => 'facebook|instagram|linkedin|twitter|youtube|website',
    'label'    => 'Custom label (optional)',
    'url'      => 'https://example.com/profile'
]</code></pre>
                        </div>

                        <h5 class="mt-4">⚙️ Parameters</h5>
                        <p><em>This shortcode does not accept any parameters.</em></p>

                        <h5 class="mt-4">🎯 Use Cases</h5>
                        <ul>
                            <li>User profile page social links</li>
                            <li>Portfolio/bio page social connections</li>
                            <li>Digital business card creation</li>
                            <li>User onboarding social verification</li>
                        </ul>

                        <h5 class="mt-4">📝 Example</h5>
                        <div class="alert alert-info">
                            <strong>Page Content:</strong><br>
                            <code>[mm_spg_social_links_form]</code><br><br>
                            <strong>Result:</strong> A form with dynamic rows for adding social media links, with platform selection, custom labels, and URLs.
                        </div>

                    </div>
                </div>
            </div>

            <!-- Shortcode 3: mm_spg_additional_profile_details -->
            <div class="col-12">
                <div class="shadow-sm border-4 border-left border-warning card" id="mm_spg_additional_profile_details">
                    <div class="bg-light card-header">
                        <h3 class="mb-0">
                            <code class="text-warning">[mm_spg_additional_profile_details]</code>
                        </h3>
                    </div>
                    <div class="card-body">
                        
                        <div class="mb-4 row">
                            <div class="col-md-6">
                                <h5>📋 Description</h5>
                                <p>A comprehensive form for additional user profile information including professional title, about section, address, keywords, and hashtags. Supports character limits and input validation.</p>
                            </div>
                            <div class="col-md-6">
                                <h5>👤 User Role</h5>
                                <p><span class="bg-info badge">Login Required</span></p>
                                <p>Only logged-in users can update their profile details. This is typically used during Phase 3 of onboarding.</p>
                            </div>
                        </div>

                        <h5>🔧 Usage</h5>
                        <div class="bg-dark mb-3 p-3 rounded text-light" style="overflow-x: auto;">
                            <code>[mm_spg_additional_profile_details]</code>
                        </div>

                        <h5>📊 Form Fields</h5>
                        <table class="table table-hover table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Field Name</th>
                                    <th>Type</th>
                                    <th>Limits</th>
                                    <th>Required</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Title</strong></td>
                                    <td>Text Input</td>
                                    <td>Max 60 characters</td>
                                    <td>✓ Yes</td>
                                </tr>
                                <tr>
                                    <td><strong>About Me</strong></td>
                                    <td>Textarea</td>
                                    <td>Max 150 characters</td>
                                    <td>✓ Yes</td>
                                </tr>
                                <tr>
                                    <td><strong>Address</strong></td>
                                    <td>Text Input</td>
                                    <td>Max 120 characters</td>
                                    <td>Optional</td>
                                </tr>
                                <tr>
                                    <td><strong>Keywords</strong></td>
                                    <td>Tag Input</td>
                                    <td>Comma separated</td>
                                    <td>Optional</td>
                                </tr>
                                <tr>
                                    <td><strong>Hashtags</strong></td>
                                    <td>Tag Input</td>
                                    <td>Comma separated</td>
                                    <td>Optional</td>
                                </tr>
                            </tbody>
                        </table>

                        <h5 class="mt-4">📊 Validation Rules</h5>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item"><strong>✓ Title is required</strong> (cannot be empty)</li>
                            <li class="list-group-item"><strong>✓ About section is required</strong> (max 150 characters)</li>
                            <li class="list-group-item"><strong>✓ At least one of: Address, Keyword, or Hashtag must be provided</strong></li>
                            <li class="list-group-item"><strong>✓ Hashtags are auto-prefixed with # if missing</strong></li>
                        </ul>

                        <h5 class="mt-4">💾 Data Saved</h5>
                        <table class="table table-hover table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Meta Key</th>
                                    <th>Value Type</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>designation</code></td>
                                    <td>String</td>
                                    <td>Professional title (max 60 chars)</td>
                                </tr>
                                <tr>
                                    <td><code>digital_card_about</code></td>
                                    <td>String</td>
                                    <td>About section (max 150 chars)</td>
                                </tr>
                                <tr>
                                    <td><code>place_display_name</code></td>
                                    <td>String</td>
                                    <td>Address (max 120 chars)</td>
                                </tr>
                                <tr>
                                    <td><code>show_full_address</code></td>
                                    <td>Boolean</td>
                                    <td>Display full address on profile</td>
                                </tr>
                                <tr>
                                    <td><code>user_keywords</code></td>
                                    <td>String (CSV)</td>
                                    <td>Comma-separated keywords</td>
                                </tr>
                                <tr>
                                    <td><code>user_hashtags</code></td>
                                    <td>String (CSV)</td>
                                    <td>Comma-separated hashtags (auto-formatted)</td>
                                </tr>
                                <tr>
                                    <td><code>mm_spg_additional_profile_completed</code></td>
                                    <td>Boolean (1)</td>
                                    <td>Profile completion flag</td>
                                </tr>
                            </tbody>
                        </table>

                        <h5 class="mt-4">⚙️ Parameters</h5>
                        <p><em>This shortcode does not accept any parameters.</em></p>

                        <h5 class="mt-4">🎯 Use Cases</h5>
                        <ul>
                            <li>Phase 3 user onboarding</li>
                            <li>Profile completion forms</li>
                            <li>Professional profile setup</li>
                            <li>Digital business card creation</li>
                            <li>User metadata collection</li>
                        </ul>

                        <h5 class="mt-4">📝 Example</h5>
                        <div class="alert alert-info">
                            <strong>Page Content:</strong><br>
                            <code>[mm_spg_additional_profile_details]</code><br><br>
                            <strong>Result:</strong> A multi-field form collecting professional information, location, keywords, and hashtags for the user's profile.
                        </div>

                        <h5 class="mt-4">💡 Special Features</h5>
                        <ul>
                            <li>🏷️ <strong>Auto-formatting:</strong> Hashtags automatically prefixed with # if not provided</li>
                            <li>📊 <strong>Character Counter:</strong> Real-time character count for "About Me" field (150 char limit)</li>
                            <li>🔖 <strong>Tag Input:</strong> Keywords and hashtags support add/remove functionality</li>
                            <li>🎯 <strong>Validation:</strong> Client and server-side validation</li>
                        </ul>

                    </div>
                </div>
            </div>

            <!-- Shortcode 4: zoom_connect_button -->
            <div class="col-12">
                <div class="shadow-sm border-4 border-info border-left card" id="zoom_connect_button">
                    <div class="bg-light card-header">
                        <h3 class="mb-0">
                            <code class="text-info">[zoom_connect_button]</code>
                        </h3>
                    </div>
                    <div class="card-body">
                        
                        <div class="mb-4 row">
                            <div class="col-md-6">
                                <h5>📋 Description</h5>
                                <p>Displays a button for connecting or managing Zoom account integration. Shows connection status with the connected user name, or provides a connect button for new connections.</p>
                            </div>
                            <div class="col-md-6">
                                <h5>👤 User Role</h5>
                                <p><span class="bg-info badge">Login Required</span></p>
                                <p>Only logged-in users can connect their Zoom account. Displays login prompt for non-authenticated users.</p>
                            </div>
                        </div>

                        <h5>🔧 Usage</h5>
                        <div class="bg-dark mb-3 p-3 rounded text-light" style="overflow-x: auto;">
                            <code>[zoom_connect_button]</code>
                        </div>

                        <h5>📊 Display States</h5>
                        <table class="table table-hover table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Condition</th>
                                    <th>Display</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>User not logged in</td>
                                    <td>Login prompt message in red</td>
                                </tr>
                                <tr>
                                    <td>Logged in, not connected</td>
                                    <td>Blue "Connect Zoom Account" button with OAuth link</td>
                                </tr>
                                <tr>
                                    <td>Logged in, connected</td>
                                    <td>Green success message with user name + Disconnect button</td>
                                </tr>
                            </tbody>
                        </table>

                        <h5 class="mt-4">💾 Data Saved</h5>
                        <table class="table table-hover table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Meta Key</th>
                                    <th>Value Type</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>_zoom_access_token</code></td>
                                    <td>String</td>
                                    <td>OAuth access token from Zoom</td>
                                </tr>
                                <tr>
                                    <td><code>_zoom_refresh_token</code></td>
                                    <td>String</td>
                                    <td>OAuth refresh token for token renewal</td>
                                </tr>
                                <tr>
                                    <td><code>_zoom_user_id</code></td>
                                    <td>String</td>
                                    <td>Zoom user ID</td>
                                </tr>
                                <tr>
                                    <td><code>_zoom_user_name</code></td>
                                    <td>String</td>
                                    <td>User's display name on Zoom</td>
                                </tr>
                                <tr>
                                    <td><code>_zoom_scopes</code></td>
                                    <td>String</td>
                                    <td>Granted OAuth scopes</td>
                                </tr>
                                <tr>
                                    <td><code>_zoom_connected_at</code></td>
                                    <td>Timestamp</td>
                                    <td>Connection date/time</td>
                                </tr>
                            </tbody>
                        </table>

                        <h5 class="mt-4">⚙️ Parameters</h5>
                        <p><em>This shortcode does not accept any parameters.</em></p>

                        <h5 class="mt-4">🎯 Use Cases</h5>
                        <ul>
                            <li>Zoom integration settings page</li>
                            <li>User profile OAuth connections</li>
                            <li>Video call scheduler integration</li>
                            <li>Meeting management dashboard</li>
                        </ul>

                        <h5 class="mt-4">📝 Example</h5>
                        <div class="alert alert-info">
                            <strong>Page Content:</strong><br>
                            <code>[zoom_connect_button]</code><br><br>
                            <strong>Result:</strong> Shows either a login prompt, connect button with OAuth flow, or confirmation of connected Zoom account with disconnect option.
                        </div>

                        <h5 class="mt-4">🔌 OAuth Flow</h5>
                        <ol>
                            <li>User clicks "Connect Zoom Account" button</li>
                            <li>Redirected to Zoom OAuth authorization page</li>
                            <li>User grants permissions to the application</li>
                            <li>Zoom redirects back with authorization code</li>
                            <li>Application exchanges code for access token</li>
                            <li>User metadata stored in WordPress user meta</li>
                            <li>Confirmation message displayed</li>
                        </ol>

                    </div>
                </div>
            </div>

            <!-- Shortcode 5: zoom_callback_thankyou -->
            <div class="col-12">
                <div class="shadow-sm border-4 border-left border-success card" id="zoom_callback_thankyou">
                    <div class="bg-light card-header">
                        <h3 class="mb-0">
                            <code class="text-success">[zoom_callback_thankyou]</code>
                        </h3>
                    </div>
                    <div class="card-body">
                        
                        <div class="mb-4 row">
                            <div class="col-md-6">
                                <h5>📋 Description</h5>
                                <p>Success confirmation message displayed after successful Zoom OAuth callback. Shows connected user details and connection timestamp. Hidden for non-authenticated or non-connected users.</p>
                            </div>
                            <div class="col-md-6">
                                <h5>👤 User Role</h5>
                                <p><span class="bg-info badge">Login Required</span></p>
                                <p>Only appears for logged-in users who have successfully connected their Zoom account.</p>
                            </div>
                        </div>

                        <h5>🔧 Usage</h5>
                        <div class="bg-dark mb-3 p-3 rounded text-light" style="overflow-x: auto;">
                            <code>[zoom_callback_thankyou]</code>
                        </div>

                        <h5>📊 Display Conditions</h5>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">✓ Only shows if user is logged in</li>
                            <li class="list-group-item">✓ Only shows if Zoom account is connected</li>
                            <li class="list-group-item">✓ Hidden for non-connected users</li>
                            <li class="list-group-item">✓ Displays green success box styling</li>
                        </ul>

                        <h5 class="mt-4">🎨 Styling</h5>
                        <div class="alert alert-success">
                            <strong>Visual Style:</strong>
                            <ul class="mt-2">
                                <li>Background: Green (#d4edda)</li>
                                <li>Border: 1px solid #c3e6cb</li>
                                <li>Text Color: #155724 (dark green)</li>
                                <li>Padding: 15px</li>
                                <li>Border Radius: 4px</li>
                            </ul>
                        </div>

                        <h5>💾 Data Displayed</h5>
                        <table class="table table-hover table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Field</th>
                                    <th>Source</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Heading</strong></td>
                                    <td>Static</td>
                                    <td>"✅ Zoom Account Connected!"</td>
                                </tr>
                                <tr>
                                    <td><strong>Connected User</strong></td>
                                    <td><code>_zoom_user_name</code></td>
                                    <td>Display name from Zoom profile</td>
                                </tr>
                                <tr>
                                    <td><strong>Connection Time</strong></td>
                                    <td><code>_zoom_connected_at</code></td>
                                    <td>Date/time of connection</td>
                                </tr>
                                <tr>
                                    <td><strong>Description</strong></td>
                                    <td>Static</td>
                                    <td>"You can now view your Zoom meetings below."</td>
                                </tr>
                            </tbody>
                        </table>

                        <h5 class="mt-4">⚙️ Parameters</h5>
                        <p><em>This shortcode does not accept any parameters.</em></p>

                        <h5 class="mt-4">🎯 Typical Usage</h5>
                        <ul>
                            <li>OAuth callback success page (e.g., /zoom-callback-success/)</li>
                            <li>Post-connection confirmation display</li>
                            <li>User settings/profile page</li>
                            <li>Zoom integration dashboard</li>
                        </ul>

                        <h5 class="mt-4">📝 Example</h5>
                        <div class="alert alert-info">
                            <strong>Page: Zoom Callback Success Page</strong><br>
                            <code>[zoom_callback_thankyou]</code><br><br>
                            <strong>Result:</strong> Green success box showing:<br>
                            "✅ Zoom Account Connected!<br>
                            Connected as: John Smith<br>
                            Connected at: 2024-04-21 15:30:45<br>
                            You can now view your Zoom meetings below."
                        </div>

                        <h5 class="mt-4">🔄 Recommended Placement</h5>
                        <ol>
                            <li>Create a page titled "Zoom Connected" with URL: /zoom-callback-success/</li>
                            <li>Add shortcode: <code>[zoom_callback_thankyou]</code></li>
                            <li>This page will auto-display after OAuth redirect</li>
                            <li>Users see instant confirmation of successful connection</li>
                        </ol>

                    </div>
                </div>
            </div>

        </div>

        <!-- Additional Information -->
        <div class="mt-5 row g-4">
            <div class="col-12">
                <div class="bg-light border-0 card">
                    <div class="bg-dark text-white card-header">
                        <h5 class="mb-0">📚 Additional Information</h5>
                    </div>
                    <div class="card-body">
                        
                        <h6>🔐 Security Notes</h6>
                        <ul>
                            <li>All shortcodes require user login</li>
                            <li>Data is validated and sanitized server-side</li>
                            <li>AJAX requests include nonce verification</li>
                            <li>User can only modify their own data</li>
                        </ul>

                        <h6 class="mt-3">⚡ Performance</h6>
                        <ul>
                            <li>Shortcodes use efficient database queries</li>
                            <li>User meta data is cached by WordPress</li>
                            <li>Dynamic loading via AJAX for better UX</li>
                        </ul>

                        <h6 class="mt-3">🔗 Dependencies</h6>
                        <ul>
                            <li><strong>Plugin:</strong> mm-spg (Sweet Portal Guide)</li>
                            <li><strong>Framework:</strong> WordPress 6.x</li>
                            <li><strong>UI Framework:</strong> Bootstrap 5.3.3</li>
                            <li><strong>JavaScript:</strong> jQuery (legacy)</li>
                        </ul>

                        <h6 class="mt-3">📁 File Locations</h6>
                        <ul>
                            <li><code>/wp/frontend/shortcodes.php</code> - Frontend shortcode definitions</li>
                            <li><code>/wp/wp-content/plugins/mm-spg/inc/frontend/shortcodes.php</code> - Plugin shortcodes</li>
                        </ul>

                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
    .border-left {
        position: relative;
        padding-left: 0;
    }
    
    .border-left.border-4 {
        border-left: 4px solid !important;
        padding-left: 0 !important;
    }

    .card {
        transition: box-shadow 0.3s ease;
    }

    .card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }

    code {
        font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', 'Consolas', monospace;
        font-size: 0.9rem;
    }

    .bg-dark {
        background-color: #2d2d2d !important;
    }

    .alert-info {
        background-color: #e7f3ff;
        border-left: 4px solid #0066cc;
    }

    .table-hover tbody tr:hover {
        background-color: #f5f5f5;
    }

    @media (max-width: 768px) {
        .card-body {
            font-size: 0.95rem;
        }
    }
</style>

<?php get_footer_based_on_login(); ?>
