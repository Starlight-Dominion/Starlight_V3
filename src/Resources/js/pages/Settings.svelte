<script>
    import { game } from '../stores/gameStore.svelte.js';
    import { fade, slide } from 'svelte/transition';

    let username = $state(game.user?.username || '');
    let current_password = $state('');
    let new_password = $state('');
    let confirm_password = $state('');
    let loading = $state(false);
    let message = $state(null);

    async function updateProfile(e) {
        e.preventDefault();
        loading = true;
        message = null;
        
        const formData = new FormData();
        formData.append('username', username);
        formData.append('current_password', current_password);
        formData.append('new_password', new_password);
        formData.append('confirm_password', confirm_password);
        formData.append('_csrf', game.csrf);

        try {
            const res = await fetch('/settings/profile', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            message = await res.json();
            if (message.success) {
                setTimeout(() => window.location.reload(), 1500);
            }
        } catch (e) {
            message = { success: false, message: "Profile update failure." };
        } finally {
            loading = false;
        }
    }
</script>

<div in:fade class="max-w-2xl mx-auto space-y-8 pb-20">
    <header class="border-b border-[#2a231e] pb-6">
        <h1 class="text-4xl font-black text-white uppercase tracking-tighter">Identity & Sigil</h1>
    </header>

    {#if message}
        <div in:slide class="p-4 rounded-xl text-[10px] font-black uppercase text-center border {message.success ? 'bg-[#3f6b2f]/20 border-[#3f6b2f] text-[#3f6b2f]' : 'bg-red-900/20 border-red-900 text-red-500'}">
            {message.message}
        </div>
    {/if}

    <div class="bg-[#0f0f0f] border border-[#2a231e] rounded-3xl p-8 space-y-8">
        <form onsubmit={updateProfile} class="space-y-6">
            <div class="space-y-2">
                <label for="set-user" class="text-[9px] font-black text-gray-600 uppercase tracking-widest ml-2">Lord Identity</label>
                <input id="set-user" type="text" bind:value={username} class="w-full bg-black border border-[#2a231e] rounded-xl px-4 py-4 text-white focus:outline-none focus:border-[#c5a059]" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-[#2a231e]">
                <div class="space-y-2">
                    <label for="set-newpass" class="text-[9px] font-black text-gray-600 uppercase tracking-widest ml-2">New Cipher</label>
                    <input id="set-newpass" type="password" bind:value={new_password} class="w-full bg-black border border-[#2a231e] rounded-xl px-4 py-4 text-white focus:outline-none focus:border-[#c5a059]" />
                </div>
                <div class="space-y-2">
                    <label for="set-confpass" class="text-[9px] font-black text-gray-600 uppercase tracking-widest ml-2">Confirm Cipher</label>
                    <input id="set-confpass" type="password" bind:value={confirm_password} class="w-full bg-black border border-[#2a231e] rounded-xl px-4 py-4 text-white focus:outline-none focus:border-[#c5a059]" />
                </div>
            </div>

            <div class="space-y-2 pt-6 border-t border-[#2a231e]">
                <label for="set-auth" class="text-[9px] font-black text-red-900 uppercase tracking-widest ml-2">Authorizing Phrase (Required)</label>
                <input id="set-auth" type="password" bind:value={current_password} class="w-full bg-black border border-[#2a231e] rounded-xl px-4 py-4 text-white focus:outline-none focus:border-[#8b0000]" required />
            </div>

            <button type="submit" class="w-full btn-primary py-5 disabled:opacity-50" disabled={loading}>
                {loading ? 'Transcribing...' : 'Commit Changes'}
            </button>
        </form>
    </div>
</div>