<!-- Sidenav Menu Start -->
<div class="sidebar" id="sidebar">

    <!-- Start Logo -->
    <div class="sidebar-logo">
        <div>
            <a href="{{ route('dashboard') }}" class="logo logo-normal">
                <img src="{{ $appLogoUrl ?? asset('assets/img/logo.svg') }}" alt="Logo" class="app-logo-img">
            </a>
            <a href="{{ route('dashboard') }}" class="logo-small">
                <img src="{{ $appLogoUrl ?? asset('assets/img/logo-small.svg') }}" alt="Logo" class="app-logo-img">
            </a>
            <a href="{{ route('dashboard') }}" class="dark-logo">
                <img src="{{ $appLogoUrl ?? asset('assets/img/logo-white.svg') }}" alt="Logo" class="app-logo-img">
            </a>
        </div>
        <button class="sidenav-toggle-btn btn border-0 p-0 active" id="toggle_btn">
            <i class="ti ti-arrow-bar-to-left"></i>
        </button>
        <button class="sidebar-close">
            <i class="ti ti-x align-middle"></i>
        </button>
    </div>

    <div class="sidebar-inner" data-simplebar>
        <div id="sidebar-menu" class="sidebar-menu">
            <ul>
                <li class="menu-title"><span>SYSCOHADA</span></li>
                <li>
                    <ul>
                        @php
                            $modules = config('accounting_menu.modules', []);
                            $modulePermissions = config('accounting_route_permissions.modules', []);
                        @endphp

                        @foreach ($modules as $key => $mod)
                        @php
                            $perm = $mod['permission'] ?? ($modulePermissions[$key] ?? "{$key}.view");
                            if (! auth()->user()->can($perm)) {
                                continue;
                            }
                            $isActive = request()->routeIs('accounting.modules.show') && request()->route('module') === $key;
                            $href = $key === 'dashboard'
                                ? route('dashboard')
                                : route('accounting.modules.show', ['module' => $key]);
                        @endphp
                        <li>
                            <a href="{{ $href }}" class="{{ $isActive ? 'active' : '' }}">
                                <i class="ti {{ $mod['icon'] }}"></i>
                                <span>{{ $mod['number'] }} {{ $mod['title'] }}</span>
                            </a>
                        </li>
                        @endforeach
                    </ul>
                </li>

                @if(auth()->user()->can('users.view') || auth()->user()->can('roles.view') || auth()->user()->can('audit.view'))
                <li class="menu-title"><span>ADMINISTRATION</span></li>
                <li>
                    <ul>
                        @can('users.view')
                        <li>
                            <a href="{{ route('admin.users') }}" class="{{ request()->routeIs('admin.users*') ? 'active' : '' }}">
                                <i class="ti ti-users"></i><span>Utilisateurs</span>
                            </a>
                        </li>
                        @endcan
                        @can('roles.view')
                        <li>
                            <a href="{{ route('admin.roles') }}" class="{{ request()->routeIs('admin.roles*') ? 'active' : '' }}">
                                <i class="ti ti-shield-lock"></i><span>Rôles & Permissions</span>
                            </a>
                        </li>
                        @endcan
                        @can('audit.view')
                        <li>
                            <a href="{{ route('admin.logs') }}" class="{{ request()->routeIs('admin.logs*') ? 'active' : '' }}">
                                <i class="ti ti-file-description"></i><span>Journal d'audit</span>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </li>
                @endif
            </ul>
        </div>
    </div>
</div>
<!-- Sidenav Menu End -->
