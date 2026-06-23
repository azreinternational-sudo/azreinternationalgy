/* Azre International — front-end interactions */
(function () {
  'use strict';

  // Header shadow on scroll
  const header = document.getElementById('siteHeader');
  if (header) {
    const onScroll = () => {
      header.classList.toggle('scrolled', window.scrollY > 8);
    };
    document.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  // Mobile nav burger
  const burger = document.getElementById('navBurger');
  const navActions = document.querySelector('.nav-actions');
  if (burger && navActions) {
    burger.addEventListener('click', () => {
      const open = navActions.classList.toggle('is-open');
      burger.setAttribute('aria-expanded', String(open));
    });
  }

  // Quantity buttons
  document.querySelectorAll('.qty-control').forEach((ctl) => {
    const input = ctl.querySelector('input[type="number"]');
    ctl.querySelectorAll('button[data-qty]').forEach((btn) => {
      btn.addEventListener('click', () => {
        if (!input) return;
        const step = parseInt(btn.dataset.qty, 10) || 0;
        const min = parseInt(input.min || '0', 10);
        const max = parseInt(input.max || '999999', 10);
        let val = parseInt(input.value || '0', 10) + step;
        if (Number.isNaN(val)) val = min || 1;
        val = Math.max(min || 0, Math.min(max, val));
        input.value = String(val);
      });
    });
  });

  // Auto-submit qty forms on change (cart row)
  document.querySelectorAll('.cart-qty input[type="number"]').forEach((inp) => {
    let t;
    inp.addEventListener('input', () => {
      clearTimeout(t);
      t = setTimeout(() => {
        const f = inp.closest('form');
        if (f && inp.value !== '') f.submit();
      }, 700);
    });
  });

  // Reveal on scroll for hero card stack
  const cards = document.querySelectorAll('.hero-card');
  if (cards.length && 'IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach((e) => {
        if (e.isIntersecting) {
          e.target.style.opacity = '1';
          e.target.style.transform = e.target.classList.contains('hero-card-a') ? 'rotate(-4deg) translateY(0)' :
                                     e.target.classList.contains('hero-card-b') ? 'rotate(3deg) translateY(0)' :
                                     'rotate(-2deg) translateY(0)';
          io.unobserve(e.target);
        }
      });
    }, { threshold: 0.2 });
    cards.forEach((c) => {
      c.style.opacity = '0';
      c.style.transform = c.style.transform + ' translateY(20px)';
      c.style.transition = 'opacity .6s ease, transform .6s cubic-bezier(.2,.7,.2,1)';
      io.observe(c);
    });
  }

  // Smooth anchor focus
  document.querySelectorAll('a[href^="#"]').forEach((a) => {
    a.addEventListener('click', (e) => {
      const id = a.getAttribute('href').slice(1);
      const tgt = document.getElementById(id);
      if (tgt) {
        e.preventDefault();
        tgt.scrollIntoView({ behavior: 'smooth', block: 'start' });
        tgt.setAttribute('tabindex', '-1');
        tgt.focus({ preventScroll: true });
      }
    });
  });
})();
