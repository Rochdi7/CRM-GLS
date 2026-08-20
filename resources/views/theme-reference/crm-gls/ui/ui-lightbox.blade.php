<?php $page = 'ui-lightbox'; ?>
@extends('layout.mainlayout')
@section('content')
<div class="page-wrapper">
    <div class="content">
        @component('components.breadcrumb')
        @slot('title')
        Light Box
        @endslot
    @endcomponent	
        <!-- start row -->
        <div class="row">

            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Single Image Lightbox</h5>
                    </div>
                    <div class="card-body pb-1">

                        <!-- start row -->
                        <div class="row">

                            <div class="col-md-4 mb-3">
                                <a href="{{URL::asset('build/img/media/img-01.jpg')}}" class="image-popup">
                                    <img src="{{URL::asset('build/img/media/img-01.jpg')}}" class="img-fluid" alt="image">
                                </a>
                            </div> <!-- end col -->

                            <div class="col-md-4 mb-3">
                                <a href="{{URL::asset('build/img/media/img-02.jpg')}}" class="image-popup">
                                    <img src="{{URL::asset('build/img/media/img-02.jpg')}}" class="img-fluid" alt="image">
                                </a>
                            </div> <!-- end col -->

                        </div>
                        <!-- end row -->

                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Image with Description</h5>
                    </div>
                    <div class="card-body pb-1">

                        <!-- start row -->
                        <div class="row">

                            <div class="col-md-4 mb-3">
                                <a href="{{URL::asset('build/img/media/img-03.jpg')}}" class="image-popup-desc" data-title="Title 01" data-description="Lorem ipsum dolor sit amet, consectetuer adipiscing elit">
                                    <img src="{{URL::asset('build/img/media/img-03.jpg')}}" class="img-fluid" alt="work-thumbnail">
                                </a>
                            </div> <!-- end col -->

                            <div class="col-md-4 mb-3">
                                <a href="{{URL::asset('build/img/media/img-04.jpg')}}" class="image-popup-desc" data-title="Title 02" data-description="Lorem ipsum dolor sit amet, consectetuer adipiscing elit">
                                    <img src="{{URL::asset('build/img/media/img-04.jpg')}}" class="img-fluid" alt="work-thumbnail">
                                </a>
                            </div> <!-- end col -->

                            <div class="col-md-4 mb-3">
                                <a href="{{URL::asset('build/img/media/img-05.jpg')}}" class="image-popup-desc" data-title="Title 03" data-description="Lorem ipsum dolor sit amet, consectetuer adipiscing elit">
                                    <img src="{{URL::asset('build/img/media/img-05.jpg')}}" class="img-fluid" alt="work-thumbnail">
                                </a>
                            </div> <!-- end col -->

                        </div>
                        <!-- end row -->

                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

        </div>
        <!-- end row -->
    </div>
</div>
@endsection
