<?php $page = 'ui-dropdowns'; ?>
@extends('layout.mainlayout')
@section('content')
<div class="page-wrapper">
    <div class="content">
        @component('components.breadcrumb')
        @slot('title')
      Dropdowns
        @endslot
    @endcomponent	
        <!-- start row -->
        <div class="row">
            
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Dropdowns</h5>
                    </div>
                    <div class="card-body d-flex flex-wrap gap-2">
                    
                        <div class="dropdown">
                            <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Dropdown Button
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#">Action</a></li>
                                <li><a class="dropdown-item" href="#">Another action</a></li>
                                <li><a class="dropdown-item" href="#">Something else here</a></li>
                            </ul>
                        </div>
                        <div class="dropdown">
                            <a class="btn btn-secondary dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Dropdown Link
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#">Action</a></li>
                                <li><a class="dropdown-item" href="#">Another action</a></li>
                                <li><a class="dropdown-item" href="#">Something else here</a></li>
                            </ul>
                        </div>
                        
                    </div> <!-- end card body -->
                </div> <!-- end card-->
            </div> <!-- end col -->

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Dark Dropdowns</h5>
                    </div>
                    <div class="card-body">
                    
                        <div class="dropdown">
                            <button class="btn btn-dark dropdown-toggle" type="button" id="dropdownMenuButton3" data-bs-toggle="dropdown" aria-expanded="false">
                                Dropdown button
                            </button>
                            <ul class="dropdown-menu dropdown-menu-dark">
                                <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                            </ul>
                        </div>
                        
                    </div> <!-- end card body -->
                </div> <!-- end card-->
            </div> <!-- end col -->

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Single dropdown buttons</h5>
                    </div>
                    <div class="card-body">
                    
                        <div class="btn-list d-flex flex-wrap gap-2">
                            <div class="btn-group">
                                <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    Action
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                                </ul>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    Action
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                                </ul>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    Action
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                                </ul>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    Action
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                                </ul>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-warning dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    Action
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                                </ul>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-danger dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    Action
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                                </ul>
                            </div>
                        </div>
                        
                    </div> <!-- end card body -->
                </div> <!-- end card-->
            </div> <!-- end col -->

            <div class="col-xl-6">
            
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Rounded Button Dropdowns</h5>
                    </div>
                    <div class="card-body">
                        <div class="btn-list d-flex flex-wrap gap-2">
                            <div class="btn-group">
                                <button type="button" class="btn btn-primary dropdown-toggle rounded-pill" data-bs-toggle="dropdown" aria-expanded="false">
                                    Action
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                                </ul>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-secondary dropdown-toggle rounded-pill" data-bs-toggle="dropdown" aria-expanded="false">
                                    Action
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                                </ul>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-success dropdown-toggle rounded-pill" data-bs-toggle="dropdown" aria-expanded="false">
                                    Action
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                                </ul>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-info dropdown-toggle rounded-pill" data-bs-toggle="dropdown" aria-expanded="false">
                                    Action
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                                </ul>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-warning dropdown-toggle rounded-pill" data-bs-toggle="dropdown" aria-expanded="false">
                                    Action
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                                </ul>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-danger dropdown-toggle rounded-pill" data-bs-toggle="dropdown" aria-expanded="false">
                                    Action
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                                </ul>
                            </div>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card-->
            </div> <!-- end col -->

            <div class="col-xl-6">
            
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Outline Button Dropdowns</h5>
                    </div>
                    <div class="card-body">
                        <div class="btn-list d-flex flex-wrap gap-2">
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    Action
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                                </ul>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    Action
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                                </ul>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    Action
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                                </ul>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    Action
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                                </ul>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-warning dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    Action
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                                </ul>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-danger dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    Action
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                                </ul>
                            </div>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card-->
            </div> <!-- end col -->

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Rounded Outline Dropdowns</h5>
                    </div>
                    <div class="card-body">
                        <div class="btn-list d-flex flex-wrap gap-2">
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-primary dropdown-toggle rounded-pill" data-bs-toggle="dropdown" aria-expanded="false">
                                    Action
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                                </ul>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-secondary dropdown-toggle rounded-pill" data-bs-toggle="dropdown" aria-expanded="false">
                                    Action
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                                </ul>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-success dropdown-toggle rounded-pill" data-bs-toggle="dropdown" aria-expanded="false">
                                    Action
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                                </ul>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-info dropdown-toggle rounded-pill" data-bs-toggle="dropdown" aria-expanded="false">
                                    Action
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                                </ul>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-warning dropdown-toggle rounded-pill" data-bs-toggle="dropdown" aria-expanded="false">
                                    Action
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                                </ul>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-danger dropdown-toggle rounded-pill" data-bs-toggle="dropdown" aria-expanded="false">
                                    Action
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                                </ul>
                            </div>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card-->
            </div> <!-- end col -->
            
            <div class="col-xl-6">
            
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Soft Button Dropdowns</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-2">
                            <div class="btn-group">
                                <button type="button" class="btn btn-soft-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    Primary
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                                </ul>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-soft-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    Secondary
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                                </ul>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-soft-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    Success
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                                </ul>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-soft-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    Info
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                                </ul>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-soft-warning dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    Warning
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                                </ul>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-soft-danger dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    Danger
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                                </ul>
                            </div>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card-->
            </div> <!-- end col -->
        
            <div class="col-xl-6">
            
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Rounded Soft Dropdowns</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-2">
                            <div class="btn-group">
                                <button type="button" class="btn btn-soft-primary dropdown-toggle rounded-pill" data-bs-toggle="dropdown" aria-expanded="false">
                                    Primary
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                                </ul>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-soft-secondary dropdown-toggle rounded-pill" data-bs-toggle="dropdown" aria-expanded="false">
                                    Secondary
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                                </ul>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-soft-success dropdown-toggle rounded-pill" data-bs-toggle="dropdown" aria-expanded="false">
                                    Success
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                                </ul>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-soft-info dropdown-toggle rounded-pill" data-bs-toggle="dropdown" aria-expanded="false">
                                    Info
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                                </ul>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-soft-warning dropdown-toggle rounded-pill" data-bs-toggle="dropdown" aria-expanded="false">
                                    Warning
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                                </ul>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-soft-danger dropdown-toggle rounded-pill" data-bs-toggle="dropdown" aria-expanded="false">
                                    Danger
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                                </ul>
                            </div>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card-->
            </div> <!-- end col -->

            <div class="col-xl-6">
            
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Split button dropdowns</h5>
                    </div>
                    <div class="card-body py-3">
                        <div class="btn-group my-1">
                            <button type="button" class="btn btn-primary">Action</button>
                            <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split me-2" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="visually-hidden">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                            </ul>
                        </div>
                        <div class="btn-group my-1">
                            <button type="button" class="btn btn-secondary">Action</button>
                            <button type="button" class="btn btn-secondary dropdown-toggle dropdown-toggle-split me-2" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="visually-hidden">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                            </ul>
                        </div>
                        <div class="btn-group my-1">
                            <button type="button" class="btn btn-info">Action</button>
                            <button type="button" class="btn btn-info dropdown-toggle dropdown-toggle-split me-2" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="visually-hidden">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                            </ul>
                        </div>
                        <div class="btn-group my-1">
                            <button type="button" class="btn btn-success">Action</button>
                            <button type="button" class="btn btn-success dropdown-toggle dropdown-toggle-split me-2" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="visually-hidden">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                            </ul>
                        </div>
                        <div class="btn-group my-1">
                            <button type="button" class="btn btn-warning">Action</button>
                            <button type="button" class="btn btn-warning dropdown-toggle dropdown-toggle-split me-2" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="visually-hidden">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                            </ul>
                        </div>
                        <div class="btn-group my-1">
                            <button type="button" class="btn btn-danger">Action</button>
                            <button type="button" class="btn btn-danger dropdown-toggle dropdown-toggle-split me-2" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="visually-hidden">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                            </ul>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card-->
            </div> <!-- end col -->
        
            <div class="col-xl-6">
            
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Dropdown Sizing</h5>
                    </div>
                    <div class="card-body py-3">
                    
                        <div class="btn-group my-1 me-2">
                            <button class="btn btn-primary btn-lg dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Large button
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                            </ul>
                        </div>
                        <div class="btn-group my-1 me-2">
                            <button class="btn btn-success btn-lg" type="button">
                                Large split button
                            </button>
                            <button type="button" class="btn btn-lg btn-success dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="visually-hidden">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                            </ul>
                        </div>
                        
                        <div class="btn-group my-1 me-2">
                            <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Small button
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                            </ul>
                        </div>
                        <div class="btn-group my-1">
                            <button class="btn btn-danger btn-sm" type="button">
                                Small split button
                            </button>
                            <button type="button" class="btn btn-sm btn-danger dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="visually-hidden">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                            </ul>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card-->
            </div> <!-- end col -->

            <div class="col-xl-6">
            
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Active</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Add <code>.active</code> to item in the dropdown to <strong>style them as active</strong>.</p>
                        <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            Dropstart
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="javascript:void(0);">Regular link</a></li>
                            <li><a class="dropdown-item active" href="javascript:void(0);" aria-current="true">Active link</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);">Another link</a></li>
                        </ul>
                    </div> <!-- end card body -->
                </div> <!-- end card-->
            </div> <!-- end col -->

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Disabled</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Add <code>.disabled</code> to items in the dropdown to <strong>style them as disabled</strong>.</p>
                        <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            Dropstart
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="javascript:void(0);">Regular link</a></li>
                            <li><a class="dropdown-item active" href="javascript:void(0);" aria-current="true">Active link</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);">Another link</a></li>
                        </ul>
                    </div> <!-- end card body -->
                </div> <!-- end card-->
            </div> <!-- end col -->

            <div class="col-xl-6">
            
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Dropdown menu centered</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Use <code>.dropdown-center</code> on the parent element.</p>
                        <div class="dropdown-center">
                            <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownCenterBtn" data-bs-toggle="dropdown" aria-expanded="false">
                                Centered dropdown
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownCenterBtn">
                                <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Action two</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Action three</a></li>
                            </ul>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card-->
            </div> <!-- end col -->

            <div class="col-xl-6">
            
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Dropup Centered</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Use <code>.dropup-center</code> on the parent element.</p>
                        <div class="dropup-center dropup">
                            <button class="btn btn-secondary dropdown-toggle" type="button" id="dropupCenterBtn" data-bs-toggle="dropdown" aria-expanded="false">
                                Centered dropup
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropupCenterBtn">
                                <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Action two</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Action three</a></li>
                            </ul>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card-->
            </div> <!-- end col -->

            <div class="col-xl-6">
            
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Menu Items</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">You can use <code>&lt;a&gt;</code> or <code>&lt;button&gt;</code> as dropdown items.</p>
                        <div class="dropdown">
                            <button class="btn btn-info dropdown-toggle" type="button" id="dropdownMenu1" data-bs-toggle="dropdown" aria-expanded="false">
                                Dropdown
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
                                <li>
                                    <button class="dropdown-item" type="button">Action</button>
                                </li>
                                <li>
                                    <button class="dropdown-item" type="button">Another action</button>
                                </li>
                                <li>
                                    <button class="dropdown-item" type="button">Something else here</button>
                                </li>
                            </ul>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card-->
            </div> <!-- end col -->
            
            <div class="col-xl-6">
            
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Dropdown Options</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Use <code>data-bs-offset</code> or <code>data-bs-reference</code> to change the location of the dropdown.</p>
                        <div class="d-flex align-items-center">
                            <div class="dropdown me-1">
                                <button type="button" class="btn btn-primary dropdown-toggle" id="dropdownMenuOffset" data-bs-toggle="dropdown" aria-expanded="false" data-bs-offset="10,20">
                                    Offset
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuOffset">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                </ul>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-info">Reference</button>
                                <button type="button" class="btn btn-info dropdown-toggle dropdown-toggle-split" id="dropdownMenuReference" data-bs-toggle="dropdown" aria-expanded="false" data-bs-reference="parent">
                                    <span class="visually-hidden">Toggle Dropdown</span>
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuReference">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                                </ul>
                            </div>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card-->
            </div> <!-- end col -->

            <div class="col-xl-6">
            
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Auto close behavior</h5>
                    </div>
                    <div class="card-body">
                        <p>You can use the <code>autoClose</code> option to change this behavior of the dropdown.</p>
                        <div class="d-flex align flex-wrap gap-2">
                            <div class="btn-group">
                                <button class="btn btn-primary dropdown-toggle" type="button" id="defaultDropdown" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false">
                                    Default dropdown
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="defaultDropdown">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Menu item</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Menu item</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Menu item</a></li>
                                </ul>
                            </div>
                            <div class="btn-group">
                                <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuClickableOutside" data-bs-toggle="dropdown" data-bs-auto-close="inside" aria-expanded="false">
                                    Clickable outside
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuClickableOutside">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Menu item</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Menu item</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Menu item</a></li>
                                </ul>
                            </div>
                            <div class="btn-group">
                                <button class="btn btn-info dropdown-toggle" type="button" id="dropdownMenuClickableInside" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                    Clickable inside
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuClickableInside">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Menu item</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Menu item</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Menu item</a></li>
                                </ul>
                            </div>
                            <div class="btn-group">
                                <button class="btn btn-warning dropdown-toggle" type="button" id="dropdownMenuClickableInsise" data-bs-toggle="dropdown" data-bs-auto-close="false" aria-expanded="false">
                                    Manual close
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuClickableInsise">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Menu item</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Menu item</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Menu item</a></li>
                                </ul>
                            </div>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card-->
            </div> <!-- end col -->

            <div class="col-xl-6">
            
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Alignment Options</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align flex-wrap gap-2">
                            <div class="btn-group">
                                <button class="btn btn-primary dropdown-toggle mb-0" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                    Dropdown
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Menu item</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Menu item</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Menu item</a></li>
                                </ul>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-secondary dropdown-toggle mb-0" data-bs-toggle="dropdown" aria-expanded="false">
                                    Right-aligned menu
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Menu item</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Menu item</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Menu item</a></li>
                                </ul>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-info dropdown-toggle mb-0" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                                    Left-aligned, right-aligned lg
                                </button>
                                <ul class="dropdown-menu dropdown-menu-lg-end">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Menu item</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Menu item</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Menu item</a></li>
                                </ul>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-warning dropdown-toggle mb-0" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                                    Right-aligned, left-aligned lg
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-lg-start">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Menu item</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Menu item</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Menu item</a></li>
                                </ul>
                            </div>
                            <div class="btn-group dropstart">
                                <button type="button" class="btn btn-success dropdown-toggle mb-0" data-bs-toggle="dropdown" aria-expanded="false">
                                    Dropstart
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Menu item</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Menu item</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Menu item</a></li>
                                </ul>
                            </div>
                            <div class="btn-group dropend">
                                <button type="button" class="btn btn-danger dropdown-toggle mb-0" data-bs-toggle="dropdown" aria-expanded="false">
                                    Dropend
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Menu item</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Menu item</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Menu item</a></li>
                                </ul>
                            </div>
                            <div class="btn-group dropup">
                                <button type="button" class="btn btn-info dropdown-toggle mb-0" data-bs-toggle="dropdown" aria-expanded="false">
                                    Dropup
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Menu item</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Menu item</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Menu item</a></li>
                                </ul>
                            </div>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card-->
            </div> <!-- end col -->

            <div class="col-xl-6">
            
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Dropdowns with Forms</h5>
                    </div>
                    <div class="card-body">
                        <p>Put a form within a dropdown menu, or make it into a dropdown menu</p>
                        <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenu2" data-bs-toggle="dropdown" aria-expanded="false">
                            Dropdown
                        </button>
                        <div class="dropdown-menu">
                            <form class="px-4 py-3">
                                <div class="mb-3">
                                    <label for="exampleDropdownFormEmail1" class="form-label">Email address</label>
                                    <input type="email" class="form-control" id="exampleDropdownFormEmail1" placeholder="email@example.com">
                                </div>
                                <div class="mb-3">
                                    <label for="exampleDropdownFormPassword1" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="exampleDropdownFormPassword1" placeholder="Password">
                                </div>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="dropdownCheck">
                                        <label class="form-check-label" for="dropdownCheck">
                                            Remember me
                                        </label>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">Sign in</button>
                            </form>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="javascript:void(0);">New around here? Sign up</a>
                            <a class="dropdown-item" href="javascript:void(0);">Forgot password?</a>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card-->
            </div> <!-- end col -->

            <div class="col-xl-6">
            
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Menu Alignment</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">
                            Add <code>.dropdown-menu-end</code> to a <code>.dropdown-menu</code> to right align the dropdown menu.
                        </p>
                        <div class="btn-group">
                            <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                Right-aligned menu example
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <button class="dropdown-item" type="button">Action</button>
                                </li>
                                <li>
                                    <button class="dropdown-item" type="button">Another action</button>
                                </li>
                                <li>
                                    <button class="dropdown-item" type="button">Something else here</button>
                                </li>
                            </ul>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card-->
            </div> <!-- end col -->

            <div class="col-xl-6">
            
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Responsive alignment end</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">To align <strong>right</strong> the dropdown menu with the given breakpoint or larger, add <code>.dropdown-menu{-sm|-md|-lg|-xl|-xxl}-end</code>.</p>
                        <div class="btn-group">
                            <button type="button" class="btn btn-secondary dropdown-toggle text-wrap" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                                Left-aligned but right aligned when large screen
                            </button>
                            <ul class="dropdown-menu dropdown-menu-lg-end">
                                <li>
                                    <button class="dropdown-item" type="button">Action</button>
                                </li>
                                <li>
                                    <button class="dropdown-item" type="button">Another action</button>
                                </li>
                                <li>
                                    <button class="dropdown-item" type="button">Something else here</button>
                                </li>
                            </ul>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card-->
            </div> <!-- end col -->
            
            <div class="col-xl-6">
            
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Responsive alignment left</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">To align <strong>left</strong> the dropdown menu with the given breakpoint or larger, add <code>.dropdown-menu-end</code> and <code>.dropdown-menu{-sm|-md|-lg|-xl|-xxl}-start</code>.</p>
                        <div class="btn-group">
                            <button type="button" class="btn btn-info dropdown-toggle text-wrap" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                                Left-aligned but right aligned when large screen
                            </button>
                            <ul class="dropdown-menu dropdown-menu-lg-start">
                                <li>
                                    <button class="dropdown-item" type="button">Action</button>
                                </li>
                                <li>
                                    <button class="dropdown-item" type="button">Another action</button>
                                </li>
                                <li>
                                    <button class="dropdown-item" type="button">Something else here</button>
                                </li>
                            </ul>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card-->
            </div> <!-- end col -->

        </div>
        <!-- end row -->
    
        <!-- start row -->
        <div class="row">
        
            <div class="col-xl-6">

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Custom Dropdown Arrow</h5>
                    </div>
                    <div class="card-body">

                        <p class="text-muted">
                            Any single <code>.btn</code> can be turned into a dropdown toggle with some markup changes. Here’s  how you can put them to work with either <code>&lt;button&gt;</code> elements:
                        </p>

                        <div class="row">
                            <div class="col-auto">
                                <div class="dropdown">
                                    <button class="btn btn-primary dropdown-toggle drop-arrow-none" type="button" id="dropdownWithoutArrow" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        Without Arrow
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownWithoutArrow">
                                        <li><a class="dropdown-item" href="#">Action</a></li>
                                        <li><a class="dropdown-item" href="#">Another action</a></li>
                                        <li><a class="dropdown-item" href="#">Something else here</a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="dropdown">
                                    <button class="btn btn-outline-info dropdown-toggle drop-arrow-none" type="button" id="dropdownMenuIcon" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        Tabler Icon <i class="ti ti-chevron-down align-middle ms-1"></i>
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuIcon">
                                        <li><a class="dropdown-item" href="#">Action</a></li>
                                        <li><a class="dropdown-item" href="#">Another action</a></li>
                                        <li><a class="dropdown-item" href="#">Something else here</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                    </div> <!-- end card-body -->
                </div> <!-- end card-->
            </div>
    
            <div class="col-xl-6">

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Dropup Variation</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Trigger dropdown menus above elements by adding <code>.dropup</code> to the parent element.</p>

                        <!-- Default dropup button -->
                        <div class="btn-group dropup">
                            <button type="button" class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Dropup</button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#">Action</a></li>
                                <li><a class="dropdown-item" href="#">Another action</a></li>
                                <li><a class="dropdown-item" href="#">Something else here</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#">Separated link</a></li>
                            </ul>
                        </div>

                        <!-- Split dropup button -->
                        <div class="btn-group dropup">
                            <button type="button" class="btn btn-light">
                                Split dropup
                            </button>
                            <button type="button" class="btn btn-light dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="visually-hidden">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#">Action</a></li>
                                <li><a class="dropdown-item" href="#">Another action</a></li>
                                <li><a class="dropdown-item" href="#">Something else here</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#">Separated link</a></li>
                            </ul>
                        </div>

                    </div> <!-- end card-body -->
                </div> <!-- end card-->
            </div>
        
            <div class="col-xl-6">

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Dropstart Variation</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Trigger dropdown menus at the right of the elements by adding <code>.dropleft</code> to the parent element.</p>

                        <!-- Default dropstart button -->
                        <div class="btn-group dropstart ">
                            <button type="button" class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Dropstart
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#">Action</a></li>
                                <li><a class="dropdown-item" href="#">Another action</a></li>
                                <li><a class="dropdown-item" href="#">Something else here</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#">Separated link</a></li>
                            </ul>
                        </div>

                        <!-- Split dropstart button -->
                        <div class="btn-group">
                            <div class="btn-group dropstart" role="group">
                                <button type="button" class="btn btn-secondary dropdown-toggle dropdown-split dropdown-toggle-split" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <span class="visually-hidden">Toggle Dropstart</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#">Action</a></li>
                                    <li><a class="dropdown-item" href="#">Another action</a></li>
                                    <li><a class="dropdown-item" href="#">Something else here</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="#">Separated link</a></li>
                                </ul>
                            </div>
                            <button type="button" class="btn btn-secondary">
                                Split dropstart
                            </button>
                        </div>
                    </div> <!-- end card-body -->
                </div> <!-- end card-->
            </div>

            <div class="col-xl-6">

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Headers</h5>
                    </div>

                    <div class="card-body">
                        <p class="text-muted">Add a header to label sections of actions in any dropdown menu.
                        </p>
                        <!-- Header Item -->
                        <div class="btn-group">
                            <button type="button" class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Header
                            </button>
                            <div class="dropdown-menu">
                                <h6 class="dropdown-header">Dropdown header</h6>
                                <a class="dropdown-item" href="#">Action</a>
                                <a class="dropdown-item" href="#">Another action</a>
                            </div>
                        </div>
                    </div> <!-- end card-body -->
                </div> <!-- end card -->
                
            </div> <!-- end col -->
        
            <div class="col-xl-6">
            
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Text</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Place any freeform text within a dropdown menu with text and use spacing utilities. Note that you’ll likely need additional sizing styles to constrain the menu width.</p>

                        <!-- Text Example -->
                        <div class="btn-group">
                            <button type="button" class="btn btn-primary   dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Text Dropdown
                            </button>
                            <div class="dropdown-menu p-3 text-muted" style="max-width: 200px;">
                                <p>
                                    Some example text that's free-flowing within the dropdown menu.
                                </p>
                                <p class="mb-0">
                                    And this is more example text.
                                </p>
                            </div>
                        </div>

                    </div> <!-- end card-body -->
                </div> <!-- end card-->
            </div> <!-- end col -->
            
        </div>
        <!-- end row -->
    </div>
</div>
@endsection
