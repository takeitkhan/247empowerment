<?php
/**
 * Text Formatting Toolbar Component
 */
?>

<div class="posting-toolbar mt-3">
    <div class="d-flex gap-2 flex-wrap">
        <button type="button" class="btn btn-sm btn-outline-secondary formatting-btn" data-format="bold" title="Bold (Ctrl+B)">
            <i class="bi bi-type-bold"></i>
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary formatting-btn" data-format="italic" title="Italic (Ctrl+I)">
            <i class="bi bi-type-italic"></i>
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary formatting-btn" data-format="underline" title="Underline (Ctrl+U)">
            <i class="bi bi-type-underline"></i>
        </button>
        <div class="vr"></div>
        <button type="button" class="btn btn-sm btn-outline-secondary formatting-btn" data-format="ul" title="Bullet List">
            <i class="bi bi-list-ul"></i>
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary formatting-btn" data-format="ol" title="Numbered List">
            <i class="bi bi-list-ol"></i>
        </button>
        <div class="vr"></div>
        <button type="button" class="btn btn-sm btn-outline-secondary formatting-btn emoji-btn" title="Add Emoji">
            <i class="bi bi-emoji-smile"></i>
        </button>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get the active textarea
    const getActiveTextarea = () => {
        return document.getElementById('post-content-instant') || 
               document.getElementById('post-content-schedule') || 
               document.getElementById('post-content');
    };

    // Format text helper
    const formatText = (textarea, format) => {
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const selectedText = textarea.value.substring(start, end);
        const beforeText = textarea.value.substring(0, start);
        const afterText = textarea.value.substring(end);
        
        let formattedText = selectedText;
        
        switch(format) {
            case 'bold':
                formattedText = `**${selectedText || 'bold text'}**`;
                break;
            case 'italic':
                formattedText = `*${selectedText || 'italic text'}*`;
                break;
            case 'underline':
                formattedText = `<u>${selectedText || 'underlined text'}</u>`;
                break;
            case 'ul':
                const ulLines = selectedText ? selectedText.split('\n') : ['Item 1', 'Item 2'];
                formattedText = ulLines.map(line => `• ${line || 'List item'}`).join('\n');
                break;
            case 'ol':
                const olLines = selectedText ? selectedText.split('\n') : ['Item 1', 'Item 2'];
                formattedText = olLines.map((line, i) => `${i + 1}. ${line || 'List item'}`).join('\n');
                break;
        }
        
        textarea.value = beforeText + formattedText + afterText;
        textarea.selectionStart = start + formattedText.length;
        textarea.selectionEnd = start + formattedText.length;
        textarea.focus();
        
        // Trigger character counter update if exists
        const event = new Event('input', { bubbles: true });
        textarea.dispatchEvent(event);
    };

    // Formatting button click handler
    document.querySelectorAll('.formatting-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const textarea = getActiveTextarea();
            if (!textarea) {
                console.warn('No textarea found');
                return;
            }
            
            const format = this.dataset.format;
            
            if (format === 'emoji') {
                // Toggle emoji picker
                const emojiContainer = document.getElementById('emoji-picker-container') || 
                                     document.getElementById('emoji-picker-container-schedule');
                if (emojiContainer) {
                    emojiContainer.style.display = emojiContainer.style.display === 'none' ? 'block' : 'none';
                }
            } else {
                formatText(textarea, format);
            }
            
            this.blur();
        });
    });

    // Emoji picker integration (if exists)
    const emojiPickerButtons = document.querySelectorAll('.emoji-btn');
    emojiPickerButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const textarea = getActiveTextarea();
            if (!textarea) return;
            
            // Find the emoji picker container
            let emojiContainer = this.closest('.posting-editor')?.querySelector('.emoji-picker-wrapper');
            if (emojiContainer) {
                emojiContainer.style.display = emojiContainer.style.display === 'none' ? 'block' : 'none';
            }
        });
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        const textarea = getActiveTextarea();
        if (!textarea || textarea !== document.activeElement) return;
        
        if (e.ctrlKey || e.metaKey) {
            switch(e.key.toLowerCase()) {
                case 'b':
                    e.preventDefault();
                    formatText(textarea, 'bold');
                    break;
                case 'i':
                    e.preventDefault();
                    formatText(textarea, 'italic');
                    break;
                case 'u':
                    e.preventDefault();
                    formatText(textarea, 'underline');
                    break;
            }
        }
    });
});
</script>