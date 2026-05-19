<script>
    import { game } from '../stores/gameStore.svelte.js';
    import { fade, slide } from 'svelte/transition';

    let username = $state('');
    let email = $state('');
    let kingdom_name = $state('');
    let password = $state('');
    let password_confirmation = $state('');
    let loading = $state(false);
    let errors = $state([]);

    async function handleRegister(e) {
        e.preventDefault();
        loading = true;
        errors = [];

        const formData = new FormData();
        formData.append('username', username);
        formData.append('email', email);
        formData.append('kingdom_name', kingdom_name);
        formData.append('password', password);
        formData.append('password_confirmation', password_confirmation);
        formData.append('_csrf', game.csrf);

        try {
            const res = await fetch('/register', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (res.ok) {
                window.location.href = '/login?success=1';
            } else {
                const data = await res.json();
                errors = data.errors || ["The High Command rejected your application."];
                loading = false;
            }
        } catch (e) {
            errors = ["Transmission failure."];
            loading = false;
        }
    }
</script>

<div in:fade class="max-w-md mx-auto py-12 px-6">
    <div class="bg-[#0f0f0f] border border-[#2a231e] rounded-3xl p-8 shadow-2xl">
        <h1 class="text-3xl font-black text-white uppercase tracking-tighter mb-2 text-center">Forge Your Destiny</h1>
        <p class="text-gray-500 text-[10px] font-bold uppercase tracking-[3px] text-center mb-8">Establish your domain in the shadows</p>

        {#if errors.length > 0}
            <div in:slide class="bg-red-900/20 border border-red-900/50 p-4 rounded-xl mb-6">
                {#each errors as error}
                    <p class="text-red-500 text-[10px] font-black uppercase tracking-wide mb-1">• {error}</p>
                {/each}
            </div>
        {/if}

        <form onsubmit={handleRegister} class="space-y-5">
            <div class="space-y-1">
                <label for="reg-username" class="text-[9px] font-black text-gray-600 uppercase tracking-widest ml-2">Lord Identity</label>
                <input id="reg-username" type="text" bind:value={username} class="w-full bg-black border border-[#2a231e] rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#c5a059]" required />
            </div>

            <div class="space-y-1">
                <label for="reg-email" class="text-[9px] font-black text-gray-600 uppercase tracking-widest ml-2">Email Frequency</label>
                <input id="reg-email" type="email" bind:value={email} class="w-full bg-black border border-[#2a231e] rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#c5a059]" required />
            </div>

            <div class="space-y-1">
                <label for="reg-kingdom" class="text-[9px] font-black text-gray-600 uppercase tracking-widest ml-2">Kingdom Name</label>
                <input id="reg-kingdom" type="text" bind:value={kingdom_name} class="w-full bg-black border border-[#2a231e] rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#c5a059]" required />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label for="reg-pass" class="text-[9px] font-black text-gray-600 uppercase tracking-widest ml-2">Cipher</label>
                    <input id="reg-pass" type="password" bind:value={password} class="w-full bg-black border border-[#2a231e] rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#c5a059]" required />
                </div>
                <div class="space-y-1">
                    <label for="reg-confirm" class="text-[9px] font-black text-gray-600 uppercase tracking-widest ml-2">Confirm</label>
                    <input id="reg-confirm" type="password" bind:value={password_confirmation} class="w-full bg-black border border-[#2a231e] rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#c5a059]" required />
                </div>
            </div>

            <button type="submit" class="w-full btn-primary py-5 mt-4 disabled:opacity-50" disabled={loading}>
                {loading ? 'Processing...' : 'Establish Sovereignty'}
            </button>
        </form>
    </div>
</div>