document.addEventListener('DOMContentLoaded', function () {
    var giftAccordion = document.getElementById('ll-gift-accordion');
    var giftCheckbox  = document.getElementById('ll-is-gift');
    var optionsBlock  = document.getElementById('ll-gift-options');
    var priceNote     = document.getElementById('ll-gift-price-note');
    if (!giftAccordion || !giftCheckbox || !optionsBlock) return;

    // Limits and price labels (already in the visitor's currency) come from
    // ribbon-customize.php as window.LLGift; fallbacks only if it's missing.
    var config          = window.LLGift || {};
    var MAX_LINES       = config.maxLines      || 3;
    var MAX_LINE_LENGTH = config.maxLineLength || 20;
    var PRICE_LABELS    = config.priceLabels   || ['+\u20ac5', '+\u20ac4', '+\u20ac3'];
    var TOTAL_LABELS    = config.totalLabels   || ['+\u20ac5', '+\u20ac9', '+\u20ac12'];

    var weekToggles = optionsBlock.querySelectorAll('.ll-accordion-toggle');
    var fieldInputs = optionsBlock.querySelectorAll('.ll-field-input');

    // Chronological order weeks were actually enabled in — NOT week number.
    // "1st added" is whichever week the customer ticked first, wherever it
    // sits in the list; that one gets €5, the 2nd ticked gets €4, 3rd gets €3.
    var fillOrder = [];

    function weekNumberOf(toggle) {
        var accordion = toggle.closest('.ll-accordion');
        return accordion ? parseInt(accordion.getAttribute('data-week'), 10) : null;
    }

    // Ticking a box adds it to the end of fillOrder (if not already there);
    // unticking removes it, sliding any later entries down to fill the gap
    // so remaining messages keep their relative order.
    function setWeekChecked(toggle, checked) {
        var week = weekNumberOf(toggle);
        toggle.checked = checked;
        var idx = fillOrder.indexOf(week);
        if (checked) {
            if (idx === -1) fillOrder.push(week);
        } else if (idx !== -1) {
            fillOrder.splice(idx, 1);
        }
    }

    // Every filled week shows the tier price for WHEN it was added (its
    // "original" price, stable in fillOrder). Every still-empty week shows
    // the same next-available tier price, since whichever one gets filled
    // next will cost that amount.
    function updateWeekPrices() {
        weekToggles.forEach(function (toggle) {
            var accordion = toggle.closest('.ll-accordion');
            var priceEl = accordion ? accordion.querySelector('.ll-accordion-price') : null;
            if (!priceEl) return;
            var week = weekNumberOf(toggle);
            var idx = fillOrder.indexOf(week);
            var label = toggle.checked && idx !== -1
                ? PRICE_LABELS[idx]
                : PRICE_LABELS[fillOrder.length];
            if (label) priceEl.textContent = label;
        });
        return fillOrder.length;
    }

    function updatePriceNote() {
        var count = updateWeekPrices();
        if (priceNote) {
            priceNote.textContent = count > 0
                ? (TOTAL_LABELS[count - 1] + ' for ' + count + ' personal message' + (count > 1 ? 's' : ''))
                : '';
        }
    }

    weekToggles.forEach(function (toggle) {
        var accordion = toggle.closest('.ll-accordion');
        toggle.addEventListener('change', function () {
            setWeekChecked(toggle, toggle.checked);
            if (accordion) accordion.classList.toggle('is-open', toggle.checked);
            if (!toggle.checked) clearWeekPreview(weekNumberOf(toggle));
            updatePriceNote();
            refreshPreviewButton();
        });
    });

    function hasContent(accordion) {
        if (!accordion) return false;
        var fields = accordion.querySelectorAll('.ll-field-input');
        for (var i = 0; i < fields.length; i++) {
            if (fields[i].value.trim() !== '') return true;
        }
        return false;
    }

    // Keeps the accordion's open/closed state in sync with the gift checkbox.
    // Needed on load too, not just on change: a browser refresh restores the
    // checkbox's checked state natively, but not the .is-open class I add via
    // JS, so without this the box could show ticked-but-collapsed after F5.
    function syncGiftAccordionOpenState() {
        giftAccordion.classList.toggle('is-open', giftCheckbox.checked);
        if (!giftCheckbox.checked) {
            weekToggles.forEach(function (t) {
                setWeekChecked(t, false);
                clearWeekPreview(weekNumberOf(t));
                var accordion = t.closest('.ll-accordion');
                if (accordion) accordion.classList.remove('is-open');
            });
        } else {
            // Text typed earlier is still sitting in the fields — re-tick and
            // re-open whichever weeks actually have something in them, so the
            // customer sees it again instead of having to re-tick each one.
            // Original entry order isn't recoverable across a full uncheck, so
            // this re-adds them to fillOrder in Week order as a reasonable default.
            weekToggles.forEach(function (t) {
                var accordion = t.closest('.ll-accordion');
                if (hasContent(accordion)) {
                    setWeekChecked(t, true);
                    accordion.classList.add('is-open');
                }
            });
        }
        updatePriceNote();
        refreshPreviewButton();
    }

    giftCheckbox.addEventListener('change', syncGiftAccordionOpenState);
    syncGiftAccordionOpenState();

    // Word-wraps at MAX_LINE_LENGTH like a fixed-width text editor, and caps
    // the result at MAX_LINES. overflowed is true when there was more wrapped
    // content than MAX_LINES could hold — the box is then genuinely full,
    // even if the kept text's raw character count is short of `max` (a
    // dropped line can leave the surviving lines shorter than 20 chars each).
    function wordWrap(text) {
        var rawLines = text.split('\n');
        var wrapped = [];

        rawLines.forEach(function (line) {
            while (line.length > MAX_LINE_LENGTH) {
                var breakAt = line.lastIndexOf(' ', MAX_LINE_LENGTH);
                if (breakAt <= 0) breakAt = MAX_LINE_LENGTH;
                wrapped.push(line.slice(0, breakAt).replace(/\s+$/, ''));
                line = line.slice(breakAt).replace(/^\s+/, '');
            }
            wrapped.push(line);
        });

        return {
            text: wrapped.slice(0, MAX_LINES).join('\n'),
            overflowed: wrapped.length > MAX_LINES
        };
    }

    fieldInputs.forEach(function (field) {
        var counter = field.parentElement.querySelector('.ll-field-count');
        var max = counter ? (parseInt(counter.getAttribute('data-max'), 10) || MAX_LINES * MAX_LINE_LENGTH) : MAX_LINES * MAX_LINE_LENGTH;

        var lastGoodValue     = field.value;
        var lastGoodSelection = field.value.length;

        function renderCounter(length) {
            if (!counter) return;
            var remaining = Math.max(0, max - length);
            counter.textContent = remaining + ' character' + (remaining === 1 ? '' : 's') + ' left';
            counter.classList.toggle('ll-shown', length >= 20);
            counter.classList.toggle('is-near-limit', remaining === 0);
        }

        function updateCount() {
            var oldValue = field.value;

            // Typing/pasting purely onto the END of what was already there
            // (lastGoodValue is an exact prefix of the new value) can only
            // ever affect the freshly-added tail when it gets wrapped —
            // every earlier line is untouched, so truncating the overflow is
            // safe. An edit ANYWHERE else (the middle of line 2, say) that
            // pushes the total past 3 lines is different: wordWrap doesn't
            // know that content is "new" vs "already there", so it would
            // silently drop whatever's currently on the last line(s) to make
            // room — content the customer already typed, somewhere they
            // aren't even looking. Refuse that edit outright instead.
            var isPureAppend = oldValue.indexOf(lastGoodValue) === 0;
            var wrapped = wordWrap(oldValue);

            if (wrapped.overflowed && !isPureAppend) {
                field.value = lastGoodValue;
                field.setSelectionRange(lastGoodSelection, lastGoodSelection);
                return; // nothing changed — leave the counter exactly as it was
            }

            if (wrapped.text !== oldValue) {
                // A reflow can shift every word boundary that follows the
                // edit, so trying to preserve a precise mid-text cursor
                // position is more trouble than it's worth here — anchoring
                // to the end is simple, predictable, and matches how people
                // actually fill in a short message field (sequentially).
                field.value = wrapped.text;
                field.setSelectionRange(wrapped.text.length, wrapped.text.length);
            }

            lastGoodValue     = field.value;
            lastGoodSelection = field.selectionStart;
            // Always the true count of what's actually in the field — never
            // forced to zero just because some excess text got dropped
            // beyond it; that misrepresents genuine remaining room as none.
            renderCounter(field.value.length);
        }

        field.addEventListener('input', updateCount);
        updateCount();
    });

    // ── Single "see it on the ribbon" preview trigger ──
    // Appears only once a CHECKED week has a field that's been blurred
    // non-empty, and disappears the moment that no longer holds — whether
    // because every week got unchecked, or because the only touched field
    // belonged to a week that's since been unticked.
    var previewBtn = document.getElementById('ll-preview-ribbon-btn');
    var touchedFields = new Set();

    // A field can already hold text the moment the page loads — most notably
    // when arriving via the cart's product link for an already-customized
    // ribbon (LL_get_prefill_gift_data() server-renders the saved message
    // straight into the textarea). That's just as much "real content" as
    // anything typed in this session, so the preview link should be visible
    // immediately rather than waiting for a blur that will never happen.
    fieldInputs.forEach(function (field) {
        if (field.value.trim() !== '') touchedFields.add(field);
    });

    function clearWeekPreview(week) {
        if (window.LLBoardPreview && typeof window.LLBoardPreview.clear === 'function') {
            window.LLBoardPreview.clear((week - 1) * 7);
        }
    }

    function anyCheckedWeekHasTouchedContent() {
        for (var i = 0; i < weekToggles.length; i++) {
            var toggle = weekToggles[i];
            if (!toggle.checked) continue;
            var accordion = toggle.closest('.ll-accordion');
            var fields = accordion.querySelectorAll('.ll-field-input');
            for (var j = 0; j < fields.length; j++) {
                if (touchedFields.has(fields[j]) && fields[j].value.trim() !== '') return true;
            }
        }
        return false;
    }

    function refreshPreviewButton() {
        if (!previewBtn) return;
        previewBtn.classList.toggle('ll-shown', anyCheckedWeekHasTouchedContent());
    }

    fieldInputs.forEach(function (field) {
        field.addEventListener('blur', function () {
            if (field.value.trim() !== '') {
                touchedFields.add(field);
            } else {
                touchedFields.delete(field);
            }
            refreshPreviewButton();
        });
    });

    function escapeHtml(str) {
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    if (previewBtn) {
        previewBtn.addEventListener('click', function () {
            var preview = window.LLBoardPreview;
            if (!preview || typeof preview.reveal !== 'function') return;

            // Insert every filled week first — each insert shifts the days after
            // it — then scroll to the earliest one. Week order (1, 2, 3) is also
            // ribbon order, so the first filled week is the first one met.
            var firstDay = null;
            weekToggles.forEach(function (toggle) {
                if (!toggle.checked) return;
                var accordion  = toggle.closest('.ll-accordion');
                var day        = (weekNumberOf(toggle) - 1) * 7;
                var leftField  = accordion.querySelector('textarea[name$="[left]"]');
                var rightField = accordion.querySelector('textarea[name$="[right]"]');
                var left  = leftField  ? leftField.value.trim()  : '';
                var right = rightField ? rightField.value.trim() : '';

                if (!left && !right) { preview.clear(day); return; }
                preview.show(day, { left: escapeHtml(left), right: escapeHtml(right) });
                if (firstDay === null) firstDay = day;
            });
            if (firstDay !== null) preview.reveal(firstDay);
        });
    }

    refreshPreviewButton();
});
