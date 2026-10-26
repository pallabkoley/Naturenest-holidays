/* =========================================================
   NATURE NEST HOLIDAYS — main.js
   ========================================================= */
(function () {
  'use strict';
  var $  = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return [].slice.call((c || document).querySelectorAll(s)); };

  /* =========================================================
     Sticky header
     ========================================================= */
  var header = $('#siteHeader');
  function onScroll() {
    if (header) header.classList.toggle('is-scrolled', window.scrollY > 12);
  }
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  /* =========================================================
     MOBILE NAV — SINGLE SOURCE OF TRUTH (class-based only)
     ========================================================= */
  var navToggle   = $('#navToggle'),
      mainNav     = $('#mainNav'),
      navBackdrop = $('#navBackdrop');

  function openNav() {
    if (!mainNav) return;
    mainNav.classList.add('is-open');
    if (navToggle) {
      navToggle.classList.add('is-open');
      navToggle.setAttribute('aria-expanded', 'true');
    }
    document.body.classList.add('no-scroll');
    if (navBackdrop) {
      navBackdrop.hidden = false;
      requestAnimationFrame(function () { navBackdrop.classList.add('is-visible'); });
    }
  }

  function closeNav() {
    if (!mainNav) return;
    mainNav.classList.remove('is-open');
    if (navToggle) {
      navToggle.classList.remove('is-open');
      navToggle.setAttribute('aria-expanded', 'false');
    }
    document.body.classList.remove('no-scroll');

    /* Reset every expanded mega-menu when the drawer closes */
    $$('.has-mega-menu.is-open').forEach(function (el) { el.classList.remove('is-open'); });
    $$('.mega-left-item.is-open').forEach(function (el) { el.classList.remove('is-open'); });

    if (navBackdrop) {
      navBackdrop.classList.remove('is-visible');
      setTimeout(function () { navBackdrop.hidden = true; }, 320);
    }
  }

  if (navToggle) {
    navToggle.addEventListener('click', function (e) {
      e.preventDefault();
      mainNav.classList.contains('is-open') ? closeNav() : openNav();
    });
  }

  if (navBackdrop) navBackdrop.addEventListener('click', closeNav);

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeNav();
  });

  var rt;
  window.addEventListener('resize', function () {
    clearTimeout(rt);
    rt = setTimeout(function () { if (window.innerWidth > 991) closeNav(); }, 160);
  });

  /* =========================================================
     MOBILE MEGA-MENU TRIGGER (Tour Packages)
     — Only toggles its own <li>. Never touches #mainNav.
     ========================================================= */
  $$('.has-dropdown').forEach(function (li) {
    var trigger = li.querySelector(':scope > .nav-link');
    if (!trigger) return;

    trigger.addEventListener('click', function (e) {
      if (window.innerWidth > 991) return;   /* desktop = hover */
      e.preventDefault();                     /* kill anchor nav */
      e.stopPropagation();                    /* keep bubble contained */
      li.classList.toggle('is-open');         /* show/hide mega-menu wrapper */
    });
  });

  /* =========================================================
     Back to top
     ========================================================= */
  var toTop = $('#toTop');
  if (toTop) {
    window.addEventListener('scroll', function () {
      toTop.classList.toggle('is-visible', window.scrollY > 500);
    }, { passive: true });
    toTop.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });
  }

  /* =========================================================
     Reveal on scroll
     ========================================================= */
  var revealItems = $$('.reveal');
  if ('IntersectionObserver' in window && revealItems.length) {
    var obs = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) { en.target.classList.add('is-visible'); obs.unobserve(en.target); }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -60px 0px' });
    revealItems.forEach(function (el, i) {
      el.style.transitionDelay = (Math.min(i % 4, 3) * 80) + 'ms';
      obs.observe(el);
    });
  } else {
    revealItems.forEach(function (el) { el.classList.add('is-visible'); });
  }

  /* =========================================================
     Hero slider
     ========================================================= */
  var slides = $$('.hero-slide');
  if (slides.length > 1) {
    var dotsBox = $('#heroDots'), prevBtn = $('.hero-nav.prev'), nextBtn = $('.hero-nav.next');
    var idx = 0, timer = null, DELAY = 6500;
    function buildDots() {
      if (!dotsBox) return;
      dotsBox.innerHTML = '';
      slides.forEach(function (_, i) {
        var d = document.createElement('button');
        d.type = 'button';
        d.setAttribute('aria-label', 'Slide ' + (i + 1));
        d.addEventListener('click', function () { go(i); });
        dotsBox.appendChild(d);
      });
    }
    function upd() {
      if (!dotsBox) return;
      $$('button', dotsBox).forEach(function (d, i) { d.classList.toggle('is-active', i === idx); });
    }
    function go(n) {
      slides[idx].classList.remove('is-active');
      idx = (n + slides.length) % slides.length;
      slides[idx].classList.add('is-active');
      upd(); restart();
    }
    function next() { go(idx + 1); }
    function prev() { go(idx - 1); }
    function start() { timer = setInterval(next, DELAY); }
    function stop() { if (timer) { clearInterval(timer); timer = null; } }
    function restart() { stop(); start(); }
    buildDots(); upd(); start();
    if (prevBtn) prevBtn.addEventListener('click', prev);
    if (nextBtn) nextBtn.addEventListener('click', next);
    var hero = $('.hero');
    if (hero) {
      hero.addEventListener('mouseenter', stop);
      hero.addEventListener('mouseleave', start);
    }
    document.addEventListener('visibilitychange', function () { document.hidden ? stop() : start(); });
  }

  /* =========================================================
     Itinerary accordion
     ========================================================= */
  $$('.itinerary-head').forEach(function (head) {
    head.addEventListener('click', function () {
      var item = head.closest('.itinerary-item');
      item.classList.toggle('is-open');
    });
  });

  /* =========================================================
     Contact / Booking form validation
     ========================================================= */
  function setupForm(formId, successId) {
    var form = $('#' + formId);
    if (!form) return;
    var success = successId ? $('#' + successId) : null;

    function setErr(field, msg) {
      if (!field) return;
      var wrap = field.closest('.field');
      if (!wrap) return;
      var err = wrap.querySelector('.error-msg');
      if (msg) {
        wrap.classList.add('has-error');
        if (err) err.textContent = msg;
      } else {
        wrap.classList.remove('has-error');
        if (err) err.textContent = '';
      }
    }

    function validate(input) {
      var v = (input.value || '').trim();
      var name = input.name;
      if (input.hasAttribute('required') && !v) return 'This field is required.';
      if (input.type === 'email' && v && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v)) return 'Enter a valid email.';
      if (input.type === 'tel' && v && v.replace(/\D/g, '').length < 10) return 'Enter a valid phone.';
      if (name === 'travellers' && v && Number(v) < 1) return 'At least 1 traveler.';
      return '';
    }

    $$('input,select,textarea', form).forEach(function (inp) {
      inp.addEventListener('blur', function () { setErr(inp, validate(inp)); });
      inp.addEventListener('input', function () {
        var wrap = inp.closest('.field');
        if (wrap && wrap.classList.contains('has-error')) setErr(inp, validate(inp));
      });
    });

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var ok = true, firstBad = null;
      $$('[required]', form).forEach(function (inp) {
        var err = validate(inp);
        setErr(inp, err);
        if (err) { ok = false; if (!firstBad) firstBad = inp; }
      });
      if (!ok) { if (success) success.hidden = true; if (firstBad) firstBad.focus(); return; }
      if (success) { success.hidden = false; success.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
      form.reset();
      setTimeout(function () { if (success) success.hidden = true; }, 8000);
    });
  }
  setupForm('contactForm', 'contactSuccess');
  setupForm('bookingForm', 'bookingSuccess');
})();