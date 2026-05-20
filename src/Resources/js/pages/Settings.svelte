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

    // Avatar Logic
    let avatarPreview = $state(user_profile.avatar || '');
    let fileInput = $state(null);

    const isStasisActive = $derived(user_profile.stasis_until && new Date(user_profile.stasis_until) > new Date());

    /**
     * Standard JSON submission for text fields
     */
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
            if (data.success && body.username) {
                game.user.username = body.username;
            }
        } catch (e) {
            message = { success: false, message: "Terminal link lost." };
        } finally {
            loading = false;
        }
    }

    /**
     * Specialized Binary Submission for Avatar Upload
     */
    async function uploadAvatar(e) {
        const file = e.target.files[0];
        if (!file) return;

        // Local Preview
        avatarPreview = URL.createObjectURL(file);
        
        loading = true;
        message = null;

        const fd = new FormData();
        fd.append('avatar', file);
        fd.append('_csrf', game.csrf);

        try {
            const res = await fetch('/settings/avatar', {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            message = data;
            if (data.success) {
                avatarPreview = data.path;
            }
        } catch (e) {
            message = { success: false, message: "Sigil transmission failed." };
        } finally {
            loading = false;
        }
    }
</script>

<div in:fade class="space-y-8 pb-20">
    <header class="border-b border-cyan-500/20 pb-6">
        <h1 class="text-4xl font-title font-black text-white uppercase tracking-tighter text-shadow-glow">System Settings</h1>
        <p class="text-cyan-500/60 text-[9px] font-bold uppercase tracking-[4px] mt-2">Commander Profile & Life Support</p>
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
                            <p class="text-gray-400 text-sm leading-relaxed font-sans">
                                Stasis mode suspends all dominion activities. Sorties and espionage against your sector are disabled. Resource extraction ceases immediately.
                            </p>
                            
                            {#if isStasisActive}
                                <div class="p-6 bg-cyan-900/20 border border-cyan-500 rounded-xl text-center">
                                    <span class="text-cyan-400 font-black uppercase text-[10px] tracking-widest">Life Support: STABLE</span>
                                    <p class="text-white font-mono text-xs mt-1">Expiration: {user_profile.stasis_until}</p>
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
                    <div in:fly={{ x: 20 }} class="space-y-8">
                        <h2 class="text-white font-title text-xl uppercase tracking-widest">Visual Sigil Uplink</h2>
                        
                        <div class="flex flex-col items-center gap-10">
                            <div class="relative group">
                                <div class="w-48 h-48 rounded-full border-4 border-cyan-500/30 overflow-hidden bg-black shadow-[0_0_50px_rgba(34,211,238,0.15)] transition-all group-hover:border-cyan-400">
                                    {#if avatarPreview}
                                        <img src={avatarPreview} alt="Sigil" class="w-full h-full object-cover" />
                                    {:else}
                                        <div class="w-full h-full flex items-center justify-center text-cyan-950 font-title text-4xl">NO_SIG</div>
                                    {/if}
                                </div>
                                <div class="absolute inset-0 bg-cyan-500/10 rounded-full opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"></div>
                            </div>

                            <div class="bg-black/40 border border-white/5 p-8 rounded-2xl w-full space-y-6">
                                <div class="space-y-2 text-center md:text-left">
                                    <h3 class="text-cyan-400 font-bold uppercase text-xs tracking-widest">Sigil Upload Constraints</h3>
                                    <ul class="text-[9px] text-gray-500 uppercase tracking-widest space-y-1 font-mono">
                                        <li>• Max Payload: 10.0 MB</li>
                                        <li>• Max Resolution: 500 x 500 px</li>
                                        <li>• Formats: JPEG, PNG, WEBP</li>
                                    </ul>
                                </div>

                                <input 
                                    type="file" 
                                    accept="image/*" 
                                    class="hidden" 
                                    bind:this={fileInput} 
                                    onchange={uploadAvatar} 
                                />

                                <button 
                                    onclick={() => fileInput.click()} 
                                    class="btn-launch w-full py-5 text-sm" 
                                    disabled={loading}
                                >
                                    {loading ? 'Transmitting Binary...' : 'Uplink Local Sigil'}
                                </button>
                            </div>
                        </div>
                    </div>
                {/if}
            </div>
        </main>
    </div>
</div>