/* ribbon3d.js — scroll-driven 3D ribbon boards.
 *
 * Markup: LL_get_multi_animation_markup() in ribbon3d.php. Styles: ribbon3d.css.
 * One wrapper holds 1–3 left/right board pairs that turn in lockstep while the
 * sticky stage is pinned. Progress is read from the wrapper's position in the
 * viewport: turning starts the moment the stage pins (whole stage on screen)
 * and rests briefly on the last day before the stage scrolls away.
 *
 * Two layouts, chosen from the stage size (layout()):
 *   rows    — pairs stacked top to bottom, each pair's boards side by side;
 *   stacked — pairs side by side as columns, each pair's boards one above the
 *             other (single board on narrow screens; 2–3 pairs on short,
 *             wide screens such as a phone in landscape). */

(function () {
    'use strict';

    /* Share of the pinned travel spent resting on the last day. */
    const END_HOLD = 0.05;
    /* Natural sizes at scale 1 (px) — keep in step with ribbon3d.css. */
    const ROW_H         = 438; // rows layout: one pair, boards side by side
    const BOARD_W       = 350; //   board width in rows layout
    const STACK_H       = 480; // stacked layout: one pair, boards one above the other
    const STACK_BOARD_W = 280; //   board width in stacked layout
    const EDGE          = 40;  // breathing room beside a board
    /* Rows until their boards would drop below this scale, then stacked if bigger. */
    const MIN_ROWS_SCALE = 0.45;
    const MIN_SCALE      = 0.2;
    /* Face font cap for a full-size (350px) face; scales with the face. */
    const FACE_FULL_W      = 350;
    const FONT_MAX_AT_FULL = 30;

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    function isImageMsg(msg) {
        return !!msg && /^\s*<img\b/i.test(msg);
    }

    /* Write a face only when its message changes (compared against what we
     * wrote, not innerHTML, which the browser re-serialises). */
    function setFace(face, msg) {
        if (!face || face.llMsg === msg) return;
        face.llMsg = msg;
        face.innerHTML = msg;
        face.classList.toggle('face--image', isImageMsg(msg));
    }

    /* One half-turn per day after the first; messages come in left/right pairs. */
    function rotationBudget(pairData) {
        return pairData.reduce(
            (max, pd) => Math.max(max, Math.floor((pd.messages.length - 2) / 2)), 1
        ) * 180;
    }

    /* Largest font at which the longest text message fits a face. */
    function fitFont(pd) {
        const faces = pd.faces.filter(Boolean);
        const ref = faces[0];
        if (!ref) return;

        let longest = '';
        pd.messages.forEach(msg => {
            if (!msg || isImageMsg(msg)) return;
            const plain = msg.replace(/<[^>]*>/g, '');
            if (plain.length > longest.length) longest = plain;
        });
        if (!longest) return;

        const cs    = getComputedStyle(ref);
        const faceW = ref.clientWidth;
        const faceH = ref.clientHeight;
        if (faceW < 20 || faceH < 20) return;
        const availW = Math.max(20, faceW - parseFloat(cs.paddingLeft) - parseFloat(cs.paddingRight));
        const availH = Math.max(10, faceH - parseFloat(cs.paddingTop)  - parseFloat(cs.paddingBottom));
        const maxSize = Math.max(14, Math.round(FONT_MAX_AT_FULL * faceW / FACE_FULL_W));
        /* Unitless ratio: computed line-height is in px at the face's CURRENT
         * size, which would make every fit depend on the previous one. */
        const lineRatio = (parseFloat(cs.lineHeight) / parseFloat(cs.fontSize)) || 1.4;

        const probe = document.createElement('div');
        probe.style.cssText =
            'position:absolute;visibility:hidden;pointer-events:none;' +
            'white-space:pre-wrap;overflow-wrap:break-word;text-align:center;' +
            'box-sizing:border-box;width:' + availW + 'px;' +
            'font-family:' + cs.fontFamily + ';font-weight:' + cs.fontWeight +
            ';line-height:' + lineRatio + ';';
        probe.textContent = longest;
        document.body.appendChild(probe);

        let size = 10;
        for (let sz = 10; sz <= maxSize; sz++) {
            probe.style.fontSize = sz + 'px';
            if (probe.scrollHeight > availH) break;
            size = sz;
        }
        probe.remove();

        faces.forEach(face => { face.style.fontSize = size + 'px'; });
    }

    function setupMultiWrapper(wrapper) {
        if (wrapper.dataset.llMultiInit === '1') return;
        wrapper.dataset.llMultiInit = '1';

        const stage = wrapper.querySelector('.sticky-wrapper');
        const pairs = Array.from(wrapper.querySelectorAll('.ll-multi-pair'));
        if (!stage || !pairs.length) return;

        const pairData = pairs.map(pair => {
            let messages = [];
            try { messages = JSON.parse(pair.dataset.messages || '[]'); } catch (e) {}
            const q = sel => pair.querySelector(sel);
            const pd = {
                messages,
                originalMessages: messages.slice(), // pristine copy — gift previews are rebuilt from it
                activeInserts: {},
                boards    : Array.from(pair.querySelectorAll('.ll-board')),
                polys     : Array.from(pair.querySelectorAll('.ll-trap-poly')),
                leftFront : q('.ll-face-front'),
                leftBack  : q('.ll-face-back'),
                rightFront: q('.ll-face-right-front'),
                rightBack : q('.ll-face-right-back'),
            };
            pd.faces = [pd.leftFront, pd.leftBack, pd.rightFront, pd.rightBack];
            return pd;
        });

        let maxRotation  = rotationBudget(pairData);
        let lastRotation = null;
        let travelPx     = 1;
        let lastW = 0, lastH = 0;

        /* progress (0–1 of pinned travel) ⇄ rotation (degrees). */
        function rotationAt(progress) {
            const active = Math.max(0, Math.min(1, progress / (1 - END_HOLD)));
            return Math.min(active * maxRotation, maxRotation - 0.001);
        }
        function progressFor(rotation) {
            return Math.max(0, Math.min(1, rotation / maxRotation)) * (1 - END_HOLD);
        }

        /* ── Draw one rotation state: board turn, ribbon tail, face contents. ── */
        function render(rotation) {
            lastRotation = rotation;

            const transform = `perspective(1200px) rotateX(${rotation % 360}deg)`;

            /* Ribbon tail shortens and widens at the top as the roll unwinds. */
            const t    = rotation / maxRotation;
            const topW = 111 + t * 88;
            const off  = (222 - topW) / 2;
            const y    = t * 222;
            const points = `${off},${y} ${off + topW},${y} 222,333 0,333`;

            const step    = Math.floor(rotation / 180);
            const flipped = step % 2 === 1;

            pairData.forEach(pd => {
                pd.boards.forEach(b => { b.style.transform = transform; });
                pd.polys.forEach(p => p.setAttribute('points', points));

                const m = pd.messages, total = m.length;
                if (!total) return;

                const pairIdx = Math.min(step, Math.max(0, Math.floor((total - 2) / 2)));
                const l = Math.min(pairIdx * 2,     total - 2);
                const r = Math.min(pairIdx * 2 + 1, total - 1);
                const lCur = m[l] || '', lNxt = m[Math.min(l + 2, total - 1)] || '';
                const rCur = m[r] || '', rNxt = m[Math.min(r + 2, total - 1)] || '';

                /* The visible side alternates every half-turn. */
                setFace(flipped ? pd.leftBack   : pd.leftFront,  lCur);
                setFace(flipped ? pd.leftFront  : pd.leftBack,   lNxt);
                setFace(flipped ? pd.rightBack  : pd.rightFront, rCur);
                setFace(flipped ? pd.rightFront : pd.rightBack,  rNxt);
            });
        }

        function onScroll() {
            const rect = wrapper.getBoundingClientRect();
            if (rect.bottom < 0 || rect.top > window.innerHeight) return;
            const rotation = rotationAt(-rect.top / travelPx);
            if (rotation !== lastRotation) render(rotation);
        }

        function redraw() {
            lastRotation = null;
            onScroll();
        }

        /* ── Layout: pick rows or stacked, scale everything so every pair fits
         *    the stage (height AND width), cache the pinned travel. The stage
         *    is sized in svh, so mobile toolbars sliding don't re-trigger it. ── */
        function layout(force) {
            const w = stage.clientWidth;
            const h = stage.clientHeight;
            if (!force && w === lastW && h === lastH) return;
            lastW = w; lastH = h;

            const cs     = getComputedStyle(stage);
            const n      = pairs.length;
            const gaps   = (parseFloat(cs.rowGap) || 0) * (n - 1); // row and column gap are equal
            const availH = h - (parseFloat(cs.paddingTop) || 0) - (parseFloat(cs.paddingBottom) || 0);

            const rowsScale  = Math.min(1, (availH - gaps) / n / ROW_H, (w / 2 - EDGE) / BOARD_W);
            const stackScale = Math.min(1, availH / STACK_H, ((w - gaps) / n - EDGE) / STACK_BOARD_W);
            /* A single board stacks below the CSS breakpoint (--ll-prefer-stacked). */
            const preferStacked = n === 1 &&
                getComputedStyle(wrapper).getPropertyValue('--ll-prefer-stacked').trim() === '1';
            const stacked = preferStacked || (rowsScale < MIN_ROWS_SCALE && stackScale > rowsScale);
            const scale   = Math.max(MIN_SCALE, stacked ? stackScale : rowsScale);

            wrapper.classList.toggle('is-stacked', stacked);
            pairs.forEach(pair => {
                pair.style.setProperty('--ll-pair-scale', scale);
                pair.style.setProperty('--ll-pair-height', (stacked ? STACK_H : ROW_H) * scale + 'px');
            });

            travelPx = Math.max(wrapper.offsetHeight - h, 1);

            requestAnimationFrame(() => {
                pairData.forEach(fitFont);
                redraw();
            });
        }

        /* ── Gift-message live preview API ──
         * The physical product ADDS a bonus segment before day 1/8/15 — it never
         * overwrites that day, and everything after shifts one slot later. Each
         * active preview is keyed by the ORIGINAL day index it inserts before,
         * and the messages are rebuilt from the pristine original on every
         * change. Session-local only — the saved _board_messages meta is never
         * touched. Targets pairData[0]: gift customisation only runs on simple
         * (single-board) product pages. */
        function rebuild(pd) {
            const base    = pd.originalMessages;
            const dayIdxs = Object.keys(pd.activeInserts).map(Number).sort((a, b) => a - b);
            const days    = Math.floor(base.length / 2);

            const result = [];
            let cursor = 0;
            const pushUntil = limit => {
                for (; cursor < limit; cursor++) result.push(base[cursor * 2], base[cursor * 2 + 1]);
            };
            dayIdxs.forEach(d => {
                pushUntil(d);
                result.push(pd.activeInserts[d].left, pd.activeInserts[d].right);
            });
            pushUntil(days);

            pd.messages = result;
            maxRotation = rotationBudget(pairData); // every insert adds a day to reach
            fitFont(pd);
            redraw();
        }

        /* Where an insert sits in the current (shifted) sequence. */
        function insertedPosition(pd, dayIndex) {
            return dayIndex + Object.keys(pd.activeInserts).map(Number).filter(d => d < dayIndex).length;
        }

        function scrollTopForRotation(rotation) {
            const docTop = wrapper.getBoundingClientRect().top + window.scrollY;
            return Math.round(docTop + progressFor(rotation) * travelPx);
        }

        function pulse() {
            pairs.forEach(pair => {
                pair.classList.add('ll-preview-pulse');
                setTimeout(() => pair.classList.remove('ll-preview-pulse'), 1400);
            });
        }

        function previewDay(dayIndex, msgs) {
            const pd = pairData[0];
            pd.activeInserts[dayIndex] = msgs; // { left, right } — '' is a deliberate blank side
            rebuild(pd);
        }

        /* Removes a previewed insert (week unticked or emptied) so the extra day
         * and the shift it caused don't linger. */
        function clearDay(dayIndex) {
            const pd = pairData[0];
            if (!(dayIndex in pd.activeInserts)) return;
            delete pd.activeInserts[dayIndex];
            rebuild(pd);
        }

        /* Scroll so the given (inserted) day faces the viewer, then pulse. Call
         * after every insert is in place — each one changes the rotation budget. */
        function revealDay(dayIndex) {
            const pd  = pairData[0];
            const top = scrollTopForRotation(insertedPosition(pd, dayIndex) * 180);

            if (Math.abs(top - window.scrollY) < 2) { pulse(); return; }
            if ('onscrollend' in window) {
                window.addEventListener('scrollend', pulse, { once: true });
            } else {
                setTimeout(pulse, 700);
            }
            window.scrollTo({ top, behavior: reduceMotion.matches ? 'auto' : 'smooth' });
        }

        const api = window.LLBoardPreview = window.LLBoardPreview || { wrappers: [] };
        api.wrappers.push({ previewDay, clearDay, revealDay });
        api.show   = api.show   || ((d, msgs) => api.wrappers[0] && api.wrappers[0].previewDay(d, msgs));
        api.clear  = api.clear  || (d => api.wrappers[0] && api.wrappers[0].clearDay(d));
        api.reveal = api.reveal || (d => api.wrappers[0] && api.wrappers[0].revealDay(d));

        /* The faces are usually the only Aleo text on the page, so the font
         * only starts downloading once they render — until it lands, the fit
         * is measured with the fallback serif and comes out too large. Re-fit
         * when it (or any later font) finishes loading. */
        function refitWhenFontsLoad() {
            if (!document.fonts) return;
            const ref = pairData[0].faces.find(Boolean);
            if (ref) {
                const cs = getComputedStyle(ref);
                document.fonts.load(`${cs.fontWeight} 16px ${cs.fontFamily}`).then(() => layout(true), () => {});
            }
            document.fonts.addEventListener('loadingdone', () => layout(true));
        }

        /* ── Bootstrap ── */
        render(0);
        layout(true);
        refitWhenFontsLoad();
        window.addEventListener('scroll', onScroll, { passive: true });

        /* Re-layout whenever the stage itself changes size (width, svh, an
         * overlay iframe settling). layout() ignores no-op calls. */
        if ('ResizeObserver' in window) {
            new ResizeObserver(() => layout(false)).observe(stage);
        } else {
            window.addEventListener('resize', () => layout(false));
        }
    }

    window.initMultiBoardAnimation = function () {
        document.querySelectorAll('.ll-multi-animation-wrapper').forEach(setupMultiWrapper);
    };

    document.addEventListener('DOMContentLoaded', window.initMultiBoardAnimation);
})();
