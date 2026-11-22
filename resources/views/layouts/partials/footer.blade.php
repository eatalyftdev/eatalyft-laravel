<div class="content-backdrop fade"></div>

<footer class="footer footer-static footer-transparent">
    <div class="container-xxl d-flex flex-wrap justify-content-between py-2 flex-md-row flex-column">
        <div class="mb-2 mb-md-0">
            © {{ date('Y') }} Eatalyft. {{ __('All rights reserved.') }}
        </div>
        <div>
            <a href="{{ config('app.privacy_url', '#') }}" class="text-muted me-4">{{ __('Privacy Policy') }}</a>
            <a href="{{ config('app.terms_url', '#') }}" class="text-muted me-4">{{ __('Terms of Use') }}</a>
            <a href="{{ config('app.support_url', '#') }}" class="text-muted">{{ __('Support') }}</a>
        </div>
    </div>
</footer>