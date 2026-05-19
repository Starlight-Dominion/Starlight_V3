// src/Resources/js/bootstrap.js
import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * CSRF Protection
 * Injects the token from our Svelte store into all Axios requests
 */
import { state } from './stores/gameStore.svelte.js';
import { get } from 'svelte/store';

window.axios.interceptors.request.use(config => {
    const currentState = get(state);
    if (currentState.csrf) {
        config.headers['X-CSRF-TOKEN'] = currentState.csrf;
        // Also support standard POST field if needed
        if (config.method === 'post') {
            if (config.data instanceof FormData) {
                config.data.append('_csrf', currentState.csrf);
            } else {
                config.data = { ...config.data, _csrf: currentState.csrf };
            }
        }
    }
    return config;
});