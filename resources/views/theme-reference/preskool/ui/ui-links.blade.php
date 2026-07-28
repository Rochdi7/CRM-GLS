<?php $page = 'ui-links'; ?>
@extends('layout.mainlayout')
@section('content')
<div class="page-wrapper">
    <div class="content">
        @component('components.breadcrumb')
        @slot('title')
        Links
        @endslot
    @endcomponent	
        <!-- start row -->
        <div class="row">

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Colored Links</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">You can use the <code>.link-*</code> classes to colorize links. Unlike the <code>.text-*</code> classes, these classes have a <code>:hover</code> and <code>:focus</code> state. Some of the link styles use a relatively light foreground color, and should only be used on a dark background in order to have sufficient contrast.</p>
                        <p><a href="#" class="link-primary">Primary link</a></p>
                        <p><a href="#" class="link-secondary">Secondary link</a></p>
                        <p><a href="#" class="link-success">Success link</a></p>
                        <p><a href="#" class="link-danger">Danger link</a></p>
                        <p><a href="#" class="link-warning">Warning link</a></p>
                        <p><a href="#" class="link-info">Info link</a></p>
                        <p><a href="#" class="link-light">Light link</a></p>
                        <p><a href="#" class="link-dark">Dark link</a></p>
                        <p class="mb-0"><a href="#" class="link-body-emphasis">Emphasis link</a></p>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xl-6">
                <div class="card card-h-100">
                    <div class="card-header">
                        <h5 class="card-title">Link Utilities</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Colored link helpers have been updated to pair with our link utilities. Use the new utilities to modify the link opacity, underline opacity, and underline offset.</p>
                        <p><a href="#" class="link-primary text-decoration-underline link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover">Primary link</a></p>
                        <p><a href="#" class="link-secondary text-decoration-underline link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover">Secondary link</a></p>
                        <p><a href="#" class="link-success text-decoration-underline link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover">Success link</a></p>
                        <p><a href="#" class="link-danger text-decoration-underline link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover">Danger link</a></p>
                        <p><a href="#" class="link-warning text-decoration-underline link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover">Warning link</a></p>
                        <p><a href="#" class="link-info text-decoration-underline link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover">Info link</a></p>
                        <p><a href="#" class="link-light text-decoration-underline link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover">Light link</a></p>
                        <p><a href="#" class="link-dark text-decoration-underline link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover">Dark link</a></p>
                        <p class="mb-0"><a href="#" class="link-body-emphasis text-decoration-underline link-offset-2 link-underline-opacity-25 link-underline-opacity-75-hover">Emphasis link</a></p>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

        </div>
        <!-- end row -->

        <!-- start row -->
        <div class="row">
            
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Link Opacity</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Change the alpha opacity of the link <code>rgba()</code> color value with utilities. Please be aware that changes to a color’s opacity can lead to links with <em>insufficient</em> contrast.</p>
                        <p><a class="link-opacity-10" href="#">Link opacity 10</a></p>
                        <p><a class="link-opacity-25" href="#">Link opacity 25</a></p>
                        <p><a class="link-opacity-50" href="#">Link opacity 50</a></p>
                        <p><a class="link-opacity-75" href="#">Link opacity 75</a></p>
                        <p class="mb-0"><a class="link-opacity-100" href="#">Link opacity 100</a></p>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xl-6">
                <div class="card card-h-100">
                    <div class="card-header">
                        <h5 class="card-title">Link Hover Opacity</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">You can even change the opacity level on hover.</p>

                        <p><a class="link-opacity-10-hover" href="#">Link hover opacity 10</a></p>
                        <p><a class="link-opacity-25-hover" href="#">Link hover opacity 25</a></p>
                        <p><a class="link-opacity-50-hover" href="#">Link hover opacity 50</a></p>
                        <p><a class="link-opacity-75-hover" href="#">Link hover opacity 75</a></p>
                        <p class="mb-0"><a class="link-opacity-100-hover" href="#">Link hover opacity 100</a></p>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->
        </div>
        <!--end row-->

        <!-- start row -->
        <div class="row">
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Underline Color</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Change the underline’s color independent of the link text color.</p>
                        
                        <p><a href="#" class="text-decoration-underline link-underline-primary">Primary underline</a></p>
                        <p><a href="#" class="text-decoration-underline link-underline-secondary">Secondary underline</a></p>
                        <p><a href="#" class="text-decoration-underline link-underline-success">Success underline</a></p>
                        <p><a href="#" class="text-decoration-underline link-underline-danger">Danger underline</a></p>
                        <p><a href="#" class="text-decoration-underline link-underline-warning">Warning underline</a></p>
                        <p><a href="#" class="text-decoration-underline link-underline-info">Info underline</a></p>
                        <p><a href="#" class="text-decoration-underline link-underline-light">Light underline</a></p>
                        <p class="mb-0"><a href="#" class="text-decoration-underline link-underline-dark">Dark underline</a></p>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->
            
            <div class="col-xl-6">
                <div class="card card-h-100">
                    <div class="card-header">
                        <h5 class="card-title">Underline Opacity</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Change the underline’s opacity. Requires adding <code>.link-underline</code> to first set an <code>rgba()</code> color we use to then modify the alpha opacity.</p>

                        <p><a class="text-decoration-underline link-offset-2 link-underline link-underline-opacity-0" href="#">Underline opacity 0</a></p>
                        <p><a class="text-decoration-underline link-offset-2 link-underline link-underline-opacity-10" href="#">Underline opacity 10</a></p>
                        <p><a class="text-decoration-underline link-offset-2 link-underline link-underline-opacity-25" href="#">Underline opacity 25</a></p>
                        <p><a class="text-decoration-underline link-offset-2 link-underline link-underline-opacity-50" href="#">Underline opacity 50</a></p>
                        <p><a class="text-decoration-underline link-offset-2 link-underline link-underline-opacity-75" href="#">Underline opacity 75</a></p>
                        <p class="mb-0"><a class="text-decoration-underline link-offset-2 link-underline link-underline-opacity-100" href="#">Underline opacity 100</a></p>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->
        </div> 
        <!-- end row -->

        <!-- start row -->
        <div class="row">
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Underline Offset</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Change the underline’s opacity. Requires adding <code>.link-underline</code> to first set an <code>rgba()</code> color we use to then modify the alpha opacity.</p>

                        <p><a href="#">Default link</a></p>
                        <p><a class="text-decoration-underline link-offset-1" href="#">Offset 1 link</a></p>
                        <p><a class="text-decoration-underline link-offset-2" href="#">Offset 2 link</a></p>
                        <p class="mb-0"><a class="text-decoration-underline link-offset-3" href="#">Offset 3 link</a></p>

                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->
            
            <div class="col-xl-6">
                <div class="card card-h-100">
                    <div class="card-header">
                        <h5 class="card-title">Hover Variants</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Just like the <code>.link-opacity-*-hover</code> utilities, <code>.link-offset</code> and <code>.link-underline-opacity</code> utilities include <code>:hover</code> variants by default. Mix and match to create unique link styles.</p>

                        <a class="link-offset-2 link-offset-3-hover text-decoration-underline link-underline link-underline-opacity-0 link-underline-opacity-75-hover" href="#">
                            Underline opacity 0
                        </a>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

        </div> 
        <!--end row -->
    </div>
</div>
@endsection
