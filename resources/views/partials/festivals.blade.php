@if ($festivals)
    <div class="gap-3 gap-lg-4 px-2 px-lg-4 grid m-auto">

        @foreach($festivals as $festival)
            @php
                $festivalYear = \Carbon\Carbon::createFromFormat('Y-m', data_get($festival, 'year-month'))->format('Y');
                $festivalMonthYear = \Carbon\Carbon::createFromFormat('Y-m', data_get($festival, 'year-month'))->format('F Y');
            @endphp

            <div class="grid-item">
                <div class="p-4 card">
                    <div class="flex align-items-center justify-content-between mb-4 gap-3 date-img">
                        <div class="flex align-items-center gap-2 date flex-grow-1 flex-shrink-0">
                            @include('components.icons', ['icon' => 'calendar'])
                            <span>{{ data_get($festival, $festivalYear) }}, {{ $festivalYear }}</span>
                        </div>

                        @if (data_get($festival, 'image'))
                            <img class="festival-image flex-shrink-1" loading="lazy" src="{{ data_get($festival, 'image') }}" alt="{{ data_get($festival, 'festival-name-alphabetized') }}" height=50/>
                        @endif
                    </div>

                    <h2 class="mt-1 mb-3">{{ data_get($festival, 'festival-name') }}</h2>

                    <div class="flex align-items-center mb-2 info-row">
                        @include('components.icons', ['icon' => 'translate'])
                        <span class="ml-2" >{{ data_get($festival, 'languages') }}</span>
                    </div>

                    <div class="flex align-items-center mb-2 info-row">
                        @include('components.icons', ['icon' => 'facebook-logo'])
                        <a class="ml-2" href="{{ data_get($festival, 'facebook') }}" target="_blank" title="{{ data_get($festival, 'festival-name') }} facebook page">Facebook page</a>
                    </div>

                    <div class="flex align-items-center mb-4 info-row">
                        @include('components.icons', ['icon' => 'globe'])
                        <a class="ml-2" href="{{ data_get($festival, 'normalized_webpage') }}" target="_blank" title="{{ data_get($festival, 'festival-name') }} website">{{ data_get($festival, 'webpage') }}</a>
                    </div>

                    <div class="flex align-items-center justify-content-end">
                        <div class="flex align-items-center px-3 py-2 pill location">
                            @include('components.icons', ['icon' => 'pin'])
                            <span class="ml-1">{{ data_get($festival, 'city') }}, {{ data_get($festival, 'country') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <p class="text-center">No festivals found in this location.</p>
@endif
