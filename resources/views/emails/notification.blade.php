@component('mail::layout')
    {{-- Header --}}
    @slot('header')
        @component('mail::header', ['url' => config('app.url')])
            {{ config('app.name') }}
        @endcomponent
    @endslot

    {{-- Body --}}
    @foreach ($introLines as $line)
        {{ $line }}
        
    @endforeach

    @if (isset($actionText))
        <?php
            switch ($level) {
                case 'success':
                case 'error':
                    $color = $level;
                    break;
                default:
                    $color = 'primary';
            }
        ?>
        @component('mail::button', ['url' => $actionUrl, 'color' => $color])
            {{ $actionText }}
        @endcomponent
    @endif

    {{-- Subcopy --}}
    @if (isset($actionText))
        @slot('subcopy')
            @component('mail::subcopy')
                {{ __('If you’re having trouble clicking the "' . $actionText . '" button, copy and paste the URL below into your web browser:') }}
                <span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
            @endcomponent
        @endslot
    @endif

    {{-- Footer --}}
    @slot('footer')
        @component('mail::footer')
            © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            
            @if (config('app.env') !== 'production')
                <div style="margin-top: 10px; color: #999; font-size: 12px;">
                    Environment: {{ config('app.env') }}
                </div>
            @endif
        @endcomponent
    @endslot
@endcomponent
