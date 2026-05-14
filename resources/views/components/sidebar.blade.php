<!-- Sidenav Menu Start -->
<div class="sidebar" id="sidebar">

    <!-- Start Logo -->
    <div class="sidebar-logo">
        <div>
            <!-- Logo Normal -->
            <a href="{{ route('dashboard') }}" class="logo logo-normal">
                <img src="{{ asset('assets/img/logo.svg') }}" alt="Logo">
            </a>

            <!-- Logo Small -->
            <a href="{{ route('dashboard') }}" class="logo-small">
                <img src="{{ asset('assets/img/logo-small.svg') }}" alt="Logo">
            </a>

            <!-- Logo Dark -->
            <a href="{{ route('dashboard') }}" class="dark-logo">
                <img src="{{ asset('assets/img/logo-white.svg') }}" alt="Logo">
            </a>
        </div>
        <button class="sidenav-toggle-btn btn border-0 p-0 active" id="toggle_btn">
            <i class="ti ti-arrow-bar-to-left"></i>
        </button>

        <!-- Sidebar Menu Close -->
        <button class="sidebar-close">
            <i class="ti ti-x align-middle"></i>
        </button>
    </div>
    <!-- End Logo -->

    <!-- Sidenav Menu -->
    <div class="sidebar-inner" data-simplebar>
        <div id="sidebar-menu" class="sidebar-menu">
            <ul>
                <!-- SECTION DASHBOARD -->
                <li class="menu-title"><span>DASHBOARD</span></li>
                <li>
                    <ul>
                        <li>
                            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                <i class="ti ti-dashboard"></i><span>Dashboard</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- SECTION COMPTABILITE -->
                <li class="menu-title"><span>COMPTABILITE</span></li>
                <li>
                    <ul>
                        <!-- 1. Journal comptable -->
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ request()->routeIs('accounting.journal*') ? 'active subdrop' : '' }}">
                                <i class="ti ti-notebook"></i><span>Journal comptable</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{ route('accounting.journal') }}" class="{{ request()->routeIs('accounting.journal') ? 'active' : '' }}">Saisie des écritures</a></li>
                                <li><a href="#">Consultation journal</a></li>
                            </ul>
                        </li>

                        <!-- 2. Grand livre -->
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ request()->routeIs('accounting.ledger*') ? 'active subdrop' : '' }}">
                                <i class="ti ti-books"></i><span>Grand livre</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{ route('accounting.ledger') }}" class="{{ request()->routeIs('accounting.ledger') ? 'active' : '' }}">Grand livre général</a></li>
                                <li><a href="#">Grand livre auxiliaire</a></li>
                            </ul>
                        </li>

                        <!-- 3. Balance générale -->
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ request()->routeIs('accounting.trial-balance*') ? 'active subdrop' : '' }}">
                                <i class="ti ti-scale"></i><span>Balance générale</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{ route('accounting.trial-balance') }}" class="{{ request()->routeIs('accounting.trial-balance') ? 'active' : '' }}">Balance de vérification</a></li>
                            </ul>
                        </li>

                        <!-- 4. Balance auxiliaire -->
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ request()->routeIs('accounting.subsidiary-balance*') ? 'active subdrop' : '' }}">
                                <i class="ti ti-scale-outline"></i><span>Balance auxiliaire</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{ route('accounting.subsidiary-balance') }}" class="{{ request()->routeIs('accounting.subsidiary-balance') ? 'active' : '' }}">Balance clients</a></li>
                                <li><a href="#">Balance fournisseurs</a></li>
                            </ul>
                        </li>

                        <!-- 5. Brouillard de caisse -->
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ request()->routeIs('accounting.cash-draft*') ? 'active subdrop' : '' }}">
                                <i class="ti ti-mist"></i><span>Brouillard de caisse</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{ route('accounting.cash-draft') }}" class="{{ request()->routeIs('accounting.cash-draft') ? 'active' : '' }}">Saisie de caisse</a></li>
                            </ul>
                        </li>

                        <!-- 6. Lettrage des comptes -->
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ request()->routeIs('accounting.reconciliation*') ? 'active subdrop' : '' }}">
                                <i class="ti ti-checkup-list"></i><span>Lettrage des comptes</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{ route('accounting.reconciliation') }}" class="{{ request()->routeIs('accounting.reconciliation') ? 'active' : '' }}">Lettrage manuel</a></li>
                                <li><a href="#">Lettrage automatique</a></li>
                            </ul>
                        </li>

                        <!-- 7. Clôture comptable -->
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ request()->routeIs('accounting.closing*') ? 'active subdrop' : '' }}">
                                <i class="ti ti-lock"></i><span>Clôture comptable</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{ route('accounting.closing') }}" class="{{ request()->routeIs('accounting.closing') ? 'active' : '' }}">Clôture mensuelle</a></li>
                                <li><a href="#">Clôture annuelle</a></li>
                            </ul>
                        </li>

                        <!-- 8. Réouverture d'exercice -->
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ request()->routeIs('accounting.reopening*') ? 'active subdrop' : '' }}">
                                <i class="ti ti-refresh"></i><span>Réouverture d'exercice</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{ route('accounting.reopening') }}" class="{{ request()->routeIs('accounting.reopening') ? 'active' : '' }}">Bilan d'ouverture</a></li>
                            </ul>
                        </li>

                        <!-- 9. Exports comptables -->
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ request()->routeIs('accounting.exports*') ? 'active subdrop' : '' }}">
                                <i class="ti ti-file-export"></i><span>Exports comptables</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{ route('accounting.exports') }}" class="{{ request()->routeIs('accounting.exports') ? 'active' : '' }}">Export PDF</a></li>
                                <li><a href="#">Export Excel</a></li>
                            </ul>
                        </li>
                    </ul>
                </li>

                <!-- SECTION ADMINISTRATION -->
                @if(auth()->user()->can('users.view') || auth()->user()->can('roles.view') || auth()->user()->can('logs.view'))
                <li class="menu-title"><span>ADMINISTRATION</span></li>
                <li>
                    <ul>
                        <!-- Utilisateurs -->
                        @can('users.view')
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ request()->routeIs('admin.users*') ? 'active subdrop' : '' }}">
                                <i class="ti ti-users"></i><span>Utilisateurs</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{ route('admin.users') }}" class="{{ request()->routeIs('admin.users') ? 'active' : '' }}">Liste des utilisateurs</a></li>
                                <li><a href="javascript:void(0);">Activités utilisateurs</a></li>
                            </ul>
                        </li>
                        @endcan

                        <!-- Rôles & Permissions -->
                        @can('roles.view')
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ request()->routeIs('admin.roles*') ? 'active subdrop' : '' }}">
                                <i class="ti ti-shield-lock"></i><span>Rôles & Permissions</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{ route('admin.roles') }}" class="{{ request()->routeIs('admin.roles') ? 'active' : '' }}">Gestion des rôles</a></li>
                                <li><a href="javascript:void(0);">Matrice des droits</a></li>
                            </ul>
                        </li>
                        @endcan

                        <!-- Logs système -->
                        @can('logs.view')
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ request()->routeIs('admin.logs*') ? 'active subdrop' : '' }}">
                                <i class="ti ti-file-description"></i><span>Logs d'activité</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{ route('admin.logs') }}" class="{{ request()->routeIs('admin.logs') ? 'active' : '' }}">Logs système</a></li>
                                <li><a href="javascript:void(0);">Historique des erreurs</a></li>
                            </ul>
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
