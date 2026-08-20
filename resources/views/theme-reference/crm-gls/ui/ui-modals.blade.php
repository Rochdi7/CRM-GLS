<?php $page = 'ui-modal'; ?>
@extends('layout.mainlayout')
@section('content')
<div class="page-wrapper">
    <div class="content">
        @component('components.breadcrumb')
        @slot('title')
      Modals
        @endslot
    @endcomponent	

        <div class="row">

            <!-- Basic Modal -->
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Basic Modal</h5>
                    </div>
                    <div class="card-body">

                        <div id="standard-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="standard-modalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h4 class="modal-title" id="standard-modalLabel">Modal Heading</h4>
                                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                                    </div>
                                    <div class="modal-body">
                                        <h6>Text in a modal</h6>
                                        <p>Duis mollis, est non commodo luctus, nisi erat porttitor ligula.</p>
                                        <hr>
                                        <h6>Overflowing text to show scroll behavior</h6>
                                        <p>Cras mattis consectetur purus sit amet fermentum. Cras justo odio, dapibus ac facilisis in, egestas eget quam. Morbi leo risus, porta ac consectetur ac, vestibulum at eros.</p>
                                        <p>Praesent commodo cursus magna, vel scelerisque nisl consectetur et. Vivamus sagittis lacus vel augue laoreet rutrum faucibus dolor auctor.</p>
                                        <p>Aenean lacinia bibendum nulla sed consectetur. Praesent commodo cursus magna, vel scelerisque nisl consectetur et. Donec sed odio dui. Donec ullamcorper nulla non metus auctor fringilla.</p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light me-2 " data-bs-dismiss="modal">Close</button>
                                        <button type="button" class="btn btn-primary">Save changes</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                                                        
                        <div class="button-list">
                            <!-- Standard  modal -->
                            <button type="button" class="btn btn-primary mt-1" data-bs-toggle="modal" data-bs-target="#standard-modal">Launch Demo Modal</button>
                        </div>
                    </div> 
                </div> 
            </div> 
            <!-- /Basic Modal -->

            <!-- Static Backdrop -->
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Static Backdrop</h5>
                    </div>
                    <div class="card-body card-buttons">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#staticBackdrop">
                            Launch static backdrop modal
                        </button>

                        <!-- Modal -->
                        <div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="staticBackdropLabel">Modal title</h5>
                                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                                    </div>
                                    <div class="modal-body">
                                        Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute,
                                        non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon
                                        tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et.
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light me-2 " data-bs-dismiss="modal">Close</button>
                                        <button type="button" class="btn btn-primary">Understood</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Static Backdrop -->

            <!-- Scrolling Long Content -->
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Scrolling Long Content</h5>
                    </div>
                    <div class="card-body card-buttons">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#scrollable-modal">
                            Scrolling Long Content
                        </button>

                        <!-- Long Content Scroll Modal -->
                        <div class="modal fade" id="scrollable-modal" tabindex="-1" role="dialog"
                            aria-labelledby="scrollableModalTitle" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-scrollable" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="scrollableModalTitle">Modal title</h5>
                                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                                    </div>
                                    <div class="modal-body">
                                        <p>Cras mattis consectetur purus sit amet fermentum. Cras justo odio, dapibus ac facilisis in, egestas
                                            eget quam. Morbi leo risus, porta ac consectetur ac, vestibulum at eros.</p>
                                        <p>Praesent commodo cursus magna, vel scelerisque nisl consectetur et. Vivamus sagittis lacus vel augue
                                            laoreet rutrum faucibus dolor auctor.</p>
                                        <p>Aenean lacinia bibendum nulla sed consectetur. Praesent commodo cursus magna, vel scelerisque nisl
                                            consectetur et. Donec sed odio dui. Donec ullamcorper nulla non metus auctor fringilla.</p>
                                        <p>Cras mattis consectetur purus sit amet fermentum. Cras justo odio, dapibus ac facilisis in, egestas
                                            eget quam. Morbi leo risus, porta ac consectetur ac, vestibulum at eros.</p>
                                        <p>Praesent commodo cursus magna, vel scelerisque nisl consectetur et. Vivamus sagittis lacus vel augue
                                            laoreet rutrum faucibus dolor auctor.</p>
                                        <p>Aenean lacinia bibendum nulla sed consectetur. Praesent commodo cursus magna, vel scelerisque nisl
                                            consectetur et. Donec sed odio dui. Donec ullamcorper nulla non metus auctor fringilla.</p>
                                        <p>Cras mattis consectetur purus sit amet fermentum. Cras justo odio, dapibus ac facilisis in, egestas
                                            eget quam. Morbi leo risus, porta ac consectetur ac, vestibulum at eros.</p>
                                        <p>Praesent commodo cursus magna, vel scelerisque nisl consectetur et. Vivamus sagittis lacus vel augue
                                            laoreet rutrum faucibus dolor auctor.</p>
                                        <p>Aenean lacinia bibendum nulla sed consectetur. Praesent commodo cursus magna, vel scelerisque nisl
                                            consectetur et. Donec sed odio dui. Donec ullamcorper nulla non metus auctor fringilla.</p>
                                        <p>Cras mattis consectetur purus sit amet fermentum. Cras justo odio, dapibus ac facilisis in, egestas
                                            eget quam. Morbi leo risus, porta ac consectetur ac, vestibulum at eros.</p>
                                        <p>Praesent commodo cursus magna, vel scelerisque nisl consectetur et. Vivamus sagittis lacus vel augue
                                            laoreet rutrum faucibus dolor auctor.</p>
                                        <p>Aenean lacinia bibendum nulla sed consectetur. Praesent commodo cursus magna, vel scelerisque nisl
                                            consectetur et. Donec sed odio dui. Donec ullamcorper nulla non metus auctor fringilla.</p>
                                        <p>Cras mattis consectetur purus sit amet fermentum. Cras justo odio, dapibus ac facilisis in, egestas
                                            eget quam. Morbi leo risus, porta ac consectetur ac, vestibulum at eros.</p>
                                        <p>Praesent commodo cursus magna, vel scelerisque nisl consectetur et. Vivamus sagittis lacus vel augue
                                            laoreet rutrum faucibus dolor auctor.</p>
                                        <p>Aenean lacinia bibendum nulla sed consectetur. Praesent commodo cursus magna, vel scelerisque nisl
                                            consectetur et. Donec sed odio dui. Donec ullamcorper nulla non metus auctor fringilla.</p>
                                        <p>Cras mattis consectetur purus sit amet fermentum. Cras justo odio, dapibus ac facilisis in, egestas
                                            eget quam. Morbi leo risus, porta ac consectetur ac, vestibulum at eros.</p>
                                        <p>Praesent commodo cursus magna, vel scelerisque nisl consectetur et. Vivamus sagittis lacus vel augue
                                            laoreet rutrum faucibus dolor auctor.</p>
                                        <p>Aenean lacinia bibendum nulla sed consectetur. Praesent commodo cursus magna, vel scelerisque nisl
                                            consectetur et. Donec sed odio dui. Donec ullamcorper nulla non metus auctor fringilla.</p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light me-2 " data-bs-dismiss="modal">Close</button>
                                        <button type="button" class="btn btn-primary">Save changes</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /Modal -->  
                    </div>
                </div>
            </div>
            <!-- /Scrolling Long Content -->

            <!-- Using the Grid -->
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            <h5 class="card-title">Using the grid</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                        data-bs-target="#exampleModalScrollable5">
                        Launch demo modal
                        </button>
                        <div class="modal fade" id="exampleModalScrollable5" tabindex="-1"
                            aria-labelledby="exampleModalScrollable5" data-bs-keyboard="false"
                            aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h4 class="modal-title" id="staticBackdropLabel5">Modal title
                                        </h4>
                                        <button type="button" class="close" data-bs-dismiss="modal"
                                            aria-label="Close">
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="container-fluid">
                                            <div class="row">
                                                <div class="col-md-4 bg-light border">.col-md-4</div>
                                                <div class="col-md-4 ms-auto bg-light border">.col-md-4
                                                    .ms-auto</div>
                                            </div>
                                            <div class="row mt-3">
                                                <div class="col-md-3 ms-auto bg-light border">.col-md-3
                                                    .ms-auto</div>
                                                <div class="col-md-2 ms-auto bg-light border">.col-md-2
                                                    .ms-auto</div>
                                            </div>
                                            <div class="row mt-3">
                                                <div class="col-md-6 ms-auto bg-light border">.col-md-6
                                                    .ms-auto</div>
                                            </div>
                                            <div class="row mt-3">
                                                <div class="col-sm-9 bg-light border">
                                                    Level 1: .col-sm-9
                                                    <div class="row">
                                                        <div class="col-8 col-sm-6 bg-light border">
                                                            Level 2: .col-8 .col-sm-6
                                                        </div>
                                                        <div class="col-4 col-sm-6 bg-light border">
                                                            Level 2: .col-4 .col-sm-6
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light me-2"
                                            data-bs-dismiss="modal">Close</button>
                                        <button type="button" class="btn btn-primary">Save
                                            Changes</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Using the Grid -->

            <!-- Toggle Between Modals -->
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Toggle Between Modals</h5>
                    </div>
                    <div class="card-body">
                        <!-- Modal -->
                        <div class="modal fade" id="exampleModalToggle" aria-hidden="true" aria-labelledby="exampleModalToggleLabel"
                            tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h4 class="modal-title" id="exampleModalToggleLabel">Modal 1</h4>
                                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                                    </div>
                                    <div class="modal-body">
                                        Show a second modal and hide this one with the button below.
                                    </div>
                                    <div class="modal-footer">
                                        <button class="btn btn-primary" data-bs-target="#exampleModalToggle2" data-bs-toggle="modal"
                                            data-bs-dismiss="modal">Open second modal</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /Modal -->

                        <!-- Modal -->
                        <div class="modal fade" id="exampleModalToggle2" aria-hidden="true" aria-labelledby="exampleModalToggleLabel2"
                            tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h4 class="modal-title" id="exampleModalToggleLabel2">Modal 2</h4>
                                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                                    </div>
                                    <div class="modal-body">
                                        Hide this modal and show the first with the button below.
                                    </div>
                                    <div class="modal-footer">
                                        <button class="btn btn-primary" data-bs-target="#exampleModalToggle" data-bs-toggle="modal"
                                            data-bs-dismiss="modal">Back to first</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /Modal -->

                        <a class="btn btn-primary" data-bs-toggle="modal" href="#exampleModalToggle" role="button">Open first modal</a>

                    </div> 
                </div>
            </div>
            <!-- /Toggle Between Modals --> 

            <!-- Optional Sizes -->
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Optional Sizes</h5>
                    </div>
                    <div class="card-body">

                        <!--  Modal -->
                        <div class="modal fade" id="bs-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h4 class="modal-title" id="myLargeModalLabel">Large modal</h4>
                                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                                    </div>
                                    <div class="modal-body">
                                        ...
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /Modal -->

                        <div class="modal fade" id="bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-sm">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h4 class="modal-title" id="mySmallModalLabel">Small modal</h4>
                                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                                    </div>
                                    <div class="modal-body">
                                        ...
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /Modal -->

                        <!-- Modal -->
                        <div class="modal fade" id="exampleModalLg" tabindex="-1"
                            aria-labelledby="exampleModalLgLabel" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h4 class="modal-title" id="exampleModalLgLabel">Extra Large Modal
                                        </h4>
                                        <button type="button" class="close" data-bs-dismiss="modal"
                                            aria-label="Close"><span aria-hidden="true">×</span></button>
                                    </div>
                                    <div class="modal-body">
                                        ...
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /Modal -->
                                                        
                        <div class="button-list">
                            <button  type="button" class="btn btn-primary mt-1" data-bs-toggle="modal" data-bs-target="#exampleModalLg">Extra Large Modal</button>
                            <button type="button" class="btn btn-primary mt-1" data-bs-toggle="modal" data-bs-target="#bs-example-modal-lg">Large Modal</button>
                            <button  type="button" class="btn btn-primary mt-1" data-bs-toggle="modal" data-bs-target="#bs-example-modal-sm">Small Modal</button>
                        </div>
                    </div> 
                </div>
            </div> 
            <!-- /Optional Sizes -->
            
        </div> 
        
        <div class="row">

            <!-- Modal with Pages -->
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Modal with Pages</h5>
                    </div>
                    <div class="card-body card-buttons">
                        <!-- Modal -->
                        <div id="signup-modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">

                                    <div class="modal-body">
                                        <div class="text-center mt-2 mb-4">
                                            <div class="auth-logo">
                                                <a href="{{url('index')}}" class="logo logo-dark">
                                                    <span class="logo-lg">
                                                        <img src="{{URL::asset('build/img/logo.svg')}}" alt="Logo" height="42">
                                                    </span>
                                                </a>
                                            </div>
                                        </div>

                                        <form class="px-3" action="#">

                                            <div class="mb-3">
                                                <label for="username" class="form-label">Name</label>
                                                <input class="form-control" type="email" id="username" required="" placeholder="Michael Zenaty">
                                            </div>

                                            <div class="mb-3">
                                                <label for="emailaddress" class="form-label">Email address</label>
                                                <input class="form-control" type="email" id="emailaddress" required="" placeholder="john@deo.com">
                                            </div>

                                            <div class="mb-3">
                                                <label for="password" class="form-label">Password</label>
                                                <input class="form-control" type="password" required="" id="password" placeholder="Enter your password">
                                            </div>

                                            <div class="mb-3">
                                                <div class="form-check">
                                                    <input type="checkbox" class="form-check-input" id="customCheck1">
                                                    <label class="form-check-label" for="customCheck1">I accept <a href="#">Terms and Conditions</a></label>
                                                </div> 
                                            </div>

                                            <div class="mb-3 text-center">
                                                <button class="btn btn-primary" type="submit">Sign Up Free</button>
                                            </div>

                                        </form>

                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /Modal -->

                        <!-- Modal -->
                        <div id="login-modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-body">
                                        <div class="text-center mt-2 mb-4">
                                            <div class="auth-logo">
                                                <a href="{{url('index')}}" class="logo logo-dark">
                                                    <span class="logo-lg">
                                                        <img src="{{URL::asset('build/img/logo.svg')}}" alt="Logo" height="42">
                                                    </span>
                                                </a>
                                            </div>
                                        </div>

                                        <form action="#" class="px-3">

                                            <div class="mb-3">
                                                <label for="emailaddress1" class="form-label">Email address</label>
                                                <input class="form-control" type="email" id="emailaddress1" required="" placeholder="john@deo.com">
                                            </div>

                                            <div class="mb-3">
                                                <label for="password1" class="form-label">Password</label>
                                                <input class="form-control" type="password" required="" id="password1" placeholder="Enter your password">
                                            </div>

                                            <div class="mb-3">
                                                <div class="form-check">
                                                    <input type="checkbox" class="form-check-input" id="customCheck2">
                                                    <label class="form-check-label" for="customCheck2">Remember me</label>
                                                </div>
                                            </div>

                                            <div class="mb-2 text-center">
                                                <button class="btn rounded-pill btn-primary" type="submit">Sign In</button>
                                            </div>

                                        </form>

                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /Modal -->

                        <div class="button-list">
                            <!-- Sign Up modal -->
                            <button type="button" class="btn btn-primary mt-1" data-bs-toggle="modal" data-bs-target="#signup-modal">Sign Up Modal</button>
                            <!-- Log In modal -->
                            <button type="button" class="btn btn-primary mt-1" data-bs-toggle="modal" data-bs-target="#login-modal">Log In Modal</button>
                        </div>

                    </div> 
                </div>
            </div>   
            <!-- /Modal with Pages -->    

            <!-- Custom Modals -->
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Custom Modals</h5>
                    </div>
                    <div class="card-body">

                        <!-- sample modal content -->
                        <div id="con-close-modal" class="modal fade" tabindex="-1" role="dialog"  aria-hidden="true" style="display: none;">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h4 class="modal-title">Modal Content is Responsive</h4>
                                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                                    </div>
                                    <div class="modal-body p-4">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="field-1" class="form-label">Name</label>
                                                    <input type="text" class="form-control" id="field-1" placeholder="John">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="field-2" class="form-label">Surname</label>
                                                    <input type="text" class="form-control" id="field-2" placeholder="Doe">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="mb-3">
                                                    <label for="field-3" class="form-label">Address</label>
                                                    <input type="text" class="form-control" id="field-3" placeholder="Address">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="field-4" class="form-label">City</label>
                                                    <input type="text" class="form-control" id="field-4" placeholder="Boston">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="field-5" class="form-label">Country</label>
                                                    <input type="text" class="form-control" id="field-5" placeholder="United States">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="field-6" class="form-label">Zip</label>
                                                    <input type="text" class="form-control" id="field-6" placeholder="123456">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div>
                                                    <label for="field-7" class="form-label">Personal Info</label>
                                                    <textarea class="form-control" id="field-7" placeholder="Write something about yourself"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light me-2 waves-effect me-2" data-bs-dismiss="modal">Close</button>
                                        <button type="button" class="btn btn-primary waves-effect waves-light">Save changes</button>
                                    </div>
                                </div>
                            </div>
                        </div><!-- /Modal -->

                        <div id="accordion-modal" class="modal fade" tabindex="-1" role="dialog"  aria-hidden="true" style="display: none;">
                            <div class="modal-dialog">
                                <div class="modal-content p-0">
                                    <div id="accordion">
                                        <div class="card mb-0">
                                            <div class="card-header" id="headingOne">
                                                <h5 class="m-0">
                                                    <a href="#collapseOne" class="text-dark" data-bs-toggle="collapse" aria-expanded="true" aria-controls="collapseOne">
                                                        Collapsible Group Item #1
                                                    </a>
                                                </h5>
                                            </div>
                                
                                            <div id="collapseOne" class="collapse show" aria-labelledby="headingOne" data-bs-parent="#accordion">
                                                <div class="card-body">
                                                    Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute,
                                                    non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon
                                                    tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Nihil
                                                    anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan
                                                    excepteur butcher vice lomo. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt
                                                    you probably haven't heard of them accusamus labore sustainable VHS.
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card mb-0">
                                            <div class="card-header" id="headingTwo">
                                                <h5 class="m-0">
                                                    <a href="#collapseTwo" class="collapsed text-dark" data-bs-toggle="collapse" aria-expanded="false" aria-controls="collapseTwo">
                                                        Collapsible Group Item #2
                                                    </a>
                                                </h5>
                                            </div>
                                            <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-bs-parent="#accordion">
                                                <div class="card-body">
                                                    Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute,
                                                    non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon
                                                    tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Nihil
                                                    anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan
                                                    excepteur butcher vice lomo. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt
                                                    you probably haven't heard of them accusamus labore sustainable VHS.
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card mb-0">
                                            <div class="card-header" id="headingThree">
                                                <h5 class="m-0">
                                                    <a href="#collapseThree" class="collapsed text-dark" data-bs-toggle="collapse" aria-expanded="false" aria-controls="collapseThree">
                                                        Collapsible Group Item #3
                                                    </a>
                                                </h5>
                                            </div>
                                            <div id="collapseThree" class="collapse" aria-labelledby="headingThree" data-bs-parent="#accordion">
                                                <div class="card-body">
                                                    Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute,
                                                    non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon
                                                    tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Nihil
                                                    anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan
                                                    excepteur butcher vice lomo. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt
                                                    you probably haven't heard of them accusamus labore sustainable VHS.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div><!-- /Modal -->

                        <div class="button-list">
                            <!-- Responsive modal -->
                            <button type="button" class="btn btn-primary waves-effect waves-light mt-1" data-bs-toggle="modal" data-bs-target="#con-close-modal">Responsive Modal</button>
                            <!-- Accordion modal -->
                            <button type="button" class="btn btn-secondary waves-effect waves-light mt-1" data-bs-toggle="modal" data-bs-target="#accordion-modal">Accordion in Modal</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Custom Modals -->
        
        </div>
                            
        <div class="row">

            <!-- Vertically Centered Modal -->
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            <h5 class="card-title">Vertically Centered Modal</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#exampleModalScrollable2">
                                Vertically centered modal
                        </button>
                        <div class="modal fade" id="exampleModalScrollable2" tabindex="-1"
                            aria-labelledby="exampleModalScrollable2" data-bs-keyboard="false"
                            aria-hidden="true">
                            <!-- Scrollable modal -->
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h4 class="modal-title" id="staticBackdropLabel2">Modal title
                                        </h4>
                                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                                    </div>
                                    <div class="modal-body">
                                        <p>Lorem ipsum dolor sit, amet consectetur adipisicing elit.
                                            Libero
                                            ipsum quasi, error quibusdam debitis maiores hic eum? Vitae
                                            nisi
                                            ipsa maiores fugiat deleniti quis reiciendis veritatis.</p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light me-2"
                                            data-bs-dismiss="modal">Close</button>
                                        <button type="button" class="btn btn-primary">Save
                                            Changes</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Vertically Centered Modal -->

            <!-- Vertically Centered Scrollable -->
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            <h5 class="card-title">Vertical Centered Scrollable</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#exampleModalScrollable3">
                                Vertically centered scrollable modal
                        </button>
                        <div class="modal fade" id="exampleModalScrollable3" tabindex="-1"
                            aria-labelledby="exampleModalScrollable3" data-bs-keyboard="false"
                            aria-hidden="true">
                            <!-- Scrollable modal -->
                            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h4 class="modal-title" id="staticBackdropLabel3">Modal title
                                        </h4>
                                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                                    </div>
                                    <div class="modal-body">
                                        <p>Lorem ipsum dolor sit amet consectetur, adipisicing elit. Ea
                                            voluptatibus, ipsam quo est rerum modi quos expedita facere,
                                            ex
                                            tempore fuga similique ipsa blanditiis et accusamus
                                            temporibus
                                            commodi voluptas! Nobis veniam illo architecto expedita quam
                                            ratione quaerat omnis. In, recusandae eos! Pariatur,
                                            deleniti
                                            quis ad nemo ipsam officia temporibus, doloribus fuga
                                            asperiores
                                            ratione distinctio velit alias hic modi praesentium aperiam
                                            officiis eaque, accusamus aut. Accusantium assumenda,
                                            commodi
                                            nulla provident asperiores fugit inventore iste amet aut
                                            placeat
                                            consequatur reprehenderit. Ratione tenetur eligendi, quis
                                            aperiam dolores magni iusto distinctio voluptatibus minus a
                                            unde
                                            at! Consequatur voluptatum in eaque obcaecati, impedit
                                            accusantium ea soluta, excepturi, quasi quia commodi
                                            blanditiis?
                                            Qui blanditiis iusto corrupti necessitatibus dolorem fugiat
                                            consequuntur quod quo veniam? Labore dignissimos reiciendis
                                            accusamus recusandae est consequuntur iure.</p>
                                        <p>Lorem ipsum dolor sit amet.</p>
                                        <p>Lorem ipsum dolor sit amet.</p>
                                        <p>Lorem ipsum dolor sit amet.</p>
                                        <p>Lorem ipsum dolor sit amet.</p>
                                        <p>Lorem ipsum dolor sit amet.</p>
                                        <p>Lorem ipsum dolor sit amet.</p>
                                        <p>Lorem ipsum dolor sit amet.</p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light me-2"
                                            data-bs-dismiss="modal">Close</button>
                                        <button type="button" class="btn btn-primary">Save
                                            Changes</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Vertically Centered Scrollable -->

        </div>  

        <div class="row">

            <!-- Fullscreen Modal -->
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            <h5 class="card-title">Fullscreen Modal</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="bd-example">
                            <button type="button" class="btn btn-primary mb-1" data-bs-toggle="modal"
                                data-bs-target="#exampleModalFullscreen">Full screen</button>
                            <button type="button" class="btn btn-secondary mb-1" data-bs-toggle="modal"
                                data-bs-target="#exampleModalFullscreenSm">Full screen below sm</button>
                            <button type="button" class="btn btn-warning mb-1" data-bs-toggle="modal"
                                data-bs-target="#exampleModalFullscreenMd">Full screen below md</button>
                            <button type="button" class="btn btn-info mb-1" data-bs-toggle="modal"
                                data-bs-target="#exampleModalFullscreenLg">Full screen below lg</button>
                            <button type="button" class="btn btn-success mb-1" data-bs-toggle="modal"
                                data-bs-target="#exampleModalFullscreenXl">Full screen below xl</button>
                            <button type="button" class="btn btn-danger mb-1" data-bs-toggle="modal"
                                data-bs-target="#exampleModalFullscreenXxl">Full screen below
                                xxl</button>
                        </div>
                        <div class="modal fade" id="exampleModalFullscreen" tabindex="-1"
                            aria-labelledby="exampleModalFullscreenLabel" aria-hidden="true"
                            style="display: none;">
                            <div class="modal-dialog modal-fullscreen">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h4 class="modal-title" id="exampleModalFullscreenLabel">Full
                                            screen modal</h4>
                                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                                    </div>
                                    <div class="modal-body">
                                        ...
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary"
                                            data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal fade" id="exampleModalFullscreenSm" tabindex="-1"
                            aria-labelledby="exampleModalFullscreenSmLabel" aria-hidden="true"
                            style="display: none;">
                            <div class="modal-dialog modal-fullscreen-sm-down">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h4 class="modal-title" id="exampleModalFullscreenSmLabel">
                                            Full
                                            screen below sm</h4>
                                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                                    </div>
                                    <div class="modal-body">
                                        ...
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary"
                                            data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal fade" id="exampleModalFullscreenMd" tabindex="-1"
                            aria-labelledby="exampleModalFullscreenMdLabel" aria-hidden="true"
                            style="display: none;">
                            <div class="modal-dialog modal-fullscreen-md-down">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h4 class="modal-title" id="exampleModalFullscreenMdLabel">
                                            Full
                                            screen below md</h4>
                                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                                    </div>
                                    <div class="modal-body">
                                        ...
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary"
                                            data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal fade" id="exampleModalFullscreenLg" tabindex="-1"
                            aria-labelledby="exampleModalFullscreenLgLabel" aria-hidden="true"
                            style="display: none;">
                            <div class="modal-dialog modal-fullscreen-lg-down">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h4 class="modal-title" id="exampleModalFullscreenLgLabel">
                                            Full
                                            screen below lg</h4>
                                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                                    </div>
                                    <div class="modal-body">
                                        ...
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary"
                                            data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal fade" id="exampleModalFullscreenXl" tabindex="-1"
                            aria-labelledby="exampleModalFullscreenXlLabel" aria-hidden="true"
                            style="display: none;">
                            <div class="modal-dialog modal-fullscreen-xl-down">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h4 class="modal-title" id="exampleModalFullscreenXlLabel">
                                            Full
                                            screen below xl</h4>
                                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                                    </div>
                                    <div class="modal-body">
                                        ...
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary"
                                            data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal fade" id="exampleModalFullscreenXxl" tabindex="-1"
                            aria-labelledby="exampleModalFullscreenXxlLabel" aria-hidden="true"
                            style="display: none;">
                            <div class="modal-dialog modal-fullscreen-xxl-down">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h4 class="modal-title" id="exampleModalFullscreenXxlLabel">
                                            Full
                                            screen below xxl</h4>
                                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                                    </div>
                                    <div class="modal-body">
                                        ...
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary"
                                            data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Fullscreen Modal -->

        </div>

        <!-- Modal Position -->
        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Modal Position</h5>
                    </div>
                    <div class="card-body">
                        <!-- Modal	 -->
                        <div id="top-modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
                            <div class="modal-dialog modal-top">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h4 class="modal-title" id="topModalLabel">Modal Heading</h4>
                                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                                    </div>
                                    <div class="modal-body">
                                        <h5>Text in a modal</h5>
                                        <p>Duis mollis, est non commodo luctus, nisi erat porttitor ligula.</p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light me-2 " data-bs-dismiss="modal">Close</button>
                                        <button type="button" class="btn btn-primary">Save changes</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /Modal -->

                        <!-- Modal -->
                        <div id="right-modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
                            <div class="modal-dialog modal-sm modal-right">
                                <div class="modal-content">
                                    <div class="modal-header border-0">
                                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="text-center">
                                            <h4 class="mt-0">Text in a modal</h4>
                                            <p>Duis mollis, est non commodo luctus, nisi erat porttitor ligula.</p>
                                            <button type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /Modal -->

                        <!-- Modal -->
                        <div id="bottom-modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
                            <div class="modal-dialog modal-sm  modal-dialog-bottom">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h4 class="modal-title" id="bottomModalLabel">Modal Heading</h4>
                                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                                    </div>
                                    <div class="modal-body">
                                        <h5>Text in a modal</h5>
                                        <p>Duis mollis, est non commodo luctus, nisi erat porttitor ligula.</p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light me-2 " data-bs-dismiss="modal">Close</button>
                                        <button type="button" class="btn btn-primary">Save changes</button>
                                    </div>
                                </div>
                            </div>
                        </div><!-- /Modal -->

                        <!-- Modal -->
                        <div class="modal fade" id="centermodal" tabindex="-1" role="dialog" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h4 class="modal-title" id="myCenterModalLabel">Center Modal</h4>
                                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                                    </div>
                                    <div class="modal-body">
                                        <h5>Overflowing text to show scroll behavior</h5>
                                        <p>Cras mattis consectetur purus sit amet fermentum. Cras justo odio, dapibus ac facilisis in, egestas eget quam. Morbi leo risus, porta ac consectetur ac, vestibulum at eros.</p>
                                        <p>Praesent commodo cursus magna, vel scelerisque nisl consectetur et. Vivamus sagittis lacus vel augue laoreet rutrum faucibus dolor auctor.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /Modal -->

                        <div class="button-list">
                            <!-- Top modal -->
                            <button type="button" class="btn btn-primary mt-1" data-bs-toggle="modal" data-bs-target="#top-modal">Top Modal</button>
                            <!-- Bottom modal -->
                            <button type="button" class="btn btn-primary mt-1" data-bs-toggle="modal" data-bs-target="#bottom-modal">Bottom Modal</button>
                            <!-- Center modal -->
                            <button type="button" class="btn btn-primary mt-1" data-bs-toggle="modal" data-bs-target="#centermodal">Center modal</button>
                            <!-- Right modal -->
                            <button type="button" class="btn btn-primary mt-1" data-bs-toggle="modal" data-bs-target="#right-modal">Rightbar Modal</button>
                        </div>
                        
                    </div> 
                </div>
            </div> 

        </div>
        <!-- /Modal Position -->

    </div>
</div>

@endsection
