@props(['vehicle'])

<div x-data="{ 
    isSelected: isVehicleSelected({{ $vehicle->id }}),
    toggle() {
        toggleComparison({{ $vehicle->id }});
        this.isSelected = isVehicleSelected({{ $vehicle->id }});
    }
}" 
class="group relative bg-zinc-950 border border-zinc-900 overflow-hidden hover:border-accent transition-all duration-500">
    
    <!-- Tag (Featured) -->
    @if($vehicle->is_featured)
    <div class="absolute top-4 left-4 z-10">
        <span class="bg-accent text-[10px] font-black uppercase tracking-widest px-3 py-1 text-white">
            Premium
        </span>
    </div>
    @endif

    <!-- Selection Overlay for Comparison -->
    <div class="absolute top-4 right-4 z-10">
        <button @click.prevent="toggle()" 
            :class="isSelected ? 'bg-accent text-white' : 'bg-zinc-900/50 text-zinc-400 hover:text-white'"
            class="w-10 h-10 flex items-center justify-center transition-all duration-300 backdrop-blur-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path x-show="!isSelected" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                <path x-show="isSelected" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
        </button>
    </div>

    <!-- Image Container -->
    <div class="aspect-[16/10] overflow-hidden grayscale group-hover:grayscale-0 transition-all duration-700">
        @if($vehicle->primaryImage)
            @if(Str::startsWith($vehicle->primaryImage->path, 'http'))
                <img loading="lazy" src="{{ $vehicle->primaryImage->path }}" alt="{{ $vehicle->name }}" class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-1000" />
            @else
                <img loading="lazy" src="{{ Storage::url($vehicle->primaryImage->path) }}" alt="{{ $vehicle->name }}" class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-1000" />
            @endif
        @else
            <img src="https://placehold.co/800x600/111111/ffffff?text=No+Image" alt="{{ $vehicle->name }}" class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-1000" />
        @endif
    </div>

    <!-- Content -->
    <div class="p-8">
        <div class="flex justify-between items-start mb-4">
            <div>
                <p class="text-xs font-black text-accent uppercase tracking-widest mb-1">{{ $vehicle->brand->name }}</p>
                <h3 class="text-2xl font-black text-white uppercase tracking-tighter leading-none group-hover:text-accent transition-colors">
                    {{ $vehicle->name }}
                </h3>
            </div>
        </div>

        <div class="flex items-end justify-between mt-8 pt-6 border-t border-zinc-900">
            <div>
                <p class="text-[10px] font-black text-zinc-500 uppercase tracking-widest">Giá niêm yết</p>
                <p class="text-xl font-black text-white tracking-tighter">
                    {{ number_format($vehicle->price) }} <span class="text-xs text-zinc-500">VNĐ</span>
                </p>
                @if($vehicle->deposit_amount)
                    <p class="text-[10px] text-zinc-600 uppercase tracking-widest mt-1">
                        Cọc: <span class="text-zinc-400">{{ number_format($vehicle->deposit_amount) }}</span>
                    </p>
                @endif
            </div>
            
            <div class="flex items-center gap-3">
                {{-- Add to Cart --}}
                <form action="{{ route('cart.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $vehicle->id }}">
                    <input type="hidden" name="quantity" value="1">
                    <button type="submit" title="Thêm vào giỏ hàng"
                        class="w-10 h-10 flex items-center justify-center bg-zinc-900 border border-zinc-800 text-zinc-400 hover:border-accent hover:text-accent transition-all duration-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-4H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </button>
                </form>

                {{-- Explore Link --}}
                <a href="{{ route('products.show', $vehicle->slug) }}" class="inline-flex items-center gap-2 group/btn">
                    <span class="text-[10px] font-black uppercase tracking-widest text-white group-hover/btn:mr-2 transition-all">Khám phá</span>
                    <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </a>
            </div>
        </div>
    </div>
</div>
