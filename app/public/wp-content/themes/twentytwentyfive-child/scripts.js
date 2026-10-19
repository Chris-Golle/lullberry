/* scripts.js — site-wide behaviour (handle ll-core, loaded in the footer). */

/* ── Region switcher ─────────────────────────────────────────────── */

// Animated close — shared by every close trigger.
function llCloseRegion($popup, $closeBtn) {
  $popup.addClass('ll-region-closing');
  setTimeout(function () {
    $popup.removeClass('show ll-region-closing').hide();
    $closeBtn.hide();
  }, 220);
}

function llLoadCurrencyAndRedirect(currency, targetUrl, forceSwitch) {
  if (targetUrl) {
    window.history.replaceState(null, '', targetUrl);
  }
  if (currency && typeof window.wcml_load_currency === 'function') {
    window.wcml_load_currency(currency, forceSwitch || 0);
  } else if (targetUrl) {
    window.location.href = targetUrl;
  }
}

// Reveal only once the cover background image has loaded — no blank flash.
function llRevealRegionPopup($popup) {
  function show() { $popup.addClass('show').css('display', 'flex'); }

  var bgEl  = $popup.find('.wp-block-cover__image-background').get(0);
  var match = bgEl && /url\(["']?([^"')]+)["']?\)/.exec(bgEl.style.backgroundImage || '');
  if (!match) { show(); return; }

  var img = new Image();
  img.onload = img.onerror = show;
  img.src = match[1];
  if (img.complete) show();
}

function llToggleRegion() {
  var $popup    = jQuery('#change_region');
  var $closeBtn = jQuery('#close-region');
  if (!$popup.length || !$closeBtn.length) return;

  if ($popup.is(':visible')) {
    llCloseRegion($popup, $closeBtn);
    return;
  }

  if (!$popup.data('ll-enhanced')) {
    $popup.data('ll-enhanced', true);
    var $card = $popup.find('#region-content');

    // Close button lives on <body> so position:fixed anchors to the viewport.
    if (!jQuery('body').children('#close-region').length) {
      jQuery('body').append($closeBtn);
    }

    // Region chips (native wp:list block).
    var regionHref = {
      'region-USA': '?region=usa',
      'region-Europe': '?region=europe',
      'region-Canada': '?region=canada',
      'region-Australia': '?region=oz-nz'
    };
    var selectedHref = null;
    var $regionItems = $card.find('li[id^="region-"]');
    $regionItems.on('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      $regionItems.removeClass('ll-region-active');
      jQuery(this).addClass('ll-region-active');
      selectedHref = regionHref[this.id] || null;
    });
    var current = (window.llRegion || {}).name;
    if (current) $card.find('#region-' + current).addClass('ll-region-active');

    // Currency — intercept the WCML switcher links to track the selection.
    var selectedCurrency = null;
    var $switcher = $card.find('.wcml_currency_switcher');
    $switcher.find('li a[rel]').on('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      $switcher.find('li').removeClass('wcml-cs-active-currency');
      $switcher.find('a').removeClass('ll-currency-active');
      jQuery(this).addClass('ll-currency-active').closest('li').addClass('wcml-cs-active-currency');
      selectedCurrency = jQuery(this).attr('rel');
    });

    // Confirm — apply region + currency together.
    $card.find('#ll-region-confirm a').on('click', function () {
      llCloseRegion($popup, $closeBtn);
      if (selectedCurrency) {
        setTimeout(function () { llLoadCurrencyAndRedirect(selectedCurrency, selectedHref); }, 230);
      } else if (selectedHref) {
        setTimeout(function () { window.location.href = selectedHref; }, 230);
      }
    });
  }

  llRevealRegionPopup($popup);
}
window.chrisToggleRegion = llToggleRegion; // legacy name, in case block markup still calls it

/* ── Typewriter (quiz results adjectives) ────────────────────────── */

window.addDots = function (strings) {
  return strings.reduce(function (res, str) {
    if (!str) return res;
    res.push(str, ' .'.repeat(String(str).length));
    return res;
  }, []);
};

window.wordflick = function (strings) {
  if (window.wordflickInterval) clearInterval(window.wordflickInterval);
  if (!Array.isArray(strings) || !strings.length) return;
  var words = window.addDots(strings), i = 0, offset = 0, forwards = true, skip = 0, len = words.length;
  window.wordflickInterval = setInterval(function () {
    if (forwards) {
      if (offset >= words[i].length && ++skip === 15) { forwards = false; skip = 0; }
    } else if (offset === 0) {
      forwards = true; i = (i + 1) % len;
    }
    if (skip === 0) offset += forwards ? 1 : -1;
    document.querySelectorAll('.typewriter').forEach(function (el) {
      el.textContent = words[i].substring(0, offset);
    });
  }, 70);
};

/* ── Quiz results: body class follows #quiz-results / ?all ───────── */

(function () {
  function syncQuizClass() {
    document.body.classList.toggle(
      'quiz-results-active',
      window.location.hash === '#quiz-results' || window.location.search.includes('all')
    );
  }
  syncQuizClass();
  window.addEventListener('hashchange', syncQuizClass);
})();

/* ── jQuery-dependent init ───────────────────────────────────────── */

jQuery(function ($) {
  $('#close-region').on('click', function () {
    llCloseRegion($('#change_region'), $(this));
  });

  // Header region pill: "Europe · EUR".
  var $trigger = $('#ll-region-trigger');
  if ($trigger.length) {
    var llr = window.llRegion || {};
    var $link = $trigger.find('.wp-block-button__link').empty()
      .append($('<span class="ll-region-name">').text(llr.name || 'Europe'));
    if (llr.currency) {
      $link.append('<span class="ll-region-sep"> · </span>', $('<span class="ll-region-currency">').text(llr.currency));
    }
    $trigger.on('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      llToggleRegion();
    });
  }

  // Strength page: bloom / seed mode toggle.
  $(document).on('click', '.ll-toggle-btn', function () {
    var mode    = $(this).hasClass('ll-toggle--bloom') ? 'bloom' : 'seed';
    var $toggle = $(this).closest('.ll-strength-toggle');
    $toggle.find('.ll-toggle-btn').removeClass('ll-toggle-active');
    $(this).addClass('ll-toggle-active');
    $toggle.siblings('.ll-panel').removeClass('ll-mode-bloom ll-mode-seed').addClass('ll-mode-' + mode);
  });
});

/* ── Vanilla init ────────────────────────────────────────────────── */

document.addEventListener('DOMContentLoaded', function () {
  // Hamburger menu — animated close.
  var menuClose = document.querySelector('.wp-block-navigation__responsive-container-close');
  var menu      = document.querySelector('.wp-block-navigation__responsive-container');
  if (menuClose && menu) {
    menuClose.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopImmediatePropagation();
      menu.classList.add('ll-menu-closing');
      document.body.classList.add('ll-menu-closing');
      setTimeout(function () {
        menu.classList.remove('is-menu-open', 'll-menu-closing');
        document.body.classList.remove('modal-open', 'll-menu-closing');
      }, 220);
    }, true);
  }

  // Enso click-to-replay.
  var enso = document.querySelector('.ll-enso');
  var ensoPath = enso && enso.querySelector('path');
  if (ensoPath) {
    enso.addEventListener('click', function () {
      ensoPath.style.animation = 'none';
      ensoPath.getBoundingClientRect();
      ensoPath.style.animation = '';
    });
  }

  // Fade sections in as they enter the viewport (see .ll-visible in style.css).
  var reveal = document.querySelectorAll(
    'main .entry-content > .wp-block-group, ' +
    '.single-product main .wp-block-group, ' +
    '.product .wp-block-post-title, ' +
    '.product .wp-block-woocommerce-product-price, ' +
    '.product .wp-block-post-excerpt, ' +
    '.product .wp-block-add-to-cart-form, ' +
    '.product .wp-block-woocommerce-product-details'
  );
  if (reveal.length) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('ll-visible');
        io.unobserve(entry.target);
      });
    }, { threshold: 0.08 });
    reveal.forEach(function (el) { io.observe(el); });
  }

  // Ribbon library: soft fade when switching tab or opening a category.
  function fadeIn(el) {
    el.classList.add('is-fading');
    requestAnimationFrame(function () { el.classList.remove('is-fading'); });
  }
  document.querySelectorAll('.ll-tab').forEach(function (tab) {
    tab.addEventListener('click', function () {
      setTimeout(function () {
        document.querySelectorAll('.ll-cat').forEach(function (cat) {
          if (getComputedStyle(cat).display === 'none') cat.classList.remove('is-fading');
          else fadeIn(cat);
        });
      }, 0);
    });
  });
  document.querySelectorAll('.ll-cat-header').forEach(function (button) {
    button.addEventListener('click', function () {
      if (this.nextElementSibling) fadeIn(this.nextElementSibling);
    });
  });

  // Auto-dismiss cart notice after 8s.
  var notice = document.querySelector('.ll-cart-notice');
  if (notice) {
    setTimeout(function () {
      notice.style.transition = 'opacity .4s, transform .4s';
      notice.style.opacity = '0';
      notice.style.transform = 'translateX(-50%) translateY(.75rem)';
      setTimeout(function () { notice.remove(); }, 420);
    }, 8000);
  }

  // Quiz results typewriter (words rendered by [product_categories_quiz]).
  var typewriterWords = document.querySelector('.ll-typewriter-words');
  if (typewriterWords) {
    try { window.wordflick(JSON.parse(typewriterWords.dataset.words || '[]')); } catch (e) {}
  }

  // Every close button gets the shared .ll-x look (style.css) and the wood
  // plaque; WooCommerce's own buttons get the plaque too. Also for buttons
  // rendered later (mini-cart drawer, checkout).
  var CLOSE_BUTTONS = [
    '#close-region .wp-block-button__link',              // region picker
    '.wp-block-navigation__responsive-container-close',  // menu
    '.wp-block-navigation-overlay-close',
    '.wc-block-components-drawer__close',                // mini-cart drawer
    '.ll-close'                                          // legacy class in saved content
  ].join(', ');
  function tagButtons(root) {
    root.querySelectorAll(CLOSE_BUTTONS)
      .forEach(function (el) { el.classList.add('ll-x', 'wp-element-button'); });
    root.querySelectorAll('button.wc-block-components-button:not(.wp-element-button)')
      .forEach(function (el) { el.classList.add('wp-element-button'); });
  }
  tagButtons(document);
  new MutationObserver(function (mutations) {
    mutations.forEach(function (m) {
      m.addedNodes.forEach(function (node) {
        if (node.nodeType === 1) tagButtons(node);
      });
    });
  }).observe(document.body, { childList: true, subtree: true });
});
