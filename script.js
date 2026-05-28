/* ================================================
   Nav — scroll state + active link highlight
   ================================================ */
const header = document.getElementById('header');

window.addEventListener('scroll', onScroll, { passive: true });

function onScroll() {
  header.classList.toggle('scrolled', window.scrollY > 8);
  highlightNav();
}

function highlightNav() {
  const mid = window.scrollY + window.innerHeight * 0.35;
  document.querySelectorAll('.nav-link').forEach(link => {
    const target = document.querySelector(link.getAttribute('href'));
    if (!target) return;
    const { offsetTop, offsetHeight } = target;
    link.classList.toggle('active', mid >= offsetTop && mid < offsetTop + offsetHeight);
  });
}

/* ================================================
   Smooth scroll — all # anchors
   ================================================ */
document.querySelectorAll('a[href^="#"]').forEach(a => {
  a.addEventListener('click', e => {
    const el = document.querySelector(a.getAttribute('href'));
    if (!el) return;
    e.preventDefault();
    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    closeDrawer();
  });
});

/* ================================================
   Mobile burger menu
   ================================================ */
const burger  = document.getElementById('burger');
const drawer  = document.getElementById('drawer');

burger.addEventListener('click', () => {
  const open = drawer.classList.toggle('open');
  burger.classList.toggle('open', open);
  burger.setAttribute('aria-label', open ? 'メニューを閉じる' : 'メニューを開く');
});

function closeDrawer() {
  drawer.classList.remove('open');
  burger.classList.remove('open');
}

/* ================================================
   Scroll reveal — IntersectionObserver
   ================================================ */
const io = new IntersectionObserver(
  entries => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      // stagger siblings inside the same parent
      const siblings = [...entry.target.parentElement.querySelectorAll('.reveal:not(.in)')];
      const idx = siblings.indexOf(entry.target);
      const delay = Math.max(0, idx) * 60;
      setTimeout(() => entry.target.classList.add('in'), delay);
      io.unobserve(entry.target);
    });
  },
  { threshold: 0.08, rootMargin: '0px 0px -32px 0px' }
);

document.querySelectorAll('.reveal').forEach(el => io.observe(el));

/* ================================================
   Skill icons — subtle hover lift stagger on load
   ================================================ */
document.querySelectorAll('.skill-icon-item').forEach((item, i) => {
  item.style.transitionDelay = `${i * 30}ms`;
});
