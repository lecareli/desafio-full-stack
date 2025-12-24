@if ($paginator->hasPages())
    <nav aria-label="Paginação"
        style="display:flex; align-items:center; justify-content:space-between; gap: 12px; flex-wrap: wrap;">
        <div class="text" style="font-size: 13px;">
            @if ($paginator->firstItem() !== null)
                Mostrando {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} de {{ $paginator->total() }}
                resultados
            @else
                {{ $paginator->count() }} resultados
            @endif
        </div>

        <div style="display:flex; gap: 8px; align-items:center; flex-wrap: wrap; justify-content: flex-end;">
            @if ($paginator->onFirstPage())
                <span class="btn" aria-disabled="true" style="opacity:.5; cursor:not-allowed; text-decoration:none;">
                    Anterior
                </span>
            @else
                <a class="btn" href="{{ $paginator->previousPageUrl() }}" rel="prev" style="text-decoration:none;">
                    Anterior
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="text" style="padding: 0 4px; font-size: 13px;">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="btn btn-primary" aria-current="page"
                                style="cursor:default; text-decoration:none;">
                                {{ $page }}
                            </span>
                        @else
                            <a class="btn" href="{{ $url }}" style="text-decoration:none;">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="btn" href="{{ $paginator->nextPageUrl() }}" rel="next" style="text-decoration:none;">
                    Próxima
                </a>
            @else
                <span class="btn" aria-disabled="true" style="opacity:.5; cursor:not-allowed; text-decoration:none;">
                    Próxima
                </span>
            @endif
        </div>
    </nav>
@endif
