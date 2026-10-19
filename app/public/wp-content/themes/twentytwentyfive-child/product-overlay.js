// Product overlay: ribbon/buy cards open the product page in a full-screen
// iframe (?overlay_mode=1). ?view=<slug> in the page URL reopens it.
jQuery(function ($) {
    function closeOverlay() {
        var clean = new URL(window.location.href);
        clean.searchParams.delete('view');
        history.replaceState(null, '', clean);
        $('.product-overlay-container').remove();
        $('html, body').removeClass('ll-scroll-lock');
    }

    $(document.body).on('click', '.ll-buy-card, .ll-ribbon-card:not(.is-soon)', function (e) {
        e.preventDefault();

        var productUrl = new URL($(this).data('product-url'), window.location.origin);
        var overlayUrl = new URL(productUrl.pathname + productUrl.search, window.location.origin);
        overlayUrl.searchParams.set('overlay_mode', '1');

        var $iframe = $('<iframe>').attr('src', overlayUrl.href);
        $('<div class="product-overlay-container"></div>')
            .append($iframe, '<button class="ll-x wp-element-button" aria-label="Close">&times;</button>')
            .appendTo('body');
        $('html, body').addClass('ll-scroll-lock');

        // The iframe loads a second time only when its add-to-cart form was
        // submitted: close and let the mini cart refresh.
        var initialLoad = true;
        $iframe.on('load', function () {
            if (initialLoad) { initialLoad = false; return; }
            closeOverlay();
            setTimeout(function () { $(document.body).trigger('added_to_cart', [[]]); }, 300);
        });

        var slug    = productUrl.pathname.replace(/^\/|\/?$/g, '').split('/').pop();
        var withView = new URL(window.location.href);
        withView.searchParams.set('view', slug);
        history.pushState({ overlay: slug }, '', withView);
    });

    $(document.body).on('click', '.product-overlay-container .ll-x', closeOverlay);
    $(document).on('keyup', function (e) { if (e.key === 'Escape' && $('.product-overlay-container').length) closeOverlay(); });
    window.addEventListener('popstate', function () {
        if ($('.product-overlay-container').length) closeOverlay();
    });

    var viewParam = new URLSearchParams(window.location.search).get('view');
    if (viewParam) {
        $('.ll-buy-card, .ll-ribbon-card:not(.is-soon)').filter(function () {
            return ($(this).data('product-url') || '').indexOf(viewParam) !== -1;
        }).first().trigger('click');
    }
});
