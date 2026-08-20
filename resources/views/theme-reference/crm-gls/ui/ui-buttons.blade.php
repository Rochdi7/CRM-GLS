<?php $page = 'ui-buttons'; ?>
@extends('layout.mainlayout')
@section('content')
<div class="page-wrapper">
    <div class="content">
        @component('components.breadcrumb')
        @slot('title')
        Buttons
        @endslot
    @endcomponent	
        <!-- start row -->
        <div class="row">

            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Default Buttons</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-xl mb-3">Use the <span class="text-secondary">btn</span> class to show the default button style.</p>
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <button type="button" class="btn btn-primary">Primary</button>
                            <button type="button" class="btn btn-secondary">Secondary</button>
                            <button type="button" class="btn btn-success">Success</button>
                            <button type="button" class="btn btn-danger">Danger</button>
                            <button type="button" class="btn btn-warning">Warning</button>
                            <button type="button" class="btn btn-info">Info</button>
                            <button type="button" class="btn btn-light">Light</button>
                            <button type="button" class="btn btn-dark">Dark</button>
                            <button type="button" class="btn btn-outline-white">White</button>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card-->
            </div> <!-- end col -->

            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Outline Buttons</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-xl mb-3">Use the <span class="text-secondary">btn-outline-</span> class with the below-mentioned variation to create a button with the outline.</p>
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <button type="button" class="btn btn-outline-primary">Primary</button>
                            <button type="button" class="btn btn-outline-secondary">Secondary</button>
                            <button type="button" class="btn btn-outline-success">Success</button>
                            <button type="button" class="btn btn-outline-danger">Danger</button>
                            <button type="button" class="btn btn-outline-warning">Warning</button>
                            <button type="button" class="btn btn-outline-info">Info</button>
                            <button type="button" class="btn btn-outline-light">Light</button>
                            <button type="button" class="btn btn-outline-dark">Dark</button>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card-->
            </div> <!-- end col -->

            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Rounded Buttons</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-xl mb-3">Use the <span class="text-secondary">rounded-pill</span> class to make a rounded button.</p>
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <button type="button" class="btn btn-primary rounded-pill">Primary</button>
                            <button type="button" class="btn btn-secondary rounded-pill">Secondary</button>
                            <button type="button" class="btn btn-success rounded-pill">Success</button>
                            <button type="button" class="btn btn-danger rounded-pill">Danger</button>
                            <button type="button" class="btn btn-warning rounded-pill">Warning</button>
                            <button type="button" class="btn btn-info rounded-pill">Info</button>
                            <button type="button" class="btn btn-light rounded-pill">Light</button>
                            <button type="button" class="btn btn-dark rounded-pill">Dark</button>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card-->
            </div> <!-- end col -->

            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Soft Buttons</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-xl mb-3">Use the <span class="text-secondary">btn-soft-</span> class with the below-mentioned variation to create a button with the soft background.</p>
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <button type="button" class="btn btn-soft-primary">Primary</button>
                            <button type="button" class="btn btn-soft-secondary">Secondary</button>
                            <button type="button" class="btn btn-soft-success">Success</button>
                            <button type="button" class="btn btn-soft-danger">Danger</button>
                            <button type="button" class="btn btn-soft-warning">Warning</button>
                            <button type="button" class="btn btn-soft-info">Info</button>
                            <button type="button" class="btn btn-soft-light">Light</button>
                            <button type="button" class="btn btn-soft-dark">Dark</button>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card-->
            </div> <!-- end col -->

            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header border-bottom d-flex align-items-center">
                        <h4 class="header-title">Gradient Buttons</h4>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Use the button classes on an <code>&lt;a&gt;</code>, <code>&lt;button&gt;</code>, or <code>&lt;input&gt;</code> element.</p>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-primary bg-gradient">Primary</button>
                            <button type="button" class="btn btn-secondary bg-gradient">Secondary</button>
                            <button type="button" class="btn btn-success bg-gradient">Success</button>
                            <button type="button" class="btn btn-danger bg-gradient">Danger</button>
                            <button type="button" class="btn btn-warning bg-gradient">Warning</button>
                            <button type="button" class="btn btn-info bg-gradient">Info</button>
                            <button type="button" class="btn btn-light bg-gradient">Light</button>
                            <button type="button" class="btn btn-dark bg-gradient">Dark</button>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card-->
            </div> <!-- end col -->
            
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Gradient Buttons 2</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-xl mb-3">Use the <span class="text-secondary">bg-*-gradient</span> class to create a gradient button.</p>
                        <div class="btn-list">
                            <button type="button" class="btn  bg-primary-gradient btn-primary btn-effect">Primary</button>
                            <button type="button" class="btn  bg-secondary-gradient btn-secondary">Secondary</button>
                            <button type="button" class="btn  bg-success-gradient btn-success">Success</button>
                            <button type="button" class="btn  bg-danger-gradient btn-danger">Danger</button>
                            <button type="button" class="btn  bg-warning-gradient btn-warning">Warning</button>
                            <button type="button" class="btn  bg-info-gradient btn-info">Info</button>
                            <button type="button" class="btn  bg-light-gradient btn-light">Light</button>
                            <button type="button" class="btn  bg-dark-gradient btn-dark">Dark</button>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card-->
            </div> <!-- end col -->

            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Animation Button</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-xl mb-3">Use the <span class="text-secondary">btn-animation</span> class to create a animation effect.</p>
                        <div class="btn-list">
                            <button type="button" class="btn btn-primary btn-animation " data-text="Primary"><span>Primary</span></button>
                            <button type="button" class="btn btn-secondary btn-animation" data-text="Secondary"><span>Secondary</span></button>
                            <button type="button" class="btn btn-success btn-animation" data-text="Success"><span>Success</span></button>
                            <button type="button" class="btn btn-danger btn-animation" data-text="Danger"><span>Danger</span></button>
                            <button type="button" class="btn btn-warning btn-animation" data-text="Warning"><span>Warning</span></button>
                            <button type="button" class="btn btn-info btn-animation" data-text="Info"><span>Info</span></button>
                            <button type="button" class="btn btn-dark btn-animation" data-text="Dark"><span>Dark</span></button>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card-->
            </div> <!-- end col -->

            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Buttons with Label</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-xl mb-3">Use the <span class="text-secondary">btn-label</span> class to create a button with the label.</p>
                        <div class="row row-gap-3">
                            <div class="col-lg-4">
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-primary btn-label"><i class="ti ti-mood-smile label-icon align-middle fs-16 me-2"></i> Primary</button>
                                    <button type="button" class="btn btn-warning btn-label"><i class="ti ti-alert-triangle label-icon align-middle fs-16 me-2 "></i> Warning</button>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-primary btn-label rounded-pill"><i class="ti ti-mood-smile label-icon align-middle rounded-pill fs-16 me-2"></i> Primary</button>
                                    <button type="button" class="btn btn-success btn-label rounded-pill"><i class="ti ti-checks label-icon align-middle rounded-pill fs-16 me-2"></i> Success</button>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-primary btn-label right"><i class="ti ti-mood-smile label-icon align-middle fs-16 me-2"></i> Primary</button>
                                    <button type="button" class="btn btn-success btn-label right rounded-pill"><i class="ti ti-checks label-icon align-middle rounded-pill fs-16 me-2"></i> Success</button>
                                </div>
                            </div>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card-->
            </div> <!-- end col -->

            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Buttons Sizes</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-xl mb-3">Use <span class="text-secondary">btn-lg</span> class to create a large size button and <span class="text-secondary">btn-sm</span> class to create a small size button.</p>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <button type="button" class="btn btn-primary btn-lg">Large</button>
                            <button type="button" class="btn btn-success">Normal</button>
                            <button type="button" class="btn btn-danger btn-sm">Small</button>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card-->
            </div> <!-- end col -->

            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Buttons Toggle States</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-xl mb-3">Use the <span class="text-secondary">data-bs-toggle="button"</span> to toggle a button’s active state.</p>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <button type="button" class="btn" data-bs-toggle="button">Toggle button</button>
                            <button type="button" class="btn active" data-bs-toggle="button" aria-pressed="true">Active toggle button</button>
                            <button type="button" class="btn" disabled data-bs-toggle="button">Disabled toggle button</button>
                            <button type="button" class="btn btn-primary" data-bs-toggle="button">Toggle button</button>
                            <button type="button" class="btn btn-primary active" data-bs-toggle="button" aria-pressed="true">Active toggle button</button>
                            <button type="button" class="btn btn-primary" disabled data-bs-toggle="button">Disabled toggle button</button>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card-->
            </div> <!-- end col -->
            
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Button Tags</h5>
                    </div>

                    <div class="card-body">
                        <p class="text-muted">The <code>.btn</code> classes are designed to be used with the <code>&lt;button&gt;</code> element. However, you can also use these classes on <code>&lt;a&gt;</code> or <code>&lt;input&gt;</code> elements (though some browsers may apply a slightly different rendering).</p>

                        <div class="d-flex flex-wrap gap-2">
                            <a class="btn btn-primary" href="#" role="button">Link</a>
                            <button class="btn btn-primary" type="submit">Button</button>
                            <input class="btn btn-primary" type="button" value="Input">
                            <input class="btn btn-primary" type="submit" value="Submit">
                            <input class="btn btn-primary" type="reset" value="Reset">
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card-->
            </div> <!-- end col -->

            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Icon Buttons</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-xl mb-3">Use <span class="text-secondary">btn-icon</span> class to wrap icon in button</p>
                        <div class="d-flex align-items-center flex-wrap gap-4">
                            <div class="d-flex align-items-center flex-wrap gap-2">
                                <button class="btn btn-icon btn-primary">
                                    <i class="ti ti-bell"></i>
                                </button>
                                <button class="btn btn-icon btn-secondary">
                                    <i class="ti ti-mail"></i>
                                </button>
                                <button class="btn btn-icon btn-info">
                                    <i class="ti ti-edit"></i>
                                </button>
                                <button class="btn btn-icon btn-danger">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </div>
                            <div class="d-flex align-items-center flex-wrap gap-2">
                                <button class="btn btn-icon btn-outline-primary">
                                    <i class="ti ti-bell"></i>
                                </button>
                                <button class="btn btn-icon btn-outline-secondary">
                                    <i class="ti ti-mail"></i>
                                </button>
                                <button class="btn btn-icon btn-outline-info">
                                    <i class="ti ti-edit"></i>
                                </button>
                                <button class="btn btn-icon btn-outline-danger">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card-->
            </div> <!-- end col -->

            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Block Buttons</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-xl mb-3">Use the <span class="text-secondary">d-grid</span> class to create a full-width block button.</p>
                        <div class="btn-list d-block">
                            <div class="d-grid gap-2">
                                <button class="btn btn-sm btn-primary" type="button">Button</button>
                                <button class="btn btn-warning" type="button">Button</button>
                            </div>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card-->
            </div> <!-- end col -->

        </div>
        <!-- end row -->
    </div>
</div>
@endsection
