@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('messages.pagination_label') }}" class="flex flex-col items-center justify-between gap-3 sm:flex-row">
        <p class="text-sm text-slate-400">
            {{-- Numbers are isolated so the range does not flip inside RTL text. --}}
            {!! __('messages.pagination_summary', ['range' => '<bdi dir="ltr" class="font-mono">'.(int) $paginator->firstItem().'–'.(int) $paginator->lastItem().'</bdi>', 'total' => '<bdi class="font-mono">'.(int) $paginator->total().'</bdi>']) !!}
        </p>
        <div class="flex flex-wrap items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="btn btn-ghost btn-sm" aria-disabled="true"><x-ui.icon name="chevron" flip class="h-4 w-4 rotate-180" /><span>{{ __('messages.pagination_previous') }}</span></span>
            @else
                <a class="btn btn-ghost btn-sm" href="{{ $paginator->previousPageUrl() }}" rel="prev"><x-ui.icon name="chevron" flip class="h-4 w-4 rotate-180" /><span>{{ __('messages.pagination_previous') }}</span></a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-2 text-slate-500">{{ $element }}</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="btn btn-sm bg-signal/15 font-mono text-signal ring-1 ring-signal/40" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="btn btn-quiet btn-sm font-mono" href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="btn btn-ghost btn-sm" href="{{ $paginator->nextPageUrl() }}" rel="next"><span>{{ __('messages.pagination_next') }}</span><x-ui.icon name="chevron" flip class="h-4 w-4" /></a>
            @else
                <span class="btn btn-ghost btn-sm" aria-disabled="true"><span>{{ __('messages.pagination_next') }}</span><x-ui.icon name="chevron" flip class="h-4 w-4" /></span>
            @endif
        </div>
    </nav>
@endif
