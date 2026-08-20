<?php $page = 'call-history'; ?>
@extends('layout.mainlayout')
@section('content')
<div class="page-wrapper">
    <div class="content">
        
        <!-- Page Header -->
        <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
            <div class="my-auto mb-2">
                <h3 class="page-title mb-1">Call History</h3>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="{{url('index')}}">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item">
                            Application
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Call History</li>
                    </ol>
                </nav>
            </div>
        </div>
        <!-- /Page Header -->

        <!-- Call List -->
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
                <h4 class="mb-3">Call History</h4>
            </div>
            <div class="card-body p-0 py-3">
                <div class="custom-datatable-filter table-responsive">
                    <table class="table datatable">
                        <thead class="thead-light">
                            <tr>
                                <th class="no-sort">
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox" id="select-all">
                                    </div>
                                </th>
                                <th>Username</th>
                                <th>Phone Number</th>
                                <th>Call Type</th>
                                <th>Duration</th>
                                <th>Date & Time</th>
                                <th class="no-sort">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#" class="avatar avatar-md">
                                            <img src="{{URL::asset('build/img/users/user-01.jpg')}}" class="img-fluid rounded-circle" alt="img">
                                        </a>
                                        <div class="ms-2">
                                            <p class="text-dark mb-0"><a href="#">Arroon</a></p>
                                        </div>
                                    </div>
                                </td>
                                <td>+1 25182 94528</td>
                                <td ><i class="ti ti-phone me-1"></i><i class="ti ti-arrow-down-left text-success me-1"></i>Incoming Call</td>
                                <td>00.25</td>
                                <td>19 Jan 2023 - 01:16 PM</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#user-profile-new" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center  justify-content-center rounded-circle  p-0 me-2">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                        <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex  align-items-center justify-content-center rounded-circle p-0 me-3">
                                            <i class="ti ti-trash-x"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#" class="avatar avatar-md">
                                            <img src="{{URL::asset('build/img/users/user-02.jpg')}}" class="img-fluid rounded-circle" alt="img">
                                        </a>
                                        <div class="ms-2">
                                            <p class="text-dark mb-0"><a href="#">Rose</a></p>
                                        </div>
                                    </div>
                                </td>
                                <td>+1 93016 81932</td>
                                <td ><i class="ti ti-phone me-1"></i><i class="ti ti-arrow-up-left text-danger me-1"></i>Missed Call	</td>
                                <td>00.00</td>
                                <td>24 Jan 2023 - 02:50 PM</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#user-profile-new" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center  justify-content-center rounded-circle  p-0 me-2">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                        <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex  align-items-center justify-content-center rounded-circle p-0 me-3">
                                            <i class="ti ti-trash-x"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#" class="avatar avatar-md">
                                            <img src="{{URL::asset('build/img/users/user-03.jpg')}}" class="img-fluid rounded-circle" alt="img">
                                        </a>
                                        <div class="ms-2">
                                            <p class="text-dark mb-0"><a href="#">
                                                Benjamin</a></p>
                                        </div>
                                    </div>
                                </td>
                                <td>+1 25182 94528	</td>
                                <td ><i class="ti ti-video me-1"></i><i class="ti ti-arrow-down-left text-success me-1"></i>Incoming Call</td>
                                <td>00.15</td>
                                <td>03 Feb 2023 - 10:37 AM</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#user-profile-new" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center  justify-content-center rounded-circle  p-0 me-2">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                        <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex  align-items-center justify-content-center rounded-circle p-0 me-3">
                                            <i class="ti ti-trash-x"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#" class="avatar avatar-md">
                                            <img src="{{URL::asset('build/img/users/user-04.jpg')}}" class="img-fluid rounded-circle" alt="img">
                                        </a>
                                        <div class="ms-2">
                                            <p class="text-dark mb-0"><a href="#">
                                                Kaitlin</a></p>
                                        </div>
                                    </div>
                                </td>
                                <td>+1 70328 96042	</td>
                                <td ><i class="ti ti-video me-1"></i><i class="ti ti-arrow-up-left text-danger me-1"></i>Missed Call	</td>
                                <td>00.15</td>
                                <td>03 Feb 2023 - 10:37 AM</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#user-profile-new" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center  justify-content-center rounded-circle  p-0 me-2">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                        <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex  align-items-center justify-content-center rounded-circle p-0 me-3">
                                            <i class="ti ti-trash-x"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#" class="avatar avatar-md">
                                            <img src="{{URL::asset('build/img/users/user-05.jpg')}}" class="img-fluid rounded-circle" alt="img">
                                        </a>
                                        <div class="ms-2">
                                            <p class="text-dark mb-0"><a href="#">
                                                Lilly</a></p>
                                        </div>
                                    </div>
                                </td>
                                <td>+1 83610 45175	</td>
                                <td ><i class="ti ti-video me-1"></i><i class="ti ti-arrow-up-right text-success me-1"></i>Outgoing Call	</td>
                                <td>00.45</td>
                                <td>14 Mar 2023 - 09:12 AM</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#user-profile-new" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center  justify-content-center rounded-circle  p-0 me-2">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                        <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex  align-items-center justify-content-center rounded-circle p-0 me-3">
                                            <i class="ti ti-trash-x"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#" class="avatar avatar-md">
                                            <img src="{{URL::asset('build/img/users/user-05.jpg')}}" class="img-fluid rounded-circle" alt="img">
                                        </a>
                                        <div class="ms-2">
                                            <p class="text-dark mb-0"><a href="#">Freda</a></p>
                                        </div>
                                    </div>
                                </td>
                                <td>+1 46217 84294	</td>
                                <td ><i class="ti ti-video me-1"></i><i class="ti ti-arrow-up-left text-success me-1"></i>Incoming Call	</td>
                                <td>00.30	</td>
                                <td>27 Mar 2023 - 04:32 PM</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#user-profile-new" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center  justify-content-center rounded-circle  p-0 me-2">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                        <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex  align-items-center justify-content-center rounded-circle p-0 me-3">
                                            <i class="ti ti-trash-x"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#" class="avatar avatar-md">
                                            <img src="{{URL::asset('build/img/users/user-06.jpg')}}" class="img-fluid rounded-circle" alt="img">
                                        </a>
                                        <div class="ms-2">
                                            <p class="text-dark mb-0"><a href="#">Alwin</a></p>
                                        </div>
                                    </div>
                                </td>
                                <td>+1 62573 84301	</td>
                                <td ><i class="ti ti-video me-1"></i><i class="ti ti-arrow-up-right text-success me-1"></i>Outgoing Call	</td>
                                <td>01.17</td>
                                <td>13 Apr 2023 - 02:46 PM</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#user-profile-new" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center  justify-content-center rounded-circle  p-0 me-2">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                        <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex  align-items-center justify-content-center rounded-circle p-0 me-3">
                                            <i class="ti ti-trash-x"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#" class="avatar avatar-md">
                                            <img src="{{URL::asset('build/img/users/user-08.jpg')}}" class="img-fluid rounded-circle" alt="img">
                                        </a>
                                        <div class="ms-2">
                                            <p class="text-dark mb-0"><a href="#">Maybelle</a></p>
                                        </div>
                                    </div>
                                </td>
                                <td>+1 14693 63826	</td>
                                <td ><i class="ti ti-video me-1"></i><i class="ti ti-arrow-down-left text-danger me-1"></i>Missed Call	</td>
                                <td>00.00	</td>
                                <td>11 Apr 2023 - 10:29 AM</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#user-profile-new" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center  justify-content-center rounded-circle  p-0 me-2">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                        <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex  align-items-center justify-content-center rounded-circle p-0 me-3">
                                            <i class="ti ti-trash-x"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#" class="avatar avatar-md">
                                            <img src="{{URL::asset('build/img/users/user-09.jpg')}}" class="img-fluid rounded-circle" alt="img">
                                        </a>
                                        <div class="ms-2">
                                            <p class="text-dark mb-0"><a href="#">Ellen</a></p>
                                        </div>
                                    </div>
                                </td>
                                <td>+1 83710 43827	</td>
                                <td ><i class="ti ti-phone me-1"></i><i class="ti ti-arrow-down-left text-success me-1"></i>Incoming Call	</td>
                                <td>00.50</td>
                                <td>14 May 2023 - 03:06 PM</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#user-profile-new" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center  justify-content-center rounded-circle  p-0 me-2">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                        <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex  align-items-center justify-content-center rounded-circle p-0 me-3">
                                            <i class="ti ti-trash-x"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#" class="avatar avatar-md">
                                            <img src="{{URL::asset('build/img/users/user-10.jpg')}}" class="img-fluid rounded-circle" alt="img">
                                        </a>
                                        <div class="ms-2">
                                            <p class="text-dark mb-0"><a href="#">Grace
                                            </a></p>
                                        </div>
                                    </div>
                                </td>
                                <td>+1 14693 63826	</td>
                                <td ><i class="ti ti-video me-1"></i><i class="ti ti-arrow-down-left text-danger me-1"></i>Missed Call	</td>
                                <td>00.00	</td>
                                <td>29 May 2023 - 11:23 AM
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#user-profile-new" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center  justify-content-center rounded-circle  p-0 me-2">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                        <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex  align-items-center justify-content-center rounded-circle p-0 me-3">
                                            <i class="ti ti-trash-x"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- /Call List -->

    </div>
</div>
@component('components.modal-popup')        
@endcomponent
@endsection