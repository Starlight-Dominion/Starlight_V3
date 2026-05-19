<script>
    import { fade } from 'svelte/transition';
</script>

<div in:fade class="max-w-3xl mx-auto py-20 px-6">
    <div class="text-center mb-16">
        <h1 class="text-5xl font-black text-white uppercase tracking-tighter">Covenant of Shadows</h1>
        <p class="text-[#c5a059] font-bold uppercase tracking-[4px] text-sm mt-4">The Laws Governing the Persistent Realm</p>
    </div>

    <div class="space-y-12 bg-[#0f0f0f] border border-[#2a231e] rounded-3xl p-10 shadow-2xl">
        <section class="space-y-4">
            <h2 class="text-white font-black uppercase tracking-widest text-lg flex items-center gap-3">
                <span class="text-[#3f6b2f]">01.</span> Account Integrity
            </h2>
            <p class="text-gray-500 text-sm leading-relaxed border-l border-[#2a231e] pl-6">
                One (1) kingdom per identity. Multi-accounting is an act of espionage against the infrastructure.
            </p>
        </section>

        <section class="space-y-4">
            <h2 class="text-white font-black uppercase tracking-widest text-lg flex items-center gap-3">
                <span class="text-[#3f6b2f]">02.</span> Automatic Infiltration
            </h2>
            <p class="text-gray-500 text-sm leading-relaxed border-l border-[#2a231e] pl-6">
                Automated scripts and bots are strictly prohibited. Your sovereignty must be maintained by your own hand.
            </p>
        </section>
    </div>
</div>