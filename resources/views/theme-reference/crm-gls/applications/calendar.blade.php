<?php $page = 'calendar'; ?>
@extends('layout.mainlayout')
@section('content')
<div class="page-wrapper">
    <div class="content">

        <!-- Page Header -->
        <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
            <div class="my-auto mb-2">
                <h3 class="page-title mb-1">Calendar</h3>
                 <nav>
                       <ol class="breadcrumb mb-0">
                          <li class="breadcrumb-item">
                            <a href="{{url('index')}}">Dashboard</a>
                          </li>
                          <li class="breadcrumb-item">
                            <a href="javascript:void(0);">Application</a>
                          </li>
                          <li class="breadcrumb-item active" aria-current="page">Calendar
                        </li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex my-xl-auto right-content align-items-center flex-wrap">                
                <div class="mb-2">								
                    <a href="javascript:void(0);" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add_event">Create Event</a>
                </div>
            </div>
        </div>
        <!-- /Page Header -->
         
        <div class="row">

            <!-- Drag & Drop Event -->
            <div class="col-lg-3 col-md-4">
                <h4 class="mb-3">Drag & Drop Event</h4>
                <div id="calendar-events" class="mb-3">
                    <div class="calendar-events rounded" data-class="bg-info"><i class="ti ti-circle-filled fs-8 text-info"></i> My Event One</div>
                    <div class="calendar-events rounded" data-class="bg-success"><i class="ti ti-circle-filled fs-8 text-success"></i> My Event Two</div>
                    <div class="calendar-events rounded" data-class="bg-danger"><i class="ti ti-circle-filled fs-8 text-danger"></i> My Event Three</div>
                    <div class="calendar-events rounded" data-class="bg-warning"><i class="ti ti-circle-filled fs-8 text-warning"></i> My Event Four</div>
                </div>
                <div class="form-check form-check mb-3">
                    <input class="form-check-input" type="checkbox" > Remove after drop
                </div>
                <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#add_new_event" class="btn mb-3 btn-primary d-flex align-items-center justify-content-center">
                    <i class="ti ti-plus me-1"></i>Add Category
                </a>
            </div>
            <!-- /Drag & Drop Event -->

            <!-- Calendar -->
            <div class="col-lg-9 col-md-8">
                <div class="card bg-white">
                    <div class="card-body">
                        <div id="calendar"></div>
                    </div>
                </div>
            </div>
            <!-- /Calendar -->

        </div>

    </div>
</div>

    @component('components.modal-popup')
    @endcomponent
@endsection
