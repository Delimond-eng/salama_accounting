<!DOCTYPE html>
<html lang="en" data-layout="default" data-bs-theme="light" data-sidebar="gradientsidebar4" data-topbar="white" data-color="info">
<head>

	<!-- Meta Tags -->
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<title>Dashboard | SALAMA ACCOUNTING</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
	<meta name="description"
		content="Application de comptabilité sur mesure.">
	<meta name="keywords"
		content="Comptabilité, accounting">
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

	<!-- Datatable CSS -->
	<link rel="stylesheet" href="{{ asset('assets/plugins/datatables/css/dataTables.bootstrap5.min.css') }}">

	<!-- Daterangepicker CSS -->
	<link rel="stylesheet" href="{{ asset('assets/plugins/daterangepicker/daterangepicker.css') }}">

    <!-- SweetAlert2 JS -->
    <link rel="stylesheet" href="{{ asset('assets/plugins/sweetalert2/sweetalert2.min.css') }}">

	<!-- Main CSS -->
	<link rel="stylesheet" href="{{ asset('assets/css/style.css') }}" id="app-style">

	@stack('styles')

	<style>
		[v-cloak] { display: none !important; }
		.vue-splash-loader {
			min-height: 50vh;
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
		}
		.vue-page-loading { min-height: 40vh; }
		.compte-select-wrap { min-width: 160px; }
		.compte-select-dropdown {
			max-height: 220px;
			overflow-y: auto;
			z-index: 1060;
			position: absolute;
			top: 100%;
			left: 0;
		}
	</style>

</head>

<body>

	{{--  Begin Wrapper  --}}
	<div class="main-wrapper">

        {{--  Application header  --}}
		@include("components.header")

        {{--  Application sidebar  --}}
        @include("components.sidebar")

		<div class="page-wrapper">

			@yield("content")

			<!-- Start Footer -->
			<footer class="footer d-block d-md-flex justify-content-between text-md-start text-center">
				<p class="mb-md-0 mb-1">Copyright &copy;
					<script>document.write(new Date().getFullYear())</script> <a href="javascript:void(0);"
						class="link-primary text-decoration-underline">SALAMA ACCOUNTING</a>
				</p>
				<div class="d-flex align-items-center gap-2 footer-links justify-content-center justify-content-md-end">
					<a href="javascript:void(0);">About</a>
					<a href="javascript:void(0);">Terms</a>
					<a href="javascript:void(0);">Contact Us</a>
				</div>
			</footer>
			<!-- End Footer -->

		</div>

	</div>
	{{--  End Wrapper  --}}


	<!-- jQuery -->
	<script src="{{ asset('assets/js/jquery-3.7.1.min.js') }}"></script>

	<!-- Bootstrap Core JS -->
	<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>

	<!-- Simplebar JS -->
	<script src="{{ asset('assets/plugins/simplebar/simplebar.min.js') }}"></script>

	<!-- Datatable JS -->
	<script src="{{ asset('assets/plugins/datatables/js/jquery.dataTables.min.js') }}"></script>
	<script src="{{ asset('assets/plugins/datatables/js/dataTables.bootstrap5.min.js') }}"></script>

	<!-- Daterangepicker JS -->
	<script src="{{ asset('assets/js/moment.min.js') }}"></script>
	<script src="{{ asset('assets/plugins/daterangepicker/daterangepicker.js') }}"></script>

	<!-- Apexchart JS -->
	<script src="{{ asset('assets/plugins/apexchart/apexcharts.min.js') }}"></script>
	@unless (request()->routeIs('dashboard'))
	<script src="{{ asset('assets/plugins/apexchart/chart-data.js') }}"></script>
	<script src="{{ asset('assets/json/dashboard.js') }}"></script>
	@endunless

	<!-- Main JS -->
	<script src="{{ asset('assets/js/script.js') }}"></script>

        <!-- SweetAlert2 JS -->
    <script src="{{ asset('assets/plugins/sweetalert2/sweetalert2.min.js') }}"></script>

    <script src="{{ asset('assets/js/vendor/vue2.js') }}"></script>

	@stack("scripts")

</body>
</html>
