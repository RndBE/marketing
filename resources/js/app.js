import './bootstrap';

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

window.Alpine = Alpine;

Alpine.plugin(collapse);

// Sidebar store — state dikelola di sini, di-sync dengan localStorage via app.blade.php
Alpine.store('sidebar', {
    open: true,
});

Alpine.start();
