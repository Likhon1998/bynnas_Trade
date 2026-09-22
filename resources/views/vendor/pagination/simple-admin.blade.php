@if ($paginator->hasPages())
    <nav class="pager pager-simple" role="navigation" aria-label="{{ __('Pagination Navigation') }}">
        <ul class="pager-list">
            @if ($paginator->onFirstPage())
                <li class="pager-item is-disabled" aria-disabled="true">
                    <span class="pager-btn">@lang('pagination.previous')</span>
                </li>
            @else
                <li class="pager-item">
                    <a class="pager-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev">@lang('pagination.previous')</a>
                </li>
            @endif

            @if ($paginator->hasMorePages())
                <li class="pager-item">
                    <a class="pager-btn" href="{{ $paginator->nextPageUrl() }}" rel="next">@lang('pagination.next')</a>
                </li>
            @else
                <li class="pager-item is-disabled" aria-disabled="true">
                    <span class="pager-btn">@lang('pagination.next')</span>
                </li>
            @endif
        </ul>
    </nav>
@endif
