@if ($paginator->hasPages())
    <nav class="pager" role="navigation" aria-label="{{ __('Pagination Navigation') }}">
        <p class="pager-meta">
            {!! __('Showing') !!}
            <strong>{{ $paginator->firstItem() }}</strong>
            {!! __('to') !!}
            <strong>{{ $paginator->lastItem() }}</strong>
            {!! __('of') !!}
            <strong>{{ $paginator->total() }}</strong>
            {!! __('results') !!}
        </p>

        <ul class="pager-list">
            @if ($paginator->onFirstPage())
                <li class="pager-item is-disabled" aria-disabled="true">
                    <span class="pager-btn" aria-hidden="true">&lsaquo;</span>
                </li>
            @else
                <li class="pager-item">
                    <a class="pager-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')">&lsaquo;</a>
                </li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="pager-item is-disabled" aria-disabled="true"><span class="pager-btn">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="pager-item is-active" aria-current="page"><span class="pager-btn">{{ $page }}</span></li>
                        @else
                            <li class="pager-item"><a class="pager-btn" href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li class="pager-item">
                    <a class="pager-btn" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')">&rsaquo;</a>
                </li>
            @else
                <li class="pager-item is-disabled" aria-disabled="true">
                    <span class="pager-btn" aria-hidden="true">&rsaquo;</span>
                </li>
            @endif
        </ul>
    </nav>
@endif
