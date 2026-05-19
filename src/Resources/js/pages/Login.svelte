<script>
    import { game } from '../stores/gameStore.svelte.js';
    import { fade, slide } from 'svelte/transition';
    
    let username = $state('');
    let password = $state('');
    let error = $state(null);
    let loading = $state(false);

    async function handleLogin(e) {
        e.preventDefault();
        loading = true;
        error = null;
        
        const formData = new FormData();
        formData.append('username', username);
        formData.append('password', password);
        formData.append('_csrf', game.csrf);

        try {
            const res = await fetch('/login', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (res.ok) {
                window.location.href = '/dashboard';
            } else {
                error = "Invalid lord credentials.";
                loading = false;
            }
        } catch (e) {
            error = "Signal failure.";
            loading = false;
        }
    }
</script>

<div in:fade class="max-w-md mx-auto py-20 px-6">
    <div class="bg-[#0f0f0f] border border-[#2a231e] rounded-3xl p-10 shadow-2xl">
        <h1 class="text-3xl font-black text-white uppercase tracking-tighter mb-8 text-center">Return to Power</h1>

        {#if error}
            <div in:slide class="bg-red-900/20 border border-red-900/50 p-4 rounded-xl mb-6 text-red-500 text-xs font-bold uppercase text-center">
                {error}
            </div>
        {/if}

        <form onsubmit={handleLogin} class="space-y-6">
            <div class="space-y-2">
                <label for="lord-id" class="text-[10px] font-black text-gray-600 uppercase tracking-widest">Lord Name</label>
                <input id="lord-id" type="text" bind:value={username} class="w-full bg-black border border-[#2a231e] rounded-xl px-4 py-4 text-white focus:outline-none focus:border-[#c5a059]" required />
            </div>

            <div class="space-y-2">
                <label for="lord-phrase" class="text-[10px] font-black text-gray-600 uppercase tracking-widest">Secret Phrase</label>
                <input id="lord-phrase" type="password" bind:value={password} class="w-full bg-black border border-[#2a231e] rounded-xl px-4 py-4 text-white focus:outline-none focus:border-[#c5a059]" required />
            </div>

            <button type="submit" class="w-full btn-primary py-5 disabled:opacity-50" disabled={loading}>
                {loading ? 'Authenticating...' : 'Enter the Shadows'}
            </button>
        </form>

        <p class="mt-8 text-center text-gray-600 text-[10px] font-bold uppercase tracking-widest">
            New sovereign? <a href="/register" class="text-[#c5a059] hover:text-white transition-colors">Forge Your Kingdom</a>
        </p>
    </div>
</div>