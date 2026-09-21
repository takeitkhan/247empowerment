</div> <!-- Closing main container if opened earlier -->
<footer class="bg-white shadow-sm py-2 border-top xfooter-gradient footer-height">
    <div class="pt-4 pb-4 h-100 container container-home custom-card">

        <!-- Site Name -->
        <p class="mb-1 pt-4 fw-bold"><?php echo get_bloginfo('name'); ?></p>

        <div class="align-items-center g-3 g-md-0 row">

            <!-- Site Description / Info -->
            <div class="col-md-12">
                <p class="mt-2 mt-md-0 mb-0 fs14">
                    <?php echo get_theme_mod('mm_custom_subline', 'Default subline here'); ?>
                </p>
                  <ul class="list-inline d-flex align-items-center gap-3 mb-2 social-icons-list">
                    <?php
                    // Social media platforms and Bootstrap icon classes
                    $social_platforms = [
                        'facebook'  => 'bi-facebook',
                        'twitter'   => 'bi-twitter-x',
                        'instagram' => 'bi-instagram',
                        'linkedin'  => 'bi-linkedin',
                        'youtube'   => 'bi-youtube'
                    ];

                    // Loop through each platform and show only those with URLs set in the Customizer
                    foreach ($social_platforms as $platform => $icon_class):
                        $url = get_theme_mod("{$platform}_url"); // Get from Customizer
                        if (!empty($url)):
                            ?>
                            <li class="list-inline-item m-0">
                                <a href="<?php echo esc_url($url); ?>" 
                                  class="text-decoration-none social-icon" 
                                  target="_blank" 
                                  rel="noopener" 
                                  aria-label="<?php echo ucfirst($platform); ?>">
                                    <i class="bi <?php echo esc_attr($icon_class); ?>"></i>
                                </a>
                            </li>
                            <?php
                        endif;
                    endforeach;
                    ?>
                </ul>
                <?php
                wp_nav_menu([
                    'theme_location' => 'secondary',
                    'container' => false,
                    'menu_class' => 'list-inline d-flex justify-content-start mb-0 gap-4',
                    'items_wrap' => '<ul id="%1$s" class="%2$s">%3$s</ul>',
                    'walker' => new MM_Footer_Walker_Nav_Menu(),
                    'fallback_cb' => false,
                ]);
                ?>
            </div>
        </div>
    </div>
</footer>

<!-- Custom JS -->
<script>
    document.addEventListener('DOMContentLoaded', function () {

        if (window.innerWidth > 1365) return;

        const toggles = document.querySelectorAll(
            '.mobile-menu .nav-item.dropdown > .nav-link'
        );

        toggles.forEach(toggle => {

            toggle.addEventListener('click', function (e) {
                e.preventDefault();

                const parent = this.parentElement;
                const submenu = parent.querySelector('.dropdown-menu');

                if (!submenu) return;

                /* Close other open submenus (accordion) */
                document
                    .querySelectorAll('.mobile-menu .dropdown-menu.show')
                    .forEach(open => {
                        if (open !== submenu) {
                            open.classList.remove('show');
                            open.parentElement
                                .querySelector('.nav-link')
                                ?.classList.remove('active');
                        }
                    });

                /* Toggle current */
                submenu.classList.toggle('show');
                this.classList.toggle('active');
            });

        });

    });
    document.addEventListener('DOMContentLoaded', function () {

        // ===== Search toggle =====
        const searchIcon = document.querySelector(".search-icon");
        const searchBox = document.querySelector(".search-box");
        const searchInput = document.querySelector("#searchInput");
        if (searchIcon && searchBox && searchInput) {
            searchIcon.addEventListener("click", e => {
                e.stopPropagation();
                searchBox.classList.toggle("active");
                if (searchBox.classList.contains("active")) searchInput.focus();
            });
            document.addEventListener("click", () => searchBox.classList.remove("active"));
            searchBox.addEventListener("click", e => e.stopPropagation());
        }

        // ===== Mobile menu toggle =====
        const menuToggle = document.getElementById("menuToggle");
        const mobileMenu = document.querySelector(".mobile-menu");
        if (menuToggle && mobileMenu) {
            menuToggle.addEventListener("click", () => mobileMenu.classList.toggle("show"));
        }

        // ===== Optional Tab slider (uncomment if needed) =====
        // const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
        // const tabContents = document.querySelectorAll('.tab-content');
        // let currentIndex = 0, delay = 11000, intervalId;
        // function showNextTab() { ... }
        // function startSlider() { ... }
        // function stopSlider() { ... }

    });
</script>
<!-- <script>window.$zoho=window.$zoho || {};$zoho.salesiq=$zoho.salesiq||{ready:function(){}}</script><script id="zsiqscript" src="https://salesiq.zohopublic.com/widget?wc=siq0404bc707758b5be1581c34ee195cc074669c21e31c113ca806673af92388c1522bdc47681216462f4c41d2bab016384" defer></script> -->
<?php wp_footer(); ?>
<?php if ( ! is_user_logged_in() ) : ?>
<style>
/* Guest lead chat — hide logged-in AI widget if it loads elsewhere */
#mm-ai-chat-widget-container { display: none !important; }
#wc-bubble {
  position:fixed; bottom:24px; right:24px; z-index:999999;
  width:58px; height:58px; border-radius:50%;
  background:linear-gradient(135deg,#a07840,#f0c060);
  box-shadow:0 4px 22px rgba(0,0,0,.38);
  cursor:pointer; display:flex; align-items:center; justify-content:center;
  border:none; transition:transform .2s;
}
#wc-bubble:hover { transform:scale(1.08); }
#wc-bubble svg { width:26px; height:26px; fill:#1a0800; pointer-events:none; }
#wc-badge {
  position:absolute; top:-3px; right:-3px;
  background:#e53e3e; color:#fff; border-radius:50%;
  width:18px; height:18px; font-size:10px; font-weight:900;
  display:flex; align-items:center; justify-content:center;
  border:2px solid #fff;
  animation:wcBadgePop .4s ease;
}
@keyframes wcBadgePop { 0%{transform:scale(0)} 70%{transform:scale(1.3)} 100%{transform:scale(1)} }
@keyframes wcShake {
  0%,100%{ transform:rotate(0) scale(1); }
  10%    { transform:rotate(-12deg) scale(1.1); }
  20%    { transform:rotate(12deg)  scale(1.1); }
  30%    { transform:rotate(-10deg) scale(1.08); }
  40%    { transform:rotate(10deg)  scale(1.08); }
  50%    { transform:rotate(-6deg)  scale(1.05); }
  60%    { transform:rotate(6deg)   scale(1.05); }
  70%    { transform:rotate(-3deg)  scale(1.02); }
  80%    { transform:rotate(3deg)   scale(1.02); }
  90%    { transform:rotate(0)      scale(1); }
}
@keyframes wcRing {
  0%   { box-shadow:0 0 0 0   rgba(240,192,96,.7), 0 4px 22px rgba(0,0,0,.38); }
  60%  { box-shadow:0 0 0 14px rgba(240,192,96,0),  0 4px 22px rgba(0,0,0,.38); }
  100% { box-shadow:0 0 0 0   rgba(240,192,96,0),  0 4px 22px rgba(0,0,0,.38); }
}
#wc-bubble.wc-attract {
  animation:wcShake .7s ease, wcRing 1s ease;
}
#wc-notify {
  position:fixed; bottom:92px; right:24px; z-index:999997;
  background:#1a0e05; color:#f5deb3; font-size:.8rem; font-weight:500;
  padding:9px 14px; border-radius:12px; max-width:210px; line-height:1.45;
  box-shadow:0 4px 16px rgba(0,0,0,.32); display:none;
  animation:wcUp .3s ease;
}
#wc-notify::after {
  content:''; position:absolute; bottom:-7px; right:20px;
  border:7px solid transparent; border-top-color:#1a0e05; border-bottom:0;
}
#wc-box {
  position:fixed; bottom:92px; right:24px; z-index:999998;
  width:348px; max-width:calc(100vw - 32px);
  background:#fff; border-radius:18px;
  box-shadow:0 10px 44px rgba(0,0,0,.24);
  display:none; flex-direction:column; overflow:hidden;
  font-family:inherit; animation:wcUp .3s ease;
}
#wc-box.wc-open { display:flex; }
@keyframes wcUp { from{opacity:0;transform:translateY(14px)} to{opacity:1;transform:translateY(0)} }
#wc-head {
  background:linear-gradient(135deg,#1a0e05,#3a2010);
  padding:13px 16px; display:flex; align-items:center; gap:10px;
}
#wc-head img { width:40px; height:40px; border-radius:50%; object-fit:cover; border:2px solid #f0c060; }
.wch-name   { color:#f0c060; font-weight:800; font-size:.88rem; }
.wch-status { color:rgba(255,255,255,.6); font-size:.7rem; margin-top:1px; }
#wc-x {
  margin-left:auto; background:none; border:none;
  color:#f5deb3; cursor:pointer; font-size:1.1rem;
  padding:2px 4px; line-height:1;
}
#wc-x:hover { color:#fff; }
#wc-msgs {
  padding:14px 12px 4px; display:flex; flex-direction:column;
  gap:9px; overflow-y:auto; max-height:260px;
}
.wc-bot, .wc-usr {
  max-width:87%; font-size:.82rem; line-height:1.55;
  padding:8px 12px; border-radius:12px;
}
.wc-bot { background:#f4f0e8; color:#1a0800; align-self:flex-start; border-bottom-left-radius:3px; }
.wc-usr { background:linear-gradient(135deg,#a07840,#c9963e); color:#fff; align-self:flex-end; border-bottom-right-radius:3px; }
.wc-dots {
  display:flex; align-items:center; gap:4px; padding:8px 12px;
  background:#f4f0e8; border-radius:12px; border-bottom-left-radius:3px;
  align-self:flex-start;
}
.wc-dots span {
  width:6px; height:6px; background:#a07840; border-radius:50%; display:block;
  animation:wcDot 1.2s ease-in-out infinite;
}
.wc-dots span:nth-child(2){animation-delay:.2s}
.wc-dots span:nth-child(3){animation-delay:.4s}
@keyframes wcDot{0%,60%,100%{transform:translateY(0)}30%{transform:translateY(-5px)}}
#wc-qr { padding:8px 12px 10px; display:flex; flex-wrap:wrap; gap:6px; }
.wc-qr-btn {
  background:#fff; border:1.5px solid #a07840; color:#a07840;
  border-radius:20px; padding:5px 12px; font-size:.74rem; font-weight:700;
  cursor:pointer; font-family:inherit; transition:background .15s,color .15s;
}
.wc-qr-btn:hover { background:#a07840; color:#fff; }
#wc-form {
  padding:8px 12px 14px; display:none; flex-direction:column; gap:7px;
}
.wc-inp {
  border:1.5px solid #d4b87a; border-radius:8px; padding:8px 10px;
  font-size:.8rem; font-family:inherit; color:#1a0800; outline:none;
  transition:border-color .15s; width:100%; box-sizing:border-box;
}
.wc-inp:focus { border-color:#a07840; }
.wc-inp.err   { border-color:#e53e3e; }
#wc-go {
  background:linear-gradient(135deg,#a07840,#f0c060); color:#1a0800;
  border:none; border-radius:8px; padding:10px; font-size:.88rem;
  font-weight:900; cursor:pointer; font-family:inherit;
  transition:opacity .2s; margin-top:2px;
}
#wc-go:hover { opacity:.85; }
#wc-go:disabled { opacity:.6; cursor:not-allowed; }
</style>

<!-- Teaser tooltip -->
<div id="wc-notify">👋 Hi! I'm Joshua.<br>Can I help you today?</div>

<!-- Floating button -->
<button id="wc-bubble" aria-label="Chat with Joshua" onclick="wcToggle()">
  <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
    <path d="M20 2H4a2 2 0 00-2 2v18l4-4h14a2 2 0 002-2V4a2 2 0 00-2-2z"/>
  </svg>
  <span id="wc-badge">1</span>
</button>

<!-- Chat box -->
<div id="wc-box">
  <div id="wc-head">
    <img src="https://personalempowermentteams.me/wp-content/uploads/2026/04/cropped-247empowerment_logo-1.png" alt="Joshua">
    <div>
      <div class="wch-name">Joshua Joseph</div>
      <div class="wch-status">● Online now</div>
    </div>
    <button id="wc-x" onclick="wcToggle()" aria-label="Close">✕</button>
  </div>
  <div id="wc-msgs"></div>
  <div id="wc-qr"></div>
  <div id="wc-form">
    <input class="wc-inp" id="wc-fname" type="text"  placeholder="First name *">
    <input class="wc-inp" id="wc-lname" type="text"  placeholder="Last name *">
    <input class="wc-inp" id="wc-phone" type="tel"   placeholder="Phone number *">
    <input class="wc-inp" id="wc-email" type="email" placeholder="Email address *">
    <button id="wc-go" onclick="wcSubmit()">Enjoy Your Free Access →</button>
  </div>
</div>

<script>
(function(){
  var SIGNUP = 'https://personalempowermentteams.me/signup/';
  var opened = true, interest = '';

  var msgs = document.getElementById('wc-msgs');
  var qr   = document.getElementById('wc-qr');
  var form = document.getElementById('wc-form');
  var box  = document.getElementById('wc-box');

  /* Auto-open on load */
  box.classList.add('wc-open');
  var _b = document.getElementById('wc-badge');
  if(_b) _b.style.display = 'none';
  wcStart();

  /* Periodic attract animation every 5 s (stops once opened) */
  function wcAttract(){
    if(opened) return;
    var btn = document.getElementById('wc-bubble');
    if(!btn) return;
    btn.classList.remove('wc-attract');
    void btn.offsetWidth; /* reflow to restart */
    btn.classList.add('wc-attract');
    setTimeout(function(){ btn.classList.remove('wc-attract'); }, 1000);
  }
  setTimeout(function(){
    wcAttract();
    setInterval(wcAttract, 5000);
  }, 1500);

  window.wcToggle = function(){
    opened = !opened;
    box.classList.toggle('wc-open', opened);
    var b = document.getElementById('wc-badge');
    if(b) b.style.display = opened ? 'none' : '';
    var n = document.getElementById('wc-notify');
    if(n) n.style.display = 'none';
    if(opened && msgs.children.length === 0) wcStart();
  };

  function wcStart(){
    wcBot("Welcome, my name is Joshua Joseph. Please tell me your main interest in visiting 24/7 Empowerment.", function(){
      wcBot("Connect? Collaborate? Grow? Support? Other?", function(){
        wcQR([
          {l:'Connect',     v:'Connect'},
          {l:'Collaborate', v:'Collaborate'},
          {l:'Grow',        v:'Grow'},
          {l:'Support',     v:'Support'},
          {l:'Other',       v:'Other'},
        ]);
      });
    });
  }

  function wcBot(text, cb){
    var dots = document.createElement('div');
    dots.className = 'wc-dots';
    dots.innerHTML = '<span></span><span></span><span></span>';
    msgs.appendChild(dots); wcScroll();
    setTimeout(function(){
      dots.remove();
      var m = document.createElement('div');
      m.className = 'wc-bot'; m.textContent = text;
      msgs.appendChild(m); wcScroll();
      if(cb) setTimeout(cb, 350);
    }, 950);
  }

  function wcUser(text){
    qr.innerHTML = '';
    var m = document.createElement('div');
    m.className = 'wc-usr'; m.textContent = text;
    msgs.appendChild(m); wcScroll();
  }

  function wcQR(opts){
    qr.innerHTML = '';
    opts.forEach(function(o){
      var b = document.createElement('button');
      b.className = 'wc-qr-btn'; b.textContent = o.l;
      b.onclick = function(){
        wcUser(o.l); interest = o.v;
        wcBot("Thank you! I'll give you free access. I just need your name, phone, and email to activate your free link.", function(){
          qr.innerHTML = ''; form.style.display = 'flex'; wcScroll();
        });
      };
      qr.appendChild(b);
    });
  }

  function wcScroll(){ msgs.scrollTop = msgs.scrollHeight; }

  window.wcSubmit = function(){
    var fn = document.getElementById('wc-fname');
    var ln = document.getElementById('wc-lname');
    var p  = document.getElementById('wc-phone');
    var e  = document.getElementById('wc-email');
    var ok = true;
    [fn,ln,p,e].forEach(function(f){
      f.classList.toggle('err', !f.value.trim());
      if(!f.value.trim()) ok = false;
    });
    if(!ok) return;
    var go = document.getElementById('wc-go');
    go.disabled = true; go.textContent = 'Loading…';
    /* Cache in sessionStorage so signup page can pre-fill */
    try{
      sessionStorage.setItem('wc_fname',    fn.value.trim());
      sessionStorage.setItem('wc_lname',    ln.value.trim());
      sessionStorage.setItem('wc_phone',    p.value.trim());
      sessionStorage.setItem('wc_email',    e.value.trim());
      sessionStorage.setItem('wc_interest', interest);
    }catch(e){}
    var url = new URL(SIGNUP);
    url.searchParams.set('interest', interest);
    url.searchParams.set('fname',    fn.value.trim());
    url.searchParams.set('lname',    ln.value.trim());
    url.searchParams.set('phone',    p.value.trim());
    url.searchParams.set('email',    e.value.trim());
    window.location.href = url.toString();
  };
})();
</script>
<?php endif; ?>

<?php if ( ! is_user_logged_in() && is_page( 'signup' ) ) : ?>
<script>
(function(){
  /* Read from URL params first, fall back to sessionStorage */
  var sp    = new URLSearchParams(window.location.search);
  var ss    = function(k){ try{ return sessionStorage.getItem(k)||''; }catch(e){ return ''; } };
  var fname = sp.get('fname') || ss('wc_fname');
  var lname = sp.get('lname') || ss('wc_lname');
  var phone = sp.get('phone') || ss('wc_phone');
  var email = sp.get('email') || ss('wc_email');

  if(!fname && !lname && !phone && !email) return; /* nothing to fill */

  function fill(sel, val){
    if(!val) return;
    var el = document.querySelector(sel);
    if(el && !el.value){
      el.value = val;
      el.dispatchEvent(new Event('input',  {bubbles:true}));
      el.dispatchEvent(new Event('change', {bubbles:true}));
    }
  }

  function doFill(){
    /* Email */
    fill('input[type="email"]',         email);
    fill('input[name="user_email"]',    email);
    fill('input[name="email"]',         email);
    fill('input[name="signup_email"]',  email);
    /* First name */
    fill('input[name="first_name"]',             fname);
    fill('input[name="fname"]',                  fname);
    fill('input[placeholder*="first" i]',        fname);
    /* Last name */
    fill('input[name="last_name"]',              lname);
    fill('input[name="lname"]',                  lname);
    fill('input[placeholder*="last" i]',         lname);
    /* Phone */
    fill('input[type="tel"]',                    phone);
    fill('input[name="phone"]',                  phone);
    fill('input[name="billing_phone"]',          phone);
    fill('input[placeholder*="phone" i]',        phone);
  }

  if(document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', doFill);
  } else {
    doFill();
  }
  /* Re-run after 800 ms for dynamically rendered forms (page builders, etc.) */
  setTimeout(doFill, 800);
})();
</script>
<?php endif; ?>
</body>

</html>