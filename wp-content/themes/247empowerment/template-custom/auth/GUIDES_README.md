# 📚 User Guides Page - Implementation Guide

## Overview

A dedicated **Guides** page has been created with tab-based navigation for user education. This replaces the previous "User Guide" tab in the Collaboration Hub with individual dedicated pages for each guide.

## Page Structure

### Main Template
**Location:** `wp-content/themes/247empowerment/template-custom/auth/guides.php`

**Template Name:** `Guides` (Page Template)

**Features:**
- Tab-based navigation in left sidebar
- Dynamic guide loading based on `?guide=` query parameter
- Responsive design for desktop and mobile
- Built-in styles for consistent formatting
- Access control: logged-in users only

### Guide Content Files
**Location:** `wp-content/themes/247empowerment/template-custom/auth/guides-parts/`

Individual guide files:
1. `guide-create-meeting.php` - How to Create a Meeting
2. `guide-join-meeting.php` - How to Join a Meeting
3. `guide-stream-youtube.php` - How to Stream to YouTube
4. `guide-share-screen.php` - Screen Sharing & Collaboration
5. `guide-manage-attendees.php` - Managing Attendees & Participants
6. `guide-troubleshooting.php` - Troubleshooting & Support

## How to Use

### For Users

1. **Access the Guides Page:**
   - Login to the platform
   - Click "📚 User Guides" in the Collaboration Hub sidebar
   - Or navigate directly to `/guides/` page

2. **Navigate Between Guides:**
   - Click any guide in the left sidebar
   - Or click related guide links shown at the bottom

3. **Guide Features:**
   - Step-by-step instructions with numbered steps
   - Info boxes with tips and warnings
   - Code examples and technical details
   - Images and embeds support
   - Responsive design works on all devices

### For Developers/Admins

#### Adding a New Guide

1. **Create guide content file:**
   ```bash
   wp-content/themes/247empowerment/template-custom/auth/guides-parts/guide-{slug}.php
   ```

2. **Update guides.php array:**
   ```php
   $guides = [
       'create-meeting' => [...],
       'your-new-guide' => [
           'title' => '🎯 Your Guide Title',
           'icon' => '📍',
           'description' => 'Brief description of the guide'
       ],
   ];
   ```

3. **Create content file** with your guide HTML/PHP

4. **Save and test** - Navigate to `/guides/?guide=your-new-guide`

#### Guide Content Format

Each guide file can use these CSS classes:

```php
<!-- Step with Number -->
<div class="guides-step">
    <div class="guides-step-number">1</div>
    <div class="guides-step-content">
        <h4>Step Title</h4>
        <p>Step description</p>
    </div>
</div>

<!-- Info Box (Blue) -->
<div class="guides-info-box">
    <strong>💡 Tip:</strong> Your content here
</div>

<!-- Warning Box (Yellow) -->
<div class="guides-warning-box">
    <strong>⚠️ Note:</strong> Your content here
</div>

<!-- Success Box (Green) -->
<div class="guides-success-box">
    <strong>✅ Great!</strong> Your content here
</div>

<!-- Images -->
<img src="path/to/image.png" alt="Description">

<!-- Code Block -->
<pre><code>
Your code here
</code></pre>

<!-- Inline Code -->
<code>inline code here</code>

<!-- Lists -->
<ul>
    <li>Item 1</li>
    <li>Item 2</li>
</ul>

<ol>
    <li>Numbered item 1</li>
    <li>Numbered item 2</li>
</ol>
```

## Styling & Customization

### Available CSS Classes

- `.guides-page-wrapper` - Main container
- `.guides-sidebar` - Left sidebar
- `.guides-nav-item` - Navigation links
- `.guides-content` - Main content area
- `.guides-header` - Page header section
- `.guides-body` - Body content
- `.guides-step` - Step container with number
- `.guides-info-box` - Info/tip box
- `.guides-warning-box` - Warning box
- `.guides-success-box` - Success box
- `.guides-related` - Related guides section
- `.guides-support-cta` - Support call-to-action

### Responsive Breakpoints

- Desktop: 2-column layout (sidebar + content)
- Mobile (≤768px): 1-column stacked layout

### Color Scheme

- **Primary:** Purple gradient (#667eea → #764ba2)
- **Background:** Light blue-gray (#f8fafc)
- **Text:** Slate gray (#334155)
- **Accents:** Info (blue), Warning (orange), Success (green)

## Integration with Collaboration Hub

### Updated Sidebar
The Collaboration Hub sidebar now includes a link to "📚 User Guides" that navigates directly to the Guides page.

**File:** `collaboration-parts/sidebar.php`

**Updated:** Removed "User Guide" tab and added direct link to guides page

### Removed Content
- Removed `'user-guide'` tab from Collaboration Hub tabs array
- Removed `case 'user-guide':` from content-area.php switch statement
- Kept `tab-guide.php` for potential future use (not currently loaded)

## Features Implemented

✅ **Tab-based guide selection** - Left sidebar with clickable guide links
✅ **Dynamic content loading** - Content loaded via query parameter
✅ **Related guides** - Shows 3 related guides at bottom
✅ **Support CTA** - Call-to-action for contacting support
✅ **Access control** - Logged-in users only (redirect to signin)
✅ **Responsive design** - Works on desktop, tablet, mobile
✅ **Rich content support** - Steps, boxes, images, code, lists
✅ **Professional styling** - Modern, clean, easy-to-read layout

## Security Features

1. **Login Required** - Non-logged-in users redirected to `/signin`
2. **Query Parameter Sanitization** - Guide parameter sanitized via `sanitize_text_field()`
3. **File Existence Check** - Only loads guide files that exist
4. **Fallback Content** - Shows placeholder if guide file missing

## Performance Considerations

- Single template file for all guides
- Lightweight CSS included inline in template
- Files organized in `/guides-parts/` subdirectory
- Clean, efficient query parameter handling
- No additional database queries needed

## Directory Structure

```
247empowerment/theme/
└── template-custom/
    └── auth/
        ├── guides.php (main template)
        └── guides-parts/
            ├── guide-create-meeting.php
            ├── guide-join-meeting.php
            ├── guide-stream-youtube.php
            ├── guide-share-screen.php
            ├── guide-manage-attendees.php
            └── guide-troubleshooting.php
```

## Next Steps

1. **Create WordPress Page:**
   - Title: "Guides"
   - Slug: "guides"
   - Template: "Guides"
   - Status: Published

2. **Test All Guides:**
   - Verify each guide loads correctly
   - Test navigation between guides
   - Test on mobile devices
   - Verify access control

3. **Update Documentation:**
   - Update help/support docs
   - Add breadcrumb navigation if needed
   - Create quick-start guide

4. **Optional Enhancements:**
   - Add search functionality
   - Add guides to footer navigation
   - Create PDF export feature
   - Add video embeds
   - Implement guide ratings/feedback

## Support & Troubleshooting

### Guide Not Displaying
1. Verify file exists in `/guides-parts/`
2. Check file name matches guide slug
3. Verify `guides.php` array includes the guide
4. Check for PHP syntax errors

### Styling Issues
1. Clear browser cache
2. Check CSS class names match documentation
3. Verify responsive breakpoints working
4. Check for conflicting theme CSS

### Navigation Not Working
1. Verify WordPress page template is selected
2. Check URL structure: `/guides/?guide=slug`
3. Verify user is logged in
4. Check browser console for JavaScript errors

---

**Created:** 2026-08-29
**Version:** 1.0
**Status:** Production Ready
