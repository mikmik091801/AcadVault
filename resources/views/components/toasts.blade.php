@php
    $toasts = [];

    foreach (['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'status' => 'success'] as $key => $variant) {
        if (session()->has($key)) {
            $toasts[] = ['variant' => $variant, 'message' => session($key)];
        }
    }

    $icons = [
        'success' => 'bi-check-circle-fill',
        'danger' => 'bi-x-circle-fill',
        'warning' => 'bi-exclamation-triangle-fill',
    ];
@endphp

@if (! empty($toasts))
    <div class="toast-container position-fixed top-0 end-0 p-3">
        @foreach ($toasts as $toast)
            <div class="toast align-items-center border-0 text-bg-{{ $toast['variant'] }}"
                 role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body d-flex align-items-start gap-2">
                        <i class="bi {{ $icons[$toast['variant']] }}" aria-hidden="true"></i>
                        <span>{{ $toast['message'] }}</span>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto"
                            data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        @endforeach
    </div>
@endif
