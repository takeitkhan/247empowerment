/**
 * Displays a gamification points notification modal.
 *
 * @param {object} data The data for the modal.
 * @param {string} [data.title='Points Earned!'] The title to display in the modal.
 * @param {string} data.message The descriptive message.
 * @param {number} data.points The number of points earned.
 * @param {string} [data.buttonText='Awesome!'] The text for the close button.
 */
function showGamificationPointsModal(data) {
    // Default values
    const defaults = {
        title: 'Points Earned!',
        message: 'You just earned',
        points: 0,
        buttonText: 'Awesome!',
    };
    // Merge user data with defaults
    const config = { ...defaults, ...data };

    const modalElement = document.getElementById('gamificationPointsModal');
    if (!modalElement) {
        console.error('Gamification modal element not found!');
        return;
    }

    // Get the Bootstrap 5 modal instance
    const gamificationModal = bootstrap.Modal.getOrCreateInstance(modalElement);

    // Update modal content
    const modalTitle = document.getElementById('gamification-modal-title');
    const modalMessage = document.getElementById('gamification-modal-message');

    if (modalTitle) {
        modalTitle.textContent = config.title;
    }
    if (modalMessage) {
        // The message already contains the points from the server-side processing.
        modalMessage.innerHTML = config.message;
    }

    // Show the modal (sound plays when notification arrives, not when modal appears)
    gamificationModal.show();
}

// On page load, check if the server sent any notification data
document.addEventListener('DOMContentLoaded', function() {
    if (typeof gamificationNotification !== 'undefined') {
        showGamificationPointsModal(gamificationNotification);
    }
});
