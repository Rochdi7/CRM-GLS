<?php $page = 'ui-popovers'; ?>
@extends('layout.mainlayout')
@section('content')
  	<!-- Pae Wrapper -->
      <div class="page-wrapper">
        <div class="content">
            @component('components.breadcrumb')
        @slot('title')
     Popovers
        @endslot
    @endcomponent	
        <!-- start row -->
        <div class="row">

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Simple Popover</h5>
                    </div>
                    <div class="card-body">
                        <p>
                            Popover is a component which displays a box with a content after a click on an element - similar to the tooltip but can contain more content.
                        </p>
                        <button type="button" class="btn btn-danger" data-bs-toggle="popover" title="Popover title" data-bs-content="And here's some amazing content. It's very engaging. Right?">Click to toggle popover
                        </button>

                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Dismiss on Next Click</h5>
                    </div>
                    <div class="card-body">
                        <p>Use the <code>focus</code> trigger to dismiss popovers on the user’s next click of a different element than the toggle element.</p>

                        <button type="button" tabindex="0" class="btn btn-success" data-bs-toggle="popover" data-bs-trigger="focus" data-bs-content="And here's some amazing content. It's very engaging. Right?" title="Dismissible popover">
                            Dismissible popover
                        </button>

                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

        </div>
        <!-- end row -->

        <!-- start row -->
        <div class="row">

            <div class="col-xl-6">
                <div class="card card-h-100">
                    <div class="card-header">
                        <h5 class="card-title">Hover</h5>
                    </div>
                    <div class="card-body">
                        <p>Use the attribute <code>data-bs-trigger="hover"</code> to show the popover on hovering the element.</p>
                        <button type="button" tabindex="0" class="btn btn-dark" data-bs-toggle="popover" data-bs-trigger="hover" data-bs-content="And here's some amazing content. It's very engaging. Right?" title="Ohh Wow !">
                            Please Hover Me
                        </button>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Four Directions</h5>
                    </div>
                    <div class="card-body">
                        <p>Four options are available: top, right, bottom, and left aligned.</p>

                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-primary" data-bs-toggle="popover" data-bs-placement="top" data-bs-content="Vivamus sagittis lacus vel augue laoreet rutrum faucibus.">
                                Popover on top
                            </button>

                            <button type="button" class="btn btn-primary" data-bs-toggle="popover" data-bs-placement="bottom" data-bs-content="Vivamus sagittis lacus vel augue laoreet rutrum faucibus.">
                                Popover on bottom
                            </button>

                            <button type="button" class="btn btn-primary" data-bs-toggle="popover" data-bs-placement="right" data-bs-content="Vivamus sagittis lacus vel augue laoreet rutrum faucibus.">
                                Popover on right
                            </button>

                            <button type="button" class="btn btn-primary" data-bs-toggle="popover" data-bs-placement="left" data-bs-content="Vivamus sagittis lacus vel augue laoreet rutrum faucibus." title="Popover title">
                                Popover on left
                            </button>
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
                        <h5 class="card-title">Custom Popovers</h5>
                    </div>
                    <div class="card-body">
                        <p>You can customize the appearance of popovers using CSS variables. We set a custom class with <code>data-bs-custom-class="popover-primary"</code> to scope our custom appearance and use it to override some of the local CSS variables.
                        </p>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-primary" data-bs-toggle="popover" data-bs-placement="right" data-bs-custom-class="popover-primary" data-bs-title="Primary popover" data-bs-content="This popover is themed via CSS variables.">
                                Primary popover
                            </button>

                            <button type="button" class="btn btn-success" data-bs-toggle="popover" data-bs-placement="right" data-bs-custom-class="popover-success" data-bs-title="Success popover" data-bs-content="This popover is themed via CSS variables.">
                                Success popover
                            </button>

                            <button type="button" class="btn btn-danger" data-bs-toggle="popover" data-bs-placement="right" data-bs-custom-class="popover-danger" data-bs-title="Danger popover" data-bs-content="This popover is themed via CSS variables.">
                                Danger popover
                            </button>

                            <button type="button" class="btn btn-info" data-bs-toggle="popover" data-bs-placement="right" data-bs-custom-class="popover-info" data-bs-title="Info popover" data-bs-content="This popover is themed via CSS variables.">
                                Info popover
                            </button>

                            <button type="button" class="btn btn-dark" data-bs-toggle="popover" data-bs-placement="right" data-bs-custom-class="popover-dark" data-bs-title="Dark popover" data-bs-content="This popover is themed via CSS variables.">
                                Dark popover
                            </button>

                            <button type="button" class="btn btn-secondary" data-bs-toggle="popover" data-bs-placement="right" data-bs-custom-class="popover-secondary" data-bs-title="Secondary popover" data-bs-content="This popover is themed via CSS variables.">
                                Secondary popover
                            </button>
                        </div>

                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xl-6">
                <div class="card card-h-100">
                    <div class="card-header">
                        <h5 class="card-title">Disabled Elements</h5>
                    </div>
                    <div class="card-body">
                        <p>Elements with the <code>disabled</code> attribute aren’t interactive, meaning users cannot hover or click them to trigger a popover (or tooltip). As a workaround, you’ll want to trigger the popover from a wrapper <code>&lt;div&gt;</code>                                    or <code>&lt;span&gt;</code> and override the
                            <code>pointer-events</code> on the disabled element.
                        </p>
                        <span class="d-inline-block">
                            <button class="btn btn-primary" style="pointer-events: none;" type="button" disabled>Disabled button</button>
                        </span>

                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->
            
        </div>
        <!-- end row -->
        </div>
    </div>
    <!-- /Page Wrapper -->  
@endsection
