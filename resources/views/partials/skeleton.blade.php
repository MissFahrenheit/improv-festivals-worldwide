<div class="gap-4 px-4 grid m-auto">
    @for ($i = 0; $i < 2; $i++)
        <div class="grid-item">
            <div class="card p-4">

                <div class="flex align-items-center justify-content-between mb-4 gap-3" style="height:50px">
                    <div class="flex align-items-center gap-2 date">
                        @include('components.icons', ['icon' => 'calendar'])
                        <span class="skeleton"></span>
                    </div>
                </div>

                <h2 class="mt-1 mb-3 skeleton"></h2>

                <div class="flex align-items-center mb-2 info-row">
                    @include('components.icons', ['icon' => 'translate'])
                    <span class="ml-2 skeleton"></span>
                </div>

                <div class="flex align-items-center mb-2 info-row">
                    @include('components.icons', ['icon' => 'facebook-logo'])
                    <span class="ml-2 skeleton"></span>
                </div>

                <div class="flex align-items-center mb-2 info-row">
                    @include('components.icons', ['icon' => 'globe'])
                    <span class="ml-2 skeleton"></span>
                </div>


                <div class="flex align-items-center justify-content-end">
                    <div class="flex align-items-center px-3 py-2 pill location skeleton">
                        @include('components.icons', ['icon' => 'pin'])
                        <span class="ml-1"></span>
                    </div>
                </div>
            </div>
        </div>
    @endfor
</div>
