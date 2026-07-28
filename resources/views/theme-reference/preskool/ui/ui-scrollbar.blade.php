<?php $page = 'ui-scrollbar'; ?>
@extends('layout.mainlayout')
@section('content')
<div class="page-wrapper cardhead">
    <div class="content ">

       <!-- Page Header -->
       @component('components.breadcrumb')
       @slot('title')
   Scroll Bar
       @endslot
   @endcomponent	
        <!-- start row -->
        <div class="row">
            
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header border-bottom d-flex align-items-center">
                        <h4 class="header-title">Default Scroll</h4>
                    </div> 

                    <p class="text-muted m-3">Just use data attribute <code>data-simplebar</code> and add <code>max-height: **px</code> or fix height</p>

                    <div class="card-body py-0 mb-3 simplebar-height" data-simplebar>
                        SimpleBar does only one thing: replace the browser's default scrollbar with a custom CSS-styled one without losing performances. Unlike some popular plugins, SimpleBar doesn't mimic scroll with Javascript, causing janks and strange scrolling behaviours... You keep the awesomeness of native scrolling...with a custom scrollbar!
                        <p>SimpleBar <strong>does NOT implement a custom scroll behaviour</strong>. It keeps the <strong>native</strong> <code>overflow: auto</code> scroll and <strong>only</strong> replace the scrollbar visual appearance.</p>
                        <h5>Design it as you want</h5>
                        <p>SimpleBar uses pure CSS to style the scrollbar. You can easily customize it as you want! Or even have multiple style on the same page...or just keep the default style ("Mac OS" scrollbar style).</p>
                        <h5>Lightweight and performant</h5>
                        <p>Only 6kb minified. SimpleBar doesn't use Javascript to handle scrolling. You keep the performances/behaviours of the native scroll.</p>
                        <h5>Supported everywhere</h5>
                        <p class="mb-0">SimpleBar has been tested on the following browsers: Chrome, Firefox, Safari, Edge, IE11.</p>
                    </div> <!-- end card-body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header border-bottom d-flex align-items-center">
                        <h4 class="header-title">RTL Position</h4>
                    </div> 

                    <div class="card-body pb-0">
                        <p class="text-muted">Just use data attribute <code>data-simplebar data-simplebar-direction='rtl'</code> and add <code>max-height: **px</code> or fix height</p>
                    </div> <!-- end card-body -->

                    <div class="card-body py-0 mb-3 simplebar-height" data-simplebar data-simplebar-direction='rtl'>
                        SimpleBar does only one thing: replace the browser's default scrollbar with a custom CSS-styled one without losing performances. Unlike some popular plugins, SimpleBar doesn't mimic scroll with Javascript, causing janks and strange scrolling behaviours... You keep the awesomeness of native scrolling...with a custom scrollbar!
                        <p>SimpleBar <strong>does NOT implement a custom scroll behaviour</strong>. It keeps the <strong>native</strong> <code>overflow: auto</code> scroll and <strong>only</strong> replace the scrollbar visual appearance. </p>
                        <h5>Design it as you want</h5>
                        <p>SimpleBar uses pure CSS to style the scrollbar. You can easily customize it as you want! Or even have multiple style on the same page...or just keep the default style ("Mac OS" scrollbar style).</p>
                        <h5>Lightweight and performant</h5>
                        <p>Only 6kb minified. SimpleBar doesn't use Javascript to handle scrolling. You keep the performances/behaviours of the native scroll.</p>
                        <h5>Supported everywhere</h5>
                        <p class="mb-0">SimpleBar has been tested on the following browsers: Chrome, Firefox, Safari, Edge, IE11.</p>
                    </div> <!-- end card-body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

        </div>
        <!-- end row -->

        <!-- start row -->
        <div class="row">

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header border-bottom d-flex align-items-center">
                        <h4 class="header-title">Scroll Size</h4>
                    </div> 

                    <div class="card-body pb-0">
                        <p class="text-muted">Just use data attribute <code>data-simplebar</code> and add <code>max-height: **px</code> or fix height</p>
                    </div> <!-- end card-body -->

                    <div class="card-body py-0 mb-3 simplebar-height" data-simplebar data-simplebar-lg>
                        SimpleBar does only one thing: replace the browser's default scrollbar with a custom CSS-styled one without losing performances. Unlike some popular plugins, SimpleBar doesn't mimic scroll with Javascript, causing janks and strange scrolling behaviours... You keep the awesomeness of native scrolling...with a custom scrollbar!
                        <p>SimpleBar <strong>does NOT implement a custom scroll behaviour</strong>. It keeps the <strong>native</strong> <code>overflow: auto</code> scroll and <strong>only</strong> replace the scrollbar visual appearance. </p>
                        <h5>Design it as you want</h5>
                        <p>SimpleBar uses pure CSS to style the scrollbar. You can easily customize it as you want! Or even have multiple style on the same page...or just keep the default style ("Mac OS" scrollbar style).</p>
                        <h5>Lightweight and performant</h5>
                        <p>Only 6kb minified. SimpleBar doesn't use Javascript to handle scrolling. You keep the performances/behaviours of the native scroll.</p>
                        <h5>Supported everywhere</h5>
                        <p class="mb-0">SimpleBar has been tested on the following browsers: Chrome, Firefox, Safari, Edge, IE11.</p>
                    </div> <!-- end card-body -->

                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header border-bottom d-flex align-items-center">
                        <h4 class="header-title">Scroll Color</h4>
                    </div> 

                    <div class="card-body pb-0">
                        <p class="text-muted">Just use data attribute <code>data-simplebar data-simplebar-primary</code> and add <code>max-height: **px</code> or fix height </p>
                    </div> <!-- end card-body -->

                    <div class="card-body py-0 mb-3 simplebar-height" data-simplebar data-simplebar-primary>
                        SimpleBar does only one thing: replace the browser's default scrollbar with a custom CSS-styled one without losing performances. Unlike some popular plugins, SimpleBar doesn't mimic scroll with Javascript, causing janks and strange scrolling behaviours... You keep the awesomeness of native scrolling...with a custom scrollbar!
                        <p>SimpleBar <strong>does NOT implement a custom scroll behaviour</strong>. It keeps the <strong>native</strong> <code>overflow: auto</code> scroll and <strong>only</strong> replace the scrollbar visual appearance.</p>
                        <h5>Design it as you want</h5>
                        <p>SimpleBar uses pure CSS to style the scrollbar. You can easily customize it as you want! Or even have multiple style on the same page...or just keep the default style ("Mac OS" scrollbar style). </p>
                        <h5>Lightweight and performant</h5>
                        <p>Only 6kb minified. SimpleBar doesn't use Javascript to handle scrolling. You keep the performances/behaviours of the native scroll.</p>
                        <h5>Supported everywhere</h5>
                        <p class="mb-0">SimpleBar has been tested on the following browsers: Chrome, Firefox, Safari, Edge, IE11.</p>
                    </div> <!-- end card-body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

        </div>
        <!-- end row -->
    </div>
</div>
      
@endsection
