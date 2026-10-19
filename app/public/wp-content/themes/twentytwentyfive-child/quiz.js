// quiz.js — drives the #quiz-modal markup rendered by [personality_quiz]
// (shortcodes.php). Questions/answers come from the trigger's data attributes.

class PersonalityQuiz {
    constructor() {
        this.modal    = document.getElementById('quiz-modal');
        this.active   = document.getElementById('quiz-active');
        this.complete = document.getElementById('quiz-complete');
        if (!this.modal || !this.active || !this.complete) return;

        this.questions = [];
        this.answers   = [];
        this.index     = 0;
        this.results   = '';
        this.isOpen    = false;
        this.backgroundImages = [
            '/wp-content/uploads/Lullberry-growth.jpg',
            '/wp-content/uploads/awe.jpg',
            '/wp-content/uploads/spirituality.jpg',
            '/wp-content/uploads/prudence.jpg',
        ];

        // Move modal to body to escape any transform containing-block.
        if (this.modal.parentElement !== document.body) document.body.appendChild(this.modal);

        this.active.setAttribute('tabindex', '-1');
        this.questionEl  = this.active.querySelector('.quiz-qst');
        this.eyebrowEl   = this.active.querySelector('.ll-eyebrow');
        this.answerEls   = Array.from(this.active.querySelectorAll('.wp-block-button'));
        this.starsEl     = document.getElementById('quiz-stars');
        this.readyEls    = Array.from(this.complete.querySelectorAll('.quiz-ready'));
        this.starCountEl = Array.from(this.complete.querySelectorAll('p'))
                               .find(p => p.textContent.includes('stars counted'));
        this.closeButton = this.modal.querySelector('.ll-x');

        this.complete.style.display = 'none';
        this.bindEvents();
    }

    bindEvents() {
        document.querySelectorAll('.start-quiz').forEach(btn => {
            btn.addEventListener('click', e => this.start(e.currentTarget));
        });
        if (this.closeButton) this.closeButton.addEventListener('click', () => this.close());
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape' && this.isOpen) this.close();
        });
        this.modal.addEventListener('click', e => {
            if (e.target === this.modal) this.close();
        });

        // Answers: one delegated listener instead of re-cloning buttons per question.
        this.answerEls.forEach((wrapper, i) => {
            wrapper.addEventListener('click', e => {
                if (!e.target.closest('a, button')) return;
                e.preventDefault();
                this.answer(i);
            });
        });

        const viewBtn = this.complete.querySelector('.quiz-ready a');
        if (viewBtn) {
            viewBtn.addEventListener('click', e => {
                e.preventDefault();
                this.redirectToResults();
            });
        }
    }

    start(trigger) {
        const container = trigger && trigger.closest('.personality-quiz-trigger');
        if (container) {
            this.questions = (container.dataset.questions || '').split('|||');
            this.answers   = (container.dataset.answers || '').split('|||').map(g => g.split('|'));
        }
        if (!this.questions.length || !this.questions[0]) return;

        this.index   = 0;
        this.results = '';
        this.isOpen  = true;

        this.active.style.display   = '';
        this.complete.style.display = 'none';
        this.readyEls.forEach(el => { el.style.visibility = 'hidden'; });

        this.modal.classList.add('show');
        document.body.classList.add('quiz-active');
        this.active.focus();
        this.showQuestion();
    }

    close() {
        this.isOpen = false;
        this.modal.classList.remove('show');
        document.body.classList.remove('quiz-active');
    }

    async showQuestion() {
        const src = this.backgroundImages[this.index];
        if (src) {
            await new Promise(resolve => {
                const img = new Image();
                img.onload = img.onerror = resolve;
                img.src = src;
            });
            this.active.style.backgroundImage = `url(${src})`;
        }
        this.updateProgress();

        if (this.eyebrowEl) this.eyebrowEl.textContent = (this.index + 1) + ' of ' + this.questions.length;
        if (this.questionEl) this.questionEl.innerHTML = this.questions[this.index];

        const answers = this.answers[this.index] || [];
        this.answerEls.forEach((wrapper, i) => {
            const link = wrapper.querySelector('a, button');
            if (link) link.innerHTML = answers[i] || '';
        });

        requestAnimationFrame(() => this.active.classList.remove('is-switching'));
    }

    answer(i) {
        this.results += String(i + 1);
        this.index++;
        if (this.index < this.questions.length) {
            this.active.classList.add('is-switching');
            setTimeout(() => this.showQuestion(), 200);
        } else {
            this.finish();
        }
    }

    updateProgress() {
        let bar = this.active.querySelector('.quiz-progress');
        if (!bar) {
            bar = document.createElement('div');
            bar.className = 'quiz-progress';
            this.active.appendChild(bar);
        }
        bar.style.width = `${((this.index + 1) / this.questions.length) * 100}%`;
    }

    setStarCount(html) {
        if (this.starCountEl) this.starCountEl.innerHTML = html;
    }

    finish() {
        this.complete.style.backgroundImage = this.active.style.backgroundImage;
        this.active.style.display   = 'none';
        this.complete.style.display = '';
        this.readyEls.forEach(el => { el.style.visibility = 'hidden'; });

        const pending = n => n + ' stars counted<br><span aria-hidden="true" style="visibility:hidden">your results are ready</span>';
        this.setStarCount(pending(0));
        if (!this.starsEl) return;

        // 11 stars on a half circle, revealed as the count climbs to 1000.
        const total = 11, radius = 62;
        this.starsEl.innerHTML = '';
        const stars = Array.from({ length: total }, (_, i) => {
            const angle = Math.PI - (Math.PI * i) / (total - 1);
            const star  = document.createElement('div');
            star.className   = 'zen-star';
            star.textContent = '★';
            star.style.setProperty('--star-x', `${radius * Math.cos(angle)}px`);
            star.style.setProperty('--star-y', `${-radius * Math.sin(angle)}px`);
            this.starsEl.appendChild(star);
            return star;
        });

        let count = 0;
        const step = () => {
            if (count < 1000) {
                count = Math.min(count + Math.ceil(1000 / 12), 1000);
                this.setStarCount(pending(count));
                const star = stars[Math.floor((count / 1000) * total)];
                if (star) star.classList.add('is-lit');
                setTimeout(step, 2000 / total);
            } else {
                this.setStarCount('1001 stars counted,<br>your results are ready');
                this.readyEls.forEach(el => { el.style.visibility = ''; });
            }
        };
        setTimeout(step, 100);
    }

    redirectToResults() {
        const url = `${window.location.pathname}?quiz=${this.results}#quiz-results`;
        this.close();
        setTimeout(() => { window.location.href = url; }, 500);
    }
}

// Script loads in the footer — the DOM is already parsed.
window.personalityQuiz = new PersonalityQuiz();
