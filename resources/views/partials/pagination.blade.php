@if ($paginator->hasPages())
    <nav aria-label="Pages" class="mt-6 flex flex-wrap items-center justify-between gap-3 text-xs text-muted">
        <p>Showing {{ $paginator->firstItem() }} to {{ $paginator->lastItem() }} of {{ $paginator->total() }}</p>

        <ul class="flex flex-wrap items-center gap-1">
            <li>
                @if ($paginator->onFirstPage())
                    <span class="page-link opacity-40">Previous</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="page-link">Previous</a>
                @endif
            </li>

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="page-link">{{ $element }}</span></li>
                @else
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="page-link bg-brass font-semibold text-ink">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" aria-label="Page {{ $page }}" class="page-link">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="page-link">Next</a>
                @else
                    <span class="page-link opacity-40">Next</span>
                @endif
            </li>
        </ul>
    </nav>
@endif
