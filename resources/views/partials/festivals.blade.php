@if ($festivals)
    <div class="gap-3 gap-lg-4 px-2 px-lg-4 grid m-auto">

        @foreach($festivals as $festival)

            <div class="grid-item">
                <div class="p-4 card">
                    <div class="flex align-items-center justify-content-between mb-4 gap-3 date-img">
                        <div class="flex align-items-center gap-2 date flex-grow-1 flex-shrink-0">
                            @include('components.icons', ['icon' => 'calendar'])
                            <span>{{  $festival->date }}, {{ $festival->yearMonth->year }}</span>
                        </div>

                        @if ( !empty($festival->image) )
                            <img class="festival-image flex-shrink-1" loading="lazy" src="{{ $festival->image }}" alt="{{ $festival->name }}" height=50/>
                        @endif
                    </div>

                    <h2 class="mt-1 mb-3">{{ $festival->name }}</h2>

                    <div class="flex align-items-center mb-2 info-row">
                        @include('components.icons', ['icon' => 'translate'])
                        <span class="ml-2" >{{ $festival->languages }}</span>
                    </div>

                    <div class="flex align-items-center mb-2 info-row">
                        @include('components.icons', ['icon' => 'facebook-logo'])
                        <a class="ml-2" href="{{ $festival->facebook }}" target="_blank" title="{{ $festival->name }} facebook page">Facebook page</a>
                    </div>

                    <div class="flex align-items-center mb-4 info-row">
                        @include('components.icons', ['icon' => 'globe'])
                        <a class="ml-2" href="{{ $festival->webpage }}" target="_blank" title="{{ $festival->name }} website">{{ $festival->webpage }}</a>
                    </div>

                    <div class="flex align-items-center justify-content-end">
                        <div class="flex align-items-center px-3 py-2 pill location">
                            @include('components.icons', ['icon' => 'pin'])
                            <span class="ml-1">{{ $festival->city }}, {{ $festival->country }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <p class="text-center">No festivals found in this location.</p>
@endif
