<?php $page = 'delete-account'; ?>
@extends('layout.mainlayout')
@section('content')
	<!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content">
            @component('components.breadcrumb')
            @slot('title')
            Delete Account Request
            @endslot
            @slot('item1')
            Dashboard
            @endslot
            @slot('item2')
            User Management
            @endslot
            @slot('item3')
            Delete Account Request
            @endslot
           @endcomponent
           

            <!-- Filter Section -->
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
                    <h4 class="mb-3">Delete Account Request List</h4>
                    <div class="d-flex align-items-center flex-wrap">
                        <div class="input-icon-start mb-3 me-2 position-relative">
                            <span class="icon-addon">
                                <i class="ti ti-calendar"></i>
                            </span>
                            <input type="text" class="form-control  date-range bookingrange" placeholder="Select"
                                value="Academic Year : 2024 / 2025">
                        </div>
                        <div class="dropdown mb-3">
                            <a href="javascript:void(0);" class="btn btn-outline-light bg-white dropdown-toggle"
                                data-bs-toggle="dropdown"><i class="ti ti-sort-ascending-2 me-2"></i>Sort by A-Z
                            </a>
                            <ul class="dropdown-menu p-3">
                                <li>
                                    <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                        Ascending
                                    </a>
                                </li>
                                <li>
                                    <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                        Descending
                                    </a>
                                </li>
                                <li>
                                    <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                        Recently Viewed
                                    </a>
                                </li>
                                <li>
                                    <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                        Recently Added
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0 py-3">
                    <!-- Delete Account List -->
                    <div class="custom-datatable-filter table-responsive">
                        <table class="table datatable">
                            <thead class="thead-light">
                                <tr>
                                    <th class="no-sort">
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox" id="select-all">
                                        </div>
                                    </th>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Requisition Date</th>
                                    <th>Delete Request Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td><a href="#" class="link-primary">U461893</a></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="{{url('student-details')}}" class="avatar avatar-md"><img src="{{URL::asset('build/img/students/student-01.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a href="{{url('student-details')}}">Janet</a>
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>25 Mar 2024</td>
                                    <td>27 Mar 2024</td>
                                    <td>
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#delete-modal"><span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                            class="ti ti-circle-filled fs-5 me-1"></i>Active</span></a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td><a href="#" class="link-primary">U461892</a></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="{{url('student-details')}}" class="avatar avatar-md"><img src="{{URL::asset('build/img/students/student-02.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a href="{{url('staff-details')}}">Joann</a></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>18 Mar 2024</td>
                                    <td>20 Mar 2024</td>
                                    <td>
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#delete-modal"><span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                            class="ti ti-circle-filled fs-5 me-1"></i>Active</span></a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td><a href="#" class="link-primary">U461891</a></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="{{url('student-details')}}" class="avatar avatar-md"><img src="{{URL::asset('build/img/students/student-03.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a href="{{url('student-details')}}">Kathleen</a></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>14 Mar 2024</td>
                                    <td>16 Mar 2024</td>
                                    <td>
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#delete-modal"><span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                            class="ti ti-circle-filled fs-5 me-1"></i>Active</span></a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td><a href="#" class="link-primary">U461890</a></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="{{url('student-details')}}" class="avatar avatar-md"><img src="{{URL::asset('build/img/students/student-04.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a href="{{url('student-details')}}">Gifford</a>
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>27 Feb 2024</td>
                                    <td>26 Feb 2024</td>
                                    <td>
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#delete-modal"><span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                            class="ti ti-circle-filled fs-5 me-1"></i>Active</span></a>
                                    </td>

                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td><a href="#" class="link-primary">U461889</a></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="{{url('student-details')}}" class="avatar avatar-md"><img src="{{URL::asset('build/img/students/student-05.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a href="{{url('student-details')}}">Lisa</a>
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>13 Feb 2024</td>
                                    <td>15 Feb 2024</td>
                                    <td>
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#delete-modal"><span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                            class="ti ti-circle-filled fs-5 me-1"></i>Active</span></a>
                                    </td>

                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td><a href="#" class="link-primary">U461888</a></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="{{url('student-details')}}" class="avatar avatar-md"><img src="{{URL::asset('build/img/students/student-06.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a href="{{url('student-details')}}">Ralph</a>
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>11 Feb 2024</td>
                                    <td>13 Feb 2024</td>
                                    <td>
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#delete-modal"><span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                            class="ti ti-circle-filled fs-5 me-1"></i>Active</span></a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td><a href="#" class="link-primary">U461887</a></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="{{url('student-details')}}" class="avatar avatar-md"><img src="{{URL::asset('build/img/students/student-07.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a href="{{url('student-details')}}">Julie</a>
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>26 Jan 2024</td>
                                    <td>28 Jan 2024</td>
                                    <td>
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#delete-modal"><span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                            class="ti ti-circle-filled fs-5 me-1"></i>Active</span></a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td><a href="#" class="link-primary">U461886</a></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="{{url('student-details')}}" class="avatar avatar-md"><img src="{{URL::asset('build/img/students/student-08.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a href="{{url('student-details')}}">Ryan</a>
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>19 Jan 2024</td>
                                    <td>21 Jan 2024</td>
                                    <td>
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#delete-modal"><span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                            class="ti ti-circle-filled fs-5 me-1"></i>Active</span></a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td><a href="#" class="link-primary">U461885</a></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="{{url('student-details')}}" class="avatar avatar-md"><img src="{{URL::asset('build/img/students/student-09.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a href="{{url('student-details')}}">Susan</a>
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>08 Jan 2024</td>
                                    <td>10 Jan 2024</td>
                                    <td>
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#delete-modal"><span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                            class="ti ti-circle-filled fs-5 me-1"></i>Active</span></a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td><a href="#" class="link-primary">U461884</a></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="{{url('student-details')}}" class="avatar avatar-md"><img src="{{URL::asset('build/img/students/student-09.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a href="{{url('student-details')}}">Susan</a>
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>22 Dec 2024</td>
                                    <td>24 Dec 2024</td>
                                    <td>
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#delete-modal"><span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                            class="ti ti-circle-filled fs-5 me-1"></i>Active</span></a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- /Delete Account List -->
                </div>

            </div>
            <!-- /Filter Section -->




            <div class="row align-items-center">
                <div class="col-md-12">
                    <div class="datatable-paginate mt-4"></div>
                </div>
            </div>

        </div>
    </div>
    <!-- /Page Wrapper -->
@component('components.modal-popup')        
@endcomponent
@endsection