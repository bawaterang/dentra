<?php

use Livewire\Component;

new class extends Component
{
    public int $count = 0;

    public function increment()
    {
        $this->count++;
    }

    public function decrement()
    {
        $this->count--;
    }
};
?>

<div>
    <h2 class="text-3xl font-semibold mb-6 flex items-center justify-center gap-2">
        <span class="text-amber-500">⚡</span> Livewire Counter
    </h2>
    <div class="flex flex-col items-center gap-6 p-6 rounded-2xl bg-[#0a0a0a]/5 dark:bg-white/5 border border-black/5 dark:border-white/5">
        <div class="text-6xl font-bold font-mono tracking-tighter text-black dark:text-white">
            {{ $count }}
        </div>
        <div class="flex items-center gap-4">
            <button wire:click="decrement" class="flex items-center justify-center w-12 h-12 rounded-full bg-rose-500 hover:bg-rose-600 text-white shadow-lg hover:shadow-xl transition-all active:scale-95 text-2xl font-medium">
                -
            </button>
            <button wire:click="increment" class="flex items-center justify-center w-12 h-12 rounded-full bg-emerald-500 hover:bg-emerald-600 text-white shadow-lg hover:shadow-xl transition-all active:scale-95 text-2xl font-medium">
                +
            </button>
        </div>
    </div>
</div>