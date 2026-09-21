document.addEventListener("DOMContentLoaded", function () {

    
    const currentUrl = window.location.href;
    if (currentUrl.includes('/signin/') || currentUrl.includes('/signup/') || currentUrl.includes('/guide/')) {
        return; 
    }

    const popup = document.getElementById("custom-popup");
    if (!popup) return; 

    const popupContent = document.querySelector(".popup-content");
    const buttons = document.querySelector(".popup-button");
    const closeBtn = document.querySelector(".popup-close");
    const listItems = document.querySelectorAll(".popup-list li");

   
    setTimeout(() => {
        popup.classList.add("show");
        setTimeout(startTypingSequence, 600);
    }, 500);

    function startTypingSequence() {
        new Typed("#typing-heading", {
            strings: ["Ask About Our:"],
            typeSpeed: 50,
            showCursor: false,
            onComplete: function () {
                typeListItems(0);
            }
        });
    }

    function typeListItems(index) {
        if (index >= listItems.length) {
            buttons.classList.add("show");
            return;
        }
        const item = listItems[index];
        const text = item.getAttribute("data-text");
        new Typed(item, {
            strings: [text],
            typeSpeed: 40,
            showCursor: false,
            onComplete: function () {
                typeListItems(index + 1);
            }
        });
    }

    closeBtn.addEventListener("click", function () {
        popup.classList.remove("show");
        setTimeout(() => { popup.style.display = "none"; }, 600);
    });
});