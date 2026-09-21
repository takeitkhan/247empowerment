// Dummy function for Google Maps callback (suppress error)
window.initProfileMap = function() {};

const formId = "frontend-profile-form";
let form = null;

// Function to initialize when elements are ready
function initializeAutoSave() {
    form = document.getElementById(formId);
    
    if (!form) {
        setTimeout(initializeAutoSave, 500);
        return;
    }
    
    setupAutoSave();
}

function setupAutoSave() {
    // Direct AJAX save to database
    function saveToDatabase(fieldName, fieldValue) {
        console.log("💾 Saving:", fieldName);
        
        // Get nonce from form
        const nonceElement = document.querySelector('[name="frontend_profile_update_nonce"]');
        if (!nonceElement) {
            console.error("❌ Nonce element not found");
            return;
        }
        const nonce = nonceElement.value;
        
        const data = new FormData();
        data.append('action', 'save_profile_field');
        data.append('field_name', fieldName);
        data.append('field_value', fieldValue);
        data.append('nonce', nonce);
        
        const url = typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php';
        console.log("📡 AJAX URL:", url);
        console.log("📋 Data:", { field_name: fieldName, field_value: fieldValue, nonce: nonce });
        
        fetch(url, {
            method: 'POST',
            body: data
        })
        .then(response => {
            console.log("📥 Response status:", response.status);
            return response.json();
        })
        .then(result => {
            console.log("📥 Response data:", result);
            if (result.success) {
                console.log("✅ Saved:", fieldName);
                showAlert();
            } else {
                console.error("❌ Error:", result.data);
            }
        })
        .catch(error => console.error("❌ Fetch Error:", error));
    }
    
    function showAlert() {
        toastr.success('Saved', '', {
            positionClass: 'toast-top-left',
            timeOut: 1500,
            showMethod: 'slideDown',
            hideMethod: 'slideUp'
        });
    }
    
    // Attach blur listeners to all input fields
    const allInputs = form.querySelectorAll("input[type='text'], input[type='email'], input[type='date'], textarea");
    
    allInputs.forEach((field) => {
        field.addEventListener("blur", function(e) {
            if (this.value.length > 0) {
                saveToDatabase(this.name, this.value);
            }
        });
    });
    
    // Checkbox changes
    form.addEventListener("change", function(e) {
        if (e.target.type === "checkbox") {
            saveToDatabase(e.target.name, e.target.checked ? "1" : "0");
        }
    });
    
    console.log("✅ Auto-Save Ready");
}

// Start initialization
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initializeAutoSave);
} else {
    initializeAutoSave();
}
