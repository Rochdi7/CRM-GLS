<?php $page = 'ui-alerts'; ?>
@extends('layout.mainlayout')
@section('content')
<div class="page-wrapper">
    <div class="content">
        @component('components.breadcrumb')
        @slot('title')
       Alerts
        @endslot
    @endcomponent
        <!-- start row -->
        <div class="row">
            
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Default Alert</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">
                            For proper styling, use one of the eight
                            <strong>required</strong> contextual classes (e.g.,
                            <code>.alert-success</code>). For background color use class
                            <code>.bg-* </code>, <code>.text-white </code>
                        </p>
                        
                        <div class="alert alert-primary" role="alert">
                            <strong>Primary - </strong> A simple primary alert—check it out!
                        </div>
                        <div class="alert alert-secondary" role="alert">
                            <strong>Secondary - </strong> A simple secondary alert—check it out!
                        </div>
                        <div class="alert alert-success" role="alert">
                            <strong>Success - </strong> A simple success alert—check it out!
                        </div>
                        <div class="alert alert-danger" role="alert">
                            <strong>Danger - </strong> A simple danger alert—check it out!
                        </div>
                        <div class="alert alert-warning" role="alert">
                            <strong>Warning - </strong> A simple warning alert—check it out!
                        </div>
                        <div class="alert alert-info" role="alert">
                            <strong>Info - </strong> A simple info alert—check it out!
                        </div>
                        <div class="alert alert-light" role="alert">
                            <strong>Light - </strong> A simple light alert—check it out!
                        </div>
                        <div class="alert alert-dark mb-0" role="alert">
                            <strong>Dark - </strong> A simple dark alert—check it out!
                        </div>
                        
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Dismissing Alert</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">
                            Add a dismiss button and the <code>.alert-dismissible</code> class, which adds
                            extra padding to the right of the alert
                            and positions the <code>.btn-close</code> button.
                        </p>
                        
                        <div class="alert alert-primary text-bg-primary alert-dismissible" role="alert">
                            <strong>Primary - </strong> A simple primary alert — check
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        
                        <div class="alert alert-secondary text-bg-secondary alert-dismissible" role="alert">
                            <strong>Secondary - </strong> A simple secondary alert — check
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>

                        <div class="alert alert-success text-bg-success alert-dismissible" role="alert">
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                            <strong>Success - </strong> A simple success alert — check
                        </div>

                        <div class="alert alert-danger text-bg-danger alert-dismissible" role="alert">
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                            <strong>Error - </strong> A simple danger alert — check it
                        </div>

                        <div class="alert alert-warning text-bg-warning alert-dismissible" role="alert">
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                            <strong>Warning - </strong> A simple warning alert—check
                        </div>

                        <div class="alert alert-info text-bg-info alert-dismissible" role="alert">
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                            <strong>Info - </strong> A simple info alert—check it out!
                        </div>

                        <div class="alert alert-light text-bg-light alert-dismissible" role="alert">
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            <strong>Light - </strong> A simple light alert—check it
                        </div>

                        <div class="alert alert-dark text-bg-dark alert-dismissible mb-0" role="alert">
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                            <strong>Dark - </strong> A simple dark alert—check it out!
                        </div>
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
                        <h5 class="card-title">Link Color</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">
                            Use the <code>.alert-link</code> utility class to quickly provide matching colored links within any alert.
                        </p>
                        <div class="alert alert-primary" role="alert">
                            A simple primary alert with <a href="#" class="alert-link">an example link</a>. Give it a click if you like.
                        </div>
                        <div class="alert alert-secondary" role="alert">
                            A simple secondary alert with <a href="#" class="alert-link">an example link</a>. Give it a click if you like.
                        </div>
                        <div class="alert alert-success" role="alert">
                            A simple success alert with <a href="#" class="alert-link">an example link</a>. Give it a click if you like.
                        </div>
                        <div class="alert alert-danger" role="alert">
                            A simple danger alert with <a href="#" class="alert-link">an example link</a>. Give it a click if you like.
                        </div>
                        <div class="alert alert-warning" role="alert">
                            A simple warning alert with <a href="#" class="alert-link">an example link</a>. Give it a click if you like.
                        </div>
                        <div class="alert alert-info" role="alert">
                            A simple info alert with <a href="#" class="alert-link">an example link</a>. Give it a click if you like.
                        </div>
                        <div class="alert alert-light" role="alert">
                            A simple light alert with <a href="#" class="alert-link">an example link</a>. Give it a click if you like.
                        </div>
                        <div class="alert alert-dark mb-0" role="alert">
                            A simple dark alert with <a href="#" class="alert-link">an example link</a>. Give it a click if you like.
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Alerts with Border</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">
                            Display alert with transparent background and with contextual text color. Use classes <code>.bg-white</code>, and <code>.text-*</code>. E.g. <code>bg-white text-primary</code>.
                        </p>

                        <div class="alert alert-primary alert-dismissible border border-primary" role="alert">
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            <strong>Primary - </strong> A simple primary alert — check it out!
                        </div>
                        
                        <div class="alert alert-secondary alert-dismissible border border-secondary" role="alert">
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            <strong>Secondary - </strong> A simple secondary alert — check it out!
                        </div>
                        
                        <div class="alert alert-success alert-dismissible border border-success" role="alert">
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            <strong>Success - </strong> A simple success alert — check it out!
                        </div>
                        
                        <div class="alert alert-danger alert-dismissible border border-danger" role="alert">
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            <strong>Error - </strong> A simple danger alert — check it out!
                        </div>
                        
                        <div class="alert alert-warning alert-dismissible border border-warning" role="alert">
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            <strong>Warning - </strong> A simple warning alert—check it out!
                        </div>
                        
                        <div class="alert alert-info alert-dismissible border border-info" role="alert">
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            <strong>Info - </strong> A simple info alert—check it out!
                        </div>
                        
                        <div class="alert alert-light alert-dismissible border border-light" role="alert">
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            <strong>Light - </strong> A simple light alert—check it out!
                        </div>
                        
                        <div class="alert alert-dark alert-dismissible border border-dark mb-0" role="alert">
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            <strong>Dark - </strong> A simple dark alert—check it out!
                        </div>

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
                        <h5 class="card-title">Additional Content</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Alerts can also contain additional HTML elements like headings, paragraphs and dividers.</p>
                        <div class="alert alert-success p-3" role="alert">
                            <h4 class="alert-heading">Well done!</h4>
                            <p>Aww yeah, you successfully read this important alert message. This example text is going to run a bit longer so that you can see how spacing within an alert works with this kind of content.</p>
                            <hr class="border-success border-opacity-25">
                            <p class="mb-0">Whenever you need to, be sure to use margin utilities to keep things nice and tidy.</p>
                        </div>
                        <div class="alert alert-secondary p-3 d-flex" role="alert">
                            <i class="ti ti-mood-smile fs-1 me-2"></i>
                            <div>
                                <h4 class="alert-heading">Well done!</h4>
                                <p>Aww yeah, you successfully read this important alert message. This example text is going to run a bit longer so that you can see how spacing within an alert works with this kind of content.</p>
                                <hr class="border-secondary border-opacity-25">
                                <p class="mb-0">Whenever you need to, be sure to use margin utilities to keep things nice and tidy.</p>
                            </div>
                        </div>
                        <div class="alert alert-primary d-flex p-3 mb-0" role="alert">
                            <i class="ti ti-mood-smile fs-1 me-2"></i>
                            <div>
                                <h4 class="alert-heading">Thank you!</h4>
                                <p>Aww yeah, you successfully read this important alert message. This example text is going to run a bit longer so that you can see how spacing within an alert works with this kind of content..</p>
                                <button type="button" class="btn btn-primary btn-sm">Close</button>
                            </div>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xl-6">

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Live Alert</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Click the button below to show an alert (hidden with inline styles to start), then dismiss (and destroy) it with the built-in close button.</p>
                        <div id="liveAlertPlaceholder"></div>
                        <button type="button" class="btn btn-primary" id="liveAlertBtn">Show live alert</button>
                    </div> <!-- end card body -->
                </div> <!-- end card -->

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Alert with Icons</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-primary d-flex align-items-center" role="alert">
                            <i class="ti ti-mood-smile f-12 me-2"></i>
                            <div>
                                An example alert with an icon
                            </div>
                        </div>
                        <div class="alert alert-secondary d-flex align-items-center" role="alert">
                            <i class="ti ti-triangles f-12 me-2"></i>
                            <div>
                                An example alert with an icon
                            </div>
                        </div>
                        <div class="alert alert-success d-flex align-items-center" role="alert">
                            <i class="ti ti-checks f-12 me-2"></i>
                            <div>
                                An example alert with an icon
                            </div>
                        </div>
                        <div class="alert alert-danger d-flex align-items-center" role="alert">
                            <i class="ti ti-trash-x f-12 me-2"></i>
                            <div>
                                An example alert with an icon
                            </div>
                        </div>
                        <div class="alert alert-warning d-flex align-items-center" role="alert">
                            <i class="ti ti-info-triangle f-12 me-2"></i>
                            <div>
                                An example alert with an icon
                            </div>
                        </div>
                        <div class="alert alert-info d-flex align-items-center" role="alert">
                            <i class="ti ti-info-circle  f-12 me-2"></i>
                            <div>
                                An example alert with an icon
                            </div>
                        </div>
                        <div class="alert alert-light d-flex align-items-center" role="alert">
                            <i class="ti ti-triangles f-12 me-2"></i>
                            <div>
                                An example alert with an icon
                            </div>
                        </div>
                        <div class="alert alert-dark d-flex align-items-center mb-0" role="alert">
                            <i class="ti ti-refresh f-12 me-2"></i>
                            <div>
                                An example alert with an icon
                            </div>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card -->

            </div> <!-- end col -->
        </div> 
        <!-- end row -->
    </div>
</div>
@endsection
