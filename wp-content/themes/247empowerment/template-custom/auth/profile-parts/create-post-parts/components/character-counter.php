<?php
/**
 * Character Counter Component
 * Variables (optional, defaults provided):
 * - $counter_id: ID for counter display (default: 'char-count')
 * - $limit_id: ID for limit display (default: 'char-limit')
 * - $bar_id: ID for progress bar (default: 'char-progress-bar')
 */

$counter_id = isset($counter_id) ? $counter_id : 'char-count';
$limit_id = isset($limit_id) ? $limit_id : 'char-limit';
$bar_id = isset($bar_id) ? $bar_id : 'char-progress-bar';
?>

<div class="character-counter-container mt-2">
    <div class="character-counter">
        <span id="<?php echo esc_attr($counter_id); ?>">0</span>/<span id="<?php echo esc_attr($limit_id); ?>">2000</span>
    </div>
    <div class="character-progress">
        <div class="progress">
            <div id="<?php echo esc_attr($bar_id); ?>" class="progress-bar" role="progressbar" style="width: 0%"></div>
        </div>
    </div>
</div>
