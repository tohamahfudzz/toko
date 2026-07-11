// script.js — Comp Store (revisi: hilangkan duplikasi DOMContentLoaded)
document.addEventListener('DOMContentLoaded', function () {

  /* =====================
     Tombol Akun (sidebar)
     ===================== */
  const akunBtn = document.getElementById('akunBtn');
  if (akunBtn) {
    akunBtn.addEventListener('click', function (ev) {
      ev.preventDefault();

      const loggedInAttr = document.body.getAttribute('data-logged-in');
      const isLoggedIn   = loggedInAttr === '1' || window.IS_LOGGED_IN === true;

      if (isLoggedIn) {
        window.location.href = 'account.php';
        return;
      }

      const returnUrl  = encodeURIComponent(window.location.pathname + window.location.search);
      const loginPath  = 'loginuser/index.php';
      window.location.href = loginPath + '?return=' + returnUrl;
    });
  }

  /* =====================
     Sidebar
     ===================== */
  const sidebar      = document.getElementById('sidebar');
  const hamburgerBtn = document.getElementById('hamburgerBtn');
  const closeBtn     = document.getElementById('closeSidebarBtn');

  function openSidebar() {
    if (!sidebar) return;
    sidebar.classList.add('show');
    sidebar.setAttribute('aria-hidden', 'false');
    const first = sidebar.querySelector('button, a, [tabindex]:not([tabindex="-1"])');
    if (first) first.focus();
    document.body.style.overflow = 'hidden';
  }

  function closeSidebar() {
    if (!sidebar) return;
    sidebar.classList.remove('show');
    sidebar.setAttribute('aria-hidden', 'true');
    if (hamburgerBtn) hamburgerBtn.focus();
    document.body.style.overflow = '';
  }

  if (hamburgerBtn) hamburgerBtn.addEventListener('click', openSidebar);
  if (closeBtn)      closeBtn.addEventListener('click', closeSidebar);

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && sidebar && sidebar.classList.contains('show')) closeSidebar();
  });
  document.addEventListener('click', function (e) {
    if (!sidebar || !sidebar.classList.contains('show')) return;
    if (!sidebar.contains(e.target) && !(hamburgerBtn && hamburgerBtn.contains(e.target))) closeSidebar();
  });

  /* =====================
     Slideshow
     ===================== */
  const slideItems      = Array.from(document.querySelectorAll('.slide-item'));
  const dotContainer    = document.querySelector('.dot-container');
  const prevBtn         = document.getElementById('prevBtn');
  const nextBtn         = document.getElementById('nextBtn');
  const slideshowEl     = document.querySelector('.slideshow-container');
  const reduceMotion    = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  let index      = 0;
  let intervalId = null;
  const INTERVAL = 4500;

  function buildDots() {
    if (!dotContainer) return;
    dotContainer.innerHTML = '';
    const count = Math.max(1, slideItems.length);
    for (let i = 0; i < count; i++) {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'dot' + (i === 0 ? ' active' : '');
      btn.setAttribute('role', 'tab');
      btn.setAttribute('aria-label', 'Slide ' + (i + 1));
      btn.setAttribute('aria-selected', i === 0 ? 'true' : 'false');
      btn.tabIndex = i === 0 ? 0 : -1;
      btn.addEventListener('click', function () { show(i); restartAuto(); });
      dotContainer.appendChild(btn);
    }
  }

  function show(n) {
    if (!slideItems.length) return;
    index = ((n % slideItems.length) + slideItems.length) % slideItems.length;
    slideItems.forEach(function (s, i) {
      const active = i === index;
      s.classList.toggle('show', active);
      s.setAttribute('aria-hidden', active ? 'false' : 'true');
    });
    if (dotContainer) {
      Array.from(dotContainer.children).forEach(function (d, i) {
        const active = i === index;
        d.classList.toggle('active', active);
        d.setAttribute('aria-selected', active ? 'true' : 'false');
        d.tabIndex = active ? 0 : -1;
      });
    }
  }

  function next() { show(index + 1); }
  function prev() { show(index - 1); }

  function startAuto() {
    if (reduceMotion || intervalId || slideItems.length <= 1) return;
    intervalId = setInterval(next, INTERVAL);
  }
  function stopAuto()    { clearInterval(intervalId); intervalId = null; }
  function restartAuto() { stopAuto(); startAuto(); }

  if (nextBtn) nextBtn.addEventListener('click', function () { next(); restartAuto(); });
  if (prevBtn) prevBtn.addEventListener('click', function () { prev(); restartAuto(); });

  if (slideshowEl) {
    slideshowEl.addEventListener('mouseenter', stopAuto);
    slideshowEl.addEventListener('mouseleave', startAuto);
    slideshowEl.addEventListener('focusin',    stopAuto);
    slideshowEl.addEventListener('focusout',   startAuto);
  }

  document.addEventListener('keydown', function (e) {
    if (e.key === 'ArrowLeft')  { prev(); restartAuto(); }
    if (e.key === 'ArrowRight') { next(); restartAuto(); }
  });

  if (slideItems.length) {
    buildDots();
    show(0);
    startAuto();
  }

  /* =====================
     Lazy images
     ===================== */
  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver(function (entries, observer) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        observer.unobserve(entry.target);
      });
    }, { rootMargin: '200px' });
    document.querySelectorAll('img[loading="lazy"]').forEach(function (img) { io.observe(img); });
  }

  /* =====================
     Card keyboard support
     ===================== */
  document.querySelectorAll('.card').forEach(function (card) {
    card.setAttribute('tabindex', '0');
    card.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        const link = card.querySelector('a');
        if (link) link.click();
      }
    });
  });

  /* =====================
     Hover zoom galeri images
     ===================== */
  document.querySelectorAll('.galeri-card img').forEach(function (img) {
    const card = img.closest('.galeri-card');
    if (!card) return;
    card.addEventListener('mouseenter', function () { img.style.transform = 'scale(1.07)'; });
    card.addEventListener('mouseleave', function () { img.style.transform = 'scale(1)'; });
  });

  /* =====================
     Aksi tombol Keranjang / Wishlist hover
     ===================== */
  document.querySelectorAll('.card button').forEach(function (btn) {
    btn.addEventListener('mouseenter', function () {
      btn.style.borderColor = '#1a6fe8';
      btn.style.color       = '#1a6fe8';
      btn.style.background  = '#eff6ff';
    });
    btn.addEventListener('mouseleave', function () {
      btn.style.borderColor = '';
      btn.style.color       = '';
      btn.style.background  = '';
    });
  });

});
