<script>
    import { game } from '../stores/gameStore.svelte.js';
    import { fade, slide, fly } from 'svelte/transition';
    
    let formData = $state({
        username: '',
        password: ''
    });
    
    let error = $state(null);
    let loading = $state(false);
    const bgUrl = "/images/backgroundMain.avif";

    async function handleLogin(e) {
        e.preventDefault();
        loading = true;
        error = null;
        
        const submission = new FormData();
        submission.append('username', formData.username);
        submission.append('password', formData.password);
        submission.append('_csrf', game.csrf);

        try {
            const res = await fetch('/login', {
                method: 'POST',
                body: submission,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (res.ok) {
                window.location.href = '/dashboard';
            } else {
                const data = await res.json();
                error = data.errors?.[0] || "Invalid identity handle or encryption key.";
                loading = false;
            }
        } catch (err) {
            error = "Neural link failed. Verify sector connectivity.";
            loading = false;
        }
    }
</script>

<div 
    class="min-h-screen w-full bg-cover bg-center bg-fixed flex flex-col items-center justify-center relative px-6 py-20"
    style="background-image: url('{bgUrl}');"
>
    <div class="absolute inset-0 bg-gradient-to-b from-[#030712]/80 via-[#030712]/60 to-[#030712]/90 z-0"></div>

    <div class="relative z-10 w-full max-w-md" in:fly={{ y: 20, duration: 800 }}>
        <div class="bg-[#060a19]/90 border-2 border-cyan-500/30 rounded-3xl p-10 shadow-[0_0_50px_rgba(8,145,178,0.2)] backdrop-blur-xl relative overflow-hidden">
            
            <div class="absolute top-0 left-0 w-8 h-8 border-t-2 border-l-2 border-cyan-400 opacity-40"></div>
            <div class="absolute bottom-0 right-0 w-8 h-8 border-b-2 border-r-2 border-cyan-400 opacity-40"></div>

            <header class="text-center mb-10">
                <h1 class="text-3xl font-title font-black text-white uppercase tracking-widest text-shadow-glow">Establish Link</h1>
                <p class="text-cyan-500/60 text-[9px] font-bold uppercase tracking-[4px] mt-2">Secured Sector Authentication</p>
            </header>

            {#if error}
                <div in:slide class="bg-red-950/30 border border-red-500/50 text-red-400 p-4 rounded-xl mb-8 text-[10px] font-black uppercase tracking-[2px] text-center">
                    SYSTEM ALERT: {error}
                </div>
            {/if}

            <form onsubmit={handleLogin} class="space-y-6" data-testid="login-form">
                <div class="space-y-2">
                    <label class="text-[9px] font-black text-cyan-800 uppercase tracking-[2px] ml-2">Commander Identity</label>
                    <input type="text" name="username" data-testid="login-username" bind:value={formData.username} class="input-terminal" placeholder="HANDLE / ID" required />
                </div>

                <div class="space-y-2">
                    <div class="flex justify-between items-center px-2">
                        <label class="text-[9px] font-black text-cyan-800 uppercase tracking-[2px]">Encryption Key</label>
                        <span class="text-[8px] font-bold text-gray-600 uppercase tracking-widest cursor-pointer hover:text-cyan-400">Lost Cipher?</span>
                    </div>
                    <input type="password" name="password" data-testid="login-password" bind:value={formData.password} class="input-terminal" placeholder="••••••••" required />
                </div>

                <button type="submit" data-testid="login-submit" class="w-full bg-cyan-700/50 hover:bg-cyan-600 border border-cyan-500/50 text-white font-title font-black py-5 rounded-xl uppercase tracking-[4px] transition-all disabled:opacity-50 shadow-[0_0_20px_rgba(8,145,178,0.2)]" disabled={loading}>
                    {loading ? 'Establishing Neural Link...' : 'Authorize Access'}
                </button>
            </form>

            <footer class="mt-10 pt-8 border-t border-white/5 text-center">
                <p class="text-gray-600 text-[9px] font-bold uppercase tracking-[3px]">
                    Unregistered Commander? 
                    <a href="/register" class="text-cyan-400 hover:text-white transition-colors ml-1">Enlist Sector</a>
                </p>
            </footer>
        </div>
    </div>
</div>