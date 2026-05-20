<script>
    import { game, resources } from '../stores/gameStore.svelte.js';
    import { fade, slide, fly } from 'svelte/transition';

    let { user_profile = {} } = $props();

    // UI Logic
    let activeTab = $state('identity');
    let loading = $state(false);
    let message = $state(null);

    // Form States
    let identityForm = $state({
        username: game.user?.username || '',
        email: user_profile.email || ''
    });

    let cipherForm = $state({
        current: '',
        new: '',
        confirm: ''
    });

    let avatarForm = $state({
        path: user_profile.avatar || ''
    });

    const isStasisActive = $derived(user_profile.stasis_until && new Date(user_profile.stasis_until) > new Date());

    async function submitRequest(endpoint, body) {
        loading = true;
        message = null;
        
        const fd = new FormData();
        Object.entries(body).forEach(([k, v]) => fd.append(k, v));
        fd.append('_csrf', game.csrf);

        try {
            const res = await fetch(endpoint, {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            message = data;
            if (data.success) {
                // Refresh local game state if handle changed
                if (body.username) game.user.username = body.username;
            }
        } catch (e) {
            message = { success: false, message: "Terminal link lost." };
        } finally {
            loading = false;
        }
    }
</script>

<div in:fade class="space-y-8 pb-20">
    <header class="border-b border-cyan-500/20 pb-6 flex justify-between items-end">
        <div>
            <h1 class="text-4xl font-title font-black text-white uppercase tracking-tighter text-shadow-glow">System Settings</h1>
            <p class="text-cyan-500/60 text-[9px] font-bold uppercase tracking-[4px] mt-2">Commander Profile & Life Support</p>
        </div>
    </header>

    {#if message}
        <div in:slide class="p-4 rounded-xl text-[10px] font-black uppercase text-center border {message.success ? 'bg-cyan-900/20 border-cyan-500 text-cyan-400' : 'bg-red-950/30 border-red-500 text-red-500'}">
            {message.message}
        </div>
    {/if}

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Tab Navigation -->
        <aside class="flex flex-col gap-2">
            {#each ['identity', 'security', 'stasis', 'uplink'] as tab}
                <button 
                    onclick={() => activeTab = tab}
                    class="w-full text-left px-6 py-4 rounded-xl border transition-all font-title text-[10px] uppercase tracking-widest {activeTab === tab ? 'bg-cyan-500/10 border-cyan-500 text-white font-black' : 'bg-black/40 border-white/5 text-gray-600 hover:border-cyan-500/30'}"
                >
                    {tab}
                </button>
            {/each}
        </aside>

        <main class="lg:col-span-3">
            <div class="bg-dark-translucent border border-cyan-500/10 rounded-3xl p-10 relative overflow-hidden shadow-2xl">
                <!-- HUD Decorations -->
                <div class="absolute top-0 right-0 p-4 opacity-5 pointer-events-none">
                    <span class="text-8xl font-title font-black text-white">{activeTab.toUpperCase()}</span>
                </div>

                {#if activeTab === 'identity'}
                    <div in:fly={{ x: 20 }} class="space-y-8">
                        <div class="space-y-1">
                            <h2 class="text-white font-title text-xl uppercase tracking-widest">Identity Handle</h2>
                            <p class="text-[10px] text-gray-500 uppercase">Updating your handle costs 1,000,000 Credits.</p>
                        </div>
                        <form onsubmit={(e) => { e.preventDefault(); submitRequest('/settings/identity', identityForm); }} class="space-y-6">
                            <div class="space-y-2">
                                <label class="text-[9px] font-black text-cyan-800 uppercase ml-2">Handle</label>
                                <input type="text" bind:value={identityForm.username} class="input-terminal" required />
                            </div>
                            <div class="space-y-2">
                                <label class="text-[9px] font-black text-cyan-800 uppercase ml-2">Comms Frequency (Email)</label>
                                <input type="email" bind:value={identityForm.email} class="input-terminal" required />
                            </div>
                            <button type="submit" class="btn-launch w-full py-4 text-sm" disabled={loading}>Synchronize Identity</button>
                        </form>
                    </div>

                {:else if activeTab === 'security'}
                    <div in:fly={{ x: 20 }} class="space-y-8">
                        <h2 class="text-white font-title text-xl uppercase tracking-widest">Cipher Rotation</h2>
                        <form onsubmit={(e) => { e.preventDefault(); submitRequest('/settings/cipher', cipherForm); }} class="space-y-6">
                            <div class="space-y-2">
                                <label class="text-[9px] font-black text-red-900 uppercase ml-2">Current Authorization Cipher</label>
                                <input type="password" bind:value={cipherForm.current} class="input-terminal border-red-900/20" required />
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label class="text-[9px] font-black text-cyan-800 uppercase ml-2">New Cipher</label>
                                    <input type="password" bind:value={cipherForm.new} class="input-terminal" required />
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[9px] font-black text-cyan-800 uppercase ml-2">Confirm Cipher</label>
                                    <input type="password" bind:value={cipherForm.confirm} class="input-terminal" required />
                                </div>
                            </div>
                            <button type="submit" class="btn-launch w-full py-4 text-sm" disabled={loading}>Update Encryption</button>
                        </form>
                    </div>

                {:else if activeTab === 'stasis'}
                    <div in:fly={{ x: 20 }} class="space-y-8">
                        <h2 class="text-white font-title text-xl uppercase tracking-widest">Stasis Control</h2>
                        <div class="bg-black/40 border border-white/5 p-8 rounded-2xl space-y-6">
                            <p class="text-gray-400 text-sm leading-relaxed">
                                Stasis mode suspends all dominion activities. You will be protected from all sorties and espionage, but resource production and citizen growth will cease. 
                            </p>
                            
                            {#if isStasisActive}
                                <div class="p-6 bg-cyan-900/20 border border-cyan-500 rounded-xl text-center space-y-2">
                                    <span class="text-cyan-400 font-black uppercase text-xs tracking-widest">Life Support Active</span>
                                    <p class="text-white font-mono text-sm">Protected Until: {user_profile.stasis_until}</p>
                                </div>
                            {/if}

                            <button 
                                onclick={() => submitRequest('/settings/stasis', {})} 
                                class="w-full {isStasisActive ? 'bg-red-900/20 border-red-500 text-red-500' : 'bg-cyan-700/50 border-cyan-500 text-white'} border py-5 rounded-xl font-title font-black uppercase tracking-[3px] transition-all hover:scale-[1.02]"
                                disabled={loading}
                            >
                                {isStasisActive ? 'Interrupt Stasis' : 'Engage Stasis Protocol'}
                            </button>
                        </div>
                    </div>

                {:else if activeTab === 'uplink'}
                    <div in:fly={{ x: 20 }} class="space-y-8 text-center">
                        <h2 class="text-white font-title text-xl uppercase tracking-widest text-left">Avatar Uplink</h2>
                        
                        <div class="relative inline-block group">
                            <div class="w-40 h-40 rounded-full border-4 border-cyan-500/30 overflow-hidden bg-black mx-auto shadow-[0_0_30px_rgba(34,211,238,0.2)]">
                                {#if avatarForm.path}
                                    <img src={avatarForm.path} alt="Commander" class="w-full h-full object-cover" />
                                {:else}
                                    <div class="w-full h-full flex items-center justify-center text-cyan-900 font-title text-4xl">NO_SIG</div>
                                {/if}
                            </div>
                        </div>

                        <div class="space-y-4 pt-6 text-left">
                            <div class="space-y-2">
                                <label class="text-[9px] font-black text-cyan-800 uppercase ml-2">Direct Image URL</label>
                                <input type="text" bind:value={avatarForm.path} class="input-terminal" placeholder="https://..." />
                            </div>
                            <button 
                                onclick={() => submitRequest('/settings/avatar', { avatar_path: avatarForm.path })} 
                                class="btn-launch w-full py-4 text-sm" 
                                disabled={loading}
                            >
                                Update Visual Sigil
                            </button>
                        </div>
                    </div>
                {/if}
            </div>
        </main>
    </div>
</div>