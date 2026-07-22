(() => {
  'use strict';

  const header = document.querySelector('[data-header]');
  const toggle = document.querySelector('[data-menu-toggle]');
  const mobileNav = document.querySelector('[data-mobile-nav]');
  const mobileOverlay = document.querySelector('[data-mobile-overlay]');
  const menuClose = document.querySelector('[data-menu-close]');

  const onScroll = () => {
    if (!header) return;
    header.classList.toggle('is-scrolled', window.scrollY > 8);
  };

  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });

  const setMenuOpen = (open) => {
    if (!toggle || !mobileNav) return;
    toggle.setAttribute('aria-expanded', String(open));
    if (open) {
      mobileNav.removeAttribute('hidden');
      if (mobileOverlay) mobileOverlay.removeAttribute('hidden');
      document.body.classList.add('is-nav-open');
    } else {
      mobileNav.setAttribute('hidden', '');
      if (mobileOverlay) mobileOverlay.setAttribute('hidden', '');
      document.body.classList.remove('is-nav-open');
    }
  };

  if (toggle && mobileNav) {
    toggle.addEventListener('click', () => {
      const open = toggle.getAttribute('aria-expanded') === 'true';
      setMenuOpen(!open);
    });

    if (menuClose) {
      menuClose.addEventListener('click', () => setMenuOpen(false));
    }
    if (mobileOverlay) {
      mobileOverlay.addEventListener('click', () => setMenuOpen(false));
    }

    mobileNav.querySelectorAll('a').forEach((link) => {
      link.addEventListener('click', () => setMenuOpen(false));
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') setMenuOpen(false);
    });
  }

  document.querySelectorAll('[data-flash-close]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const flash = btn.closest('[data-flash]');
      if (flash) flash.remove();
    });
  });

  const filters = document.querySelector('[data-menu-filters]');
  if (filters) {
    const chips = filters.querySelectorAll('[data-filter]');
    const categories = document.querySelectorAll('[data-category]');

    const applyFilter = (value) => {
      chips.forEach((c) => c.classList.toggle('is-active', c.getAttribute('data-filter') === value));
      categories.forEach((block) => {
        const slug = block.getAttribute('data-category');
        block.hidden = !(value === 'all' || value === slug);
      });
    };

    chips.forEach((chip) => {
      chip.addEventListener('click', () => {
        const value = chip.getAttribute('data-filter') || 'all';
        applyFilter(value);
        const url = new URL(window.location.href);
        if (value === 'all') {
          url.searchParams.delete('category');
        } else {
          url.searchParams.set('category', value);
        }
        window.history.replaceState({}, '', url);
      });
    });
  }

  const catTrack = document.querySelector('[data-categories-track]');
  const catPrev = document.querySelector('[data-cat-prev]');
  const catNext = document.querySelector('[data-cat-next]');
  const catSlider = document.querySelector('[data-categories-slider]');

  if (catTrack) {
    const nudgeMarquee = (dir) => {
      if (!catSlider || !catTrack.classList.contains('categories-track--marquee')) {
        const card = catTrack.querySelector('.category-card');
        const amount = card ? card.getBoundingClientRect().width + 16 : 160;
        catTrack.scrollBy({ left: dir * amount, behavior: 'smooth' });
        return;
      }
      const current = getComputedStyle(catTrack).animationDuration || '28s';
      const seconds = Math.max(12, parseFloat(current) + (dir > 0 ? -4 : 4));
      catTrack.style.animationDuration = `${seconds}s`;
    };

    if (catPrev) catPrev.addEventListener('click', () => nudgeMarquee(-1));
    if (catNext) catNext.addEventListener('click', () => nudgeMarquee(1));

    if (catSlider && catTrack.classList.contains('categories-track--marquee')) {
      const pauseCats = () => catSlider.classList.add('is-paused');
      const resumeCats = () => catSlider.classList.remove('is-paused');
      catTrack.addEventListener('touchstart', pauseCats, { passive: true });
      catTrack.addEventListener('touchend', resumeCats, { passive: true });
      catTrack.addEventListener('pointerdown', pauseCats);
      catTrack.addEventListener('pointerup', resumeCats);
    }
  }

  /* Menu category swipe/marquee slider */
  const menuCatsSlider = document.querySelector('[data-menu-cats]');
  const menuCatsTrack = document.querySelector('[data-menu-cats-track]');
  if (menuCatsTrack) {
    const pauseMenuCats = () => menuCatsSlider && menuCatsSlider.classList.add('is-paused');
    const resumeMenuCats = () => menuCatsSlider && menuCatsSlider.classList.remove('is-paused');

    menuCatsTrack.addEventListener('pointerdown', pauseMenuCats);
    menuCatsTrack.addEventListener('pointerup', resumeMenuCats);
    menuCatsTrack.addEventListener('pointercancel', resumeMenuCats);
    menuCatsTrack.addEventListener('touchstart', pauseMenuCats, { passive: true });
    menuCatsTrack.addEventListener('touchend', resumeMenuCats, { passive: true });

    if (!menuCatsTrack.classList.contains('menu-cats-track--marquee')) {
      const activeCat = menuCatsTrack.querySelector('.menu-cat-card.is-active');
      if (activeCat) {
        activeCat.scrollIntoView({ inline: 'center', block: 'nearest', behavior: 'smooth' });
      }

      let isDown = false;
      let startX = 0;
      let scrollLeft = 0;
      let moved = false;

      menuCatsTrack.addEventListener('pointerdown', (e) => {
        if (e.pointerType === 'mouse' && e.button !== 0) return;
        isDown = true;
        moved = false;
        startX = e.clientX;
        scrollLeft = menuCatsTrack.scrollLeft;
        menuCatsTrack.setPointerCapture(e.pointerId);
        menuCatsTrack.classList.add('is-dragging');
      });

      menuCatsTrack.addEventListener('pointermove', (e) => {
        if (!isDown) return;
        const dx = e.clientX - startX;
        if (Math.abs(dx) > 4) moved = true;
        menuCatsTrack.scrollLeft = scrollLeft - dx;
      });

      const endDrag = (e) => {
        if (!isDown) return;
        isDown = false;
        menuCatsTrack.classList.remove('is-dragging');
        try { menuCatsTrack.releasePointerCapture(e.pointerId); } catch (_) {}
      };

      menuCatsTrack.addEventListener('pointerup', endDrag);
      menuCatsTrack.addEventListener('pointercancel', endDrag);

      menuCatsTrack.addEventListener('click', (e) => {
        if (!moved) return;
        const link = e.target.closest('a');
        if (link) {
          e.preventDefault();
          e.stopPropagation();
        }
      }, true);
    }
  }

  /* Language dropdown — closed by default, closes after pick / outside click */
  const langSwitch = document.querySelector('[data-lang-switch]');
  const langToggle = document.querySelector('[data-lang-toggle]');
  const langMenu = document.querySelector('[data-lang-menu]');
  if (langSwitch && langToggle && langMenu) {
    const closeLang = () => {
      langToggle.setAttribute('aria-expanded', 'false');
      langMenu.setAttribute('hidden', '');
    };
    const openLang = () => {
      langToggle.setAttribute('aria-expanded', 'true');
      langMenu.removeAttribute('hidden');
    };

    langToggle.addEventListener('click', (ev) => {
      ev.stopPropagation();
      const open = langToggle.getAttribute('aria-expanded') === 'true';
      if (open) closeLang();
      else openLang();
    });

    langMenu.querySelectorAll('[data-lang-option]').forEach((link) => {
      link.addEventListener('click', () => closeLang());
    });

    document.addEventListener('click', (ev) => {
      if (!ev.target.closest('[data-lang-switch]')) {
        closeLang();
      }
    });

    document.addEventListener('keydown', (ev) => {
      if (ev.key === 'Escape') closeLang();
    });
  }

  const videoModal = document.querySelector('[data-video-modal]');
  const videoFrame = document.querySelector('[data-video-frame]');
  const videoOpenBtn = document.querySelector('[data-video-open]');

  const closeVideoModal = () => {
    if (!videoModal || !videoFrame) return;
    videoModal.setAttribute('hidden', '');
    document.body.classList.remove('modal-open');
    videoFrame.innerHTML = '';
  };

  if (videoOpenBtn && videoModal && videoFrame) {
    videoOpenBtn.addEventListener('click', () => {
      const src = videoOpenBtn.getAttribute('data-video-src');
      if (!src) return;
      videoFrame.innerHTML = `<iframe src="${src}" title="Видео о ресторане" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>`;
      videoModal.removeAttribute('hidden');
      document.body.classList.add('modal-open');
    });

    videoModal.querySelectorAll('[data-video-close]').forEach((el) => {
      el.addEventListener('click', closeVideoModal);
    });
  }

  const lightbox = document.querySelector('[data-lightbox]');
  const lightboxImage = document.querySelector('[data-lightbox-image]');
  const lightboxCaption = document.querySelector('[data-lightbox-caption]');
  const lightboxItems = Array.from(document.querySelectorAll('[data-lightbox-item]'));
  let lightboxIndex = 0;

  const openLightbox = (index) => {
    if (!lightbox || !lightboxImage || !lightboxItems.length) return;
    lightboxIndex = (index + lightboxItems.length) % lightboxItems.length;
    const item = lightboxItems[lightboxIndex];
    lightboxImage.src = item.getAttribute('data-src') || '';
    lightboxImage.alt = item.getAttribute('data-title') || '';
    if (lightboxCaption) {
      lightboxCaption.textContent = item.getAttribute('data-title') || '';
    }
    lightbox.removeAttribute('hidden');
    document.body.classList.add('modal-open');
  };

  const closeLightbox = () => {
    if (!lightbox || !lightboxImage) return;
    lightbox.setAttribute('hidden', '');
    document.body.classList.remove('modal-open');
    lightboxImage.src = '';
  };

  if (lightbox && lightboxItems.length) {
    lightboxItems.forEach((item, index) => {
      item.addEventListener('click', () => openLightbox(index));
    });

    lightbox.querySelectorAll('[data-lightbox-close]').forEach((el) => {
      el.addEventListener('click', closeLightbox);
    });

    const prev = lightbox.querySelector('[data-lightbox-prev]');
    const next = lightbox.querySelector('[data-lightbox-next]');
    if (prev) prev.addEventListener('click', () => openLightbox(lightboxIndex - 1));
    if (next) next.addEventListener('click', () => openLightbox(lightboxIndex + 1));
  }

  const galleryFilters = document.querySelector('[data-gallery-filters]');
  if (galleryFilters) {
    galleryFilters.querySelectorAll('[data-gallery-filter]').forEach((chip) => {
      chip.addEventListener('click', () => {
        const album = chip.getAttribute('data-gallery-filter') || 'all';
        const url = new URL(window.location.href);
        if (album === 'all') {
          url.searchParams.delete('album');
        } else {
          url.searchParams.set('album', album);
        }
        window.location.href = url.toString();
      });
    });
  }

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeVideoModal();
      closeLightbox();
    }

    if (!lightbox || lightbox.hasAttribute('hidden')) return;

    if (event.key === 'ArrowLeft') {
      openLightbox(lightboxIndex - 1);
    }
    if (event.key === 'ArrowRight') {
      openLightbox(lightboxIndex + 1);
    }
  });

  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const revealItems = document.querySelectorAll('[data-reveal]');

  if (reduceMotion || !('IntersectionObserver' in window)) {
    revealItems.forEach((el) => el.classList.add('is-visible'));
  } else {
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            const el = entry.target;
            const siblings = el.parentElement
              ? Array.from(el.parentElement.querySelectorAll(':scope > [data-reveal]'))
              : [];
            const idx = Math.max(0, siblings.indexOf(el));
            el.style.transitionDelay = `${Math.min(idx, 6) * 0.07}s`;
            el.classList.add('is-visible');
            observer.unobserve(el);
          }
        });
      },
      { threshold: 0.1, rootMargin: '0px 0px -8% 0px' }
    );

    revealItems.forEach((el) => observer.observe(el));
  }

  /* Platform interactions */
  const baseUrl = document.body.getAttribute('data-base') || '';
  const csrfMeta = document.querySelector('meta[name="csrf-token"]');
  const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

  const setCount = (selector, value) => {
    document.querySelectorAll(selector).forEach((el) => {
      const n = Number(value) || 0;
      el.textContent = String(n);
      if (n > 0) el.removeAttribute('hidden');
      else el.setAttribute('hidden', '');
    });
  };

  const postJson = async (url, payload) => {
    const body = new URLSearchParams({ ...payload, csrf_token: csrfToken || '' });
    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
      },
      body,
      credentials: 'same-origin',
      cache: 'no-store'
    });
    const ct = (res.headers.get('content-type') || '').toLowerCase();
    if (!ct.includes('application/json')) {
      throw new Error('not_json');
    }
    return res.json();
  };

  const flashBtn = (btn, text, ms = 1200) => {
    if (!btn) return;
    const prev = btn.textContent;
    btn.textContent = text;
    btn.disabled = true;
    setTimeout(() => {
      btn.textContent = prev;
      btn.disabled = false;
    }, ms);
  };

  document.querySelectorAll('[data-fav-form]').forEach((form) => {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const dishId = form.querySelector('[name="dish_id"]')?.value;
      try {
        const data = await postJson(baseUrl + '/api/favorites.php', { action: 'toggle', dish_id: dishId });
        if (!data || !data.ok) return;
        const btn = form.querySelector('[data-fav-btn]');
        if (btn) {
          btn.classList.toggle('is-active', !!data.favorited);
          btn.setAttribute('aria-pressed', data.favorited ? 'true' : 'false');
          const path = btn.querySelector('path');
          if (path) path.setAttribute('fill', data.favorited ? 'currentColor' : 'none');
        }
        if (typeof data.count !== 'undefined') setCount('[data-fav-count]', data.count);
      } catch (_) {
        form.submit();
      }
    });
  });

  document.querySelectorAll('[data-cart-form]').forEach((form) => {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      e.stopPropagation();
      const btn = form.querySelector('[data-add-cart]');
      const fd = new FormData(form);
      if (btn) btn.disabled = true;
      try {
        const data = await postJson(baseUrl + '/api/cart.php', {
          action: fd.get('action') || 'add',
          dish_id: fd.get('dish_id'),
          quantity: fd.get('quantity') || 1
        });
        if (!data || !data.ok) {
          flashBtn(btn, '!');
          form.submit();
          return;
        }
        if (typeof data.count !== 'undefined') setCount('[data-cart-count]', data.count);
        flashBtn(btn, '✓');
      } catch (_) {
        form.submit();
      }
    }, { passive: false });
  });
  /* Search suggestions with debounce */
  const searchInput = document.querySelector('[data-search-input]');
  const searchSuggest = document.querySelector('[data-search-suggest]');
  let searchTimer = null;
  if (searchInput && searchSuggest) {
    const hideSuggest = () => searchSuggest.setAttribute('hidden', '');
    searchInput.addEventListener('input', () => {
      const q = searchInput.value.trim();
      clearTimeout(searchTimer);
      if (q.length < 2) { hideSuggest(); return; }
      searchTimer = setTimeout(async () => {
        try {
          const res = await fetch(baseUrl + '/api/search.php?q=' + encodeURIComponent(q), {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
          });
          const data = await res.json();
          const items = (data && data.items) ? data.items : [];
          if (!items.length) {
            searchSuggest.innerHTML = '<div style="padding:0.8rem">—</div>';
            searchSuggest.removeAttribute('hidden');
            return;
          }
          searchSuggest.innerHTML = items.map((it) =>
            `<a role="option" href="${baseUrl}/dish.php?slug=${encodeURIComponent(it.slug)}">${it.name}</a>`
          ).join('') + `<a href="${baseUrl}/menu.php?q=${encodeURIComponent(q)}">…</a>`;
          searchSuggest.removeAttribute('hidden');
        } catch (_) { hideSuggest(); }
      }, 280);
    });
    document.addEventListener('click', (ev) => {
      if (!ev.target.closest('[data-header-search]')) hideSuggest();
    });
    searchInput.addEventListener('keydown', (ev) => {
      if (ev.key === 'Enter') {
        ev.preventDefault();
        const q = searchInput.value.trim();
        window.location.href = baseUrl + '/menu.php?q=' + encodeURIComponent(q);
      }
    });
  }

  /* User menu — closes on outside click / Escape */
  const userToggle = document.querySelector('[data-user-toggle]');
  const userDropdown = document.querySelector('[data-user-dropdown]');
  if (userToggle && userDropdown) {
    const closeUser = () => {
      userToggle.setAttribute('aria-expanded', 'false');
      userDropdown.setAttribute('hidden', '');
    };
    userToggle.addEventListener('click', (ev) => {
      ev.stopPropagation();
      const open = userToggle.getAttribute('aria-expanded') === 'true';
      if (open) closeUser();
      else {
        userToggle.setAttribute('aria-expanded', 'true');
        userDropdown.removeAttribute('hidden');
      }
    });
    document.addEventListener('click', (ev) => {
      if (!ev.target.closest('[data-user-menu]')) closeUser();
    });
    document.addEventListener('keydown', (ev) => {
      if (ev.key === 'Escape') closeUser();
    });
  }

  /* Mobile nav body scroll lock */
  if (toggle && mobileNav) {
    const syncBody = () => {
      const open = toggle.getAttribute('aria-expanded') === 'true';
      document.body.classList.toggle('nav-open', open);
      toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    };
    toggle.addEventListener('click', syncBody);
    mobileNav.querySelectorAll('a').forEach((a) => a.addEventListener('click', () => {
      document.body.classList.remove('nav-open');
    }));
  }

  /* WhatsApp float: notification + tip on every page load */
  const waFloat = document.querySelector('[data-wa-float]');
  if (waFloat) {
    const showNotify = () => {
      waFloat.classList.add('is-notified', 'is-tip-open');
    };
    const hideTip = () => {
      waFloat.classList.remove('is-tip-open');
    };
    window.setTimeout(showNotify, 600);
    window.setTimeout(hideTip, 6500);
    waFloat.addEventListener('click', () => {
      waFloat.classList.remove('is-notified', 'is-tip-open');
    });
  }

  /* Checkout: delivery toggle + geolocation address */
  const checkoutForm = document.querySelector('[data-checkout-form]');
  if (checkoutForm) {
    const addressBlock = checkoutForm.querySelector('[data-address-block]');
    const addressInput = checkoutForm.querySelector('[data-address-input]');
    const geoBtn = checkoutForm.querySelector('[data-geo-btn]');
    const geoStatus = checkoutForm.querySelector('[data-geo-status]');
    const deliveryToggles = checkoutForm.querySelectorAll('[data-delivery-toggle]');

    const syncDelivery = () => {
      const selected = checkoutForm.querySelector('input[name="delivery_type"]:checked');
      const isDelivery = !selected || selected.value === 'delivery';
      if (addressBlock) addressBlock.classList.toggle('is-hidden', !isDelivery);
      if (addressInput) addressInput.required = isDelivery;
    };

    deliveryToggles.forEach((el) => el.addEventListener('change', syncDelivery));
    syncDelivery();

    if (geoBtn && addressInput) {
      geoBtn.addEventListener('click', () => {
        if (!navigator.geolocation) {
          if (geoStatus) {
            geoStatus.hidden = false;
            geoStatus.textContent = geoBtn.getAttribute('data-geo-fail') || '';
          }
          return;
        }
        if (geoStatus) {
          geoStatus.hidden = false;
          geoStatus.textContent = geoBtn.getAttribute('data-geo-loading') || '';
        }
        geoBtn.disabled = true;
        navigator.geolocation.getCurrentPosition(async (pos) => {
          try {
            const { latitude, longitude } = pos.coords;
            const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${encodeURIComponent(latitude)}&lon=${encodeURIComponent(longitude)}&accept-language=ru`;
            const res = await fetch(url, { headers: { Accept: 'application/json' } });
            const data = await res.json();
            const addr = data && (data.display_name || '');
            addressInput.value = addr || `${latitude.toFixed(5)}, ${longitude.toFixed(5)}`;
            if (geoStatus) geoStatus.textContent = geoBtn.getAttribute('data-geo-ok') || '';
          } catch (_) {
            if (geoStatus) geoStatus.textContent = geoBtn.getAttribute('data-geo-fail') || '';
          } finally {
            geoBtn.disabled = false;
          }
        }, () => {
          if (geoStatus) geoStatus.textContent = geoBtn.getAttribute('data-geo-fail') || '';
          geoBtn.disabled = false;
        }, { enableHighAccuracy: true, timeout: 12000 });
      });
    }
  }

})();

