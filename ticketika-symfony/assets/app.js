import './styles/app.css';

/* Ticketika — interactions. Aucune dépendance externe. */
(() => {
    'use strict';

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const FEE = 2000;
    const euros = (n) => new Intl.NumberFormat('fr-FR').format(n).replace(/\u202f|\u00a0/g, ' ') + ' Ar';

    /* 1. Menu mobile */
    document.querySelectorAll('[data-menu-toggle]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const menu = document.getElementById(btn.getAttribute('aria-controls'));
            const open = btn.getAttribute('aria-expanded') === 'true';
            btn.setAttribute('aria-expanded', String(!open));
            menu.classList.toggle('hidden', open);
        });
    });

    /* 2. Apparition au scroll */
    const reveals = document.querySelectorAll('[data-reveal]');
    if ('IntersectionObserver' in window && !reduceMotion) {
        const io = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    io.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });
        reveals.forEach((el, i) => {
            el.style.transitionDelay = `${Math.min(i % 4, 3) * 80}ms`;
            io.observe(el);
        });
    } else {
        reveals.forEach((el) => el.classList.add('is-visible'));
    }

    /* 3. Compteurs animés */
    const counters = document.querySelectorAll('[data-count]');
    if (counters.length && 'IntersectionObserver' in window && !reduceMotion) {
        const io = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                const el = entry.target;
                const target = parseInt(el.dataset.count, 10) || 0;
                const start = performance.now();
                const tick = (now) => {
                    const p = Math.min(1, (now - start) / 1400);
                    el.textContent = new Intl.NumberFormat('fr-FR').format(Math.round(target * (1 - Math.pow(1 - p, 3))));
                    if (p < 1) requestAnimationFrame(tick);
                };
                requestAnimationFrame(tick);
                io.unobserve(el);
            });
        }, { threshold: 0.5 });
        counters.forEach((el) => io.observe(el));
    }

    /* 4. Carrousel de l'accueil (rotation automatique + points) */
    const carousel = document.querySelector('[data-carousel]');
    if (carousel) {
        const slides = carousel.querySelectorAll('[data-slide]');
        const dots = carousel.querySelectorAll('[data-dot]');
        let current = 0;
        const show = (i) => {
            current = (i + slides.length) % slides.length;
            slides.forEach((s, idx) => {
                const active = idx === current;
                s.classList.toggle('opacity-100', active);
                s.classList.toggle('scale-100', active);
                s.classList.toggle('opacity-0', !active);
                s.classList.toggle('scale-95', !active);
                s.classList.toggle('pointer-events-none', !active);
            });
            dots.forEach((d, idx) => d.setAttribute('aria-current', String(idx === current)));
        };
        dots.forEach((d, idx) => d.addEventListener('click', () => show(idx)));
        if (!reduceMotion && slides.length > 1) setInterval(() => show(current + 1), 5500);
    }

    /* 5. Compte à rebours vers le début des événements */
    const countdowns = document.querySelectorAll('[data-countdown]');
    const pad = (n) => String(n).padStart(2, '0');
    const updateCountdowns = () => {
        const now = Date.now();
        countdowns.forEach((el) => {
            const diff = new Date(el.dataset.countdown).getTime() - now;
            if (diff <= 0) { el.textContent = 'Événement en cours ou passé'; return; }
            const d = Math.floor(diff / 86400000);
            const h = Math.floor((diff % 86400000) / 3600000);
            const m = Math.floor((diff % 3600000) / 60000);
            el.textContent = `Commence dans ${d} j ${pad(h)} h ${pad(m)} min`;
        });
    };
    if (countdowns.length) { updateCountdowns(); setInterval(updateCountdowns, 30000); }

    /* 6. Sélecteur d'envie sur l'accueil */
    const moodGroup = document.querySelector('[data-mood-group]');
    if (moodGroup) {
        const items = document.querySelectorAll('[data-mood-item]');
        const chips = moodGroup.querySelectorAll('[data-mood-chip]');
        const applyMood = (mood) => {
            chips.forEach((c) => c.setAttribute('aria-pressed', String(c.dataset.moodChip === mood)));
            items.forEach((item) => item.classList.toggle('hidden', !(mood === 'all' || item.dataset.moodItem === mood)));
        };
        chips.forEach((chip) => chip.addEventListener('click', () => applyMood(chip.dataset.moodChip)));
        document.querySelector('[data-surprise]')?.addEventListener('click', () => {
            const moods = [...chips].map((chip) => chip.dataset.moodChip).filter((mood) => mood !== 'all');
            applyMood(moods[Math.floor(Math.random() * moods.length)]);
        });
    }

    /* 7. Sélection des billets et total tout compris */
    const ticketForm = document.querySelector('[data-tickets]');
    if (ticketForm) {
        const total = ticketForm.querySelector('[data-total]');
        const subtotal = ticketForm.querySelector('[data-subtotal]');
        const fee = ticketForm.querySelector('[data-fee]');
        const submit = ticketForm.querySelector('[data-submit]');
        const recalc = () => {
            let sum = 0;
            let qty = 0;
            ticketForm.querySelectorAll('[data-ticket]').forEach((row) => {
                const n = parseInt(row.querySelector('[data-qty]').value, 10) || 0;
                sum += n * parseInt(row.dataset.price, 10);
                qty += n;
            });
            const serviceFee = sum > 0 ? FEE : 0;
            if (subtotal) subtotal.textContent = sum === 0 ? 'Gratuit' : euros(sum);
            if (fee) fee.textContent = euros(serviceFee);
            if (total) total.textContent = (sum + serviceFee) === 0 ? 'Gratuit' : euros(sum + serviceFee);
            if (submit) {
                submit.disabled = qty === 0;
                submit.querySelector('[data-submit-label]').textContent = qty === 0 ? 'Choisissez vos billets' : `Continuer · ${euros(sum + serviceFee)}`;
            }
        };
        ticketForm.querySelectorAll('[data-ticket]').forEach((row) => {
            const input = row.querySelector('[data-qty]');
            const max = parseInt(input.max, 10) || 8;
            row.querySelector('[data-inc]').addEventListener('click', () => {
                input.value = Math.min(max, (parseInt(input.value, 10) || 0) + 1);
                recalc();
            });
            row.querySelector('[data-dec]').addEventListener('click', () => {
                input.value = Math.max(0, (parseInt(input.value, 10) || 0) - 1);
                recalc();
            });
        });
        recalc();
    }

    /* 8. Places restantes : démonstration (à remplacer par un appel API) */
    const seats = document.querySelectorAll('[data-seats]');
    if (seats.length && !reduceMotion) {
        setInterval(() => {
            seats.forEach((el) => {
                const m = el.textContent.match(/\d+/);
                if (m) el.textContent = `${Math.max(1, parseInt(m[0], 10) - (Math.random() > 0.6 ? 1 : 0))} places restantes`;
            });
        }, 15000);
    }

    /* 9. Onglets d'ancre de la fiche événement : mise en surbrillance */
    const tabLinks = document.querySelectorAll('[data-tab-link]');
    if (tabLinks.length && 'IntersectionObserver' in window) {
        const sections = [...tabLinks].map((l) => document.querySelector(l.getAttribute('href'))).filter(Boolean);
        const io = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                tabLinks.forEach((l) => l.setAttribute('aria-current', String(l.getAttribute('href') === '#' + entry.target.id)));
            });
        }, { rootMargin: '-40% 0px -55% 0px' });
        sections.forEach((s) => io.observe(s));
    }

    /* 10. Aperçu du titre dans la création d'événement */
    const titleInput = document.querySelector('input[id$="title"]');
    const preview = document.querySelector('[data-preview-title]');
    if (titleInput && preview) {
        titleInput.addEventListener('input', () => {
            preview.textContent = titleInput.value.trim() || "Titre de l'événement";
        });
    }

    /* 11. Scan des billets : validation via l'API du serveur */
    const scanZone = document.querySelector('[data-scan-url]');
    if (scanZone) {
        const url = scanZone.dataset.scanUrl;
        const btn = scanZone.querySelector('[data-simulate-scan]');
        const status = scanZone.querySelector('[data-scan-status]');
        const history = scanZone.querySelector('[data-history]');
        const stats = { in: scanZone.querySelector('[data-stat="in"]'), left: scanZone.querySelector('[data-stat="left"]') };
        const codes = JSON.parse(scanZone.querySelector('[data-codes]')?.textContent || '[]');
        const labels = {
            valide: ['Valide', 'bg-olympia-sable text-olympia-bleu'],
            deja: ['Déjà scanné', 'bg-olympia-dore text-olympia-fonce'],
            refuse: ['Refusé', 'bg-red-50 text-red-700'],
        };

        btn?.addEventListener('click', async () => {
            const code = Math.random() < 0.15 || codes.length === 0 ? 'INCONNU' : codes[Math.floor(Math.random() * codes.length)];
            let data;
            try {
                const res = await fetch(url, { method: 'POST', body: new URLSearchParams({ code }), headers: { Accept: 'application/json' } });
                data = await res.json();
            } catch {
                data = { status: 'refuse', message: 'Connexion impossible' };
            }

            status.textContent = data.message;
            if (data.status === 'valide' && stats.in) {
                stats.in.textContent = parseInt(stats.in.textContent, 10) + 1;
                stats.left.textContent = Math.max(0, parseInt(stats.left.textContent, 10) - 1);
            }
            if (history) {
                const [label, cls] = labels[data.status] ?? labels.refuse;
                const li = document.createElement('li');
                li.className = 'flex items-center justify-between gap-3 py-3 text-sm';
                const span = document.createElement('span');
                span.textContent = code;
                const badge = document.createElement('span');
                badge.className = `rounded-full px-3 py-1 text-xs font-semibold ${cls}`;
                badge.textContent = label;
                li.append(span, badge);
                history.querySelector('li.text-olympia-secondaire')?.remove();
                history.prepend(li);
            }
        });
    }
})();
