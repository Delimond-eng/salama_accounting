<!DOCTYPE html>
<html lang="en">


<head>

    <!-- Meta Tags -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Error 404 </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="author" content="SALAMA DRC">
	<meta name="robots" content="index, follow">

    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('assets/img/icon.png') }}">

    <!-- Apple Icon -->
    <link rel="apple-touch-icon" href="{{ asset('assets/img/icon.png') }}">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">

    <!-- Tabler Icon CSS -->
    <link rel="stylesheet" href="{{ asset('assets/plugins/tabler-icons/tabler-icons.min.css') }}">

    <!-- Simplebar CSS -->
    <link rel="stylesheet" href="{{ asset('assets/plugins/simplebar/simplebar.min.css') }}">

    <!-- Main CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}" id="app-style">

</head>

<body class="error-page">

    <!-- Begin Wrapper -->
    <div class="main-wrapper">

        <div class="container">

            <!-- start row -->
            <div class="row justify-content-center align-items-center vh-100">

                <div class="col-md-8 d-flex align-items-center justify-content-center mx-auto">
                    <div>
                        <div class="error-img p-4">
                            <img src="{{ asset('assets/img/authentication/error-404.png') }}" class="img-fluid" alt="Img">
                        </div>
                        <div class="text-center">
                            <h2 class="mb-3">Oups, une erreur s’est produite</h2>
                            <p class="mb-3"> Erreur 404 - Page introuvable. Désolé, la page que vous recherchez <br>
                                n’existe pas ou a été déplacée.</p>
                            <div class="pb-4">
                                <a href="{{ route('dashboard') }}" class="btn btn-primary d-inline-flex align-items-center">
                                    <i class="ti ti-chevron-left me-1"></i>Retour à l'accueil
                                </a>
                            </div>
                        </div>
                    </div>
                </div> <!-- end col -->

            </div>
            <!-- end row -->

        </div>

    </div>
    <!-- End Wrapper -->

    <!-- jQuery -->
    <script src="{{ asset('assets/js/jquery-3.7.1.min.js') }}"></script>

    <!-- Bootstrap Core JS -->
    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>

	<!-- Simplebar JS -->
	<script src="{{ asset('assets/plugins/simplebar/simplebar.min.js') }}"></script>

    <!-- Main JS -->
    <script src="{{ asset('assets/js/script.js') }}"></script>

</body>

</html>
