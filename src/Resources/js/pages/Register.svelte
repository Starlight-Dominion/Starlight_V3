<script>
    import { game } from '../stores/gameStore.svelte.js';
    import { fade, slide, fly } from 'svelte/transition';

    const bgUrl = "/images/backgroundMain.avif";
    const raceOptions = ['Human', 'Cyborg', 'Shade', 'Synthera'];

    let formData = $state({
        username: '',
        email: '',
        dominion_name: '',
        race: 'Human',
        password: '',
        password_confirmation: ''
    });

    let loading = $state(false);
    let error = $state(null);

    async function handleRegister(e) {
        e.preventDefault();
        loading = true;
        error = null;

        const submission = new FormData();
        Object.entries(formData).forEach(([key, value]) => submission.append(key, value));
        submission.append('_csrf', game.csrf);

        try {
            const res = await fetch('/register', {
                method: 'POST',
                body: submission,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (res.ok) {
                window.location.href = '/login?success=1';
            } else {
                const data = await res.json();
                error = data.errors?.[0] || "Initialization failed. Consult terminal logs.";
                loading = false;
            }
        } catch (err) {
            error = "Deep-space relay failure.";
            loading = false;
        }
    }
</script>

<div class="min-h-screen w-full bg-cover bg-center bg-fixed flex flex-col items-center justify-center relative px-6 py-20" style="background-image: url('{bgUrl}');">
    <div class="absolute inset-0 bg-gradient-to-b from-[#030712]/80 via-[#030712]/60 to-[#030712]/90 z-0"></div>

    <div class="relative z-10 w-full max-w-md" in:fly={{ y: 20, duration: 800 }}>
        <div class="bg-[#060a19]/90 border-2 border-cyan-500/30 rounded-3xl p-10 shadow-[0_0_50px_rgba(8,145,178,0.2)] backdrop-blur-xl relative overflow-hidden">
            
            <div class="absolute top-0 left-0 w-8 h-8 border-t-2 border-l-2 border-cyan-400 opacity-40"></div>
            <div class="absolute bottom-0 right-0 w-8 h-8 border-b-2 border-r-2 border-cyan-400 opacity-40"></div>

            <header class="text-center mb-8">
                <h1 class="text-3xl font-title font-black text-white uppercase tracking-widest text-shadow-glow">Initialize Sector</h1>
                <p class="text-cyan-500/60 text-[9px] font-bold uppercase tracking-[4px] mt-2">Establish Dominion Control</p>
            </header>

            {#if error}
                <div in:slide class="bg-red-950/30 border border-red-500/50 text-red-400 p-4 rounded-xl mb-6 text-[10px] font-black uppercase tracking-[2px] text-center">
                    {error}
                </div>
            {/if}

            <form onsubmit={handleRegister} class="space-y-4">
                <div class="space-y-1">
                    <label for="register-email" class="text-[9px] font-black text-cyan-800 uppercase tracking-[2px] ml-2">Comms Frequency (Email)</label>
                    <input id="register-email" type="email" name="email" bind:value={formData.email} class="input-terminal" required />
                </div>
                <div class="space-y-1">
                    <label for="register-username" class="text-[9px] font-black text-cyan-800 uppercase tracking-[2px] ml-2">Identity Handle</label>
                    <input id="register-username" type="text" name="username" bind:value={formData.username} class="input-terminal" required />
                </div>
                <div class="space-y-1">
                    <label for="register-dominion-name" class="text-[9px] font-black text-cyan-800 uppercase tracking-[2px] ml-2">Dominion Designation</label>
                    <input id="register-dominion-name" type="text" name="dominion_name" bind:value={formData.dominion_name} class="input-terminal" required />
                </div>
                <div class="space-y-1">
                    <label for="register-race" class="text-[9px] font-black text-cyan-800 uppercase tracking-[2px] ml-2">Evolutionary Strain</label>
                    <div class="relative">
                        <select id="register-race" name="race" bind:value={formData.race} class="input-terminal appearance-none cursor-pointer">
                            {#each raceOptions as r}
                                <option value={r}>{r}</option>
                            {/each}
                        </select>
                        <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-cyan-500">▼</div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label for="register-password" class="text-[9px] font-black text-cyan-800 uppercase tracking-[2px] ml-2">Cipher</label>
                        <input id="register-password" type="password" name="password" bind:value={formData.password} class="input-terminal" required />
                    </div>
                    <div class="space-y-1">
                        <label for="register-password-confirmation" class="text-[9px] font-black text-cyan-800 uppercase tracking-[2px] ml-2">Verify</label>
                        <input id="register-password-confirmation" type="password" name="password_confirmation" bind:value={formData.password_confirmation} class="input-terminal" required />
                    </div>
                </div>

                <button type="submit" class="w-full bg-cyan-700/50 hover:bg-cyan-600 border border-cyan-500/50 text-white font-title font-black py-5 mt-4 rounded-xl uppercase tracking-[4px] transition-all disabled:opacity-50" disabled={loading}>
                    {loading ? 'Processing...' : 'Establish Sovereignty'}
                </button>
            </form>

            <footer class="mt-8 pt-6 border-t border-white/5 text-center">
                <p class="text-gray-600 text-[9px] font-bold uppercase tracking-[3px]">
                    Already Commissioned? 
                    <a href="/login" class="text-cyan-400 hover:text-white transition-colors ml-1">Establish Link</a>
                </p>
            </footer>
        </div>
    </div>
</div>