import './bootstrap';

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

window.Alpine = Alpine;

Alpine.plugin(collapse);

// Sidebar store — state dikelola di sini, di-sync dengan localStorage via app.blade.php
Alpine.store('sidebar', {
    open: true,
});

Alpine.data('floatingActionMenu', () => ({
    open: false,
    menuStyle: {},

    toggle() {
        this.open = !this.open;

        if (this.open) {
            this.$nextTick(() => this.position());
        }
    },

    close() {
        this.open = false;
    },

    position() {
        const trigger = this.$refs.trigger.getBoundingClientRect();
        const menu = this.$refs.menu.getBoundingClientRect();
        const viewportPadding = 8;
        const gap = 8;
        const maxLeft = window.innerWidth - menu.width - viewportPadding;
        const left = Math.min(Math.max(trigger.right - menu.width, viewportPadding), maxLeft);
        const spaceBelow = window.innerHeight - trigger.bottom - gap - viewportPadding;
        const preferredTop = spaceBelow >= menu.height
            ? trigger.bottom + gap
            : trigger.top - menu.height - gap;

        this.menuStyle = {
            left: `${Math.round(left)}px`,
            top: `${Math.round(Math.max(viewportPadding, preferredTop))}px`,
        };
    },
}));

Alpine.data('toastNotifications', (initialToasts = []) => ({
    toasts: [],
    nextId: 1,
    timers: {},

    init() {
        initialToasts.forEach((toast) => this.notify(toast));
    },

    notify(payload = {}) {
        const details = typeof payload === 'string' ? { message: payload } : payload;
        const message = String(details.message ?? '').trim();

        if (!message) {
            return;
        }

        const allowedTypes = ['success', 'error', 'warning', 'info'];
        const type = allowedTypes.includes(details.type) ? details.type : 'info';
        const titles = {
            success: 'Berhasil',
            error: 'Terjadi Kesalahan',
            warning: 'Perhatian',
            info: 'Informasi',
        };
        const defaultDurations = {
            success: 5000,
            error: 7000,
            warning: 6000,
            info: 5000,
        };

        if (this.toasts.length >= 3) {
            this.remove(this.toasts[0].id);
        }

        const toast = {
            id: this.nextId++,
            type,
            title: details.title || titles[type],
            message,
            duration: Number(details.duration) > 0 ? Number(details.duration) : defaultDurations[type],
        };

        this.toasts.push(toast);
        this.schedule(toast);
    },

    schedule(toast) {
        window.clearTimeout(this.timers[toast.id]);
        this.timers[toast.id] = window.setTimeout(() => this.remove(toast.id), toast.duration);
    },

    pause(id) {
        window.clearTimeout(this.timers[id]);
    },

    resume(id) {
        const toast = this.toasts.find((item) => item.id === id);

        if (toast) {
            this.schedule(toast);
        }
    },

    remove(id) {
        window.clearTimeout(this.timers[id]);
        delete this.timers[id];
        this.toasts = this.toasts.filter((toast) => toast.id !== id);
    },
}));

window.showToast = (message, type = 'success', options = {}) => {
    window.dispatchEvent(new CustomEvent('show-toast', {
        detail: { ...options, message, type },
    }));
};

Alpine.data('notificationBell', (config = {}) => {
    // Di luar objek reaktif: penanda internal, tidak perlu memicu render ulang.
    const seen = new Set();
    let timer = null;

    const POLL_INTERVAL = 45000;

    return {
        panelOpen: false,
        items: Array.isArray(config.items) ? config.items : [],
        unreadCount: Number(config.unreadCount) || 0,

        init() {
            // Notifikasi yang sudah ada saat halaman dibuka bukan kabar baru;
            // toast hanya untuk yang datang setelah halaman terbuka.
            this.items.forEach((item) => seen.add(item.id));
            this.start();

            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    this.stop();

                    return;
                }

                this.refresh();
                this.start();
            });
        },

        start() {
            this.stop();
            timer = window.setInterval(() => this.refresh(), POLL_INTERVAL);
        },

        stop() {
            window.clearInterval(timer);
            timer = null;
        },

        async refresh() {
            try {
                const { data } = await window.axios.get(config.pollUrl);

                // Sesi habis membuat responsnya jadi halaman login, bukan JSON.
                // Daftar yang sudah tampil jangan sampai terhapus karenanya.
                if (!Array.isArray(data?.items)) {
                    return;
                }

                const baru = data.items.filter((item) => !item.dibaca && !seen.has(item.id));

                this.items = data.items;
                this.unreadCount = Number(data.unread_count) || 0;
                this.items.forEach((item) => seen.add(item.id));

                baru.forEach((item) => window.showToast(item.pesan, 'info', { title: item.judul }));
            } catch (error) {
                // Jaringan putus sebentar bukan alasan menghentikan polling.
            }
        },

        async markAllRead() {
            try {
                const { data } = await window.axios.post(config.readAllUrl);

                if (!Array.isArray(data?.items)) {
                    return;
                }

                this.items = data.items;
                this.unreadCount = Number(data.unread_count) || 0;
            } catch (error) {
                window.showToast('Gagal menandai notifikasi.', 'error');
            }
        },
    };
});

const validationErrorFocus = (() => {
    const fieldSelector = 'input:not([type="hidden"]), select, textarea, [tabindex]:not([tabindex="-1"])';
    const highlightClasses = [
        'border-rose-400',
        'bg-rose-50',
        'ring-2',
        'ring-rose-400',
        'ring-offset-2',
        'transition',
    ];

    const escapeSelectorValue = (value) => {
        if (window.CSS?.escape) {
            return window.CSS.escape(String(value));
        }

        return String(value).replace(/\\/g, '\\\\').replace(/"/g, '\\"');
    };

    const errorKeyToFieldNames = (errorKey) => {
        const key = String(errorKey || '').trim();

        if (!key) {
            return [];
        }

        const segments = key.split('.');
        const bracketName = segments.length > 1
            ? segments[0] + segments.slice(1).map((segment) => `[${segment}]`).join('')
            : key;

        return [...new Set([key, bracketName])];
    };

    const getErrorKeys = () => {
        const source = document.querySelector('[data-validation-error-keys]');

        if (!source?.textContent) {
            return [];
        }

        try {
            const keys = JSON.parse(source.textContent);

            return Array.isArray(keys) ? keys.filter(Boolean) : [];
        } catch (error) {
            return [];
        }
    };

    const isFocusableField = (field) => {
        if (!(field instanceof HTMLElement) || !field.matches(fieldSelector)) {
            return false;
        }

        if (field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement) {
            return !field.disabled && !field.readOnly;
        }

        return true;
    };

    const findField = (errorKey) => {
        for (const name of errorKeyToFieldNames(errorKey)) {
            const escapedName = escapeSelectorValue(name);
            const field = document.querySelector(
                `[name="${escapedName}"], [data-error-key="${escapedName}"], #${escapedName}`
            );

            if (isFocusableField(field)) {
                return field;
            }
        }

        return null;
    };

    const highlightField = (field) => {
        if (!isFocusableField(field)) {
            return;
        }

        field.setAttribute('data-validation-error-highlighted', 'true');
        field.setAttribute('aria-invalid', 'true');
        field.classList.add(...highlightClasses);

        window.setTimeout(() => {
            field.classList.remove('ring-2', 'ring-rose-400', 'ring-offset-2');
        }, 2400);
    };

    const focusFirstErrorField = (keys = getErrorKeys()) => {
        for (const key of keys) {
            const field = findField(key);

            if (!field) {
                continue;
            }

            window.setTimeout(() => {
                field.scrollIntoView({ behavior: 'smooth', block: 'center' });
                field.focus({ preventScroll: true });
                highlightField(field);
            }, 150);

            return field;
        }

        return null;
    };

    return {
        errorKeyToFieldNames,
        focusFirstErrorField,
        getErrorKeys,
        highlightField,
    };
})();

window.validationErrorFocus = validationErrorFocus;

document.addEventListener('DOMContentLoaded', () => {
    window.validationErrorFocus.focusFirstErrorField();
});

const filterFormLoading = (() => {
    const buttonSelector = 'button[type="submit"], input[type="submit"]';
    const fieldSelector = 'input:not([type="hidden"]), select, textarea';

    const isFilterForm = (form) => {
        if (!(form instanceof HTMLFormElement)) {
            return false;
        }

        if (form.method.toUpperCase() !== 'GET' || form.matches('[data-no-filter-loading]')) {
            return false;
        }

        return form.querySelector(fieldSelector) !== null;
    };

    const getButtons = (form, submitter = null) => {
        const buttons = [...form.querySelectorAll(buttonSelector)];

        if (submitter instanceof HTMLButtonElement || submitter instanceof HTMLInputElement) {
            if (!buttons.includes(submitter)) {
                buttons.unshift(submitter);
            }
        }

        return buttons;
    };

    const setButtonLoading = (button, loading, label) => {
        if (loading) {
            if (!button.hasAttribute('data-filter-loading-original-disabled')) {
                button.setAttribute('data-filter-loading-original-disabled', button.disabled ? 'true' : 'false');
            }

            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            button.classList.add('cursor-wait', 'opacity-80');

            if (button instanceof HTMLInputElement) {
                if (!button.hasAttribute('data-filter-loading-original-value')) {
                    button.setAttribute('data-filter-loading-original-value', button.value);
                }

                button.value = label;
                return;
            }

            if (!button.hasAttribute('data-filter-loading-original-html')) {
                button.setAttribute('data-filter-loading-original-html', button.innerHTML);
            }

            if (!button.style.minWidth && button.offsetWidth > 0) {
                button.style.minWidth = `${button.offsetWidth}px`;
            }

            button.innerHTML = `
                <span class="inline-flex items-center gap-2">
                    <span class="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" aria-hidden="true"></span>
                    <span>${label}</span>
                </span>
            `;
            return;
        }

        const wasDisabled = button.getAttribute('data-filter-loading-original-disabled') === 'true';
        button.disabled = wasDisabled;
        button.setAttribute('aria-busy', 'false');
        button.classList.remove('cursor-wait', 'opacity-80');
        button.removeAttribute('data-filter-loading-original-disabled');

        if (button instanceof HTMLInputElement) {
            if (button.hasAttribute('data-filter-loading-original-value')) {
                button.value = button.getAttribute('data-filter-loading-original-value');
                button.removeAttribute('data-filter-loading-original-value');
            }

            return;
        }

        if (button.hasAttribute('data-filter-loading-original-html')) {
            button.innerHTML = button.getAttribute('data-filter-loading-original-html');
            button.removeAttribute('data-filter-loading-original-html');
        }

        button.style.minWidth = '';
    };

    const setFilterFormLoading = (form, loading, submitter = null) => {
        if (!isFilterForm(form)) {
            return false;
        }

        if (loading && form.dataset.filterLoading === 'true') {
            return false;
        }

        const label = submitter?.dataset?.loadingLabel || form.dataset.loadingLabel || 'Memuat...';
        form.dataset.filterLoading = loading ? 'true' : 'false';
        form.setAttribute('aria-busy', loading ? 'true' : 'false');

        getButtons(form, submitter).forEach((button) => setButtonLoading(button, loading, label));
        return true;
    };

    const resetAll = () => {
        document.querySelectorAll('form[data-filter-loading="true"]').forEach((form) => {
            if (form instanceof HTMLFormElement) {
                setFilterFormLoading(form, false);
            }
        });
    };

    return {
        isFilterForm,
        resetAll,
        setFilterFormLoading,
    };
})();

window.filterFormLoading = filterFormLoading;

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || !window.filterFormLoading.isFilterForm(form)) {
        return;
    }

    if (event.defaultPrevented) {
        return;
    }

    if (form.dataset.filterLoading === 'true') {
        event.preventDefault();
        return;
    }

    window.filterFormLoading.setFilterFormLoading(form, true, event.submitter);
});

window.addEventListener('pageshow', () => {
    window.filterFormLoading.resetAll();
});

const linkLoading = (() => {
    const resetTimers = new WeakMap();

    const isModifiedClick = (event) => event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0;

    const isLoadableLink = (link) => {
        if (!(link instanceof HTMLAnchorElement)
            || link.matches('[data-no-link-loading]')
            || link.closest('#application-sidebar')) {
            return false;
        }

        const rawHref = link.getAttribute('href') || '';

        if (!rawHref || rawHref.startsWith('#')) {
            return false;
        }

        if (link.target && link.target !== '_self' && !link.matches('[data-download-loading]')) {
            return false;
        }

        const url = new URL(link.href, window.location.href);

        if (!['http:', 'https:'].includes(url.protocol)) {
            return false;
        }

        return url.origin === window.location.origin;
    };

    const isCurrentPageLink = (link) => {
        if (!(link instanceof HTMLAnchorElement)) {
            return false;
        }

        const targetUrl = new URL(link.href, window.location.href);
        const currentUrl = new URL(window.location.href);

        return targetUrl.origin === currentUrl.origin
            && targetUrl.pathname === currentUrl.pathname
            && targetUrl.search === currentUrl.search
            && targetUrl.hash === currentUrl.hash;
    };

    const loadingLabelFor = (link) => {
        if (link.matches('[data-download-loading]')) {
            return link.dataset.loadingLabel || 'Menyiapkan...';
        }

        return link.dataset.loadingLabel || 'Membuka...';
    };

    const downloadTimeoutFor = (link) => {
        const downloadTimeout = Number(link.dataset.downloadTimeout);

        return Number.isFinite(downloadTimeout) && downloadTimeout > 0
            ? downloadTimeout
            : 4000;
    };

    const isCompactLoadingLink = (link) => (
        !link.matches('[data-download-loading]')
            && (link.matches('[data-compact-link-loading]') || link.closest('[data-table-actions]'))
    );

    const setLinkLoading = (link, loading, label = null) => {
        if (!isLoadableLink(link)) {
            return false;
        }

        window.clearTimeout(resetTimers.get(link));

        if (loading) {
            if (link.dataset.linkLoading === 'true') {
                return false;
            }

            link.dataset.linkLoading = 'true';
            link.setAttribute('aria-busy', 'true');
            link.setAttribute('aria-disabled', 'true');
            link.classList.add('pointer-events-none', 'cursor-wait', 'opacity-80');

            if (!link.hasAttribute('data-link-loading-original-html')) {
                link.setAttribute('data-link-loading-original-html', link.innerHTML);
            }

            if (!link.style.minWidth && link.offsetWidth > 0) {
                link.style.minWidth = `${link.offsetWidth}px`;
            }

            if (!link.style.minHeight && link.offsetHeight > 0) {
                link.style.minHeight = `${link.offsetHeight}px`;
            }

            const spinner = '<span class="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" aria-hidden="true"></span>';
            link.innerHTML = isCompactLoadingLink(link)
                ? `<span class="inline-flex w-full items-center justify-center">${spinner}</span>`
                : `
                    <span class="inline-flex w-full items-center justify-center gap-2">
                        ${spinner}
                        <span>${label || loadingLabelFor(link)}</span>
                    </span>
                `;

            if (link.matches('[data-download-loading]')) {
                resetTimers.set(link, window.setTimeout(() => setLinkLoading(link, false), downloadTimeoutFor(link)));
            }

            return true;
        }

        link.dataset.linkLoading = 'false';
        link.setAttribute('aria-busy', 'false');
        link.removeAttribute('aria-disabled');
        link.classList.remove('pointer-events-none', 'cursor-wait', 'opacity-80');

        if (link.hasAttribute('data-link-loading-original-html')) {
            link.innerHTML = link.getAttribute('data-link-loading-original-html');
            link.removeAttribute('data-link-loading-original-html');
        }

        link.style.minWidth = '';
        link.style.minHeight = '';
        return true;
    };

    const resetAll = () => {
        document.querySelectorAll('a[data-link-loading="true"]').forEach((link) => {
            if (link instanceof HTMLAnchorElement) {
                setLinkLoading(link, false);
            }
        });
    };

    /**
     * Khusus tautan unduh/cetak: halaman ini tidak ikut berpindah, jadi tidak
     * pernah ada sinyal "sudah selesai" selain tenggat waktunya.
     */
    const resetDownloads = () => {
        document.querySelectorAll('a[data-download-loading][data-link-loading="true"]').forEach((link) => {
            if (link instanceof HTMLAnchorElement) {
                setLinkLoading(link, false);
            }
        });
    };

    return {
        isCompactLoadingLink,
        isCurrentPageLink,
        isLoadableLink,
        isModifiedClick,
        resetAll,
        resetDownloads,
        setLinkLoading,
    };
})();

window.linkLoading = linkLoading;

window.addEventListener('pageshow', () => {
    window.linkLoading.resetAll();
});

window.addEventListener('pagehide', () => {
    window.linkLoading.resetAll();
});

// PDF dibuka di tab baru: tab itu langsung merebut fokus dan halaman ini tetap
// diam di tempat, sehingga tombolnya akan berputar sampai tenggat waktunya habis
// walau dokumennya sudah tampil. Berpindahnya fokus itulah tanda paling awal
// bahwa urusan tombol ini sudah selesai.
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        window.linkLoading.resetDownloads();
    }
});

window.addEventListener('blur', () => {
    window.linkLoading.resetDownloads();
});

Alpine.data('searchableSelect', (config = {}) => ({
    options: (config.options ?? []).map((option) => ({
        id: String(option.id ?? ''),
        label: option.label ?? '',
    })),
    selectedId: String(config.selectedId ?? ''),
    placeholder: config.placeholder ?? 'Pilih opsi',
    emptyText: config.emptyText ?? 'Tidak ada hasil.',
    query: '',
    open: false,
    highlightedIndex: 0,

    init() {
        this.syncQueryFromSelected();

        this.$watch('selectedId', () => {
            this.syncQueryFromSelected();
            this.$dispatch('searchable-select-change', {
                value: this.selectedId,
                option: this.selectedOption,
            });
        });
    },

    get selectedOption() {
        return this.options.find((option) => option.id === this.selectedId) ?? null;
    },

    get filteredOptions() {
        const keyword = this.query.trim().toLowerCase();

        if (keyword === '') {
            return this.options;
        }

        return this.options.filter((option) =>
            option.label.toLowerCase().includes(keyword)
        );
    },

    syncQueryFromSelected() {
        this.query = this.selectedOption ? this.selectedOption.label : '';
    },

    openOptions() {
        this.open = true;
        this.highlightedIndex = 0;
    },

    closeOptions() {
        this.open = false;
        this.commitTypedValue();
    },

    onInput() {
        this.open = true;
        this.highlightedIndex = 0;

        const exactMatch = this.options.find(
            (option) => option.label.toLowerCase() === this.query.trim().toLowerCase()
        );

        this.selectedId = exactMatch ? exactMatch.id : '';
    },

    commitTypedValue() {
        const keyword = this.query.trim().toLowerCase();

        if (keyword === '') {
            this.selectedId = '';
            this.query = '';
            return;
        }

        const exactMatch = this.options.find(
            (option) => option.label.toLowerCase() === keyword
        );

        if (exactMatch) {
            this.selectedId = exactMatch.id;
            this.query = exactMatch.label;
            return;
        }

        this.syncQueryFromSelected();
    },

    choose(option) {
        this.selectedId = option.id;
        this.query = option.label;
        this.open = false;
    },

    clear() {
        this.selectedId = '';
        this.query = '';
        this.open = false;
        this.highlightedIndex = 0;
    },

    moveHighlight(step) {
        if (!this.open) {
            this.openOptions();
        }

        if (this.filteredOptions.length === 0) {
            this.highlightedIndex = 0;
            return;
        }

        const nextIndex = this.highlightedIndex + step;

        if (nextIndex < 0) {
            this.highlightedIndex = this.filteredOptions.length - 1;
            return;
        }

        if (nextIndex >= this.filteredOptions.length) {
            this.highlightedIndex = 0;
            return;
        }

        this.highlightedIndex = nextIndex;
    },

    chooseHighlighted() {
        if (this.filteredOptions.length === 0) {
            return;
        }

        this.choose(this.filteredOptions[this.highlightedIndex] ?? this.filteredOptions[0]);
    },
}));

Alpine.data('currencyInput', (initialValue = '') => ({
    numericValue: '',
    displayValue: '',

    init() {
        this.setValue(initialValue);
    },

    setValue(value) {
        const normalized = String(value ?? '').replace(/\D/g, '');
        this.numericValue = normalized;
        this.displayValue = this.format(normalized);
    },

    onInput(event) {
        this.setValue(event.target.value);
    },

    format(value) {
        if (!value) {
            return '';
        }

        return new Intl.NumberFormat('id-ID').format(Number(value));
    },
}));

const setDuplicateSubmitState = (form, submitting) => {
    const button = form.querySelector('[data-duplicate-button]');

    if (!(button instanceof HTMLButtonElement)) {
        return;
    }

    const spinner = button.querySelector('[data-duplicate-spinner]');
    const label = button.querySelector('[data-duplicate-label]');

    if (submitting) {
        form.dataset.duplicateSubmitting = 'true';
    } else {
        delete form.dataset.duplicateSubmitting;
    }

    button.disabled = submitting;
    button.setAttribute('aria-busy', submitting ? 'true' : 'false');
    spinner?.classList.toggle('hidden', !submitting);

    if (label) {
        label.textContent = submitting
            ? label.dataset.loadingLabel || 'Menduplikat...'
            : label.dataset.idleLabel || 'Duplikat';
    }
};

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || !form.matches('[data-duplicate-submit]')) {
        return;
    }

    if (event.defaultPrevented) {
        return;
    }

    if (form.dataset.duplicateSubmitting === 'true') {
        event.preventDefault();
        return;
    }

    setDuplicateSubmitState(form, true);
});

window.addEventListener('pageshow', () => {
    document.querySelectorAll('[data-duplicate-submit]').forEach((form) => {
        if (form instanceof HTMLFormElement) {
            setDuplicateSubmitState(form, false);
        }
    });
});

Alpine.data('duplicateConfirmation', () => ({
    open: false,
    processing: false,
    title: 'Duplikat Data?',
    message: 'Salinan baru akan dibuat dari data ini.',
    confirmLabel: 'Duplikat',
    onConfirm: null,
    onCancel: null,
    lastFocusedElement: null,

    openDialog(options = {}) {
        if (this.processing) {
            return;
        }

        if (this.open) {
            this.finish(false);
        }

        this.processing = false;
        this.title = options.title || 'Duplikat Data?';
        this.message = options.message || 'Salinan baru akan dibuat dari data ini.';
        this.confirmLabel = options.confirmLabel || 'Duplikat';
        this.onConfirm = typeof options.onConfirm === 'function' ? options.onConfirm : null;
        this.onCancel = typeof options.onCancel === 'function' ? options.onCancel : null;
        this.lastFocusedElement = document.activeElement;
        this.open = true;
        document.body.classList.add('overflow-y-hidden');

        this.$nextTick(() => this.$refs.cancel?.focus());
    },

    confirm() {
        if (this.processing) {
            return;
        }

        this.processing = true;
        const callback = this.onConfirm;
        this.onConfirm = null;
        callback?.();
    },

    cancel() {
        if (this.processing) {
            return;
        }

        this.finish(false);
    },

    finish(confirmed) {
        const callback = confirmed ? this.onConfirm : this.onCancel;

        this.open = false;
        this.processing = false;
        this.onConfirm = null;
        this.onCancel = null;
        document.body.classList.remove('overflow-y-hidden');
        this.$nextTick(() => this.lastFocusedElement?.focus?.());

        callback?.();
    },

    focusableElements() {
        return [...this.$refs.dialog.querySelectorAll(
            'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
        )];
    },

    trapFocus(event) {
        const focusable = this.focusableElements();

        if (focusable.length === 0) {
            event.preventDefault();
            return;
        }

        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    },
}));

window.requestDuplicateConfirmation = (options = {}) => new Promise((resolve) => {
    const dialog = document.querySelector('[data-global-duplicate-confirmation]');

    if (!dialog) {
        resolve(false);
        return;
    }

    window.dispatchEvent(new CustomEvent('open-duplicate-confirmation', {
        detail: {
            ...options,
            onConfirm: () => resolve(true),
            onCancel: () => resolve(false),
        },
    }));
});

document.addEventListener('submit', async (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || !form.matches('[data-confirm-duplicate]')) {
        return;
    }

    if (form.dataset.duplicateConfirmed === 'true') {
        setTimeout(() => delete form.dataset.duplicateConfirmed, 0);
        return;
    }

    event.preventDefault();
    event.stopImmediatePropagation();

    if (form.dataset.duplicateConfirmationPending === 'true') {
        return;
    }

    form.dataset.duplicateConfirmationPending = 'true';
    const confirmed = await window.requestDuplicateConfirmation({
        title: form.dataset.confirmTitle,
        message: form.dataset.confirmDuplicate,
        confirmLabel: form.dataset.confirmLabel,
    });
    delete form.dataset.duplicateConfirmationPending;

    if (!confirmed) {
        return;
    }

    form.dataset.duplicateConfirmed = 'true';
    const submitter = event.submitter instanceof HTMLElement && event.submitter.form === form
        ? event.submitter
        : undefined;
    form.requestSubmit(submitter);
}, true);

Alpine.data('actionConfirmation', () => ({
    open: false,
    title: 'Konfirmasi Aksi',
    message: 'Lanjutkan aksi ini?',
    confirmLabel: 'Lanjutkan',
    onConfirm: null,
    onCancel: null,
    lastFocusedElement: null,

    openDialog(options = {}) {
        if (this.open) {
            this.finish(false);
        }

        this.title = options.title || 'Konfirmasi Aksi';
        this.message = options.message || 'Lanjutkan aksi ini?';
        this.confirmLabel = options.confirmLabel || 'Lanjutkan';
        this.onConfirm = typeof options.onConfirm === 'function' ? options.onConfirm : null;
        this.onCancel = typeof options.onCancel === 'function' ? options.onCancel : null;
        this.lastFocusedElement = document.activeElement;
        this.open = true;
        document.body.classList.add('overflow-y-hidden');

        this.$nextTick(() => this.$refs.cancel?.focus());
    },

    confirm() {
        this.finish(true);
    },

    cancel() {
        this.finish(false);
    },

    finish(confirmed) {
        const callback = confirmed ? this.onConfirm : this.onCancel;

        this.open = false;
        this.onConfirm = null;
        this.onCancel = null;
        document.body.classList.remove('overflow-y-hidden');
        this.$nextTick(() => this.lastFocusedElement?.focus?.());

        callback?.();
    },

    focusableElements() {
        return [...this.$refs.dialog.querySelectorAll(
            'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
        )];
    },

    trapFocus(event) {
        const focusable = this.focusableElements();

        if (focusable.length === 0) {
            event.preventDefault();
            return;
        }

        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    },
}));

window.requestActionConfirmation = (options = {}) => new Promise((resolve) => {
    const dialog = document.querySelector('[data-global-action-confirmation]');

    if (!dialog) {
        resolve(false);
        return;
    }

    window.dispatchEvent(new CustomEvent('open-action-confirmation', {
        detail: {
            ...options,
            onConfirm: () => resolve(true),
            onCancel: () => resolve(false),
        },
    }));
});

document.addEventListener('submit', async (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || !form.matches('[data-confirm-action]')) {
        return;
    }

    if (form.dataset.actionConfirmed === 'true') {
        setTimeout(() => delete form.dataset.actionConfirmed, 0);
        return;
    }

    event.preventDefault();
    event.stopImmediatePropagation();

    if (form.dataset.actionConfirmationPending === 'true') {
        return;
    }

    form.dataset.actionConfirmationPending = 'true';
    const confirmed = await window.requestActionConfirmation({
        title: form.dataset.confirmTitle,
        message: form.dataset.confirmAction,
        confirmLabel: form.dataset.confirmLabel,
    });
    delete form.dataset.actionConfirmationPending;

    if (!confirmed) {
        return;
    }

    form.dataset.actionConfirmed = 'true';
    const submitter = event.submitter instanceof HTMLElement && event.submitter.form === form
        ? event.submitter
        : undefined;
    form.requestSubmit(submitter);
}, true);

Alpine.data('deleteConfirmation', () => ({
    open: false,
    title: 'Hapus Data?',
    message: 'Data ini akan dihapus secara permanen. Tindakan ini tidak dapat dibatalkan.',
    confirmLabel: 'Hapus Permanen',
    onConfirm: null,
    onCancel: null,
    lastFocusedElement: null,

    openDialog(options = {}) {
        if (this.open) {
            this.finish(false);
        }

        this.title = options.title || 'Hapus Data?';
        this.message = options.message || 'Data ini akan dihapus secara permanen. Tindakan ini tidak dapat dibatalkan.';
        this.confirmLabel = options.confirmLabel || 'Hapus Permanen';
        this.onConfirm = typeof options.onConfirm === 'function' ? options.onConfirm : null;
        this.onCancel = typeof options.onCancel === 'function' ? options.onCancel : null;
        this.lastFocusedElement = document.activeElement;
        this.open = true;
        document.body.classList.add('overflow-y-hidden');

        this.$nextTick(() => this.$refs.cancel?.focus());
    },

    confirm() {
        this.finish(true);
    },

    cancel() {
        this.finish(false);
    },

    finish(confirmed) {
        const callback = confirmed ? this.onConfirm : this.onCancel;

        this.open = false;
        this.onConfirm = null;
        this.onCancel = null;
        document.body.classList.remove('overflow-y-hidden');
        this.$nextTick(() => this.lastFocusedElement?.focus?.());

        callback?.();
    },

    focusableElements() {
        return [...this.$refs.dialog.querySelectorAll(
            'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
        )];
    },

    trapFocus(event) {
        const focusable = this.focusableElements();

        if (focusable.length === 0) {
            event.preventDefault();
            return;
        }

        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    },
}));

window.requestDeleteConfirmation = (options = {}) => new Promise((resolve) => {
    const dialog = document.querySelector('[data-global-delete-confirmation]');

    if (!dialog) {
        resolve(false);
        return;
    }

    window.dispatchEvent(new CustomEvent('open-delete-confirmation', {
        detail: {
            ...options,
            onConfirm: () => resolve(true),
            onCancel: () => resolve(false),
        },
    }));
});

document.addEventListener('submit', async (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || !form.matches('[data-confirm-delete]')) {
        return;
    }

    if (form.dataset.deleteConfirmed === 'true') {
        setTimeout(() => delete form.dataset.deleteConfirmed, 0);
        return;
    }

    event.preventDefault();
    event.stopImmediatePropagation();

    if (form.dataset.deleteConfirmationPending === 'true') {
        return;
    }

    form.dataset.deleteConfirmationPending = 'true';
    const confirmed = await window.requestDeleteConfirmation({
        title: form.dataset.confirmTitle,
        message: form.dataset.confirmDelete,
        confirmLabel: form.dataset.confirmLabel,
    });
    delete form.dataset.deleteConfirmationPending;

    if (!confirmed) {
        return;
    }

    form.dataset.deleteConfirmed = 'true';
    const submitter = event.submitter instanceof HTMLElement && event.submitter.form === form
        ? event.submitter
        : undefined;
    form.requestSubmit(submitter);
}, true);

Alpine.data('unsavedChangesConfirmation', () => ({
    open: false,
    title: 'Perubahan belum disimpan',
    message: 'Anda punya perubahan yang belum disimpan. Kalau keluar sekarang, perubahan tersebut akan hilang.',
    onConfirm: null,
    onCancel: null,
    lastFocusedElement: null,

    openDialog(options = {}) {
        if (this.open) {
            this.finish(false);
        }

        this.title = options.title || 'Perubahan belum disimpan';
        this.message = options.message || 'Anda punya perubahan yang belum disimpan. Kalau keluar sekarang, perubahan tersebut akan hilang.';
        this.onConfirm = typeof options.onConfirm === 'function' ? options.onConfirm : null;
        this.onCancel = typeof options.onCancel === 'function' ? options.onCancel : null;
        this.lastFocusedElement = document.activeElement;
        this.open = true;
        document.body.classList.add('overflow-y-hidden');

        this.$nextTick(() => this.$refs.cancel?.focus());
    },

    confirm() {
        this.finish(true);
    },

    cancel() {
        this.finish(false);
    },

    finish(confirmed) {
        const callback = confirmed ? this.onConfirm : this.onCancel;

        this.open = false;
        this.onConfirm = null;
        this.onCancel = null;
        document.body.classList.remove('overflow-y-hidden');
        this.$nextTick(() => this.lastFocusedElement?.focus?.());

        callback?.();
    },

    focusableElements() {
        return [...this.$refs.dialog.querySelectorAll(
            'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
        )];
    },

    trapFocus(event) {
        const focusable = this.focusableElements();

        if (focusable.length === 0) {
            event.preventDefault();
            return;
        }

        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    },
}));

window.requestUnsavedChangesConfirmation = (options = {}) => new Promise((resolve) => {
    const dialog = document.querySelector('[data-global-unsaved-confirmation]');

    if (!dialog) {
        resolve(window.confirm(options.message || 'Perubahan belum disimpan. Keluar dari halaman?'));
        return;
    }

    window.dispatchEvent(new CustomEvent('open-unsaved-changes-confirmation', {
        detail: {
            ...options,
            onConfirm: () => resolve(true),
            onCancel: () => resolve(false),
        },
    }));
});

const unsavedChanges = (() => {
    const baselines = new WeakMap();
    const dirtyForms = new Set();
    let allowNextUnload = false;

    const excludedFormSelector = [
        '[data-no-unsaved-warning]',
        '[data-confirm-action]',
        '[data-confirm-delete]',
        '[data-confirm-duplicate]',
        '[data-duplicate-submit]',
    ].join(',');

    const editableFieldSelector = [
        "input:not([type='hidden']):not([type='submit']):not([type='button']):not([type='reset'])",
        'select',
        'textarea',
    ].join(',');

    const getEditableFields = (form) => [...form.querySelectorAll(editableFieldSelector)]
        .filter((field) => !field.disabled && !field.readOnly && field.name);

    const isTrackableForm = (form) => {
        if (!(form instanceof HTMLFormElement)) {
            return false;
        }

        if (form.method.toUpperCase() === 'GET' || form.matches(excludedFormSelector)) {
            return false;
        }

        return getEditableFields(form).length > 0;
    };

    const fieldValue = (field) => {
        if (field instanceof HTMLInputElement && (field.type === 'checkbox' || field.type === 'radio')) {
            return field.checked ? '1' : '0';
        }

        if (field instanceof HTMLInputElement && field.type === 'file') {
            return [...field.files].map((file) => `${file.name}:${file.size}:${file.lastModified}`).join('|');
        }

        if (field instanceof HTMLSelectElement && field.multiple) {
            return [...field.selectedOptions].map((option) => option.value).join('|');
        }

        return field.value;
    };

    const getFormSignature = (form) => getEditableFields(form)
        .map((field) => `${field.name}=${fieldValue(field)}`)
        .join('&');

    const rememberForm = (form) => {
        if (!isTrackableForm(form)) {
            dirtyForms.delete(form);
            return;
        }

        if (!baselines.has(form)) {
            baselines.set(form, getFormSignature(form));
        }
    };

    const refreshFormState = (form) => {
        if (!isTrackableForm(form)) {
            dirtyForms.delete(form);
            return;
        }

        rememberForm(form);

        if (baselines.get(form) === getFormSignature(form)) {
            dirtyForms.delete(form);
            return;
        }

        dirtyForms.add(form);
    };

    const markFormClean = (form) => {
        if (!isTrackableForm(form)) {
            dirtyForms.delete(form);
            return;
        }

        baselines.set(form, getFormSignature(form));
        dirtyForms.delete(form);
    };

    const hasDirtyForms = () => {
        dirtyForms.forEach((form) => {
            if (!document.body.contains(form)) {
                dirtyForms.delete(form);
                return;
            }

            refreshFormState(form);
        });

        return dirtyForms.size > 0;
    };

    const init = (root = document) => {
        root.querySelectorAll('form').forEach((form) => rememberForm(form));
    };

    const allowUnloadOnce = () => {
        allowNextUnload = true;
        window.setTimeout(() => {
            allowNextUnload = false;
        }, 2000);
    };

    const shouldSkipUnloadWarning = () => allowNextUnload;

    return {
        getFormSignature,
        hasDirtyForms,
        init,
        isDirty: (form) => dirtyForms.has(form),
        isTrackableForm,
        markFormClean,
        refreshFormState,
        allowUnloadOnce,
        shouldSkipUnloadWarning,
    };
})();

window.unsavedChanges = unsavedChanges;

document.addEventListener('DOMContentLoaded', () => {
    window.unsavedChanges.init();
});

document.addEventListener('input', (event) => {
    const form = event.target instanceof HTMLElement ? event.target.closest('form') : null;

    if (form instanceof HTMLFormElement) {
        window.unsavedChanges.refreshFormState(form);
    }
});

document.addEventListener('change', (event) => {
    const form = event.target instanceof HTMLElement ? event.target.closest('form') : null;

    if (form instanceof HTMLFormElement) {
        window.unsavedChanges.refreshFormState(form);
    }
});

document.addEventListener('click', async (event) => {
    const target = event.target instanceof Element ? event.target : null;
    const link = target ? target.closest('a[href]') : null;

    if (!link || event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
        return;
    }

    if (link.target && link.target !== '_self') {
        return;
    }

    if (link.hasAttribute('download') || link.getAttribute('href')?.startsWith('#')) {
        return;
    }

    if (window.linkLoading.isCurrentPageLink(link)) {
        event.preventDefault();
        return;
    }

    if (!window.unsavedChanges.hasDirtyForms()) {
        return;
    }

    event.preventDefault();
    const confirmed = await window.requestUnsavedChangesConfirmation();

    if (confirmed) {
        window.unsavedChanges.allowUnloadOnce();
        window.location.href = link.href;
    }
}, true);

document.addEventListener('click', (event) => {
    const target = event.target instanceof Element ? event.target : null;
    const link = target ? target.closest('a[href]') : null;

    if (!link || event.defaultPrevented || window.linkLoading.isModifiedClick(event)) {
        return;
    }

    if (!window.linkLoading.isLoadableLink(link)) {
        return;
    }

    if (window.linkLoading.isCurrentPageLink(link)) {
        event.preventDefault();
        return;
    }

    if (link.dataset.linkLoading === 'true') {
        event.preventDefault();
        return;
    }

    window.linkLoading.setLinkLoading(link, true);
}, true);

document.addEventListener('submit', async (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || event.defaultPrevented) {
        return;
    }

    if (form.dataset.unsavedConfirmed === 'true') {
        setTimeout(() => delete form.dataset.unsavedConfirmed, 0);
        window.unsavedChanges.allowUnloadOnce();
        return;
    }

    window.unsavedChanges.refreshFormState(form);

    if (!window.unsavedChanges.hasDirtyForms()) {
        return;
    }

    if (window.unsavedChanges.isTrackableForm(form) && window.unsavedChanges.isDirty(form)) {
        window.unsavedChanges.markFormClean(form);
        window.unsavedChanges.allowUnloadOnce();
        return;
    }

    event.preventDefault();
    event.stopImmediatePropagation();

    const confirmed = await window.requestUnsavedChangesConfirmation();

    if (!confirmed) {
        return;
    }

    form.dataset.unsavedConfirmed = 'true';
    window.unsavedChanges.allowUnloadOnce();
    const submitter = event.submitter instanceof HTMLElement && event.submitter.form === form
        ? event.submitter
        : undefined;
    form.requestSubmit(submitter);
}, true);

window.addEventListener('beforeunload', (event) => {
    if (window.unsavedChanges.shouldSkipUnloadWarning() || !window.unsavedChanges.hasDirtyForms()) {
        return;
    }

    event.preventDefault();
    event.returnValue = '';
});

const unsavedFormObserver = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
            if (!(node instanceof HTMLElement)) {
                return;
            }

            if (node.matches('form')) {
                window.unsavedChanges.init(node.parentElement || document);
                return;
            }

            if (node.querySelector('form')) {
                window.unsavedChanges.init(node);
            }
        });
    });
});

if (document.body) {
    unsavedFormObserver.observe(document.body, { childList: true, subtree: true });
} else {
    document.addEventListener('DOMContentLoaded', () => {
        unsavedFormObserver.observe(document.body, { childList: true, subtree: true });
    });
}

/**
 * Modal rincian bahan pada halaman Harga Modal.
 *
 * Yang dipanggil adalah route CRM sendiri, bukan inventory: kunci API tidak boleh
 * ikut ke browser. Route itu membalas potongan HTML yang sudah dirender server,
 * jadi format rupiah dan penanda selisih tetap satu definisi di Blade -- tidak ada
 * logika tampilan yang digandakan di sini.
 */
/**
 * Klik "Lihat Bahan": menyiarkan permintaan lewat event window.
 *
 * Alasannya sama dengan pratinjau gambar -- jendelanya tidak perlu membungkus
 * tabel, jadi tidak ada komponen yang bersarang dan tidak ada keadaan yang bisa
 * tertulis ke komponen yang salah.
 */
window.bukaRincianBahan = (event) => {
    // Ctrl/Cmd/Shift-klik dibiarkan lewat, supaya tetap bisa dibuka di tab baru.
    if (event.metaKey || event.ctrlKey || event.shiftKey || event.button !== 0) {
        return;
    }

    event.preventDefault();

    const tombol = event.currentTarget;
    const { nama = '', kode = '', produksi = '', tipe = '', harga = '' } = tombol.dataset;

    // Penanda loading tautan global dipasang di fase capture, sebelum handler ini
    // sempat membatalkan klik. Karena halamannya tidak berpindah, penandanya tidak
    // akan pernah lepas sendiri.
    window.linkLoading?.setLinkLoading(tombol, false);

    window.dispatchEvent(new CustomEvent('rincian-bahan', {
        detail: { nama, kode, produksi, tipe, harga, url: tombol.getAttribute('href') ?? '' },
    }));
};

/**
 * Sedikit lebih lama dari transisi keluar terpanjang (150ms), supaya pembungkus
 * jendela belum dilepas saat animasinya masih berjalan.
 */
const JEDA_TUTUP_MS = 200;

Alpine.data('rincianBahan', () => ({
    // `terbuka` menggerakkan animasinya; `terlihat` menahan pembungkusnya tetap
    // terpasang sampai animasi itu selesai.
    terbuka: false,
    terlihat: false,
    memuat: false,
    galat: '',
    namaProduk: '',
    kodeProduk: '',
    produksiId: '',
    labelTipe: '',
    hargaUnit: '',
    tautanPenuh: '',
    isi: '',

    terimaRincian(detail) {
        // Judul diisi dari baris yang diklik, jadi sudah terbaca sebelum data datang.
        this.namaProduk = detail?.nama ?? '';
        this.kodeProduk = detail?.kode ?? '';
        this.produksiId = detail?.produksi ?? '';
        this.labelTipe = detail?.tipe ?? '';
        // Sudah diformat di Blade; di sini cuma ditampilkan apa adanya.
        this.hargaUnit = detail?.harga ?? '';
        this.tautanPenuh = detail?.url ?? '';
        this.terlihat = true;
        this.terbuka = true;

        this.muat(this.tautanPenuh);
    },

    /**
     * Penanda loading tautan global dipasang di fase capture, jadi ia sudah jalan
     * sebelum handler ini sempat membatalkan klik. Karena halamannya tidak pernah
     * berpindah, penandanya tidak akan pernah lepas sendiri.
     */
    lepasPenandaMemuat(tautan) {
        window.linkLoading?.setLinkLoading(tautan, false);
    },

    tutup() {
        this.terbuka = false;

        // Pembungkus terluar baru dilepas setelah animasi keluar selesai. Kalau
        // langsung disembunyikan, transisi anak-anaknya berjalan di dalam elemen
        // yang sudah display:none -- tidak pernah terlihat, dan jendelanya terasa
        // hilang mendadak. Isinya juga baru dikosongkan di sini, supaya yang
        // memudar isi jendelanya, bukan kotak putih kosong.
        setTimeout(() => {
            if (this.terbuka) {
                return;
            }

            this.terlihat = false;
            this.isi = '';
            this.galat = '';
        }, JEDA_TUTUP_MS);
    },

    async muat(url) {
        if (!url) {
            return;
        }

        this.memuat = true;
        this.galat = '';
        this.isi = '';

        try {
            const respons = await fetch(url + (url.includes('?') ? '&' : '?') + 'fragmen=1', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });

            if (!respons.ok) {
                this.galat = 'Rincian bahan gagal dimuat (status ' + respons.status + ').';

                return;
            }

            this.isi = await respons.text();
        } catch (error) {
            this.galat = 'Rincian bahan gagal dimuat. Periksa koneksi lalu coba lagi.';
        } finally {
            this.memuat = false;
        }
    },

    /** Tautan paginasi di dalam modal dimuat ke modal, bukan memindahkan halaman. */
    lompat(event) {
        const tautan = event.target.closest('a[href]');

        if (!tautan) {
            return;
        }

        event.preventDefault();

        // Tautan paginasi dirender paginator Laravel, jadi tidak bisa diberi
        // data-no-link-loading dari Blade -- penandanya dilepas di sini.
        this.lepasPenandaMemuat(tautan);
        this.muat(tautan.getAttribute('href'));
    },
}));

/**
 * Pratinjau gambar pada halaman Harga Modal.
 *
 * Yang diperbesar adalah gambar yang sudah dimuat browser (gambar_url), bukan
 * halaman Google Drive-nya -- link_gambar menunjuk ke halaman, bukan berkas
 * gambar, jadi tidak bisa dipasang langsung sebagai src.
 */
/**
 * Klik thumbnail: menyiarkan permintaan pratinjau lewat event window.
 *
 * Sengaja tidak memanggil metode komponen Alpine langsung. Tabel produk berada di
 * dalam komponen modal rincian, dan kedua komponen sama-sama punya keadaan
 * "terbuka". Kalau penanganannya lewat pencarian scope Alpine, penulisan dari
 * dalam tabel mendarat di komponen terdekat -- yang membuka modal rincian, bukan
 * pratinjau gambar. Event window memutus ketergantungan pada susunan itu.
 */
window.bukaPratinjauGambar = (event) => {
    // Ctrl/Cmd/Shift-klik dan klik-tengah dibiarkan lewat, supaya tautannya tetap
    // bisa dibuka di tab baru seperti tautan biasa.
    if (event.metaKey || event.ctrlKey || event.shiftKey || event.button !== 0) {
        return;
    }

    const tombol = event.currentTarget;
    const { gambar = '', sematan = '', judul = '', tautan = '' } = tombol.dataset;

    // Tidak ada yang bisa ditampilkan sama sekali: biarkan tautannya bekerja
    // seperti biasa alih-alih membuka jendela kosong.
    if (!gambar && !sematan && !tautan) {
        return;
    }

    event.preventDefault();

    window.dispatchEvent(new CustomEvent('pratinjau-gambar', {
        detail: { gambar, sematan, judul, tautan },
    }));
};

/**
 * Jendela pratinjau gambar.
 *
 * Namanya sengaja berakhiran Gambar. Komponen ini pernah bertetangga dekat dengan
 * modal rincian yang juga memakai `terbuka`, dan tabrakan nama itu tidak
 * memunculkan galat apa pun -- hanya jendela yang salah yang terbuka.
 */
/*
 * Perhitungan margin pada halaman Harga Modal.
 *
 * Rumusnya menyalin App\Services\Inventory\MarginHargaJual -- acuannya di sana,
 * lengkap dengan pengujiannya. Yang di sini hanya supaya angkanya berubah seketika
 * tanpa memuat ulang halaman.
 */
const MARGIN_KELIPATAN = 1000;
const MARGIN_KUNCI_SIMPAN = 'harga-modal:margin-target';
const MARGIN_BAWAAN = 30;

/** Membaca angka bergaya Indonesia: 1.234,5 -> 1234.5 */
const marginKeAngka = (teks) => {
    const bersih = String(teks ?? '').replace(/[^\d,.-]/g, '').replace(/\./g, '').replace(',', '.');
    const angka = Number.parseFloat(bersih);

    return Number.isFinite(angka) ? angka : null;
};

const marginKeRupiah = (teks) => {
    const angka = Number.parseInt(String(teks ?? '').replace(/[^\d]/g, ''), 10);

    return Number.isFinite(angka) ? angka : null;
};

Alpine.data('marginHargaJual', () => ({
    marginGlobal: MARGIN_BAWAAN,
    modalBaris: [],
    // indeks baris -> { jenis: 'margin' | 'jual', nilai: number }
    ubahanBaris: {},

    init() {
        const tersimpan = marginKeAngka(window.localStorage?.getItem(MARGIN_KUNCI_SIMPAN));

        // Disimpan per pengguna di peramban masing-masing: margin satu orang tidak
        // boleh mengubah tampilan orang lain.
        if (tersimpan !== null && tersimpan >= 0 && tersimpan <= 99.9) {
            this.marginGlobal = tersimpan;
        }

        // Baris tanpa harga modal tetap memasang data-modal kosong supaya urutan
        // indeksnya tidak bergeser terhadap yang dipakai Blade.
        this.modalBaris = Array.from(this.$el.querySelectorAll('[data-modal]')).map((el) => {
            const angka = Number.parseFloat(el.dataset.modal);

            return Number.isFinite(angka) && angka > 0 ? angka : null;
        });
    },

    setMarginGlobal(teks) {
        const angka = marginKeAngka(teks);

        if (angka === null || angka < 0 || angka > 99.9) {
            return;
        }

        this.marginGlobal = angka;

        try {
            window.localStorage?.setItem(MARGIN_KUNCI_SIMPAN, String(angka));
        } catch (error) {
            // Peramban dengan penyimpanan dimatikan tetap boleh memakai halamannya.
        }
    },

    bulatkanKeAtas(nilai) {
        // Ke atas, bukan ke terdekat: membulatkan ke bawah menurunkan margin ke
        // bawah target tanpa ada yang menyadarinya.
        //
        // Sisa pembagian pecahan biner dibersihkan dulu -- 700000 / 0,7 menghasilkan
        // 1000000,0000000001, dan tanpa ini hasilnya naik satu kelipatan penuh.
        const bersih = Math.round(nilai * 1e4) / 1e4;

        return Math.ceil(bersih / MARGIN_KELIPATAN) * MARGIN_KELIPATAN;
    },

    jualDariMargin(modal, margin) {
        if (modal === null || margin === null || margin < 0 || margin > 99.9) {
            return null;
        }

        // Margin terhadap harga jual, bukan markup terhadap modal.
        return this.bulatkanKeAtas(modal / (1 - margin / 100));
    },

    jualBaris(i) {
        const modal = this.modalBaris[i] ?? null;

        if (modal === null) {
            return null;
        }

        const ubahan = this.ubahanBaris[i];

        // Harga jual yang diketik langsung tidak dibulatkan ulang -- justru angka
        // bulat pilihan klien itu yang ingin dipertahankan.
        if (ubahan?.jenis === 'jual') {
            return ubahan.nilai;
        }

        return this.jualDariMargin(modal, ubahan?.jenis === 'margin' ? ubahan.nilai : this.marginGlobal);
    },

    /** Margin yang benar-benar didapat dari harga jual yang tampil. */
    marginEfektif(i) {
        const modal = this.modalBaris[i] ?? null;
        const jual = this.jualBaris(i);

        if (modal === null || jual === null || jual <= 0) {
            return null;
        }

        return (jual - modal) / jual * 100;
    },

    marginTarget(i) {
        const ubahan = this.ubahanBaris[i];

        if (ubahan?.jenis === 'margin') {
            return ubahan.nilai;
        }

        if (ubahan?.jenis === 'jual') {
            return this.marginEfektif(i);
        }

        return this.marginGlobal;
    },

    /**
     * Selisih target dengan yang benar-benar didapat, akibat pembulatan ke atas.
     * Untuk barang murah selisihnya bisa terasa, jadi ditampilkan alih-alih diam.
     */
    selisihPembulatan(i) {
        const target = this.marginTarget(i);
        const efektif = this.marginEfektif(i);

        if (target === null || efektif === null) {
            return null;
        }

        return Math.abs(efektif - target) >= 0.05 ? efektif : null;
    },

    setMarginBaris(i, teks) {
        const angka = marginKeAngka(teks);

        if (angka === null || angka < 0 || angka > 99.9) {
            return;
        }

        this.ubahanBaris = { ...this.ubahanBaris, [i]: { jenis: 'margin', nilai: angka } };
    },

    setJualBaris(i, teks) {
        const angka = marginKeRupiah(teks);
        const modal = this.modalBaris[i] ?? null;

        // Harga jual di bawah modal berarti margin negatif; biarkan, itu keputusan
        // penggunanya -- yang penting angkanya masuk akal sebagai bilangan.
        if (angka === null || angka <= 0 || modal === null) {
            return;
        }

        this.ubahanBaris = { ...this.ubahanBaris, [i]: { jenis: 'jual', nilai: angka } };
    },

    diubah(i) {
        return this.ubahanBaris[i] !== undefined;
    },

    kembalikan(i) {
        const salinan = { ...this.ubahanBaris };
        delete salinan[i];
        this.ubahanBaris = salinan;
    },

    jualTampil(i) {
        const jual = this.jualBaris(i);

        return jual === null ? '' : new Intl.NumberFormat('id-ID').format(jual);
    },

    persenTampil(nilai) {
        if (nilai === null) {
            return '';
        }

        return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 }).format(nilai);
    },
}));

Alpine.data('pratinjauGambar', () => ({
    // `terbukaGambar` menggerakkan animasinya; `terlihatGambar` menahan
    // pembungkusnya tetap terpasang sampai animasi itu selesai.
    terbukaGambar: false,
    terlihatGambar: false,
    gagalGambar: false,
    gambar: '',
    sematan: '',
    judulGambar: '',
    tautanGambar: '',

    terimaGambar(detail) {
        this.gambar = detail?.gambar ?? '';
        this.sematan = detail?.sematan ?? '';
        this.judulGambar = detail?.judul ?? '';
        this.tautanGambar = detail?.tautan ?? '';
        this.gagalGambar = false;
        this.terlihatGambar = true;
        this.terbukaGambar = true;
    },

    tutupGambar() {
        this.terbukaGambar = false;

        // Isinya baru dilepas setelah animasi keluar selesai. Kalau langsung
        // dikosongkan, iframe atau gambarnya hilang duluan dan yang memudar tinggal
        // kotak putih -- itu yang membuat penutupannya terasa patah.
        setTimeout(() => {
            if (this.terbukaGambar) {
                return;
            }

            this.terlihatGambar = false;
            this.sematan = '';
            this.gambar = '';
            this.gagalGambar = false;
        }, JEDA_TUTUP_MS);
    },
}));

Alpine.start();
