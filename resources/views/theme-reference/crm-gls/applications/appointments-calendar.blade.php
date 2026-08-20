<?php $page = 'appointments-calendar'; ?>
@extends('layout.mainlayout')
@section('content')
<div class="page-wrapper">
    <div class="content">

        <div class="list-views">
            <ul>
                <li>
                    <a href="{{url('appointments')}}">List View</a>
                </li>
                <li>
                    <a href="{{url('appointments-calendar')}}" class="active">Calendar View</a>
                </li>
            </ul>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <div class="page-title">
                <h4>Total Appointments<span class="badge badge-warning">1000</span></h4>
            </div>
            <ul>
                <li>
                    <a href="#"><i class="ti ti-refresh"></i></a>
                </li>
                <li>
                    <a href="#"><i class="ti ti-layout-sidebar-left-expand"></i></a>
                </li>
                <li>
                    <a href="#"><i class="ti ti-printer"></i></a>
                </li>
                <li>
                    <a href="#"><i class="ti ti-cloud-download"></i></a>
                </li>
            </ul>
            <div class="page-btn">
                <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add_appointment">Add New Appointment<i class="ti ti-square-rounded-plus ms-2"></i></a>
            </div>
        </div>
        <!-- /Page Header -->

        <div class="row">

            <div class="col-xl-12">

                <!-- Appointments -->
                <div class="card main-card flex-fill">
                    <div class="card-body">
                        <div id="calendar-appointment"></div>
                    </div>
                </div>
                <!-- /Appointments -->

            </div>
        </div>
    </div>
</div>

<!-- View Details -->
<div class="modal fade" id="view-details">
    <div class="modal-dialog modal-dialog-centered custom-modal">
        <div class="modal-content">
            <div class="modal-wrapper">
                <div class="modal-header custom-modal-header">
                    <div class="page-title">
                        <h4>Appointment Details</h4>
                    </div>
                    <div class="d-flex align-items-center">
                        <ul class="modal-icons">
                            <li>
                                <a href="#"><i class="ti ti-printer"></i></a>
                            </li>
                            <li>
                                <a href="#"><i class="ti ti-cloud-download"></i></a>
                            </li>
                        </ul>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <span class="modal-close-btn"><i class="ti ti-x"></i></span>
                        </button>
                    </div>
                </div>
                <div class="details-modal">
                    <div class="appointment-user-wrap">
                        <div class="appointment-user">
                            <img src="{{URL::asset('build/img/patients/patient-01.jpg')}}" alt="img">
                            <div class="appoint-user-info">
                                <h5>Gifford</h5>
                                <p>Male  25</p>
                            </div>
                        </div>
                        <div class="appointment-type">
                            <h6><i class="ti ti-video"></i>Video</h6>
                            <span class="badge badge-info">Paid : Cash</span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="reason-list">
                                <h6>Consultation with</h6>
                                <h5>Dr. Laird</h5>
                                <p>Dermitologist</p>
                            </div>
                            <div class="reason-list">
                                <h6>Date & Time</h6>
                                <h5>$500 / 1 Hr</h5>
                                <p>24th Jan 2024, 09:00 AM to 10:00 PM</p>
                            </div>
                            <div class="reason-list visit-list">
                                <h6>Reason</h6>
                                <ul>
                                    <li>Fever</li>
                                    <li>Stomach pain</li>
                                    <li>Drowsiness</li>
                                </ul>
                            </div>
                            <div class="reason-list">
                                <h6>Notes</h6>
                                <p>Provide detailed instructions on how to use prescribed medications. Include dosage, frequency, and any specific application guidelines.</p>
                            </div>
                        </div>
                    </div>
                </div>						
                <div class="dlete-modal-btn text-end">
                    <a href="#" class="btn btn-outline-primary me-2" data-bs-dismiss="modal">Cancel</a>
                    <a href="{{url('consultation')}}" class="btn btn-primary d-inline-flex align-items-center"><i class="ti ti-video me-2"></i>Start Consultation</a>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /View Details -->


@component('components.modal-popup')        
@endcomponent

@endsection