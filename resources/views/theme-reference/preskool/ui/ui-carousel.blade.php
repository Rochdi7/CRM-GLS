<?php $page = 'ui-carousel'; ?>
@extends('layout.mainlayout')
@section('content')
  <!-- Page Wrapper -->
  <div class="page-wrapper">
    <div class="content">
        @component('components.breadcrumb')
        @slot('title')
        Carousel
        @endslot
    @endcomponent	

        <div class="row">

            <!-- Slides Only -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Slides Only</h5>
                        <p class="sub-header">Here’s a carousel with slides only. Note the presence of the
                            <code>.d-block</code> and <code>.img-fluid</code> on carousel images to prevent
                            browser default image alignment.</p>
                    </div>
                    <div class="card-body">
                        <div id="carouselExampleSlidesOnly" class="carousel slide" data-bs-ride="carousel">
                            <div class="carousel-inner" role="listbox">
                                <div class="carousel-item active">
                                    <img class="d-block img-fluid" src="{{URL::asset('build/img/img-1.jpg')}}" alt="First slide">
                                </div>
                                <div class="carousel-item">
                                    <img class="d-block img-fluid" src="{{URL::asset('build/img/img-3.jpg')}}"
                                        alt="Second slide">
                                </div>
                                <div class="carousel-item">
                                    <img class="d-block img-fluid" src="{{URL::asset('build/img/img-4.jpg')}}" alt="Third slide">
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <!-- /Slides Only -->

            <!-- With Controls -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">With Controls</h5>
                        <p class="sub-header">Adding in the previous and next controls:</p>
                    </div>
                    <div class="card-body">
                        <div id="carouselExampleControls" class="carousel slide" data-bs-ride="carousel">
                            <div class="carousel-inner" role="listbox">
                                <div class="carousel-item active">
                                    <img class="d-block img-fluid" src="{{URL::asset('build/img/img-1.jpg')}}" alt="First slide">
                                </div>
                                <div class="carousel-item">
                                    <img class="d-block img-fluid" src="{{URL::asset('build/img/img-3.jpg')}}"
                                        alt="Second slide">
                                </div>
                                <div class="carousel-item">
                                    <img class="d-block img-fluid" src="{{URL::asset('build/img/img-4.jpg')}}" alt="Third slide">
                                </div>
                            </div>
                            <a class="carousel-control-prev" href="#carouselExampleControls" role="button"
                                data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </a>
                            <a class="carousel-control-next" href="#carouselExampleControls" role="button"
                                data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </a>
                        </div>

                    </div>
                </div>
            </div>
            <!-- /With Controls -->
        </div>

        <div class="row">

            <!-- With Indicators -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">With Indicators</h5>
                        <p class="sub-header">You can also add the indicators to the carousel, alongside the
                            controls, too.</p>
                    </div>
                    <div class="card-body">
                        <div id="carouselExampleIndicators" class="carousel slide" data-bs-ride="carousel">
                            <ol class="carousel-indicators">
                                <li data-bs-target="#carouselExampleIndicators" data-bs-slide-to="0"
                                    class="active"></li>
                                <li data-bs-target="#carouselExampleIndicators" data-bs-slide-to="1"></li>
                                <li data-bs-target="#carouselExampleIndicators" data-bs-slide-to="2"></li>
                            </ol>
                            <div class="carousel-inner" role="listbox">
                                <div class="carousel-item active">
                                    <img class="d-block img-fluid" src="{{URL::asset('build/img/img-1.jpg')}}" alt="First slide">
                                </div>
                                <div class="carousel-item">
                                    <img class="d-block img-fluid" src="{{URL::asset('build/img/img-2.jpg')}}"
                                        alt="Second slide">
                                </div>
                                <div class="carousel-item">
                                    <img class="d-block img-fluid" src="{{URL::asset('build/img/img-4.jpg')}}" alt="Third slide">
                                </div>
                            </div>
                            <a class="carousel-control-prev" href="#carouselExampleIndicators" role="button"
                                data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </a>
                            <a class="carousel-control-next" href="#carouselExampleIndicators" role="button"
                                data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </a>
                        </div>

                    </div>
                </div>
            </div>
            <!-- /With Indicators -->

            <!-- With Captions -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">With Captions</h5>
                        <p class="sub-header">Add captions to your slides easily with the
                            <code>.carousel-caption</code> element within any <code>.carousel-item</code>.</p>
                    </div>
                    <div class="card-body">
                        <div id="carouselExampleCaption" class="carousel slide" data-bs-ride="carousel">
                            <div class="carousel-inner" role="listbox">
                                <div class="carousel-item active">
                                    <img src="{{URL::asset('build/img/img-1.jpg')}}" alt="Slide" class="d-block img-fluid">
                                    <div class="carousel-caption d-none d-md-block">
                                        <h3 class="text-white">First slide label</h3>
                                        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
                                    </div>
                                </div>
                                <div class="carousel-item">
                                    <img src="{{URL::asset('build/img/img-2.jpg')}}" alt="Slide" class="d-block img-fluid">
                                    <div class="carousel-caption d-none d-md-block">
                                        <h3 class="text-white">Second slide label</h3>
                                        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
                                    </div>
                                </div>
                                <div class="carousel-item">
                                    <img src="{{URL::asset('build/img/img-3.jpg')}}" alt="Slide" class="d-block img-fluid">
                                    <div class="carousel-caption d-none d-md-block">
                                        <h3 class="text-white">Third slide label</h3>
                                        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
                                    </div>
                                </div>
                            </div>
                            <a class="carousel-control-prev" href="#carouselExampleCaption" role="button"
                                data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </a>
                            <a class="carousel-control-next" href="#carouselExampleCaption" role="button"
                                data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </a>
                        </div>

                    </div>
                </div>
            </div>
            <!-- /With Captions -->

        </div>

        <div class="row">

            <!-- Crossfade -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Crossfade</h5>
                        <p class="sub-header">Add <code>.carousel-fade</code> to your carousel to animate slides
                            with a fade transition instead of a slide.</p>
                    </div>
                    <div class="card-body">
                        <div id="carouselExampleFade" class="carousel slide carousel-fade"
                            data-bs-ride="carousel">
                            <div class="carousel-inner">
                                <div class="carousel-item active">
                                    <img class="d-block img-fluid" src="{{URL::asset('build/img/img-1.jpg')}}" alt="First slide">
                                </div>
                                <div class="carousel-item">
                                    <img class="d-block img-fluid" src="{{URL::asset('build/img/img-2.jpg')}}"
                                        alt="Second slide">
                                </div>
                                <div class="carousel-item">
                                    <img class="d-block img-fluid" src="{{URL::asset('build/img/img-3.jpg')}}" alt="Third slide">
                                </div>
                            </div>
                            <a class="carousel-control-prev" href="#carouselExampleFade" role="button"
                                data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </a>
                            <a class="carousel-control-next" href="#carouselExampleFade" role="button"
                                data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Crossfade -->

            <!-- Individual Interval -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Individual Interval</h5>
                        <p class="sub-header">Add <code>data-bs-interval=""</code> to a
                            <code>.carousel-item</code> to change the amount of time to delay between
                            automatically cycling to the next item.</p>
                    </div>
                    <div class="card-body">
                        <div id="carouselExampleInterval" class="carousel slide" data-bs-ride="carousel">
                            <div class="carousel-inner">
                                <div class="carousel-item active">
                                    <img class="d-block img-fluid" src="{{URL::asset('build/img/img-2.jpg')}}" alt="First slide">
                                </div>
                                <div class="carousel-item">
                                    <img class="d-block img-fluid" src="{{URL::asset('build/img/img-3.jpg')}}"
                                        alt="Second slide">
                                </div>
                                <div class="carousel-item">
                                    <img class="d-block img-fluid" src="{{URL::asset('build/img/img-4.jpg')}}" alt="Third slide">
                                </div>
                            </div>
                            <a class="carousel-control-prev" href="#carouselExampleInterval" role="button"
                                data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </a>
                            <a class="carousel-control-next" href="#carouselExampleInterval" role="button"
                                data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </a>
                        </div>

                    </div>
                </div>
            </div>
            <!-- /Individual Interval -->

        </div>

        <div class="row">

            <!-- Disable Touch Swiping -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">Disable Touch Swiping</div>
                    </div>
                    <div class="card-body">
                        <div id="carouselExampleControlsNoTouching" class="carousel slide" data-bs-touch="false"
                            data-bs-interval="false">
                            <div class="carousel-inner">
                                <div class="carousel-item active">
                                    <img src="{{URL::asset('build/img/img-2.jpg')}}" class="d-block w-100" alt="Slide">
                                </div>
                                <div class="carousel-item">
                                    <img src="{{URL::asset('build/img/img-3.jpg')}}" class="d-block w-100" alt="Slide">
                                </div>
                                <div class="carousel-item">
                                    <img src="{{URL::asset('build/img/img-4.jpg')}}" class="d-block w-100" alt="Slide">
                                </div>
                            </div>
                            <button class="carousel-control-prev" type="button"
                                data-bs-target="#carouselExampleControlsNoTouching" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next" type="button"
                                data-bs-target="#carouselExampleControlsNoTouching" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Disable Touch Swiping -->

            <!-- Dark Variant -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">Dark Variant</div>
                    </div>
                    <div class="card-body">
                        <div id="carouselExampleDark" class="carousel slide" data-bs-ride="carousel">
                            <div class="carousel-indicators">
                                <button type="button" data-bs-target="#carouselExampleDark" data-bs-slide-to="0"
                                    class="active" aria-current="true" aria-label="Slide 1"></button>
                                <button type="button" data-bs-target="#carouselExampleDark" data-bs-slide-to="1"
                                    aria-label="Slide 2"></button>
                                <button type="button" data-bs-target="#carouselExampleDark" data-bs-slide-to="2"
                                    aria-label="Slide 3"></button>
                            </div>
                            <div class="carousel-inner">
                                <div class="carousel-item active" data-bs-interval="10000">
                                    <img src="{{URL::asset('build/img/img-2.jpg')}}" class="d-block w-100" alt="Slide">
                                    <div class="carousel-caption d-none d-md-block">
                                        <h5 class="text-fixed-white">First slide label</h5>
                                        <p class="op-7">Some representative placeholder content for the first
                                            slide.</p>
                                    </div>
                                </div>
                                <div class="carousel-item" data-bs-interval="2000">
                                    <img src="{{URL::asset('build/img/img-3.jpg')}}" class="d-block w-100" alt="Slide">
                                    <div class="carousel-caption d-none d-md-block">
                                        <h5 class="text-fixed-white">Second slide label</h5>
                                        <p class="op-7">Some representative placeholder content for the second
                                            slide.</p>
                                    </div>
                                </div>
                                <div class="carousel-item">
                                    <img src="{{URL::asset('build/img/img-4.jpg')}}" class="d-block w-100" alt="Slide">
                                    <div class="carousel-caption d-none d-md-block">
                                        <h5 class="text-fixed-white">Third slide label</h5>
                                        <p class="op-7">Some representative placeholder content for the third
                                            slide.</p>
                                    </div>
                                </div>
                            </div>
                            <button class="carousel-control-prev" type="button"
                                data-bs-target="#carouselExampleDark" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next" type="button"
                                data-bs-target="#carouselExampleDark" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Dark Variant -->

        </div>

    </div>
</div>
<!-- /Page Wrapper -->
@endsection
