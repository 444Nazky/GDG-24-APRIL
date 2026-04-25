<div class="max-w-6xl mx-auto p-4 md:p-8 flex flex-col md:flex-row gap-8 items-start justify-center min-h-screen bg-neutral-900">
    {{-- Game Board --}}
    <div class="flex-shrink-0 w-full md:w-[600px]">
        <div class="aspect-square grid grid-cols-8 grid-rows-8 border-4 border-[#505050] shadow-2xl relative select-none">
            @foreach($squares as $squareName => $square)
                @php
                    $isDark = $square['isDark'];
                    $bgColor = $isDark ? 'bg-[#769656]' : 'bg-[#eeeed2]';
                    $isSelected = $selectedSquare === $squareName;
                    $isLegalMove = isset($legalMoves[$squareName]);
                    $isCapture = false;
                    
                    if ($isLegalMove) {
                        $san = $legalMoves[$squareName]['san'] ?? '';
                        // In algebraic notation 'x' indicates a capture
                        if (str_contains($san, 'x') || $square['piece']) {
                            $isCapture = true;
                        }
                    }
                @endphp

                <div 
                    wire:click="selectSquare('{{ $squareName }}')"
                    class="relative w-full h-full flex items-center justify-center 
                           {{ $bgColor }} 
                           {{ $isSelected ? 'bg-opacity-80 ring-inset ring-4 ring-[#f6f669]/50' : '' }}
                           hover:bg-opacity-90 transition-colors cursor-pointer"
                >
                    {{-- File/Rank Labels --}}
                    @if(str_ends_with($squareName, '1'))
                        <div class="absolute bottom-0.5 right-1 text-[10px] font-bold {{ $isDark ? 'text-[#eeeed2]' : 'text-[#769656]' }}">
                            {{ substr($squareName, 0, 1) }}
                        </div>
                    @endif
                    @if(str_starts_with($squareName, 'a'))
                        <div class="absolute top-0.5 left-1 text-[10px] font-bold {{ $isDark ? 'text-[#eeeed2]' : 'text-[#769656]' }}">
                            {{ substr($squareName, 1, 1) }}
                        </div>
                    @endif

                    {{-- Highlight selected square --}}
                    @if($isSelected)
                        <div class="absolute inset-0 bg-[#f6f669] mix-blend-multiply opacity-50"></div>
                    @endif

                    {{-- Piece --}}
                    @if($square['piece'])
                        @php
                            $pieceColor = $square['piece']['color'] === 'w' ? 'w' : 'b';
                            $pieceType = $square['piece']['type'];
                            $svgId = $pieceColor . strtolower($pieceType);
                        @endphp
                        
                        <div class="z-10 w-[90%] h-[90%] drop-shadow-md pointer-events-none">
                            @include('livewire.partials.chess-pieces', ['piece' => $svgId])
                        </div>
                    @endif

                    {{-- Legal Move Indicators --}}
                    @if($isLegalMove)
                        @if($isCapture)
                            {{-- Capture Outline --}}
                            <div class="absolute inset-0 ring-[5px] ring-inset ring-black/20 rounded-full m-1 pointer-events-none z-20"></div>
                        @else
                            {{-- Move Dot --}}
                            <div class="absolute w-[30%] h-[30%] bg-black/20 rounded-full pointer-events-none z-20"></div>
                        @endif
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Side Panel --}}
    <div class="flex-grow w-full md:w-auto flex flex-col bg-neutral-800 rounded-lg shadow-xl overflow-hidden text-neutral-200 border border-neutral-700">
        {{-- Status/Turn Indicator --}}
        <div class="p-6 bg-neutral-900 border-b border-neutral-700 flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-white mb-1">
                    {{ $turn === 'w' ? 'White to Move' : 'Black to Move' }}
                </h2>
                <div class="text-sm font-semibold {{ str_contains($status, 'Checkmate') ? 'text-red-500' : 'text-neutral-400' }}">
                    {{ $status }}
                </div>
            </div>
            <div class="w-12 h-12 rounded-md shadow-inner flex items-center justify-center {{ $turn === 'w' ? 'bg-white' : 'bg-neutral-800 border border-neutral-600' }}">
                 <div class="w-10 h-10">
                     @include('livewire.partials.chess-pieces', ['piece' => $turn . 'k'])
                 </div>
            </div>
        </div>

        {{-- Move History --}}
        <div class="flex-grow p-4 overflow-y-auto max-h-[400px]">
            <h3 class="text-sm uppercase tracking-wider text-neutral-500 font-bold mb-3">Move History</h3>
            <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm font-mono">
                @foreach(array_chunk($moveHistory, 2) as $index => $pair)
                    <div class="col-span-2 flex items-center py-1 border-b border-neutral-700/50">
                        <div class="w-8 text-neutral-500 text-right pr-2">{{ $index + 1 }}.</div>
                        <div class="flex-1 flex gap-2">
                            <span class="w-1/2 font-semibold hover:bg-neutral-700 px-2 py-0.5 rounded transition">
                                {{ is_array($pair[0]) ? ($pair[0]['san'] ?? '') : $pair[0] }}
                            </span>
                            @if(isset($pair[1]))
                                <span class="w-1/2 font-semibold hover:bg-neutral-700 px-2 py-0.5 rounded transition">
                                    {{ is_array($pair[1]) ? ($pair[1]['san'] ?? '') : $pair[1] }}
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
                @if(empty($moveHistory))
                    <div class="col-span-2 text-neutral-500 italic py-2">No moves yet.</div>
                @endif
            </div>
        </div>

        {{-- Captured Pieces --}}
        <div class="p-4 bg-neutral-900 border-t border-neutral-700 flex flex-col gap-3">
             {{-- Captured by White (Black pieces) --}}
             <div class="min-h-6 flex flex-wrap items-center gap-0.5">
                 @foreach($capturedWhite as $p)
                     <div class="w-6 h-6 -ml-1.5 first:ml-0 drop-shadow-sm">
                         @include('livewire.partials.chess-pieces', ['piece' => 'b' . strtolower($p)])
                     </div>
                 @endforeach
                 @if(empty($capturedWhite))
                    <span class="text-xs text-neutral-600">White has no captures</span>
                 @endif
             </div>
             <hr class="border-neutral-700/50">
             {{-- Captured by Black (White pieces) --}}
             <div class="min-h-6 flex flex-wrap items-center gap-0.5">
                 @foreach($capturedBlack as $p)
                     <div class="w-6 h-6 -ml-1.5 first:ml-0 drop-shadow-sm">
                         @include('livewire.partials.chess-pieces', ['piece' => 'w' . strtolower($p)])
                     </div>
                 @endforeach
                 @if(empty($capturedBlack))
                    <span class="text-xs text-neutral-600">Black has no captures</span>
                 @endif
             </div>
        </div>
    </div>
</div>
