<!doctype html>
<html lang="en" data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="none" data-preloader="disable" data-theme="material" data-theme-colors="default">


<!-- Mirrored from themesbrand.com/velzon/html/master/ by HTTrack Website Copier/3.x [XR&CO'2014], Wed, 17 Sep 2025 07:33:06 GMT -->
<head>

    <meta charset="utf-8" />
    <title>Dashboard | Executive Club Cricket</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Executive Club Cricket | From archive Items, to Auctions and to cricket store, all in one place" name="description" />
    <meta content="Executive Club Cricket" name="author" />
    <!-- App favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

    <!-- jsvectormap css -->
    <link href="{{ asset('velzon/assets') }}/libs/jsvectormap/jsvectormap.min.css" rel="stylesheet" type="text/css" />

    <!--Swiper slider css-->
    <link href="{{ asset('velzon/assets') }}/libs/swiper/swiper-bundle.min.css" rel="stylesheet" type="text/css" />
    <!--SweetAlert2 css-->
    <link href="{{ asset('velzon/assets') }}/libs/sweetalert2/sweetalert2.min.css" rel="stylesheet" type="text/css" />

    <!-- Layout config Js -->
    <script src="{{ asset('velzon/assets') }}/js/layout.js"></script>
    <!-- Bootstrap Css -->
    <link href="{{ asset('velzon/assets') }}/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="{{ asset('velzon/assets') }}/css/icons.min.css" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="{{ asset('velzon/assets') }}/css/app.min.css" rel="stylesheet" type="text/css" />
    <!-- custom Css-->
    <link href="{{ asset('velzon/assets') }}/css/custom.min.css" rel="stylesheet" type="text/css" />

    <script>
        // Enforce Material Theme (Vertical, Dark Sidebar, Light Topbar, Material Theme)
        if (localStorage.getItem('data-layout') !== 'vertical' || localStorage.getItem('data-sidebar') !== 'dark' || localStorage.getItem('data-theme') !== 'material') {
            localStorage.setItem('data-layout', 'vertical');
            localStorage.setItem('data-sidebar', 'dark');
            localStorage.setItem('data-topbar', 'light');
            localStorage.setItem('data-sidebar-size', 'lg');
            localStorage.setItem('data-sidebar-image', 'none');
            localStorage.setItem('data-preloader', 'disable');
            localStorage.setItem('data-theme', 'material');
            localStorage.setItem('data-theme-colors', 'default');
        }
    </script>

    <!-- apexcharts -->
    <script src="{{ asset('velzon/assets') }}/libs/apexcharts/apexcharts.min.js"></script>

@livewireStyles
</head>

<body>

    <!-- Begin page -->
    <div id="layout-wrapper">

        <header id="page-topbar">
    <div class="layout-width">
        <div class="navbar-header">
            <div class="d-flex">
                <!-- LOGO -->
                <div class="navbar-brand-box horizontal-logo">
                    <a href="index-2.html" class="logo logo-dark">
                        <span class="logo-sm">
                            <!-- <img src="{{ asset('velzon/assets') }}/images/logo-sm.png" alt="" height="22"> -->

                             <h5 class="text-dark pt-3">Executive Club Cricket</h3>
                        </span>
                        <span class="">
                            <!-- <img src="{{ asset('velzon/assets') }}/images/logo-dark.png" alt="" height="17"> -->

                             <h5 class="text-white pt-3">Executive Club Cricket</h3>
                        </span>
                    </a>

                    <a href="index-2.html" class="logo logo-light">
                        <span class="logo-sm">
                            <!-- <img src="{{ asset('velzon/assets') }}/images/logo-sm.png" alt="" height="22"> -->
                              <h5 class="text-white pt-3">Executive Club Cricket</h3>
                        </span>
                        <span class="">
                            <!-- <img src="{{ asset('velzon/assets') }}/images/logo-light.png" alt="" height="17"> -->

                             <h5 class="text-white pt-3">Executive Club Cricket</h3>
                        </span>
                    </a>
                </div>

                <button type="button" class="btn btn-sm px-3 fs-16 header-item vertical-menu-btn topnav-hamburger material-shadow-none" id="topnav-hamburger-icon">
                    <span class="hamburger-icon">
                        <span></span>
                        <span></span>
                        <span></span>
                    </span>
                </button>

            </div>

            <div class="d-flex align-items-center">

                <div class="ms-1 header-item d-none d-sm-flex">
                    <button type="button" class="btn btn-icon btn-topbar material-shadow-none btn-ghost-secondary rounded-circle light-dark-mode">
                        <i class='bx bx-moon fs-22'></i>
                    </button>
                </div>

                <div class="ms-1 header-item d-none d-sm-flex">
                    <button type="button" class="btn btn-icon btn-topbar material-shadow-none btn-ghost-secondary rounded-circle" data-toggle="fullscreen">
                        <i class='bx bx-fullscreen fs-22'></i>
                    </button>
                </div>

                <div class="dropdown topbar-head-dropdown ms-1 header-item" id="notificationDropdown">
                    <button type="button" class="btn btn-icon btn-topbar material-shadow-none btn-ghost-secondary rounded-circle" id="page-header-notifications-dropdown" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-haspopup="true" aria-expanded="false">
                        <i class='bx bx-bell fs-22'></i>
                        <span class="position-absolute topbar-badge fs-10 translate-middle badge rounded-pill bg-danger">3<span class="visually-hidden">unread messages</span></span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0" aria-labelledby="page-header-notifications-dropdown">

                        <div class="dropdown-head bg-primary bg-pattern rounded-top">
                            <div class="p-3">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <h5 class="m-0 fs-16 fw-semibold text-white"> Notifications </h5>
                                    </div>
                                    <div class="col-auto dropdown-tabs">
                                        <span class="badge bg-light text-body fs-13"> 4 New</span>
                                    </div>
                                </div>
                            </div>

                            <div class="px-2 pt-2">
                                <ul class="nav nav-tabs dropdown-tabs nav-tabs-custom" data-dropdown-tabs="true" id="notificationItemsTab" role="tablist">
                                    <li class="nav-item waves-effect waves-light">
                                        <a class="nav-link active" data-bs-toggle="tab" href="#all-noti-tab" role="tab" aria-selected="true">
                                            All (4)
                                        </a>
                                    </li>
                                    <li class="nav-item waves-effect waves-light">
                                        <a class="nav-link" data-bs-toggle="tab" href="#messages-tab" role="tab" aria-selected="false">
                                            Messages
                                        </a>
                                    </li>
                                    <li class="nav-item waves-effect waves-light">
                                        <a class="nav-link" data-bs-toggle="tab" href="#alerts-tab" role="tab" aria-selected="false">
                                            Alerts
                                        </a>
                                    </li>
                                </ul>
                            </div>

                        </div>

                        <div class="tab-content position-relative" id="notificationItemsTabContent">
                            <div class="tab-pane fade show active py-2 ps-2" id="all-noti-tab" role="tabpanel">
                                <div data-simplebar style="max-height: 300px;" class="pe-2">
                                    <div class="text-reset notification-item d-block dropdown-item position-relative">
                                        <div class="d-flex">
                                            <div class="avatar-xs me-3 flex-shrink-0">
                                                <span class="avatar-title bg-info-subtle text-info rounded-circle fs-16">
                                                    <i class="bx bx-badge-check"></i>
                                                </span>
                                            </div>
                                            <div class="flex-grow-1">
                                                <a href="#!" class="stretched-link">
                                                    <h5 class="mt-0 mb-2 lh-base">Your <b>Elite</b> author Graphic
                                                        Optimization <span class="text-secondary">reward</span> is
                                                        ready!
                                                    </h5>
                                                </a>
                                                <p class="mb-0 fs-11 fw-medium text-uppercase text-muted">
                                                    <span><i class="mdi mdi-clock-outline"></i> Just 30 sec ago</span>
                                                </p>
                                            </div>
                                            <div class="px-2 fs-15">
                                                <div class="form-check notification-check">
                                                    <input class="form-check-input" type="checkbox" value="" id="all-notification-check01">
                                                    <label class="form-check-label" for="all-notification-check01"></label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-reset notification-item d-block dropdown-item position-relative">
                                        <div class="d-flex">
                                            <img src="{{ asset('velzon/assets') }}/images/users/avatar-2.jpg" class="me-3 rounded-circle avatar-xs flex-shrink-0" alt="user-pic">
                                            <div class="flex-grow-1">
                                                <a href="#!" class="stretched-link">
                                                    <h5 class="mt-0 mb-1 fs-13 fw-semibold">Angela Bernier</h5>
                                                </a>
                                                <div class="fs-13 text-muted">
                                                    <p class="mb-1">Answered to your comment on the cash flow forecast's
                                                        graph ðŸ””.</p>
                                                </div>
                                                <p class="mb-0 fs-11 fw-medium text-uppercase text-muted">
                                                    <span><i class="mdi mdi-clock-outline"></i> 48 min ago</span>
                                                </p>
                                            </div>
                                            <div class="px-2 fs-15">
                                                <div class="form-check notification-check">
                                                    <input class="form-check-input" type="checkbox" value="" id="all-notification-check02">
                                                    <label class="form-check-label" for="all-notification-check02"></label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-reset notification-item d-block dropdown-item position-relative">
                                        <div class="d-flex">
                                            <div class="avatar-xs me-3 flex-shrink-0">
                                                <span class="avatar-title bg-danger-subtle text-danger rounded-circle fs-16">
                                                    <i class='bx bx-message-square-dots'></i>
                                                </span>
                                            </div>
                                            <div class="flex-grow-1">
                                                <a href="#!" class="stretched-link">
                                                    <h5 class="mt-0 mb-2 fs-13 lh-base">You have received <b class="text-success">20</b> new messages in the conversation
                                                    </h5>
                                                </a>
                                                <p class="mb-0 fs-11 fw-medium text-uppercase text-muted">
                                                    <span><i class="mdi mdi-clock-outline"></i> 2 hrs ago</span>
                                                </p>
                                            </div>
                                            <div class="px-2 fs-15">
                                                <div class="form-check notification-check">
                                                    <input class="form-check-input" type="checkbox" value="" id="all-notification-check03">
                                                    <label class="form-check-label" for="all-notification-check03"></label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-reset notification-item d-block dropdown-item position-relative">
                                        <div class="d-flex">
                                            <img src="{{ asset('velzon/assets') }}/images/users/avatar-8.jpg" class="me-3 rounded-circle avatar-xs flex-shrink-0" alt="user-pic">
                                            <div class="flex-grow-1">
                                                <a href="#!" class="stretched-link">
                                                    <h5 class="mt-0 mb-1 fs-13 fw-semibold">Maureen Gibson</h5>
                                                </a>
                                                <div class="fs-13 text-muted">
                                                    <p class="mb-1">We talked about a project on linkedin.</p>
                                                </div>
                                                <p class="mb-0 fs-11 fw-medium text-uppercase text-muted">
                                                    <span><i class="mdi mdi-clock-outline"></i> 4 hrs ago</span>
                                                </p>
                                            </div>
                                            <div class="px-2 fs-15">
                                                <div class="form-check notification-check">
                                                    <input class="form-check-input" type="checkbox" value="" id="all-notification-check04">
                                                    <label class="form-check-label" for="all-notification-check04"></label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="my-3 text-center view-all">
                                        <button type="button" class="btn btn-soft-success waves-effect waves-light">View
                                            All Notifications <i class="ri-arrow-right-line align-middle"></i></button>
                                    </div>
                                </div>

                            </div>

                            <div class="tab-pane fade py-2 ps-2" id="messages-tab" role="tabpanel" aria-labelledby="messages-tab">
                                <div data-simplebar style="max-height: 300px;" class="pe-2">
                                    <div class="text-reset notification-item d-block dropdown-item">
                                        <div class="d-flex">
                                            <img src="{{ asset('velzon/assets') }}/images/users/avatar-3.jpg" class="me-3 rounded-circle avatar-xs" alt="user-pic">
                                            <div class="flex-grow-1">
                                                <a href="#!" class="stretched-link">
                                                    <h5 class="mt-0 mb-1 fs-13 fw-semibold">James Lemire</h5>
                                                </a>
                                                <div class="fs-13 text-muted">
                                                    <p class="mb-1">We talked about a project on linkedin.</p>
                                                </div>
                                                <p class="mb-0 fs-11 fw-medium text-uppercase text-muted">
                                                    <span><i class="mdi mdi-clock-outline"></i> 30 min ago</span>
                                                </p>
                                            </div>
                                            <div class="px-2 fs-15">
                                                <div class="form-check notification-check">
                                                    <input class="form-check-input" type="checkbox" value="" id="messages-notification-check01">
                                                    <label class="form-check-label" for="messages-notification-check01"></label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-reset notification-item d-block dropdown-item">
                                        <div class="d-flex">
                                            <img src="{{ asset('velzon/assets') }}/images/users/avatar-2.jpg" class="me-3 rounded-circle avatar-xs" alt="user-pic">
                                            <div class="flex-grow-1">
                                                <a href="#!" class="stretched-link">
                                                    <h5 class="mt-0 mb-1 fs-13 fw-semibold">Angela Bernier</h5>
                                                </a>
                                                <div class="fs-13 text-muted">
                                                    <p class="mb-1">Answered to your comment on the cash flow forecast's
                                                        graph ðŸ””.</p>
                                                </div>
                                                <p class="mb-0 fs-11 fw-medium text-uppercase text-muted">
                                                    <span><i class="mdi mdi-clock-outline"></i> 2 hrs ago</span>
                                                </p>
                                            </div>
                                            <div class="px-2 fs-15">
                                                <div class="form-check notification-check">
                                                    <input class="form-check-input" type="checkbox" value="" id="messages-notification-check02">
                                                    <label class="form-check-label" for="messages-notification-check02"></label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-reset notification-item d-block dropdown-item">
                                        <div class="d-flex">
                                            <img src="{{ asset('velzon/assets') }}/images/users/avatar-6.jpg" class="me-3 rounded-circle avatar-xs" alt="user-pic">
                                            <div class="flex-grow-1">
                                                <a href="#!" class="stretched-link">
                                                    <h5 class="mt-0 mb-1 fs-13 fw-semibold">Kenneth Brown</h5>
                                                </a>
                                                <div class="fs-13 text-muted">
                                                    <p class="mb-1">Mentionned you in his comment on ðŸ“ƒ invoice #12501.
                                                    </p>
                                                </div>
                                                <p class="mb-0 fs-11 fw-medium text-uppercase text-muted">
                                                    <span><i class="mdi mdi-clock-outline"></i> 10 hrs ago</span>
                                                </p>
                                            </div>
                                            <div class="px-2 fs-15">
                                                <div class="form-check notification-check">
                                                    <input class="form-check-input" type="checkbox" value="" id="messages-notification-check03">
                                                    <label class="form-check-label" for="messages-notification-check03"></label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-reset notification-item d-block dropdown-item">
                                        <div class="d-flex">
                                            <img src="{{ asset('velzon/assets') }}/images/users/avatar-8.jpg" class="me-3 rounded-circle avatar-xs" alt="user-pic">
                                            <div class="flex-grow-1">
                                                <a href="#!" class="stretched-link">
                                                    <h5 class="mt-0 mb-1 fs-13 fw-semibold">Maureen Gibson</h5>
                                                </a>
                                                <div class="fs-13 text-muted">
                                                    <p class="mb-1">We talked about a project on linkedin.</p>
                                                </div>
                                                <p class="mb-0 fs-11 fw-medium text-uppercase text-muted">
                                                    <span><i class="mdi mdi-clock-outline"></i> 3 days ago</span>
                                                </p>
                                            </div>
                                            <div class="px-2 fs-15">
                                                <div class="form-check notification-check">
                                                    <input class="form-check-input" type="checkbox" value="" id="messages-notification-check04">
                                                    <label class="form-check-label" for="messages-notification-check04"></label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="my-3 text-center view-all">
                                        <button type="button" class="btn btn-soft-success waves-effect waves-light">View
                                            All Messages <i class="ri-arrow-right-line align-middle"></i></button>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade p-4" id="alerts-tab" role="tabpanel" aria-labelledby="alerts-tab"></div>

                            <div class="notification-actions" id="notification-actions">
                                <div class="d-flex text-muted justify-content-center">
                                    Select <div id="select-content" class="text-body fw-semibold px-1">0</div> Result <button type="button" class="btn btn-link link-danger p-0 ms-3" data-bs-toggle="modal" data-bs-target="#removeNotificationModal">Remove</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="dropdown ms-sm-3 header-item topbar-user">
                    <button type="button" class="btn material-shadow-none" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="d-flex align-items-center">
                            <img class="rounded-circle header-profile-user" src="{{ asset('velzon/assets') }}/images/users/user-dummy-img.jpg" alt="Header Avatar">
                            <span class="text-start ms-xl-2">
                                <span class="d-none d-xl-inline-block ms-1 fw-medium user-name-text">{{ Auth::user()->name }}</span>
                                <span class="d-none d-xl-block ms-1 fs-12 user-name-sub-text">{{ is_array(Auth::user()->role) ? implode(', ', Auth::user()->role) : Auth::user()->role }}</span>
                            </span>
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <!-- item-->
                        <h6 class="dropdown-header">Welcome!</h6>
                        <a class="dropdown-item" href="#"><i class="mdi mdi-account-circle text-muted fs-16 align-middle me-1"></i> <span class="align-middle">Profile</span></a>
                        <a class="dropdown-item" href="#"><i class="mdi mdi-cog-outline text-muted fs-16 align-middle me-1"></i> <span class="align-middle">Settings</span></a>
                        <div class="dropdown-divider"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item">
                                <i class="mdi mdi-logout text-muted fs-16 align-middle me-1"></i>
                                <span class="align-middle" data-key="t-logout">Logout</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- removeNotificationModal -->
<div id="removeNotificationModal" class="modal fade zoomIn" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="NotificationModalbtn-close"></button>
            </div>
            <div class="modal-body">
                <div class="mt-2 text-center">
                    <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop" colors="primary:#f2b90d,secondary:#f06548" style="width:100px;height:100px"></lord-icon>
                    <div class="mt-4 pt-2 fs-15 mx-4 mx-sm-5">
                        <h5>Are you sure ?</h5>
                        <p class="text-muted mx-4 mb-0">Are you sure you want to remove this Notification ?</p>
                    </div>
                </div>
                <div class="d-flex gap-2 justify-content-center mt-4 mb-2">
                    <button type="button" class="btn w-sm btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn w-sm btn-danger" id="delete-notification">Yes, Delete It!</button>
                </div>
            </div>

        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
        <!-- ========== App Menu ========== -->
        <div class="app-menu navbar-menu">
            <!-- LOGO -->
            <div class="navbar-brand-box">
                <!-- Dark Logo-->
                <a href="index-2.html" class="logo logo-dark">
                    <span class="logo-sm">
                        <!-- <img src="{{ asset('velzon/assets') }}/images/logo-sm.png" alt="" height="22"> -->
                          <h3 class="text-dark pt-2">Executive Club Cricket</h3>
                    </span>
                    <span class="">
                        <!-- <img src="{{ asset('velzon/assets') }}/images/logo-dark.png" alt="" height="17"> -->
                         <h3 class="text-dark pt-2">Executive Club Cricket</h3>
                    </span>
                </a>
                <!-- Light Logo-->
                <a href="index-2.html" class="logo logo-light">
                    <span class="logo-sm">
                        <!-- <img src="{{ asset('velzon/assets') }}/images/logo-sm.png" alt="" height="22"> -->
                          <h5 class="text-white pt-2">Executive Club Cricket</h3>
                    </span>
                    <span class="">
                        <!-- <img src="{{ asset('velzon/assets') }}/images/logo-light.png" alt="" height="17"> -->
                          <h5 class="text-white pt-2">Executive Club Cricket</h3>
                    </span>
                </a>
                <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover" id="vertical-hover">
                    <i class="ri-record-circle-line"></i>
                </button>
            </div>
    
            <div class="dropdown sidebar-user m-1 rounded">
                <button type="button" class="btn material-shadow-none" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <span class="d-flex align-items-center gap-2">
                        <img class="rounded header-profile-user" src="{{ asset('velzon/assets') }}/images/users/avatar-1.jpg" alt="Header Avatar">
                        <span class="text-start">
                            <span class="d-block fw-medium sidebar-user-name-text">Anna Adame</span>
                            <span class="d-block fs-14 sidebar-user-name-sub-text"><i class="ri ri-circle-fill fs-10 text-success align-baseline"></i> <span class="align-middle">Online</span></span>
                        </span>
                    </span>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    <!-- item-->
                    <h5 class="dropdown-header">Welcome Anna!</h5>
                    <a class="dropdown-item" href="pages-profile.html"><i class="mdi mdi-account-circle text-muted fs-16 align-middle me-1"></i> <span class="align-middle">Profile</span></a>
                    <a class="dropdown-item" href="apps-chat.html"><i class="mdi mdi-message-text-outline text-muted fs-16 align-middle me-1"></i> <span class="align-middle">Messages</span></a>
                    <a class="dropdown-item" href="apps-tasks-kanban.html"><i class="mdi mdi-calendar-check-outline text-muted fs-16 align-middle me-1"></i> <span class="align-middle">Taskboard</span></a>
                    <a class="dropdown-item" href="pages-faqs.html"><i class="mdi mdi-lifebuoy text-muted fs-16 align-middle me-1"></i> <span class="align-middle">Help</span></a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="pages-profile.html"><i class="mdi mdi-wallet text-muted fs-16 align-middle me-1"></i> <span class="align-middle">Balance : <b>$5971.67</b></span></a>
                    <a class="dropdown-item" href="pages-profile-settings.html"><span class="badge bg-success-subtle text-success mt-1 float-end">New</span><i class="mdi mdi-cog-outline text-muted fs-16 align-middle me-1"></i> <span class="align-middle">Settings</span></a>
                    <a class="dropdown-item" href="auth-lockscreen-basic.html"><i class="mdi mdi-lock text-muted fs-16 align-middle me-1"></i> <span class="align-middle">Lock screen</span></a>
                    <a class="dropdown-item" href="auth-logout-basic.html"><i class="mdi mdi-logout text-muted fs-16 align-middle me-1"></i> <span class="align-middle" data-key="t-logout">Logout</span></a>
                </div>
            </div>
            <div id="scrollbar">
                <div class="container-fluid">


                    <div id="two-column-menu">
                    </div>
                    <ul class="navbar-nav" id="navbar-nav">
                        <li class="menu-title"><span data-key="t-menu">Menu</span></li>
                        <li class="nav-item">
                            <a class="nav-link menu-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                                <i class="ri-dashboard-2-line"></i> <span data-key="t-dashboards">Dashboard</span>
                            </a>
                        </li>
                        <li class="menu-title"><i class="ri-more-fill"></i> <span data-key="t-modules">Modules</span></li>

                        @php
                            $isUsersActive = request()->routeIs('admin.users.*');
                        @endphp
                        <li class="nav-item">
                            <a class="nav-link menu-link {{ $isUsersActive ? 'active' : '' }}" href="#sidebarUsers" data-bs-toggle="collapse" role="button" aria-expanded="{{ $isUsersActive ? 'true' : 'false' }}" aria-controls="sidebarUsers">
                                <i class="ri-user-line"></i> <span data-key="t-users">Users</span>
                            </a>
                            <div class="collapse menu-dropdown {{ $isUsersActive ? 'show' : '' }}" id="sidebarUsers">
                                <ul class="nav nav-sm flex-column">
                                    <li class="nav-item">
                                        <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.index') ? 'active' : '' }}" data-key="t-users-list">Users</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.users.admin') }}" class="nav-link {{ request()->routeIs('admin.users.admin') ? 'active' : '' }}" data-key="t-admin-users">Admin Users</a>
                                    </li>
                                </ul>
                            </div>
                        </li>

                        @php
                            $isMembershipActive = request()->routeIs('admin.membership.*');
                        @endphp
                        <li class="nav-item">
                            <a class="nav-link menu-link {{ $isMembershipActive ? 'active' : '' }}" href="#sidebarMembership" data-bs-toggle="collapse" role="button" aria-expanded="{{ $isMembershipActive ? 'true' : 'false' }}" aria-controls="sidebarMembership">
                                <i class="ri-vip-crown-line"></i>
                                <span data-key="t-membership">Membership</span>
                                @if(($pendingMembershipApplicationsCount ?? 0) > 0)
                                    <span class="badge me-3 bg-warning text-dark ms-auto">{{ $pendingMembershipApplicationsCount }}</span>
                                @endif
                            </a>
                            <div class="collapse menu-dropdown {{ $isMembershipActive ? 'show' : '' }}" id="sidebarMembership">
                                <ul class="nav nav-sm flex-column">
                                    <li class="nav-item">
                                        <a href="{{ route('admin.membership.applications') }}" class="nav-link {{ request()->routeIs('admin.membership.applications') ? 'active' : '' }}" data-key="t-applications">
                                            <span>Applications</span>
                                            @if($pendingMembershipApplicationsCount > 0)
                                                <span class="badge bg-warning text-dark ms-auto">{{ $pendingMembershipApplicationsCount }}</span>
                                            @endif
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.membership.tiers') }}" class="nav-link {{ request()->routeIs('admin.membership.tiers') ? 'active' : '' }}" data-key="t-tiers">Tiers</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.membership.members') }}" class="nav-link {{ request()->routeIs('admin.membership.members') ? 'active' : '' }}" data-key="t-members">Members</a>
                                    </li>
                                </ul>
                            </div>
                        </li>



                        @php
                            $isArchiveActive = request()->routeIs('admin.archive.*');
                        @endphp
                        <li class="nav-item">
                            <a class="nav-link menu-link {{ $isArchiveActive ? 'active' : '' }}" href="#sidebarArchive" data-bs-toggle="collapse" role="button" aria-expanded="{{ $isArchiveActive ? 'true' : 'false' }}" aria-controls="sidebarArchive">
                                <i class="ri-archive-line"></i>
                                <span data-key="t-archive">The Archive</span>
                                @if(($newArchiveEnquiriesCount ?? 0) > 0)
                                    <span class="badge me-3 bg-danger ms-auto">{{ $newArchiveEnquiriesCount }}</span>
                                @endif
                            </a>
                            <div class="collapse menu-dropdown {{ $isArchiveActive ? 'show' : '' }}" id="sidebarArchive">
                                <ul class="nav nav-sm flex-column">
                                    <li class="nav-item">
                                        <a href="{{ route('admin.archive.categories') }}" class="nav-link {{ request()->routeIs('admin.archive.categories') ? 'active' : '' }}" data-key="t-categories">Categories</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.archive.products') }}" class="nav-link {{ request()->routeIs('admin.archive.products') ? 'active' : '' }}" data-key="t-products">Products</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.archive.enquiries') }}" class="nav-link {{ request()->routeIs('admin.archive.enquiries') ? 'active' : '' }}" data-key="t-enquiries">
                                            <span>Enquiries</span>
                                            @if($newArchiveEnquiriesCount > 0)
                                                <span class="badge bg-danger ms-auto">{{ $newArchiveEnquiriesCount }}</span>
                                            @endif
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.archive.orders.index') }}" class="nav-link {{ request()->routeIs('admin.archive.orders.*') ? 'active' : '' }}" data-key="t-orders">Orders</a>
                                    </li>
                                </ul>
                            </div>
                        </li>

                        @php
                            $isAuctionsActive = request()->routeIs('admin.auctions.*');
                        @endphp
                        <li class="nav-item">
                            <a class="nav-link menu-link {{ $isAuctionsActive ? 'active' : '' }}" href="#sidebarAuctions" data-bs-toggle="collapse" role="button" aria-expanded="{{ $isAuctionsActive ? 'true' : 'false' }}" aria-controls="sidebarAuctions">
                                <i class="ri-auction-line"></i>
                                <span data-key="t-auctions">Auctions</span>
                                @if(($newAuctionEnquiriesCount ?? 0) > 0)
                                    <span class="badge me-3 bg-danger ms-auto">{{ $newAuctionEnquiriesCount }}</span>
                                @endif
                            </a>
                            <div class="collapse menu-dropdown {{ $isAuctionsActive ? 'show' : '' }}" id="sidebarAuctions">
                                <ul class="nav nav-sm flex-column">
                                    <li class="nav-item">
                                        <a href="{{ route('admin.auctions.lots.index') }}" class="nav-link {{ request()->routeIs('admin.auctions.lots.index') ? 'active' : '' }}" data-key="t-auction-lots">Auction Lots</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.auctions.enquiries') }}" class="nav-link {{ request()->routeIs('admin.auctions.enquiries') ? 'active' : '' }}" data-key="t-auction-enquiries">
                                            <span>Enquiries</span>
                                            @if($newAuctionEnquiriesCount > 0)
                                                <span class="badge bg-danger ms-auto">{{ $newAuctionEnquiriesCount }}</span>
                                            @endif
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.auctions.orders.index') }}" class="nav-link {{ request()->routeIs('admin.auctions.orders.index') ? 'active' : '' }}" data-key="t-auction-orders">Orders</a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link menu-link {{ request()->routeIs('admin.shop.*') ? 'active' : '' }}" href="#sidebarShop" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->routeIs('admin.shop.*') ? 'true' : 'false' }}" aria-controls="sidebarShop">
                                <i class="ri-store-2-line"></i> <span data-key="t-shop">Shop</span>
                                @if(($placedOrdersCount ?? 0) > 0)
                                    <span class="badge me-3 bg-danger ms-auto">{{ $placedOrdersCount }}</span>
                                @endif
                            </a>
                            <div class="collapse menu-dropdown {{ request()->routeIs('admin.shop.*') ? 'show' : '' }}" id="sidebarShop">
                                <ul class="nav nav-sm flex-column">
                                    <li class="nav-item">
                                        <a href="{{ route('admin.shop.categories') }}" class="nav-link {{ request()->routeIs('admin.shop.categories') ? 'active' : '' }}" data-key="t-shop-categories">Categories</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.shop.tags') }}" class="nav-link {{ request()->routeIs('admin.shop.tags') ? 'active' : '' }}" data-key="t-shop-tags">Tags</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.shop.products') }}" class="nav-link {{ request()->routeIs('admin.shop.products') ? 'active' : '' }}" data-key="t-shop-products">Products</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.shop.inventory') }}" class="nav-link {{ request()->routeIs('admin.shop.inventory') ? 'active' : '' }}" data-key="t-shop-inventory">Inventory</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.shop.carts') }}" class="nav-link {{ request()->routeIs('admin.shop.carts') ? 'active' : '' }}" data-key="t-shop-carts">Carts</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.shop.orders') }}" class="nav-link {{ request()->routeIs('admin.shop.orders*') ? 'active' : '' }}" data-key="t-shop-orders">
                                            <span>Orders</span>
                                            @if(($placedOrdersCount ?? 0) > 0)
                                                <span class="badge bg-danger ms-auto">{{ $placedOrdersCount }}</span>
                                            @endif
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link menu-link {{ request()->routeIs('admin.enquiries.index') ? 'active' : '' }}" href="{{ route('admin.enquiries.index') }}">
                                <i class="ri-chat-voice-line"></i> <span data-key="t-contact-enquiries">Enquiries</span>
                                @if($newContactEnquiriesCount > 0)
                                    <span class="badge bg-danger ms-auto">{{ $newContactEnquiriesCount }}</span>
                                @endif
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link menu-link {{ request()->routeIs('admin.cms.*') ? 'active' : '' }}" href="#sidebarCms" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->routeIs('admin.cms.*') ? 'true' : 'false' }}" aria-controls="sidebarCms">
                                <i class="ri-slideshow-line"></i> <span data-key="t-cms">CMS</span>
                            </a>
                            <div class="collapse menu-dropdown {{ request()->routeIs('admin.cms.*') ? 'show' : '' }}" id="sidebarCms">
                                <ul class="nav nav-sm flex-column">
                                    <li class="nav-item">
                                        <a href="{{ route('admin.cms.blocks.index') }}" class="nav-link {{ request()->routeIs('admin.cms.blocks.index') ? 'active' : '' }}" data-key="t-cms-blocks">Blocks</a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        
                        @php
                            $isVaultActive = request()->routeIs('admin.vault-access.*');
                        @endphp
                        <li class="nav-item">
                            <a class="nav-link menu-link {{ $isVaultActive ? 'active' : '' }}" href="{{ route('admin.vault-access.index') }}">
                                <i class="ri-safe-2-line"></i> <span data-key="t-vault">Vault Access</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link menu-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}">
                                <i class="ri-pie-chart-line"></i> <span data-key="t-reports">Reports</span>
                            </a>
                        </li>
                        <li class="nav-item"><a class="nav-link menu-link" href="#"><i class="ri-settings-3-line"></i> <span>Settings</span></a></li>
                    </ul>
                </div>
                <!-- Sidebar -->
            </div>
            
            <div class="sidebar-background"></div>
        </div>
        <!-- Left Sidebar End -->
        <!-- Vertical Overlay-->
        
        <div class="vertical-overlay"></div>

        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="main-content">

            <div class="page-content">
                {{ $slot }}
            </div>
            <footer class="footer">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-sm-6">
                            <script>document.write(new Date().getFullYear())</script> © Executive Club Cricket.
                        </div>
                        <div class="col-sm-6">
                            <div class="text-sm-end d-none d-sm-block">
                                Design & Develop by Store Site
                            </div>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
        <!-- end main content-->

    </div>
    <!-- END layout-wrapper -->



    <!--start back-to-top-->
    <button onclick="topFunction()" class="btn btn-danger btn-icon" id="back-to-top">
        <i class="ri-arrow-up-line"></i>
    </button>
    <!--end back-to-top-->

    <!--preloader-->
    <div id="preloader">
        <div id="status">
            <div class="spinner-border text-primary avatar-sm" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>


    <!-- JAVASCRIPT -->
    <script src="{{ asset('velzon/assets') }}/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('velzon/assets') }}/libs/simplebar/simplebar.min.js"></script>
    <script src="{{ asset('velzon/assets') }}/libs/node-waves/waves.min.js"></script>
    <script src="{{ asset('velzon/assets') }}/libs/feather-icons/feather.min.js"></script>
    <script src="{{ asset('velzon/assets') }}/js/pages/plugins/lord-icon-2.1.0.js"></script>
    <script src="{{ asset('velzon/assets') }}/js/plugins.js"></script>


    <!-- Vector map-->
    <script src="{{ asset('velzon/assets') }}/libs/jsvectormap/jsvectormap.min.js"></script>
    <script src="{{ asset('velzon/assets') }}/libs/jsvectormap/maps/world-merc.js"></script>

    <!--Swiper slider js-->
    <script src="{{ asset('velzon/assets') }}/libs/swiper/swiper-bundle.min.js"></script>

    <!-- Dashboard init -->
    <script src="{{ asset('velzon/assets') }}/js/pages/dashboard-ecommerce.init.js"></script>

    <!-- App js -->
    <script src="{{ asset('velzon/assets') }}/js/app.js"></script>
    <script src="{{ asset('velzon/assets') }}/libs/sweetalert2/sweetalert2.min.js"></script>
    
    @vite(['resources/js/app.js'])
    
    @include('admin.reports.partials._charts_js')
    @stack('scripts')
@livewireScripts
@include('layouts.partials._overlay_cleanup')
</body>


<!-- Mirrored from themesbrand.com/velzon/html/master/ by HTTrack Website Copier/3.x [XR&CO'2014], Wed, 17 Sep 2025 07:35:46 GMT -->
</html>
