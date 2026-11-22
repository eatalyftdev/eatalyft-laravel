<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" data-theme="{{ session('theme', 'dark') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    <meta name="description" content="@yield('description', config('app.description', ''))">
    <link rel="alternate" hreflang="en" href="{{ url()->current(['lang' => 'en']) }}">
    <link rel="alternate" hreflang="fr" href="{{ url()->current(['lang' => 'fr']) }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/landing.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/design-tokens.css') }}">
    <link rel="preload" as="image" href="{{ asset('assets/eatalyft/branding/logo.svg') }}">
</head>
<body>
    <nav class="ely-nav" role="navigation" aria-label="Main navigation">
        <div class="ely-nav-inner container">
            <a href="/" class="ely-nav-logo" aria-label="{{ config('app.name') }} - Home">
                <img src="{{ asset('assets/eatalyft/branding/logo.svg') }}" alt="{{ config('app.name') }}" class="ely-nav-logo-mark" width="32" height="32">
                <span class="ely-nav-logo-text">{{ config('app.name') }}</span>
            </a>

            <button class="ely-nav-toggle" aria-label="{{ __('Open menu') }}" aria-expanded="false" aria-controls="ely-nav-menu">
                <span class="ely-nav-toggle-bar"></span>
                <span class="ely-nav-toggle-bar"></span>
                <span class="ely-nav-toggle-bar"></span>
            </button>

            <div class="ely-nav-menu" id="ely-nav-menu" role="menubar">
                <ul class="ely-nav-links" role="none">
                    @if($mainMenu = \App\Models\Menu::where('slug', 'main')->where('locale', app()->getLocale())->where('is_active', true)->first())
                        @foreach($mainMenu->rootItems as $item)
                            @if($item->children->count())
                                <li role="none" class="ely-nav-dropdown">
                                    <button class="ely-nav-dropdown-toggle" aria-haspopup="true" aria-expanded="false">
                                        {{ $item->title }} <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ely-nav-dropdown-arrow"><path d="M6 9l6 6 6-6"/></path></svg>
                                    </button>
                                    <ul class="ely-nav-dropdown-menu" role="menu">
                                        @foreach($item->children as $child)
                                            <li role="none"><a href="{{ $child->url }}" role="menuitem" target="{{ $child->target }}">{{ $child->title }}</a></li>
                                        @endforeach
                                    </ul>
                                </li>
                            @else
                                <li role="none"><a href="{{ $item->url }}" role="menuitem" target="{{ $item->target }}">{{ $item->title }}</a></li>
                            @endif
                        @endforeach
                    @endif
                    <li role="none"><a href="{{ route('about') }}" role="menuitem">{{ __('About') }}</a></li>
                    <li role="none"><a href="{{ route('contact') }}" role="menuitem">{{ __('Contact') }}</a></li>
                </ul>

                <div class="ely-nav-actions">
                    <div class="ely-lang-toggle" role="group" aria-label="{{ __('Language') }}">
                        <a href="{{ url()->current(['lang' => 'en']) }}" class="{{ app()->getLocale() === 'en' ? 'ely-active' : '' }}" aria-current="{{ app()->getLocale() === 'en' ? 'page' : 'false' }}">EN</a>
                        <a href="{{ url()->current(['lang' => 'fr']) }}" class="{{ app()->getLocale() === 'fr' ? 'ely-active' : '' }}" aria-current="{{ app()->getLocale() === 'fr' ? 'page' : 'false' }}">FR</a>
                    </div>
                    @guest
                        <a href="{{ route('login') }}" class="ely-btn ely-btn-outline">{{ __('Log in') }}</a>
                        <a href="{{ route('register.customer') }}" class="ely-btn ely-btn-primary">{{ __('Create Customer Account') }}</a>
                        <a href="{{ route('register.provider') }}" class="ely-btn ely-btn-outline" style="margin-left: var(--ely-space-2);">{{ __('Become a Provider') }}</a>
                        <a href="{{ route('register.agency') }}" class="ely-btn ely-btn-outline" style="margin-left: var(--ely-space-2);">{{ __('Register Your Agency') }}</a>
                    @else
                        <a href="{{ route('dashboard') }}" class="ely-btn ely-btn-primary">{{ __('Dashboard') }}</a>
                        <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="ely-btn ely-btn-outline">{{ __('Log out') }}</button>
                        </form>
                    @endguest
                </div>
            </div>
        </div>
    </nav>

    <main id="ely-main-content" role="main">@yield('content')</main>

    <footer class="ely-footer" role="contentinfo">
        <div class="container">
            <div class="ely-footer-top">
                <div class="ely-footer-brand">
                    <img src="{{ asset('assets/eatalyft/branding/logo.svg') }}" alt="{{ config('app.name') }}" class="ely-footer-logo" width="32" height="32">
                    <span class="ely-footer-logo-text">{{ config('app.name') }}</span>
                    <p class="ely-footer-desc">{{ __('Simpler, smarter bus travel across West Africa. Discover routes, choose your seat, and book with confidence.') }}</p>
                    <div class="ely-footer-social">
                        <a href="#" aria-label="{{ __('Facebook') }}" class="ely-footer-social-link"><svg width="20" height="20" viewBox="0 0 24 24" fill="#DC2626"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg></a>
                        <a href="#" aria-label="{{ __('Twitter') }}" class="ely-footer-social-link"><svg width="20" height="20" viewBox="0 0 24 24" fill="#DC2626"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/></svg></a>
                        <a href="#" aria-label="{{ __('Instagram') }}" class="ely-footer-social-link"><svg width="20" height="20" viewBox="0 0 24 24" fill="#DC2626"><rect width="20" height="20" x="2" y="2" rx="5" stroke="currentColor" stroke-width="2"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" stroke="currentColor" stroke-width="2"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5" stroke="currentColor" stroke-width="2"/></svg></a>
                    </div>
                </div>

                <div class="ely-footer-links">
                    @if($footerMenu = \App\Models\Menu::where('slug', 'footer')->where('locale', app()->getLocale())->where('is_active', true)->first())
                        @foreach($footerMenu->rootItems as $item)
                            <div class="ely-footer-column">
                                @if($item->children->count())
                                    <h4>{{ $item->title }}</h4>
                                    <ul>
                                        @foreach($item->children as $child)
                                            <li><a href="{{ $child->url }}" target="{{ $child->target }}">{{ $child->title }}</a></li>
                                        @endforeach
                                    </ul>
                                @else
                                    <a href="{{ $item->url }}" target="{{ $item->target }}">{{ $item->title }}</a>
                                @endif
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            <div class="ely-footer-bottom">
                <p class="ely-footer-copyright">© {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}</p>
                <div class="ely-footer-legal">
                    <a href="{{ route('legal.terms') }}">{{ __('Terms') }}</a>
                    <a href="{{ route('legal.privacy') }}">{{ __('Privacy') }}</a>
                    <a href="{{ route('legal.cookies') }}">{{ __('Cookies') }}</a>
                </div>
            </div>
        </div>
    </footer>

    <div class="ely-floating">
        <button class="ely-theme-toggle" aria-label="{{ __('Toggle theme') }}" id="ely-theme-toggle">
            <span class="ely-icon-sun" aria-hidden="true">☀️</span>
            <span class="ely-icon-moon" aria-hidden="true">🌙</span>
        </button>
    </div>

    @stack('scripts')

    <script>
        document.documentElement.dataset.theme = '{{ session("theme", "dark") }}';
        document.getElementById('ely-theme-toggle')?.addEventListener('click', () => {
            const html = document.documentElement;
            const theme = html.dataset.theme === 'dark' ? 'light' : 'dark';
            html.dataset.theme = theme;
            fetch('{{ route("theme.toggle") }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: JSON.stringify({ theme }) });
        });

        // Mobile menu toggle
        const toggle = document.querySelector('.ely-nav-toggle');
        const menu = document.getElementById('ely-nav-menu');
        if (toggle && menu) {
            toggle.addEventListener('click', () => {
                const expanded = toggle.getAttribute('aria-expanded') === 'true';
                toggle.setAttribute('aria-expanded', !expanded);
                menu.classList.toggle('ely-open');
            });
        }

        document.querySelectorAll('.ely-nav-dropdown-toggle').forEach(btn => {
            btn.addEventListener('click', () => {
                const expanded = btn.getAttribute('aria-expanded') === 'true';
                btn.setAttribute('aria-expanded', !expanded);
            });
        });
    </script>
</body>
</html>
