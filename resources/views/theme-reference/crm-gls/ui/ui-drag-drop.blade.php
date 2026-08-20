<?php $page = 'ui-drag-drop'; ?>
@extends('layout.mainlayout')
@section('content')
<div class="page-wrapper cardhead">
    <div class="content">
    
        @component('components.breadcrumb')
        @slot('title')
        Drag & Drop
        @endslot
    @endcomponent	
        
        <!-- start row -->
        <div class="row">

            <div class="col-12">
                <div class="card">
                    <div class="card-header border-bottom d-flex align-items-center">
                        <h4 class="header-title">Simple Drag and Drop Example</h4>
                    </div>  
                    <div class="card-body">
                        <p class="text-muted mb-0">
                            Just specify the data attribute <code>data-plugin='dragula'</code> to have drag and drop support in your container.
                        </p>

                        <!-- start row -->
                        <div class="row" id="simple-dragula" data-plugin="dragula">

                            <div class="col-md-4">
                                <div class="card mb-0 mt-4 text-white bg-primary">
                                    <div class="card-body">
                                        <blockquote class="card-bodyquote mb-0">
                                            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante.</p>
                                            <footer>Someone famous in <cite title="Source Title">Source Title</cite>
                                            </footer>
                                        </blockquote>
                                    </div> <!-- end card body -->
                                </div> <!-- end card -->
                            </div> <!-- end col -->

                            <div class="col-md-4">
                                <div class="card mb-0 mt-4 bg-secondary text-white">
                                    <div class="card-body">
                                        <blockquote class="card-bodyquote mb-0">
                                            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante.</p>
                                            <footer>Someone famous in <cite title="Source Title">Source Title</cite>
                                            </footer>
                                        </blockquote>
                                    </div> <!-- end card body -->
                                </div> <!-- end card -->
                            </div> <!-- end col -->

                            <div class="col-md-4">
                                <div class="card mb-0 mt-4 text-white bg-success">
                                    <div class="card-body">
                                        <blockquote class="card-bodyquote mb-0">
                                            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante.</p>
                                            <footer>Someone famous in <cite title="Source Title">Source Title</cite>
                                            </footer>
                                        </blockquote>
                                    </div> <!-- end card body -->
                                </div> <!-- end card -->
                            </div> <!-- end col -->

                            <div class="col-md-4">
                                <div class="card mb-0 mt-4 text-white bg-info text-xs-center">
                                    <div class="card-body">
                                        <blockquote class="card-bodyquote mb-0">
                                            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante.</p>
                                            <footer>Someone famous in <cite title="Source Title">Source Title</cite>
                                            </footer>
                                        </blockquote>
                                    </div> <!-- end card body -->
                                </div> <!-- end card -->
                            </div> <!-- end col -->

                            <div class="col-md-4">
                                <div class="card mb-0 mt-4 text-white bg-warning text-xs-center">
                                    <div class="card-body">
                                        <blockquote class="card-bodyquote mb-0">
                                            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante.</p>
                                            <footer>Someone famous in <cite title="Source Title">Source Title</cite>
                                            </footer>
                                        </blockquote>
                                    </div> <!-- end card body -->
                                </div> <!-- end card -->
                            </div> <!-- end col -->

                            <div class="col-md-4">
                                <div class="card mb-0 mt-4 text-white bg-danger text-xs-center">
                                    <div class="card-body">
                                        <blockquote class="card-bodyquote mb-0">
                                            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante.</p>
                                            <footer>Someone famous in <cite title="Source Title">Source Title</cite>
                                            </footer>
                                        </blockquote>
                                    </div> <!-- end card body -->
                                </div> <!-- end card -->
                            </div> <!-- end col -->

                        </div>
                        <!-- end row -->

                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

        </div> 
        <!-- end row -->

        <!-- start row -->
        <div class="row">

            <div class="col-12">
                <div class="card">
                    <div class="card-header border-bottom d-flex align-items-center">
                        <h4 class="header-title">Move stuff between containers</h4>
                    </div>

                    <div class="card-body">
                        <p class="text-muted">
                            Just specify the data attribute <code>data-plugin='dragula'</code> and <code>data-containers='["first-container-id", "second-container-id"]'</code>.
                        </p>

                        <!-- start row -->
                        <div class="row" data-plugin="dragula" data-containers='["company-list-left", "company-list-right"]'>

                            <div class="col-md-6">
                                <div class="bg-light bg-opacity-50 p-2 p-lg-4">
                                    <h5 class="mt-0 mb-0">Part 1</h5>
                                    <div id="company-list-left">

                                        <div class="card mb-0 mt-4">
                                            <div class="card-body">
                                                <div class="d-flex align-items-start">
                                                    <img src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" alt="image" class="me-3 d-none d-sm-block avatar-sm rounded-circle">
                                                    <div class="w-100 overflow-hidden">
                                                        <h5 class="mb-1 mt-0">Louis K. Bond</h5>
                                                        <p> Founder & CEO </p>
                                                        <p class="mb-0 text-muted">
                                                            <span class="fst-italic"><b>"</b>Disrupt pork belly poutine, asymmetrical tousled succulents selfies. You probably haven't heard of them tattooed master cleanse live-edge keffiyeh.</span>
                                                        </p>
                                                    </div> 
                                                </div> 
                                            </div> <!-- end card-body -->
                                        </div> <!-- end col -->

                                        <div class="card mb-0 mt-4">
                                            <div class="card-body">
                                                <div class="d-flex align-items-start">
                                                    <img src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" alt="image" class="me-3 d-none d-sm-block avatar-sm rounded-circle">
                                                    <div class="w-100 overflow-hidden">
                                                        <h5 class="mb-1 mt-0">Dennis N. Cloutier</h5>
                                                        <p> Software Engineer </p>
                                                        <p class="mb-0 text-muted">
                                                            <span class="fst-italic"><b>"</b>Disrupt pork belly poutine, asymmetrical tousled succulents selfies. You probably haven't heard of them tattooed master cleanse live-edge keffiyeh.</span>
                                                        </p>
                                                    </div> 
                                                </div> 
                                            </div> <!-- end card-body -->
                                        </div> <!-- end col -->

                                        <div class="card mb-0 mt-4">
                                            <div class="card-body">
                                                <div class="d-flex align-items-start">
                                                    <img src="{{URL::asset('build/img/profiles/avatar-03.jpg')}}" alt="image" class="me-3 d-none d-sm-block avatar-sm rounded-circle">
                                                    <div class="w-100 overflow-hidden">
                                                        <h5 class="mb-1 mt-0">Susan J. Sander</h5>
                                                        <p> Web Designer </p>
                                                        <p class="mb-0 text-muted">
                                                            <span class="fst-italic"><b>"</b>Disrupt pork belly poutine, asymmetrical tousled succulents selfies. You probably haven't heard of them tattooed master cleanse live-edge keffiyeh.</span>
                                                        </p>
                                                    </div> 
                                                </div> 
                                            </div> <!-- end card-body -->
                                        </div> <!-- end col -->

                                    </div> <!-- end company-list -->
                                </div> <!-- end div.bg-light -->
                            </div> <!-- end col -->

                            <div class="col-md-6">
                                <div class="bg-light bg-opacity-50 p-2 p-lg-4">
                                    <h5 class="mt-0 mb-0">Part 2</h5>
                                    <div id="company-list-right">
                                        <div class="card mb-0 mt-4">

                                            <div class="card-body p-3">
                                                <div class="d-flex align-items-start">
                                                    <img src="{{URL::asset('build/img/profiles/avatar-04.jpg')}}" alt="image" class="me-3 d-none d-sm-block avatar-sm rounded-circle">
                                                    <div class="w-100 overflow-hidden">
                                                        <h5 class="mb-1 mt-0">James M. Short</h5>
                                                        <p> Web Developer </p>
                                                        <p class="mb-0 text-muted">
                                                            <span class="fst-italic"><b>"</b>Disrupt pork belly poutine, asymmetrical tousled succulents selfies. You probably haven't heard of them tattooed master cleanse live-edge keffiyeh </span>
                                                        </p>
                                                    </div> 
                                                </div> 
                                            </div> <!-- end card-body -->
                                        </div> <!-- end col -->

                                        <div class="card mb-0 mt-4">
                                            <div class="card-body p-3">
                                                <div class="d-flex align-items-start">
                                                    <img src="{{URL::asset('build/img/profiles/avatar-05.jpg')}}" alt="image" class="me-3 d-none d-sm-block avatar-sm rounded-circle">
                                                    <div class="w-100 overflow-hidden">
                                                        <h5 class="mb-1 mt-0">Gabriel J. Snyder</h5>
                                                        <p> Business Analyst </p>
                                                        <p class="mb-0 text-muted">
                                                            <span class="fst-italic"><b>"</b>Disrupt pork belly poutine, asymmetrical tousled succulents selfies. You probably haven't heard of them tattooed master cleanse live-edge keffiyeh </span>
                                                        </p>
                                                    </div> 
                                                </div> 
                                            </div> <!-- end card-body -->
                                        </div> <!-- end col -->

                                        <div class="card mb-0 mt-4">
                                            <div class="card-body p-3">
                                                <div class="d-flex align-items-start">
                                                    <img src="{{URL::asset('build/img/profiles/avatar-06.jpg')}}" alt="image" class="me-3 d-none d-sm-block avatar-sm rounded-circle">
                                                    <div class="w-100 overflow-hidden">
                                                        <h5 class="mb-1 mt-0">Louie C. Mason</h5>
                                                        <p>Human Resources</p>
                                                        <p class="mb-0 text-muted">
                                                            <span class="fst-italic"><b>"</b>Disrupt pork belly poutine, asymmetrical tousled succulents selfies. You probably haven't heard of them tattooed master cleanse live-edge keffiyeh </span>
                                                        </p>
                                                    </div> 
                                                </div> 
                                            </div> <!-- end card-body -->
                                        </div> <!-- end col -->

                                    </div> <!-- end company-list -->
                                </div> <!-- end div.bg-light -->
                            </div> <!-- end col -->

                        </div> <!-- end row -->

                    </div> <!-- end card-body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

        </div> 
        <!-- end row -->

        <!-- start row -->
        <div class="row">

            <div class="col-12">
                <div class="card">
                    <div class="card-header border-bottom d-flex align-items-center">
                        <h4 class="header-title">Move stuff between containers using handle</h4>
                    </div>

                    <div class="card-body">
                        <p class="text-muted">
                            Just specify the data attribute <code>data-plugin='dragula'</code>, <code>data-containers='["first-container-id", "second-container-id"]'</code> and <code>data-handle-class='dragula-handle'</code>.
                        </p>

                        <!-- start row -->
                        <div class="row" data-plugin="dragula" data-containers='["handle-dragula-left", "handle-dragula-right"]' data-handleClass="dragula-handle">

                            <div class="col-md-6">
                                <div class="bg-light bg-opacity-50 p-2 p-lg-4">
                                    <h5 class="mt-0">Part 1</h5>
                                    <div id="handle-dragula-left" class="pt-2">

                                        <div class="card mb-0 mt-4">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center">
                                                    <img src="{{URL::asset('build/img/profiles/avatar-07.jpg')}}" alt="image" class="me-3 d-none d-sm-block avatar-sm rounded-circle">
                                                    <div class="w-100 overflow-hidden">
                                                        <h5 class="mb-1">Louis K. Bond</h5>
                                                        <p class="mb-0"> Founder & CEO </p>
                                                    </div> 
                                                    <span class="dragula-handle"></span>
                                                </div> 
                                            </div> <!-- end card-body -->
                                        </div> <!-- end col -->

                                        <div class="card mb-0 mt-4">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center">
                                                    <img src="{{URL::asset('build/img/profiles/avatar-08.jpg')}}" alt="image" class="me-3 d-none d-sm-block avatar-sm rounded-circle">
                                                    <div class="w-100 overflow-hidden">
                                                        <h5 class="mb-1">Dennis N. Cloutier</h5>
                                                        <p class="mb-0"> Software Engineer </p>
                                                    </div> 
                                                    <span class="dragula-handle"></span>
                                                </div> 
                                            </div> <!-- end card-body -->
                                        </div> <!-- end col -->

                                        <div class="card mb-0 mt-4">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center">
                                                    <img src="{{URL::asset('build/img/profiles/avatar-09.jpg')}}" alt="image" class="me-3 d-none d-sm-block avatar-sm rounded-circle">
                                                    <div class="w-100 overflow-hidden">
                                                        <h5 class="mb-1">Susan J. Sander</h5>
                                                        <p class="mb-0"> Web Designer </p>
                                                    </div> 
                                                    <span class="dragula-handle"></span>
                                                </div> 
                                            </div> <!-- end card-body -->
                                        </div> <!-- end col -->

                                    </div> <!-- end company-list -->
                                </div> <!-- end div -->
                            </div> <!-- end col -->

                            <div class="col-md-6">
                                <div class="bg-light bg-opacity-50 p-2 p-lg-4">
                                    <h5 class="mt-0">Part 2</h5>
                                    <div id="handle-dragula-right" class="pt-2">
                                        <div class="card mb-0 mt-4">

                                            <div class="card-body p-3">
                                                <div class="d-flex align-items-center">
                                                    <img src="{{URL::asset('build/img/profiles/avatar-10.jpg')}}" alt="image" class="me-3 d-none d-sm-block avatar-sm rounded-circle">
                                                    <div class="w-100 overflow-hidden">
                                                        <h5 class="mb-1">James M. Short</h5>
                                                        <p class="mb-0"> Web Developer </p>
                                                    </div> 
                                                    <span class="dragula-handle"></span>
                                                </div> 
                                            </div> <!-- end card-body -->
                                        </div> <!-- end col -->

                                        <div class="card mb-0 mt-4">
                                            <div class="card-body p-3">
                                                <div class="d-flex align-items-center">
                                                    <img src="{{URL::asset('build/img/profiles/avatar-05.jpg')}}" alt="image" class="me-3 d-none d-sm-block avatar-sm rounded-circle">
                                                    <div class="w-100 overflow-hidden">
                                                        <h5 class="mb-1">Gabriel J. Snyder</h5>
                                                        <p class="mb-0"> Business Analyst </p>
                                                    </div> 
                                                    <span class="dragula-handle"></span>
                                                </div> 
                                            </div> <!-- end card-body -->
                                        </div> <!-- end col -->

                                        <div class="card mb-0 mt-4">
                                            <div class="card-body p-3">
                                                <div class="d-flex align-items-center">
                                                    <img src="{{URL::asset('build/img/profiles/avatar-03.jpg')}}" alt="image" class="me-3 d-none d-sm-block avatar-sm rounded-circle">
                                                    <div class="w-100 overflow-hidden">
                                                        <h5 class="mb-1">Louie C. Mason</h5>
                                                        <p class="mb-0">Human Resources</p>
                                                    </div> 
                                                    <span class="dragula-handle"></span>
                                                </div> 
                                            </div> <!-- end card-body -->
                                        </div> <!-- end col -->

                                    </div> <!-- end company-list -->
                                </div> <!-- end div -->
                            </div> <!-- end col -->

                        </div> 
                        <!-- end row -->

                    </div> <!-- end card-body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

        </div> 
        <!-- end row -->
    
    </div>
</div>
@endsection
