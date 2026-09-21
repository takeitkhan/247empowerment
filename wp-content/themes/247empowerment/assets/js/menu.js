document.addEventListener('DOMContentLoaded', function () {
    // ১. মোবাইল ড্রয়ার মেনু হ্যান্ডলার (আগের মতোই থাকবে)
    const menuToggle = document.getElementById('menuToggle');
    const closeDrawer = document.getElementById('closeDrawer');
    const mobileDrawer = document.getElementById('mobileMenuDrawer');
    const drawerOverlay = document.getElementById('drawerOverlay');

    if (menuToggle) menuToggle.addEventListener('click', () => {
        if (mobileDrawer) mobileDrawer.classList.add('open');
        if (drawerOverlay) drawerOverlay.classList.add('active');
        document.body.classList.add('drawer-open');
    });

    const closeMenu = () => {
        if (mobileDrawer) mobileDrawer.classList.remove('open');
        if (drawerOverlay) drawerOverlay.classList.remove('active');
        document.body.classList.remove('drawer-open');
    };

    if (closeDrawer) closeDrawer.addEventListener('click', closeMenu);
    if (drawerOverlay) drawerOverlay.addEventListener('click', closeMenu);


    // ২. বুটস্ট্র্যাপ-সেফ কন্সিস্টেন্ট অ্যাকরডিয়ন হ্যান্ডলার (ব্লিংকিং প্রবলেম ফিক্স)
    const accordionButtons = document.querySelectorAll('[data-bs-toggle="collapse"]');

    accordionButtons.forEach(button => {
        button.addEventListener('click', function (e) {
            // বুটস্ট্র্যাপের ডিফল্ট অটো-টগল ইভেন্টকে সম্পূর্ণ স্টপ করা
            e.preventDefault();
            e.stopPropagation();
            
            const targetSelector = this.getAttribute('data-bs-target');
            if (!targetSelector) return;
            
            const targetEl = document.querySelector(targetSelector);
            if (!targetEl) return;

            // বুটস্ট্র্যাপের গ্লোবাল অবজেক্ট চেক করা (বুটস্ট্র্যাপ ৫ বা ৪ এর জন্য)
            const bs = window.bootstrap || (window.jQuery && window.jQuery.fn.collapse ? 'jquery' : null);
            
            if (bs && bs !== 'jquery') {
                // বুটস্ট্র্যাপ ৫ নেটিভ মেথড ব্যবহার করে কন্ট্রোল
                let collapseInstance = bootstrap.Collapse.getInstance(targetEl);
                if (!collapseInstance) {
                    collapseInstance = new bootstrap.Collapse(targetEl, { toggle: false });
                }
                
                // একই প্যারেন্টের অন্য সব খোলা মেনু বন্ধ করা
                const parentAccordion = this.closest('.accordion');
                if (parentAccordion && !this.classList.contains('collapsed')) {
                    // যদি অলরেডি ওপেন থাকে এবং বন্ধ করতে ক্লিক করা হয়
                    collapseInstance.hide();
                } else {
                    // অন্য সব বন্ধ করে এটাকে ওপেন করা
                    if (parentAccordion) {
                        const allOpenTargets = parentAccordion.querySelectorAll('.accordion-collapse.show');
                        allOpenTargets.forEach(openEl => {
                            if (openEl !== targetEl) {
                                const inst = bootstrap.Collapse.getInstance(openEl) || new bootstrap.Collapse(openEl, { toggle: false });
                                inst.hide();
                            }
                        });
                    }
                    collapseInstance.show();
                }
            } else {
                // ফলব্যাক (Fallback): যদি বুটস্ট্র্যাপ অবজেক্ট কোনো কারণে লোড না হয়ে থাকে
                // ব্লিংকিং রোধে পিওর ক্লাস টগল লজিক (স্ট্রিক্টলি ওয়ান-ওয়ে)
                const isExpanded = targetEl.classList.contains('show');
                const parentAccordion = this.closest('.accordion');

                if (parentAccordion && !isExpanded) {
                    const allButtons = parentAccordion.querySelectorAll('[data-bs-toggle="collapse"]');
                    allButtons.forEach(btn => {
                        btn.classList.add('collapsed');
                        btn.setAttribute('aria-expanded', 'false');
                        const tSel = btn.getAttribute('data-bs-target');
                        if (tSel) {
                            const tEl = document.querySelector(tSel);
                            if (tEl) tEl.classList.remove('show');
                        }
                    });
                }

                if (isExpanded) {
                    this.classList.add('collapsed');
                    this.setAttribute('aria-expanded', 'false');
                    targetEl.classList.remove('show');
                } else {
                    this.classList.remove('collapsed');
                    this.setAttribute('aria-expanded', 'true');
                    targetEl.classList.add('show');
                }
            }
        });
    });
});