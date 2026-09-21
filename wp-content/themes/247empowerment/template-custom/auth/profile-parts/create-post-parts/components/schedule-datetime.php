<?php
/**
 * Schedule Date & Time Component
 * For the Schedule Post tab only
 */
?>

<div class="xposting-schedule-section">
    <div class="schedule-inputs mt-3">
        <!-- Date Input -->
        <div class="mb-3">
            <div class="row">
                <div class="col-md-6">
                    <label for="schedule-date" class="form-label fw-bold">Date</label>
                    <input type="date" id="schedule-date" class="form-control schedule-date" min="">
                </div>
                <div class="col-md-6">
                    <label for="schedule-time" class="form-label fw-bold">Time</label>
                    <input type="time" id="schedule-time" class="form-control schedule-time">
                </div>
            </div>
            
        </div>

        <!-- Timezone Selector 
        <div class="mb-3">
            <label for="schedule-timezone" class="form-label fw-bold">Timezone</label>
            <select id="schedule-timezone" class="form-select schedule-timezone">
                <option value="UTC">UTC (Coordinated Universal Time)</option>
                <optgroup label="Americas">
                    <option value="America/New_York">Eastern Time (EST/EDT)</option>
                    <option value="America/Chicago">Central Time (CST/CDT)</option>
                    <option value="America/Denver">Mountain Time (MST/MDT)</option>
                    <option value="America/Los_Angeles">Pacific Time (PST/PDT)</option>
                </optgroup>
                <optgroup label="Europe">
                    <option value="Europe/London">London (GMT/BST)</option>
                    <option value="Europe/Paris">Paris (CET/CEST)</option>
                </optgroup>
                <optgroup label="Asia">
                    <option value="Asia/Tokyo">Tokyo (JST)</option>
                    <option value="Asia/Singapore">Singapore (SGT)</option>
                </optgroup>
                <optgroup label="Pacific">
                    <option value="Australia/Sydney">Sydney (AEDT/AEST)</option>
                </optgroup>
            </select>
        </div> -->

        <!-- Scheduled Time Preview Info Box -->
        <div class="schedule-preview-box alert alert-info">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-info-circle"></i>
                <div>
                    <small>Scheduled for: <strong id="schedule-preview-time">Select a date and time</strong></small>
                </div>
            </div>
        </div>
    </div>
</div>
