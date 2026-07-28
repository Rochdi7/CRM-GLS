<?php $page = 'ui-badges'; ?>
@extends('layout.mainlayout')
@section('content')
   	<!-- Page Wrapper -->
       <div class="page-wrapper">
        <div class="content">
            @component('components.breadcrumb')
            @slot('title')
           Badges
            @endslot
        @endcomponent	
            <!-- start row -->
            <div class="row">

                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Default Badges</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-xl mb-3">Use the <span class="text-secondary">badge</span> class to show a default badge.</p>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge bg-primary">Primary</span>
                                <span class="badge bg-secondary">Secondary</span>
                                <span class="badge bg-success">Success</span>
                                <span class="badge bg-danger">Danger</span>
                                <span class="badge bg-warning">Warning</span>
                                <span class="badge bg-info">Info</span>
                                <span class="badge bg-light text-dark">Light</span>
                                <span class="badge bg-dark text-white">Dark</span>
                            </div>
                        </div> <!-- end card body -->
                    </div> <!-- end card -->
                </div> <!-- end col -->

                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Rounded Badges</h5>
                        </div>
                        <div class="card-body">
                            <p>Use the <code>.rounded-pill</code> to create a soft badge more rounded.</p>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge rounded-pill bg-primary">Primary</span>
                                <span class="badge rounded-pill bg-secondary">Secondary</span>
                                <span class="badge rounded-pill bg-success">Success</span>
                                <span class="badge rounded-pill bg-danger">Danger</span>
                                <span class="badge rounded-pill bg-warning">Warning</span>
                                <span class="badge rounded-pill bg-info">Info</span>
                                <span class="badge rounded-pill bg-light text-dark">Light</span>
                                <span class="badge rounded-pill bg-dark text-white">Dark</span>
                            </div>
                        </div> <!-- end card body -->
                    </div> <!-- end card -->
                </div> <!-- end col -->
                
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Soft Badges</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-xl mb-3">Use the <code>badge-soft-</code> class to create a softer badge.</p>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge badge-soft-primary">Primary</span>
                                <span class="badge badge-soft-secondary">Secondary</span>
                                <span class="badge badge-soft-success">Success</span>
                                <span class="badge badge-soft-danger">Danger</span>
                                <span class="badge badge-soft-warning">Warning</span>
                                <span class="badge badge-soft-info">Info</span>
                                <span class="badge badge-soft-light">Light</span>
                                <span class="badge badge-soft-dark">Dark</span>
                            </div>
                        </div> <!-- end card body -->
                    </div> <!-- end card -->
                </div> <!-- end col -->

                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Soft Rounded Badges</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-xl mb-3">Use the <code>rounded-pill  badge-soft-</code> to create a soft badge more rounded</p>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge rounded-pill badge-soft-primary">Primary</span>
                                <span class="badge rounded-pill badge-soft-secondary">Secondary</span>
                                <span class="badge rounded-pill badge-soft-success">Success</span>
                                <span class="badge rounded-pill badge-soft-danger">Danger</span>
                                <span class="badge rounded-pill badge-soft-warning">Warning</span>
                                <span class="badge rounded-pill badge-soft-info">Info</span>
                                <span class="badge rounded-pill badge-soft-light text-dark">Light</span>
                                <span class="badge rounded-pill badge-soft-dark">Dark</span>
                            </div>
                        </div> <!-- end card body -->
                    </div> <!-- end card -->
                </div> <!-- end col -->

                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Outline Badges</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-xl mb-3">Use the <code>badge-outline-</code> to create a badge with the outline.</p>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge badge-outline-primary">Primary</span>
                                <span class="badge badge-outline-secondary">Secondary</span>
                                <span class="badge badge-outline-success">Success</span>
                                <span class="badge badge-outline-danger">Danger</span>
                                <span class="badge badge-outline-warning">Warning</span>
                                <span class="badge badge-outline-info">Info</span>
                                <span class="badge badge-outline-light text-dark">Light</span>
                                <span class="badge badge-outline-dark">Dark</span>
                            </div>
                        </div> <!-- end card body -->
                    </div> <!-- end card -->
                </div> <!-- end col -->
                
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Outline Rounded Badges</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-xl mb-3">Use the <code>rounded-pill badge-outline-</code> class to create a outline pill badge.</p>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge rounded-pill badge-outline-primary">Primary</span>
                                <span class="badge rounded-pill badge-outline-secondary">Secondary</span>
                                <span class="badge rounded-pill badge-outline-success">Success</span>
                                <span class="badge rounded-pill badge-outline-danger">Danger</span>
                                <span class="badge rounded-pill badge-outline-warning">Warning</span>
                                <span class="badge rounded-pill badge-outline-info">Info</span>
                                <span class="badge rounded-pill badge-outline-light text-dark">Light</span>
                                <span class="badge rounded-pill badge-outline-dark">Dark</span>
                            </div>
                        </div> <!-- end card body -->
                    </div> <!-- end card -->
                </div> <!-- end col -->
                
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Soft Border Badges</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-xl mb-3">Use the <code>badge-border</code> and <code>badge-soft-</code> with below mentioned modifier classes to make badges with border & soft backgorund.</p>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge badge-soft-primary border border-primary">Primary</span>
                                <span class="badge badge-soft-secondary border border-secondary">Secondary</span>
                                <span class="badge badge-soft-success border border-success">Success</span>
                                <span class="badge badge-soft-danger border border-danger">Danger</span>
                                <span class="badge badge-soft-warning border border-warning">Warning</span>
                                <span class="badge badge-soft-info border border-info">Info</span>
                                <span class="badge badge-soft-light border border-light text-dark">Light</span>
                                <span class="badge badge-soft-dark border border-dark">Dark</span>
                            </div>
                        </div> <!-- end card body -->
                    </div> <!-- end card -->
                </div> <!-- end col -->
                
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Soft Border Rounded Badges</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-xl mb-3">Use the <code>badge-border</code> and <code>badge-soft-</code> with below mentioned modifier classes to make badges with border & soft backgorund.</p>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge rounded-pill badge-soft-primary border border-primary">Primary</span>
                                <span class="badge rounded-pill badge-soft-secondary border border-secondary">Secondary</span>
                                <span class="badge rounded-pill badge-soft-success border border-success">Success</span>
                                <span class="badge rounded-pill badge-soft-danger border border-danger">Danger</span>
                                <span class="badge rounded-pill badge-soft-warning border border-warning">Warning</span>
                                <span class="badge rounded-pill badge-soft-info border border-info">Info</span>
                                <span class="badge rounded-pill badge-soft-light border border-light text-dark">Light</span>
                                <span class="badge rounded-pill badge-soft-dark border border-dark">Dark</span>
                            </div>
                        </div> <!-- end card body -->
                    </div> <!-- end card -->
                </div> <!-- end col -->
                
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Gradient Badges</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge bg-primary-gradient">Primary</span>
                                <span class="badge bg-secondary-gradient">Secondary</span>
                                <span class="badge bg-success-gradient">Success</span>
                                <span class="badge bg-danger-gradient">Danger</span>
                                <span class="badge bg-warning-gradient">Warning</span>
                                <span class="badge bg-info-gradient">Info</span>
                                <span class="badge bg-orange-gradient">orange</span>
                                <span class="badge bg-purple-gradient">purple</span>
                            </div>
                        </div> <!-- end card body -->
                    </div> <!-- end card -->
                </div> <!-- end col -->
                
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Gradient Pill Badges</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge rounded-pill bg-primary-gradient">Primary</span>
                                <span class="badge rounded-pill bg-secondary-gradient">Secondary</span>
                                <span class="badge rounded-pill bg-success-gradient">Success</span>
                                <span class="badge rounded-pill bg-danger-gradient">Danger</span>
                                <span class="badge rounded-pill bg-warning-gradient">Warning</span>
                                <span class="badge rounded-pill bg-info-gradient">Info</span>
                                <span class="badge rounded-pill bg-orange-gradient">orange</span>
                                <span class="badge rounded-pill bg-purple-gradient">purple</span>
                            </div>
                        </div> <!-- end card body -->
                    </div> <!-- end card -->
                </div> <!-- end col -->

                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Badge Usage</h5>
                        </div>
                        <div class="card-body d-flex flex-wrap gap-4">
                            <button type="button" class="btn btn-primary btn-md position-relative">
                                Inbox
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-secondary">
                                    99+
                                    <span class="visually-hidden">unread messages</span>
                                </span>
                            </button>
                            <button type="button" class="btn btn-primary btn-md position-relative">
                                Profile
                                <span class="position-absolute top-0 start-100 translate-middle p-1 bg-warning border border-light rounded-circle">
                                    <span class="visually-hidden">New alerts</span>
                                </span>
                            </button>
                            <button type="button" class="btn btn-primary btn-md btn-icon position-relative">
                                <i class="ti ti-chevron-left"></i>
                                <span class="position-absolute top-0 start-100 translate-middle p-1 bg-warning border border-light rounded-circle">
                                    <span class="visually-hidden">New alerts</span>
                                </span>
                            </button>
                            <button type="button" class="btn btn-light rounded-circle btn-md btn-icon position-relative">
                                <i class="ti ti-bell-filled  text-dark"></i>
                            </button>
                            <button type="button" class="btn btn-light rounded-circle btn-md btn-icon position-relative">
                                <i class="ti ti-baseline-density-medium text-dark"></i>
                                <span class="position-absolute top-0 start-100 translate-middle p-1 bg-warning border border-light rounded-circle">
                                    <span class="visually-hidden">New alerts</span>
                                </span>
                            </button>
                        </div> <!-- end card body -->
                    </div> <!-- end card -->
                    
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Buttons With Badges </h5>
                        </div>
                        <div class="card-body d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-primary my-1 me-2">
                                Notifications <span class="badge ms-2 bg-secondary">3</span>
                            </button>
                            <button type="button" class="btn btn-secondary my-1 me-2">
                                Notifications <span class="badge ms-2 bg-white text-dark">24</span>
                            </button>
                            <button type="button" class="btn btn-success my-1 me-2">
                                Notifications <span class="badge ms-2 bg-danger">15</span>
                            </button>
                            <button type="button" class="btn btn-danger my-1 me-2">
                                Notifications <span class="badge ms-2 bg-white text-dark">24</span>
                            </button>
                            <button type="button" class="btn btn-warning my-1 me-2">
                                Notifications <span class="badge ms-2 bg-secondary">3</span>
                            </button>
                            <button type="button" class="btn btn-info my-1 me-2">
                                Notifications <span class="badge ms-2 bg-danger">15</span>
                            </button>
                        </div> <!-- end card body -->
                    </div> <!-- end card -->
                </div> <!-- end col -->
                
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Headings</h5>
                        </div>
                        <div class="card-body">
                            <h1 class="mb-3">Example heading <span class="badge bg-primary">New</span></h1>
                            <h2 class="mb-3">Example heading <span class="badge bg-secondary">New</span></h2>
                            <h3 class="mb-3">Example heading <span class="badge bg-success">New</span></h3>
                            <h4 class="mb-3">Example heading <span class="badge bg-info">New</span></h4>
                            <h5 class="mb-3">Example heading <span class="badge bg-warning">New</span></h5>
                            <h6 class="mb-0">Example heading <span class="badge bg-danger">New</span></h6>
                        </div> <!-- end card body -->
                    </div> <!-- end card -->
                </div> <!-- end col -->

            </div>
            <!-- end row -->
        </div>
    </div>
    <!-- /Page Wrapper -->
@endsection
