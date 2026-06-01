@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Paginação" class="flex items-center justify-between">
        {{-- Mobile: somente anterior/próxima --}}
        <div class="flex justify-between flex-1 sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-slate-400 bg-white border border-slate-300 cursor-default rounded-md">
                    {!! __('pagination.previous') !!}
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-md hover:bg-slate-50">
                    {!! __('pagination.previous') !!}
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="relative inline-flex items-center ml-3 px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-md hover:bg-slate-50">
                    {!! __('pagination.next') !!}
                </a>
            @else
                <span class="relative inline-flex items-center ml-3 px-4 py-2 text-sm font-medium text-slate-400 bg-white border border-slate-300 cursor-default rounded-md">
                    {!! __('pagination.next') !!}
                </span>
            @endif
        </div>

        {{-- Desktop --}}
        <div class="hidden sm:flex sm:items-center sm:justify-between sm:flex-1">
            <div>
                <p class="text-sm text-slate-700">
                    {!! __('Exibindo') !!}
                    @if ($paginator->firstItem())
                        <span class="font-medium">{{ $paginator->firstItem() }}</span>
                        {!! __('até') !!}
                        <span class="font-medium">{{ $paginator->lastItem() }}</span>
                    @else
                        {{ $paginator->count() }}
                    @endif
                    {!! __('de') !!}
                    <span class="font-medium">{{ $paginator->total() }}</span>
                    {!! __('registros') !!}
                </p>
            </div>

            <div>
                <span class="relative z-0 inline-flex rounded-md shadow-sm">
                    {{-- Anterior --}}
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                            <span class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-slate-400 bg-white border border-slate-300 cursor-default rounded-l-md leading-5" aria-hidden="true">
                                &laquo;
                            </span>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-slate-500 bg-white border border-slate-300 rounded-l-md leading-5 hover:text-slate-700 hover:bg-slate-50" aria-label="{{ __('pagination.previous') }}">
                            &laquo;
                        </a>
                    @endif

                    {{-- Páginas / reticências --}}
                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <span aria-disabled="true">
                                <span class="relative inline-flex items-center -ml-px px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 cursor-default leading-5">{{ $element }}</span>
                            </span>
                        @endif

                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page">
                                        <span class="relative inline-flex items-center -ml-px px-4 py-2 text-sm font-medium text-white bg-slate-900 border border-slate-900 cursor-default leading-5">{{ $page }}</span>
                                    </span>
                                @else
                                    <a href="{{ $url }}" class="relative inline-flex items-center -ml-px px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 leading-5 hover:bg-slate-50" aria-label="{{ __('Página :page', ['page' => $page]) }}">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Próxima --}}
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="relative inline-flex items-center -ml-px px-3 py-2 text-sm font-medium text-slate-500 bg-white border border-slate-300 rounded-r-md leading-5 hover:text-slate-700 hover:bg-slate-50" aria-label="{{ __('pagination.next') }}">
                            &raquo;
                        </a>
                    @else
                        <span aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                            <span class="relative inline-flex items-center -ml-px px-3 py-2 text-sm font-medium text-slate-400 bg-white border border-slate-300 cursor-default rounded-r-md leading-5" aria-hidden="true">
                                &raquo;
                            </span>
                        </span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
@endif
