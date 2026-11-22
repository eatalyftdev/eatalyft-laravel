<nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme" id="layout-navbar">
    <div class="layout-menu-toggle navbar-link align-items-xl-center me-3 me-xl-0 d-xl-none">
        <a href="javascript:void(0)" class="nav-link px-0 me-xl-4" id="layout-menu-toggle">
            <i class="bx bx-menu bx-sm"></i>
        </a>
    </div>

    <div class="navbar-nav-flex-row align-items-center ms-auto">
        <div class="nav-item d-flex align-items-center me-3">
            <select class="form-select form-select-sm border-0 bg-transparent shadow-none px-2" id="currencySwitcher" onchange="switchCurrency(this.value)">
                @foreach (config('currencies.supported', ['XAF', 'USD', 'EUR', 'GBP']) as $currency)
                    <option value="{{ $currency }}" {{ app()->getLocale() === $currency ? 'selected' : '' }}>{{ $currency }}</option>
                @endforeach
            </select>
        </div>

        <div class="nav-item d-flex align-items-center me-3">
            <select class="form-select form-select-sm border-0 bg-transparent shadow-none px-2" id="languageSwitcher" onchange="switchLanguage(this.value)">
                <option value="en" {{ app()->getLocale() === 'en' ? 'selected' : '' }}>English</option>
                <option value="fr" {{ app()->getLocale() === 'fr' ? 'selected' : '' }}>Français</option>
            </select>
        </div>

        <div class="nav-item dropdown">
            <a class="nav-link dropdown-toggle hide-arrow d-flex align-items-center" href="javascript:void(0);" data-bs-toggle="dropdown">
                <div class="avatar avatar-online">
                    <x-eatalyft.image
                        src="{{ auth()->user()?->photo_url }}"
                        manifestKey="placeholders.avatar"
                        alt="{{ auth()->user()?->name ?? 'User' }}"
                        width="40"
                        height="40"
                        class="w-px-40 h-auto rounded-circle"
                        objectFit="cover"
                    />
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item" href="{{ route('profile.show') }}">
                        <i class="bx bx-user me-2"></i> {{ __('Profile') }}
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="{{ route('settings.index') }}">
                        <i class="bx bx-cog me-2"></i> {{ __('Settings') }}
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="dropdown-item">
                            <i class="bx bx-log-out me-2"></i> {{ __('Logout') }}
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</nav>