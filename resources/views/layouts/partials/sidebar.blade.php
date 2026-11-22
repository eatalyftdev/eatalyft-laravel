<aside id="layout-menu" class="layout-menu menu-vertical menu-bg-faded" data-bg-class="menu-bg-faded">
    <div class="app-brand demo">
        <a href="{{ route('dashboard') }}" class="app-brand-link">
            <span class="app-brand-logo demo">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                    <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                    <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                </svg>
            </span>
            <span class="app-brand-text demo menu-text fw-bold ms-2">Eatalyft</span>
        </a>
        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-flex align-items-center d-xl-none">
            <i class="bx bx-chevron-left bx-sm align-middle"></i>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        @php
            $userRole = auth()->user()?->role ?? 'customer';
            $menuConfig = config("menu.$userRole", config('menu.customer', []));
        @endphp

        @foreach ($menuConfig as $item)
            @if (isset($item['children']))
                @php
                    $hasPermission = false;
                    foreach ($item['children'] as $child) {
                        if (empty($child['permission']) || auth()->user()?->can($child['permission'])) {
                            $hasPermission = true;
                            break;
                        }
                    }
                @endphp
                @if ($hasPermission)
                    <li class="menu-item menu-item-submenu {{ request()->routeIs($item['route'] ?? '') ? 'open' : '' }}">
                        <a href="javascript:void(0);" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-{{ $item['icon'] }}"></i>
                            <div data-i18n="{{ strtolower($item['label']) }}">{{ __($item['label']) }}</div>
                            <i class="bx bx-chevron-right menu-arrow"></i>
                        </a>
                        <ul class="menu-sub">
                            @foreach ($item['children'] as $child)
                                @if (empty($child['permission']) || auth()->user()?->can($child['permission']))
                                    <li class="menu-item {{ request()->routeIs($child['route']) ? 'active' : '' }}">
                                        <a href="{{ route($child['route']) }}" class="menu-link">
                                            <div data-i18n="{{ strtolower($child['label']) }}">{{ __($child['label']) }}</div>
                                        </a>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </li>
                @endif
            @else
                @php
                    $hasPermission = empty($item['permission']) || auth()->user()?->can($item['permission']);
                @endphp
                @if ($hasPermission)
                    <li class="menu-item {{ request()->routeIs($item['route']) ? 'active' : '' }}">
                        <a href="{{ route($item['route']) }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-{{ $item['icon'] }}"></i>
                            <div data-i18n="{{ strtolower($item['label']) }}">{{ __($item['label']) }}</div>
                        </a>
                    </li>
                @endif
            @endif
        @endforeach
    </ul>
</aside>

<div class="layout-menu-toggle navbar-link align-items-xl-center me-2 me-xl-0 d-xl-none">
    <a href="javascript:void(0)" class="nav-link px-0 me-xl-4" id="layout-menu-toggle">
        <i class="bx bx-menu bx-sm"></i>
    </a>
</div>