@extends("layouts.auth")

@section("content")
 <!-- start row -->
<div id="auth-app" class="row vh-100 w-100 g-0" v-cloak>

    <div class="col-lg-6 vh-100 overflow-y-auto overflow-x-hidden">

            <!-- start row -->
        <div class="row">

            <div class="col-md-10 mx-auto">
                <form action="{{ route('login') }}" method="POST" @submit.prevent="handleLogin" class=" vh-100 d-flex justify-content-between flex-column p-4 pb-0">
                    @csrf
                    <div class="text-center mb-4">
                        <img src="{{ asset('assets/img/compta.svg') }}" style="height: 80px" class="img-fluid" alt="Logo">
                    </div>
                    <div>
                        <div class="mb-3">
                            <h3 class="mb-2">LOGIN</h3>
                            <p class="mb-0">Insérez vos identifiants de connexion pour continuer.</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Adresse email</label>
                            <div class="input-group input-group-flat">
                                <input type="email" name="email" v-model="form.email" placeholder="exemple@domain" class="form-control">
                                <span class="input-group-text">
                                    <i class="ti ti-mail"></i>
                                </span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mot de passe</label>
                            <div class="input-group input-group-flat pass-group">
                                <input type="password" name="password" v-model="form.password" placeholder="Clé sécret..." class="form-control pass-input">
                                <span class="input-group-text toggle-password ">
                                    <i class="ti ti-eye-off"></i>
                                </span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary w-100 d-flex align-items-center justify-content-center" :disabled="loading">
                                <span v-if="loading" class="spinner-border spinner-border-sm text-white me-2" role="status" aria-hidden="true"></span>
                                <span v-if="loading">Connexion...</span>
                                <span v-else>Connecter</span>
                            </button>
                        </div>
                    </div>
                    <div class="text-center pb-4">
                        <p class="text-dark mb-0">Copyright &copy; 2026 — SALAMA COMPTA</p>
                    </div>
                </form>
            </div> <!-- end col -->
        </div>
        <!-- end row -->

    </div>

    <div class="col-lg-6 account-bg-01"></div> <!-- end col -->
</div>
<!-- end row -->
@endsection

@push('scripts')
    <script type="module" src="{{ asset('assets/js/scripts/auth.js') }}"></script>
@endpush
