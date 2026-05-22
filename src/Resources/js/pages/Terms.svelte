<script>
    import { fade } from 'svelte/transition';
</script>

<div in:fade class="max-w-4xl mx-auto py-32 px-6 space-y-16 pb-40">
    <header class="border-b border-cyan-500/20 pb-12 text-center md:text-left">
        <h1 class="text-5xl md:text-7xl font-title font-black text-white uppercase tracking-tighter text-shadow-glow">Sector Laws</h1>
        <p class="text-cyan-500/60 text-[10px] font-bold uppercase tracking-[8px] mt-4 italic">Operational Protocols & Neural Conduct Agreement</p>
    </header>

    <div class="space-y-8">
        <!-- 01. IDENTITY & INTEGRITY -->
        <div class="bg-dark-translucent border border-white/5 rounded-3xl p-10 shadow-2xl relative overflow-hidden group hover:border-cyan-500/30 transition-all">
            <div class="absolute top-0 right-0 p-8 opacity-5 pointer-events-none font-title font-black text-white italic text-4xl uppercase">
                IDENTITY
            </div>
            <section class="space-y-6 relative z-10">
                <h2 class="text-white font-title font-black uppercase tracking-[3px] text-xl flex items-center gap-4">
                    <span class="text-cyan-500 text-sm">01.</span> Sovereign Identity
                </h2>
                <div class="text-gray-400 text-sm leading-relaxed space-y-4 italic">
                    <p>Commanders are authorized for exactly one (1) neural uplink per individual identity. The initialization of multiple sectors (multi-accounting) constitutes a tier-one breach of the planetary infrastructure.</p>
                    <p>Detecting alternative comms frequencies linked to a single biometric signature will result in immediate decommissioning of all associated sectors without appeal.</p>
                </div>
            </section>
        </div>

        <!-- 02. THIRD-PARTY & API PROTOCOLS -->
        <div class="bg-dark-translucent border border-cyan-500/10 rounded-3xl p-10 shadow-2xl relative overflow-hidden group hover:border-cyan-500/30 transition-all">
            <div class="absolute top-0 right-0 p-8 opacity-5 pointer-events-none font-title font-black text-white italic text-4xl uppercase">
                PROTOCOLS
            </div>
            <section class="space-y-6 relative z-10">
                <h2 class="text-white font-title font-black uppercase tracking-[3px] text-xl flex items-center gap-4">
                    <span class="text-cyan-400 text-sm">02.</span> Technical Integration (API)
                </h2>
                <div class="text-gray-300 text-sm leading-relaxed space-y-4">
                    <p>High Command permits the use of third-party software and external tactical overlays **exclusively** when integrated via the official **Neural API Gate**.</p>
                    <div class="bg-black/40 border border-cyan-500/20 p-6 rounded-xl space-y-3">
                        <span class="text-[10px] font-black text-cyan-400 uppercase tracking-widest">Article 2.A: Authorized Use</span>
                        <p class="text-xs text-gray-500 italic">External tools may be used for telemetry analysis, resource visualization, and market monitoring, provided they adhere to the assigned Rate Limits (RPM).</p>
                    </div>
                    <div class="bg-red-950/20 border border-red-900/30 p-6 rounded-xl space-y-3">
                        <span class="text-[10px] font-black text-red-500 uppercase tracking-widest">Article 2.B: Prohibited Automation</span>
                        <p class="text-xs text-red-400 font-bold">THE AUTOMATION OF OFFENSIVE ACTIONS—INCLUDING BUT NOT LIMITED TO ASSAULTS, STEALTH INFILTRATION, AND TACTICAL STRIKES—IS STRICTLY FORBIDDEN.</p>
                        <p class="text-[11px] text-gray-500 italic">Any software that bypasses manual neural authorization for battlefield engagement is classified as a hostile virus. Utilization will result in total account termination and asset liquidation.</p>
                    </div>
                </div>
            </section>
        </div>

        <!-- 03. TERMINATION & LIABILITY -->
        <div class="bg-dark-translucent border border-white/5 rounded-3xl p-10 shadow-2xl relative overflow-hidden group hover:border-red-900/20 transition-all">
            <div class="absolute top-0 right-0 p-8 opacity-5 pointer-events-none font-title font-black text-white italic text-4xl uppercase">
                LIABILITY
            </div>
            <section class="space-y-6 relative z-10">
                <h2 class="text-white font-title font-black uppercase tracking-[3px] text-xl flex items-center gap-4">
                    <span class="text-red-900 text-sm">03.</span> Decommissioning
                </h2>
                <div class="text-gray-400 text-sm leading-relaxed space-y-4 italic">
                    <p>Starlight Dominion High Command reserves the absolute right to terminate neural access (Account Termination) for any Commander found in violation of these Sector Laws.</p>
                    <p>All virtual assets, credits, and military divisions are the property of the Dominion infrastructure. No compensation will be provided for liquidated assets following a protocol breach.</p>
                </div>
            </section>
        </div>
    </div>

    <footer class="text-center space-y-6">
        <div class="h-px bg-gradient-to-r from-transparent via-cyan-500/20 to-transparent w-full"></div>
        <p class="text-[10px] text-gray-600 uppercase tracking-[4px] font-black">
            Neural Uplink Active &bull; Compliance is Mandatory &bull; Est. 2026
        </p>
    </footer>
</div>
