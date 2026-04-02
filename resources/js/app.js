import './bootstrap';

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

window.Alpine = Alpine;

Alpine.plugin(collapse);

// Sidebar store — state dikelola di sini, di-sync dengan localStorage via app.blade.php
Alpine.store('sidebar', {
    open: true,
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

Alpine.start();
