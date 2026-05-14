@extends("layouts.app")

@section("content")
    {{--  Start Content  --}}
    <div class="content pb-0">

        <!-- Page Header -->
        <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
            <div>
                <h4 class="mb-0">Dashboard</h4>
            </div>
            <div class="gap-2 d-flex align-items-center flex-wrap">
                <div class="avatar-list-stacked me-2">
                    <a href="#"
                        class="avatar avatar-rounded border bg-white p-1 d-inline-flex align-items-center justify-content-center">
                        <img class="w-auto h-auto img-fluid" src="assets/img/company/company-09.svg" alt="img">
                    </a>
                    <a href="#"
                        class="avatar avatar-rounded border bg-white p-1 d-inline-flex align-items-center justify-content-center">
                        <img class="w-auto h-auto img-fluid" src="assets/img/company/company-10.svg" alt="img">
                    </a>
                    <a href="#"
                        class="avatar avatar-rounded border bg-white p-1 d-inline-flex align-items-center justify-content-center">
                        <img class="w-auto h-auto img-fluid" src="assets/img/company/company-01.svg" alt="img">
                    </a>
                    <a href="#"
                        class="avatar avatar-rounded border bg-white p-1 d-inline-flex align-items-center justify-content-center">
                        <img class="w-auto h-auto img-fluid" src="assets/img/company/company-02.svg" alt="img">
                    </a>
                    <a href="#"
                        class="avatar avatar-rounded border bg-white p-1 d-inline-flex align-items-center justify-content-center">
                        <img class="w-auto h-auto img-fluid" src="assets/img/company/company-11.svg" alt="img">
                    </a>
                    <a class="avatar bg-primary border text-white fs-24 avatar-rounded" href="#">
                        +
                    </a>
                </div>
                <div class="daterangepick form-control w-auto d-flex align-items-center me-2">
                    <i class="ti ti-calendar text-dark me-2"></i>
                    <span class="reportrange-picker-field text-dark">23 May 2025 - 30 May 2025</span>
                </div>
                <a href="javascript:void(0);" class="btn btn-icon btn-outline-light shadow"
                    data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Download"
                    data-bs-original-title="Download"><i class="ti ti-download"></i></a>
                <a href="javascript:void(0);" class="btn btn-icon btn-outline-light shadow"
                    data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Refresh"
                    data-bs-original-title="Refresh"><i class="ti ti-refresh"></i></a>
                <a href="javascript:void(0);" class="btn btn-icon btn-outline-light shadow"
                    data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Collapse"
                    data-bs-original-title="Collapse" id="collapse-header"><i
                        class="ti ti-transition-top"></i></a>
            </div>
        </div>
        <!-- End Page Header -->

        <!-- start row -->
        <div class="row">
            <div class="col-xxl-8 col-xl-7 d-flex">
                <div class="card flex-fill">
                    <div class="card-body pb-0">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                            <h5 class="mb-0 fs-16 fw-bold d-inline-flex items-center"><span
                                    class="line-title d-block me-2"></span>Revenue Analytics</h5>
                            <ul class="nav nav-tabs nav-solid-danger border rounded gap-2 p-1">
                                <li class="nav-item"><a class="nav-link py-1 px-2 rounded active" href="#wekly"
                                        data-bs-toggle="tab">Weekly</a></li>
                                <li class="nav-item"><a class="nav-link py-1 px-2 rounded" href="#monthly"
                                        data-bs-toggle="tab">Monthly</a></li>
                                <li class="nav-item"><a class="nav-link py-1 px-2 rounded" href="#yearly"
                                        data-bs-toggle="tab">Yearly</a></li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div class="d-flex align-items-center flex-wrap gap-2">
                                <h4 class="mb-0">495K</h4>
                                <p class="mb-0">Revenue with Sales (USD)</p>
                            </div>
                            <div class="d-flex align-items-center flex-wrap gap-2">
                                <div class="d-flex align-items-center border rounded px-2 py-1">
                                    <p class="d-flex align-items-center mb-0"><i
                                            class="ti ti-circle-filled fs-8 text-primary me-1"></i>Revenue</p>
                                </div>
                                <div class="d-flex align-items-center border rounded px-2 py-1">
                                    <p class="d-flex align-items-center mb-0"><i
                                            class="ti ti-circle-filled fs-8 text-light-500 me-1"></i>Sales</p>
                                </div>
                            </div>
                        </div>
                        <div id="performance-stats"></div>
                    </div>
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xxl-4 col-xl-5 d-flex">
                <div class="card flex-fill">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-0">
                            <h5 class="mb-0 fs-16 fw-bold d-inline-flex items-center"><span
                                    class="line-title d-block me-2"></span>Traffic Sources</h5>
                            <a href="deals.html" class="btn btn-sm btn-icon btn-outline-light"><i
                                    class="ti ti-arrow-right"></i></a>
                        </div>
                        <div id="traffic-sources-chart"></div>
                    </div>
                    <div class="mb-1">
                        <div class="px-3 py-2 d-flex align-items-center justify-content-between border-bottom">
                            <p class="text-dark d-flex align-items-center mb-0"><i
                                    class="ti ti-circle-filled text-success fs-8 me-1"></i>Organic Search</p>
                            <p class="text-dark fw-semibold mb-0">6598</p>
                        </div>

                        <div class="px-3 py-2 d-flex align-items-center justify-content-between border-bottom">
                            <p class="text-dark d-flex align-items-center mb-0"><i
                                    class="ti ti-circle-filled text-info fs-8 me-1"></i>Direct Traffic</p>
                            <p class="text-dark fw-semibold mb-0">2458</p>
                        </div>

                        <div class="px-3 py-2 d-flex align-items-center justify-content-between border-bottom">
                            <p class="text-dark d-flex align-items-center mb-0"><i
                                    class="ti ti-circle-filled text-warning fs-8 me-1"></i>Referral Traffic</p>
                            <p class="text-dark fw-semibold mb-0">1456</p>
                        </div>

                        <div class="px-3 pt-2 pb-3 d-flex align-items-center justify-content-between">
                            <p class="text-dark d-flex align-items-center mb-0"><i
                                    class="ti ti-circle-filled text-purple fs-8 me-1"></i>Social Media</p>
                            <p class="text-dark fw-semibold mb-0">845</p>
                        </div>


                    </div>
                </div> <!-- end card -->
            </div> <!-- end col -->

        </div>
        <!-- end row -->

        <!-- start row -->
        <div class="row">
            <div class="col-xl-3 col-sm-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-body position-relative">
                        <p class="fw-medium mb-1">Revenue</p>
                        <h4 class="mb-3">$15,44,540</h4>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span
                                class="d-inline-flex align-items-center badge rounded-pill badge-soft-success border-0">+2.5%</span>
                            <p class="text-dark mb-0">From Last Week</p>
                        </div>
                        <div class="custom-card-icon">
                            <div
                                class="avatar avatar-rounded avatar-lg bg-primary-gradient-100 position-absolute top-0 end-0">
                                <img src="assets/img/icons/revenue-icon.svg" alt="icon"
                                    class="img-fluid w-auto h-auto">
                            </div>
                        </div>
                    </div>
                </div>
            </div> <!-- end col -->

            <div class="col-xl-3 col-sm-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-body position-relative">
                        <p class="fw-medium mb-1">Active Deals</p>
                        <h4 class="mb-3">147</h4>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span
                                class="d-inline-flex align-items-center badge rounded-pill badge-soft-danger border-0">-21.15%</span>
                            <p class="text-dark mb-0">From Last Week</p>
                        </div>
                        <div class="custom-card-icon">
                            <div
                                class="avatar avatar-rounded avatar-lg bg-info-gradient-100 position-absolute top-0 end-0">
                                <img src="assets/img/icons/deal-icon.svg" alt="icon"
                                    class="img-fluid w-auto h-auto">
                            </div>
                        </div>
                    </div>
                </div>
            </div> <!-- end col -->

            <div class="col-xl-3 col-sm-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-body position-relative">
                        <p class="fw-medium mb-1">Conversion Rate</p>
                        <h4 class="mb-3">32.8%</h4>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span
                                class="d-inline-flex align-items-center badge rounded-pill badge-soft-success border-0">+15.5%</span>
                            <p class="text-dark mb-0">From Last Week</p>
                        </div>
                        <div class="custom-card-icon">
                            <div
                                class="avatar avatar-rounded avatar-lg bg-pink-gradient-100 position-absolute top-0 end-0">
                                <img src="assets/img/icons/conversion-icon.svg" alt="icon"
                                    class="img-fluid w-auto h-auto">
                            </div>
                        </div>
                    </div>
                </div>
            </div> <!-- end col -->

            <div class="col-xl-3 col-sm-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-body position-relative">
                        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3">
                            <div>
                                <div class="d-flex align-items-center gap-1">
                                    <h4 class="mb-0">4569</h4>
                                    <span
                                        class="d-inline-flex align-items-center badge rounded-pill badge-soft-success border-0">+2.5%</span>
                                </div>
                                <p class="fw-medium mb-1">Total Contacts</p>
                            </div>
                            <div id="contact-chart"></div>
                        </div>
                        <div class="d-flex alig-items-center gap-2">
                            <div class="avatar-list-stacked avatar-group-sm">
                                <span class="avatar avatar-rounded">
                                    <img class="border border-white" src="assets/img/profiles/avatar-03.jpg"
                                        alt="img">
                                </span>
                                <span class="avatar avatar-rounded">
                                    <img class="border border-white" src="assets/img/profiles/avatar-05.jpg"
                                        alt="img">
                                </span>
                                <span class="avatar avatar-rounded">
                                    <img class="border border-white" src="assets/img/profiles/avatar-01.jpg"
                                        alt="img">
                                </span>
                                <a class="avatar bg-light text-dark fs-10 avatar-rounded"
                                    href="javascript:void(0);">
                                    +4
                                </a>
                            </div>
                            <p class="text-dark mb-0">From Last Week</p>
                        </div>
                    </div>
                </div> <!-- end card -->
            </div> <!-- end col -->
        </div>
        <!-- end row -->

        <!-- start row -->
        <div class="row">
            <div class="col-xxl-4 col-xl-12 d-flex">
                <div class="card flex-fill">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                            <h5 class="mb-0 fs-16 fw-bold d-inline-flex items-center"><span
                                    class="line-title d-block me-2"></span>Top Deals</h5>
                            <div class="dropdown">
                                <a class="dropdown-toggle btn btn-outline-light shadow"
                                    data-bs-toggle="dropdown" href="javascript:void(0);">
                                    Last 30 Days
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a href="javascript:void(0);" class="dropdown-item">
                                        Last 30 Days
                                    </a>
                                    <a href="javascript:void(0);" class="dropdown-item">
                                        Last 6 months
                                    </a>
                                    <a href="javascript:void(0);" class="dropdown-item">
                                        Last 12 months
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div
                            class="d-flex align-items-sm-center justify-content-between gap-2 flex-sm-row flex-column mb-3">
                            <div class="d-flex align-items-center">
                                <a href="company-details.html"
                                    class="avatar avatar-md border rounded-circle flex-shrink-0">
                                    <img src="assets/img/icons/company-icon-01.svg"
                                        class="img-fluid w-auto h-auto" alt="img">
                                </a>
                                <div class="ms-2 flex-fill">
                                    <p class="fw-medium text-truncate mb-1 fs-14"><a
                                            href="company-details.html">NovaWave LLC</a></p>
                                    <p class="fs-13 mb-0">Germany</p>
                                </div>
                            </div>
                            <div class="text-sm-end mb-0">
                                <p class="fw-semibold mb-0 text-dark">$19,94,938</p>
                            </div>
                        </div>

                        <div
                            class="d-flex align-items-sm-center justify-content-between gap-2 flex-sm-row flex-column mb-3">
                            <div class="d-flex align-items-center">
                                <a href="company-details.html"
                                    class="avatar avatar-md border rounded-circle flex-shrink-0">
                                    <img src="assets/img/icons/company-icon-03.svg"
                                        class="img-fluid w-auto h-auto" alt="img">
                                </a>
                                <div class="ms-2 flex-fill">
                                    <h6 class="fw-medium text-truncate mb-1 fs-14"><a
                                            href="company-details.html">Silver Hawk</a></h6>
                                    <p class="fs-13 mb-0">Australia</p>
                                </div>
                            </div>
                            <div class="text-sm-end mb-0">
                                <p class="fw-semibold mb-0 text-dark">$15,44,540</p>
                            </div>
                        </div>

                        <!-- Item-4 -->
                        <div
                            class="d-flex align-items-sm-center justify-content-between gap-2 flex-sm-row flex-column mb-3">
                            <div class="d-flex align-items-center">
                                <a href="company-details.html"
                                    class="avatar avatar-md border rounded-circle flex-shrink-0">
                                    <img src="assets/img/icons/company-icon-04.svg"
                                        class="img-fluid w-auto h-auto" alt="img">
                                </a>
                                <div class="ms-2 flex-fill">
                                    <h6 class="fw-medium text-truncate mb-1 fs-14"><a
                                            href="company-details.html">Summit LLC</a></h6>
                                    <p class="fs-13 mb-0">Italy</p>
                                </div>
                            </div>
                            <div class="text-sm-end mb-0">
                                <p class="fw-semibold mb-0 text-dark">$10,36,390</p>
                            </div>
                        </div>

                        <!-- Item-2 -->
                        <div
                            class="d-flex align-items-sm-center justify-content-between gap-2 flex-sm-row flex-column mb-3">
                            <div class="d-flex align-items-center">
                                <a href="company-details.html"
                                    class="avatar avatar-md border rounded-circle flex-shrink-0">
                                    <img src="assets/img/icons/company-icon-02.svg"
                                        class="img-fluid w-auto h-auto" alt="img">
                                </a>
                                <div class="ms-2 flex-fill">
                                    <h6 class="fw-medium text-truncate mb-1 fs-14"><a
                                            href="company-details.html">Bluesky Industries</a></h6>
                                    <p class="fs-13 mb-0">Canada</p>
                                </div>
                            </div>
                            <div class="text-sm-end mb-0">
                                <p class="fw-semibold mb-0 text-dark">$10,15,280</p>
                            </div>
                        </div>

                        <!-- Item-5 -->
                        <div
                            class="d-flex align-items-sm-center justify-content-between gap-2 flex-sm-row flex-column mb-3">
                            <div class="d-flex align-items-center">
                                <a href="company-details.html"
                                    class="avatar avatar-md border rounded-circle flex-shrink-0">
                                    <img src="assets/img/icons/company-icon-05.svg"
                                        class="img-fluid w-auto h-auto" alt="img">
                                </a>
                                <div class="ms-2 flex-fill">
                                    <h6 class="fw-medium text-truncate mb-1 fs-14"><a
                                            href="company-details.html">HealthTech Innovations</a></h6>
                                    <p class="fs-13 mb-0">UK</p>
                                </div>
                            </div>
                            <div class="text-sm-end mb-0">
                                <p class="fw-semibold mb-0 text-dark">$10,14,112</p>
                            </div>
                        </div>
                        <a class="btn btn-sm btn-light d-flex align-items-center" href="deals.html">
                            View All<i class="ti ti-chevron-right ms-1"></i>
                        </a>
                    </div>
                </div> <!-- end card -->
            </div>
            <!-- end col -->

            <div class="col-xxl-4 col-xl-6 d-flex flex-column">
                <div class="card flex-fill">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                            <h5 class="mb-0 fs-16 fw-bold d-inline-flex items-center"><span
                                    class="line-title d-block me-2"></span>Pipeline Statistics</h5>
                            <div class="dropdown">
                                <a class="dropdown-toggle btn btn-outline-light shadow"
                                    data-bs-toggle="dropdown" href="javascript:void(0);">
                                    Weekly
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a href="javascript:void(0);" class="dropdown-item">
                                        Monthly
                                    </a>
                                    <a href="javascript:void(0);" class="dropdown-item">
                                        Weekly
                                    </a>
                                    <a href="javascript:void(0);" class="dropdown-item">
                                        Last 12 months
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6 col-sm-3">
                                <div>
                                    <p class="mb-1">Lead</p>
                                    <p class="text-dark fw-medium mb-1">$20010</p>
                                    <p class="mb-0">80 Deals</p>
                                </div>
                            </div>
                            <div class="col-6 col-sm-3">
                                <div>
                                    <p class="mb-1">Proposal</p>
                                    <p class="text-dark fw-medium mb-1">$17210</p>
                                    <p class="mb-0">23 Deals</p>
                                </div>
                            </div>
                            <div class="col-6 col-sm-3">
                                <div>
                                    <p class="mb-1">Sales</p>
                                    <p class="text-dark fw-medium mb-1">$9210</p>
                                    <p class="mb-0">12 Deals</p>
                                </div>
                            </div>
                            <div class="col-6 col-sm-3">
                                <div>
                                    <p class="mb-1">Won</p>
                                    <p class="text-dark fw-medium mb-1">$8210</p>
                                    <p class="mb-0">21 Deals</p>
                                </div>
                            </div>
                        </div>
                        <div id="pipelineChart"></div>
                    </div>
                </div>
                <div class="card flex-fill">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                            <h5 class="mb-0 fw-bold d-inline-flex align-items-center gap-1"><span
                                    class="fw-normal fs-14 text-body">Profit Earned</span> $85K </h5>
                            <div class="dropdown">
                                <a class="dropdown-toggle btn btn-outline-light shadow"
                                    data-bs-toggle="dropdown" href="javascript:void(0);">
                                    2025
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a href="javascript:void(0);" class="dropdown-item">
                                        2025
                                    </a>
                                    <a href="javascript:void(0);" class="dropdown-item">
                                        2024
                                    </a>
                                    <a href="javascript:void(0);" class="dropdown-item">
                                        2023
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div id="profit-chart"></div>
                    </div>
                </div> <!-- end card -->
            </div>
            <!-- end col -->

            <div class="col-xxl-4 col-xl-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-body">

                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                            <h5 class="mb-0 fs-16 fw-bold d-inline-flex items-center"><span
                                    class="line-title d-block me-2"></span>Deals Overview</h5>
                            <a href="deals.html" class="btn btn-sm btn-icon btn-outline-light"><i
                                    class="ti ti-arrow-right"></i></a>
                        </div>

                        <div class="progress-stacked progress-md bg-white gap-1 mb-3">
                            <div class="progress-bar bg-success rounded overflow-hidden" role="progressbar"
                                style="width: 30%" aria-valuenow="10" aria-valuemin="0" aria-valuemax="100">
                            </div>
                            <div class="progress-bar bg-secondary rounded overflow-hidden" role="progressbar"
                                style="width: 35%" aria-valuenow="15" aria-valuemin="0" aria-valuemax="100">
                            </div>
                            <div class="progress-bar bg-purple rounded overflow-hidden" role="progressbar"
                                style="width: 25%" aria-valuenow="15" aria-valuemin="0" aria-valuemax="100">
                            </div>
                            <div class="progress-bar bg-danger rounded overflow-hidden" role="progressbar"
                                style="width: 10%" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
                                <h4 class="mb-0">2656</h4>
                                <span
                                    class="d-inline-flex align-items-center badge rounded-pill badge-soft-success border-0">+12.5%</span>
                                <p class="mb-0">compared to last week</p>
                            </div>

                            <div class="p-2 d-flex align-items-center justify-content-between border-bottom">
                                <p class="text-dark d-flex align-items-center mb-0"><i
                                        class="ti ti-circle-filled text-teal fs-8 me-1"></i>Successful Deals</p>
                                <p class="text-dark mb-0">1000 Deals</p>
                            </div>

                            <div class="p-2 d-flex align-items-center justify-content-between border-bottom">
                                <p class="text-dark d-flex align-items-center mb-0"><i
                                        class="ti ti-circle-filled text-secondary fs-8 me-1"></i>Pending Deals
                                </p>
                                <p class="text-dark mb-0">1056 Deals</p>
                            </div>

                            <div class="p-2 d-flex align-items-center justify-content-between border-bottom">
                                <p class="text-dark d-flex align-items-center mb-0"><i
                                        class="ti ti-circle-filled text-purple fs-8 me-1"></i>Rejected Deals</p>
                                <p class="text-dark mb-0">500 Deals</p>
                            </div>

                            <div class="p-2 d-flex align-items-center justify-content-between">
                                <p class="text-dark d-flex align-items-center mb-0"><i
                                        class="ti ti-circle-filled text-danger fs-8 me-1"></i>Upcoming Deals</p>
                                <p class="text-dark mb-0">100 Deals</p>
                            </div>
                        </div>

                        <div
                            class="p-3 border rounded bg-light d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-1">Deals Won</p>
                                <h4 class="mb-0">689</h4>
                            </div>
                            <div class="avatar-group avatar-group-sm">
                                <a href="#"
                                    class="avatar avatar-rounded border bg-white p-1 d-inline-flex align-items-center justify-content-center">
                                    <img class="w-auto h-auto img-fluid" src="assets/img/company/company-09.svg"
                                        alt="img">
                                </a>
                                <a href="#"
                                    class="avatar avatar-rounded border bg-white p-1 d-inline-flex align-items-center justify-content-center">
                                    <img class="w-auto h-auto img-fluid" src="assets/img/company/company-10.svg"
                                        alt="img">
                                </a>
                                <a href="#"
                                    class="avatar avatar-rounded border bg-white p-1 d-inline-flex align-items-center justify-content-center">
                                    <img class="w-auto h-auto img-fluid" src="assets/img/company/company-01.svg"
                                        alt="img">
                                </a>
                                <a href="#"
                                    class="avatar avatar-rounded border bg-white p-1 d-inline-flex align-items-center justify-content-center">
                                    <img class="w-auto h-auto img-fluid" src="assets/img/company/company-02.svg"
                                        alt="img">
                                </a>
                                <a href="#"
                                    class="avatar avatar-rounded border bg-white p-1 d-inline-flex align-items-center justify-content-center">
                                    <img class="w-auto h-auto img-fluid" src="assets/img/company/company-11.svg"
                                        alt="img">
                                </a>
                            </div>
                        </div>

                    </div>
                </div> <!-- end card -->
            </div>
            <!-- end col -->

        </div>
        <!-- end row -->

        <!-- start row -->
        <div class="row">
            <div class="col-md-12 d-flex">
                <div class="card flex-fill">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between flex-wrap row-gap-3 mb-3">
                            <h5 class="mb-0 fs-16 fw-bold d-inline-flex items-center"><span
                                    class="line-title d-block me-2"></span>Recent Deals</h5>
                            <a class="btn btn-sm btn-light d-inline-flex align-items-center" href="deals.html">
                                View All<i class="ti ti-chevron-right ms-1"></i>
                            </a>
                        </div>
                        <div class="table-responsive custom-table">
                            <table class="table table-bordered dataTable table-nowrap" id="deal-project">
                                <thead class="table-white">
                                    <tr>
                                        <th>Deal Name</th>
                                        <th>Stage</th>
                                        <th>Deal Value</th>
                                        <th>Tags</th>
                                        <th>Owner</th>
                                        <th>Probability</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

        </div>
        <!-- end row -->
    </div>
    {{--  End Content  --}}
@endsection
