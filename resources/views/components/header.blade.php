<!-- Topbar Start -->
<header class="navbar-header">
    <div class="page-container topbar-menu">
        <div class="d-flex align-items-center gap-2">

            <!-- Logo -->
            <a href="{{ route('dashboard') }}" class="logo">
                <span class="logo-light">
                    <span class="logo-lg"><img src="{{ $appLogoUrl ?? asset('assets/img/compta.svg') }}" alt="logo" class="app-logo-img" style="max-height:36px"></span>
                    <span class="logo-sm"><img src="{{ $appLogoUrl ?? asset('assets/img/icon.png') }}" alt="small logo" class="app-logo-img" style="max-height:32px"></span>
                </span>
                <span class="logo-dark">
                    <span class="logo-lg"><img src="{{ $appLogoUrl ?? asset('assets/img/compta-light.svg') }}" alt="dark logo" class="app-logo-img" style="max-height:36px"></span>
                </span>
            </a>

            <!-- Sidebar Mobile Button -->
            <a id="mobile_btn" class="mobile-btn" href="#sidebar">
                <i class="ti ti-menu-deep fs-24"></i>
            </a>

            <button class="sidenav-toggle-btn btn border-0 p-0" id="toggle_btn2">
                <i class="ti ti-arrow-bar-to-right"></i>
            </button>

            <div class="me-auto d-flex align-items-center header-search d-lg-flex d-none">
                <div class="input-icon position-relative me-2">
                    <input type="text" class="form-control" placeholder="Rechercher…">
                    <span class="input-icon-addon d-inline-flex p-0 header-search-icon"><i class="ti ti-command"></i></span>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center">
            <div class="header-item">
                <div class="dropdown me-2">
                    <a href="javascript:void(0);" class="btn topbar-link btnFullscreen"><i class="ti ti-maximize"></i></a>
                </div>
            </div>

            <div class="dropdown profile-dropdown d-flex align-items-center justify-content-center">
                <a href="javascript:void(0);"
                    class="topbar-link dropdown-toggle drop-arrow-none position-relative"
                    data-bs-toggle="dropdown" data-bs-offset="0,22" aria-haspopup="false" aria-expanded="false">
                    <img src="{{ asset('assets/img/users/user-40.jpg') }}" width="38" class="rounded-1 d-flex"
                        alt="user-image">
                    <span class="online text-success"><i
                            class="ti ti-circle-filled d-flex bg-white rounded-circle border border-1 border-white"></i></span>
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-md p-2">
                    <div class="d-flex align-items-center bg-light rounded-3 p-2 mb-2">
                        <img src="{{ asset('assets/img/users/user-40.jpg') }}" class="rounded-circle" width="42" height="42"
                            alt="Img">
                        <div class="ms-2">
                            <p class="fw-medium text-dark mb-0">{{ Auth::user()->name }}</p>
                            <span class="d-block fs-13">{{ Auth::user()->email }}</span>
                            @php
                                $roleLabel = config('accounting_roles.labels')[Auth::user()->roles->first()?->name ?? Auth::user()->role] ?? null;
                            @endphp
                            @if($roleLabel)
                            <span class="d-block fs-12 text-muted">{{ $roleLabel }}</span>
                            @endif
                        </div>
                    </div>

                    @can('parametres.view')
                    <a href="{{ route('accounting.parametres.societe') }}" class="dropdown-item">
                        <i class="ti ti-settings me-1 align-middle"></i>
                        <span class="align-middle">Paramètres société</span>
                    </a>
                    @endcan

                    <div class="pt-2 mt-2 border-top">
                        <a href="#" class="dropdown-item text-danger" id="logout-btn">
                            <i class="ti ti-logout me-1 fs-17 align-middle"></i>
                            <span class="align-middle">Déconnexion</span>
                        </a>
                        <form id="logout-form" method="POST" hidden action="{{ route('logout') }}" class="m-0 p-0">
                            @csrf
                            <button type="submit" class="dropdown-item d-inline-flex align-items-center p-0 py-2 js-logout">
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
<!-- Topbar End -->

<!-- Search Modal -->
<div class="modal fade" id="searchModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-transparent">
            <div class="card shadow-none mb-0">
                <div class="px-3 py-2 d-flex flex-row align-items-center" id="search-top">
                    <i class="ti ti-search fs-22"></i>
                    <input type="search" class="form-control border-0" placeholder="Search">
                    <button type="button" class="btn p-0" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x fs-22"></i></button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const logoutBtn = document.getElementById('logout-btn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Déconnexion',
                    text: "Êtes-vous sûr de vouloir vous déconnecter ?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Oui, Déconnecter',
                    cancelButtonText: 'Annuler',
                    customClass: {
                        confirmButton: 'btn btn-primary btn-sm me-2',
                        cancelButton: 'btn btn-danger btn-sm'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('logout-form').submit();
                    }
                });
            });
        }
    });
</script>
