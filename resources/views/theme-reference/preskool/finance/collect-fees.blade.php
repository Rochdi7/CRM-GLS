<?php $page = 'collect-fees'; ?>
@extends('layout.mainlayout')
@section('content')
 	<!-- Page Wrapper -->
     <div class="page-wrapper">
        <div class="content">

            @component('components.breadcrumb')
				@slot('title')
				Collect Fees
				@endslot
				@slot('item1')
				Dashboard
				@endslot
				@slot('item2')
				Management
                @endslot
				@slot('item3')
				Collect Fees
				@endslot
			   @endcomponent

            <!-- Fees List -->
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
                    <h4 class="mb-3">Fees List</h4>
                    <div class="d-flex align-items-center flex-wrap">
                        <div class="input-icon-start mb-3 me-2 position-relative">
                            <span class="icon-addon">
                                <i class="ti ti-calendar"></i>
                            </span>
                            <input type="text" class="form-control  date-range bookingrange" placeholder="Select"
                                value="Academic Year : 2024 / 2025">
                        </div>
                        <div class="dropdown mb-3 me-2">
                            <a href="javascript:void(0);" class="btn btn-outline-light bg-white dropdown-toggle"
                                data-bs-toggle="dropdown" data-bs-auto-close="outside"><i
                                    class="ti ti-filter me-2"></i>Filter</a>
                            <div class="dropdown-menu drop-width">
                                <form action="{{url('collect-fees')}}">
                                    <div class="d-flex align-items-center border-bottom p-3">
                                        <h4>Filter</h4>
                                    </div>
                                    <div class="p-3 border-bottom">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-0">
                                                    <label class="form-label">Admisson No</label>
                                                    <select class="select">
                                                        <option>Select</option>
                                                        <option>AD124556</option>
                                                        <option>AD124555</option>
                                                        <option>AD124554</option>
                                                        <option>AD124553</option>
                                                        <option>AD124552</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-0">
                                                    <label class="form-label">Roll No</label>
                                                    <select class="select">
                                                        <option>Select</option>
                                                        <option>55365</option>
                                                        <option>12454</option>
                                                        <option>65454</option>
                                                        <option>78787</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="mb-0">
                                                    <label class="form-label">Student</label>
                                                    <select class="select">
                                                        <option>Select</option>
                                                        <option>Janet</option>
                                                        <option>Joann</option>
                                                        <option>Kathleen</option>
                                                        <option>Gifford</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-0">
                                                    <label class="form-label">Class</label>
                                                    <select class="select">
                                                        <option>Select</option>
                                                        <option>III</option>
                                                        <option>IV</option>
                                                        <option>III</option>
                                                        <option>I</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-0">
                                                    <label class="form-label">Section</label>
                                                    <select class="select">
                                                        <option>Select</option>
                                                        <option>Janet</option>
                                                        <option>Joann</option>
                                                        <option>Kathleen</option>
                                                        <option>Gifford</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-0">
                                                    <label class="form-label">Amount</label>
                                                    <select class="select">
                                                        <option>Select</option>
                                                        <option>2000</option>
                                                        <option>156</option>
                                                        <option>645</option>
                                                        <option>456</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-0">
                                                    <label class="form-label">Last Date</label>
                                                    <select class="select">
                                                        <option>Select</option>
                                                        <option>15 May 2024</option>
                                                    </select>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                    <div class="p-3 d-flex align-items-center justify-content-end">
                                        <a href="#" class="btn btn-light me-3">Reset</a>
                                        <button type="submit" class="btn btn-primary">Apply</button>
                                    </div>
                                </form>
                            </div>
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

                    <!-- Student List -->
                    <div class="custom-datatable-filter table-responsive">
                        <table class="table datatable">
                            <thead class="thead-light">
                                <tr>
                                    <th class="no-sort">
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox" id="select-all">
                                        </div>
                                    </th>
                                    <th>Adm No</th>
                                    <th>Roll No</th>
                                    <th>Student</th>
                                    <th>Class </th>
                                    <th>Section</th>
                                    <th>Amount ($)</th>
                                    <th>Last Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td><a href="#" class="link-primary">AD124556</a></td>
                                    <td>55365</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="{{url('student-details')}}" class="avatar avatar-md"><img
                                                    src="{{URL::asset('build/img/students/student-01.jpg')}}"
                                                    class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a href="{{url('student-details')}}">Janet</a>
                                                </p>
                                                <span class="fs-12">III, A</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>III</td>
                                    <td>A</td>
                                    <td>2000</td>
                                    <td>15 May 2024</td>
                                    <td><span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                                class="ti ti-circle-filled fs-5 me-1"></i>Paid</span></td>
                                    <td><a href="{{url('student-fees')}}" class="btn btn-light">View Details</a></td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td><a href="#" class="link-primary">AD124555</a></td>
                                    <td>12454</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="{{url('student-details')}}" class="avatar avatar-md"><img
                                                    src="{{URL::asset('build/img/students/student-02.jpg')}}"
                                                    class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a href="{{url('student-details')}}">Joann</a>
                                                </p>
                                                <span class="fs-12">IV, B</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>IV</td>
                                    <td>B</td>
                                    <td>156</td>
                                    <td>15 May 2024</td>
                                    <td><span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                                class="ti ti-circle-filled fs-5 me-1"></i>Paid</span></td>
                                    <td><a href="{{url('student-fees')}}" class="btn btn-light">View Details</a></td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td><a href="#" class="link-primary">AD124554</a></td>
                                    <td>65454</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="{{url('student-details')}}" class="avatar avatar-md"><img
                                                    src="{{URL::asset('build/img/students/student-03.jpg')}}"
                                                    class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a
                                                        href="{{url('student-details')}}">Kathleen</a></p>
                                                <span class="fs-12">III, A</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>III</td>
                                    <td>A</td>
                                    <td>645</td>
                                    <td>15 May 2024</td>
                                    <td><span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                                class="ti ti-circle-filled fs-5 me-1"></i>Paid</span></td>
                                    <td><a href="{{url('student-fees')}}" class="btn btn-light">View Details</a></td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td><a href="#" class="link-primary">AD124553</a></td>
                                    <td>78787</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="{{url('student-details')}}" class="avatar avatar-md"><img
                                                    src="{{URL::asset('build/img/students/student-04.jpg')}}"
                                                    class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a
                                                        href="{{url('student-details')}}">Gifford</a></p>
                                                <span class="fs-12">I, B</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>I</td>
                                    <td>B</td>
                                    <td>456</td>
                                    <td>15 May 2024</td>
                                    <td><span class="badge badge-soft-danger d-inline-flex align-items-center"><i
                                                class="ti ti-circle-filled fs-5 me-1"></i>UnPaid</span></td>
                                    <td><a href="{{url('student-fees')}}" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#add_fees_collect">Collect Fees</a></td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td><a href="#" class="link-primary">AD124552</a></td>
                                    <td>31564</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="{{url('student-details')}}" class="avatar avatar-md"><img
                                                    src="{{URL::asset('build/img/students/student-05.jpg')}}"
                                                    class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a
                                                        href="{{url('student-details')}}">Lisa</a></p>
                                                <span class="fs-12">II, B</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>II</td>
                                    <td>B</td>
                                    <td>645</td>
                                    <td>15 May 2024</td>
                                    <td><span class="badge badge-soft-danger d-inline-flex align-items-center"><i
                                                class="ti ti-circle-filled fs-5 me-1"></i>UnPaid</span></td>
                                    <td><a href="{{url('student-fees')}}" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#add_fees_collect">Collect Fees</a></td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td><a href="#" class="link-primary">AD124551</a></td>
                                    <td>78456</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="{{url('student-details')}}" class="avatar avatar-md"><img
                                                    src="{{URL::asset('build/img/students/student-06.jpg')}}"
                                                    class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a
                                                        href="{{url('student-details')}}">Ralph</a></p>
                                                <span class="fs-12">III, B</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>III</td>
                                    <td>B</td>
                                    <td>156</td>
                                    <td>15 May 2024</td>
                                    <td><span class="badge badge-soft-danger d-inline-flex align-items-center"><i
                                                class="ti ti-circle-filled fs-5 me-1"></i>UnPaid</span></td>
                                    <td><a href="{{url('student-fees')}}" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#add_fees_collect">Collect Fees</a></td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td><a href="#" class="link-primary">AD124550</a></td>
                                    <td>67897</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="{{url('student-details')}}" class="avatar avatar-md"><img
                                                    src="{{URL::asset('build/img/students/student-07.jpg')}}"
                                                    class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a
                                                        href="{{url('student-details')}}">Julie</a></p>
                                                <span class="fs-12">V, A</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>V</td>
                                    <td>A</td>
                                    <td>645</td>
                                    <td>15 May 2024</td>
                                    <td><span class="badge badge-soft-danger d-inline-flex align-items-center"><i
                                                class="ti ti-circle-filled fs-5 me-1"></i>UnPaid</span></td>
                                    <td><a href="{{url('student-fees')}}" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#add_fees_collect">Collect Fees</a></td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td><a href="#" class="link-primary">AD124549</a></td>
                                    <td>47895</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="{{url('student-details')}}" class="avatar avatar-md"><img
                                                    src="{{URL::asset('build/img/students/student-08.jpg')}}"
                                                    class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a
                                                        href="{{url('student-details')}}">Ryan</a></p>
                                                <span class="fs-12">VI, A</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>VI</td>
                                    <td>A</td>
                                    <td>645</td>
                                    <td>15 May 2024</td>
                                    <td><span class="badge badge-soft-danger d-inline-flex align-items-center"><i
                                                class="ti ti-circle-filled fs-5 me-1"></i>UnPaid</span></td>
                                    <td><a href="{{url('student-fees')}}" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#add_fees_collect">Collect Fees</a></td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td><a href="#" class="link-primary">AD124548</a></td>
                                    <td>65547</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="{{url('student-details')}}" class="avatar avatar-md"><img
                                                    src="{{URL::asset('build/img/students/student-09.jpg')}}"
                                                    class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a
                                                        href="{{url('student-details')}}">Susan</a></p>
                                                <span class="fs-12">VIII, B</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>VIII</td>
                                    <td>B</td>
                                    <td>456</td>
                                    <td>15 May 2024</td>
                                    <td><span class="badge badge-soft-danger d-inline-flex align-items-center"><i
                                                class="ti ti-circle-filled fs-5 me-1"></i>UnPaid</span></td>
                                    <td><a href="{{url('student-fees')}}" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#add_fees_collect">Collect Fees</a></td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td><a href="#" class="link-primary">AD124547</a></td>
                                    <td>32145</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="{{url('student-details')}}" class="avatar avatar-md"><img
                                                    src="{{URL::asset('build/img/students/student-12.jpg')}}"
                                                    class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a
                                                        href="{{url('student-details')}}">Richard</a></p>
                                                <span class="fs-12">VII, B</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>VII</td>
                                    <td>B</td>
                                    <td>456</td>
                                    <td>15 May 2024</td>
                                    <td><span class="badge badge-soft-danger d-inline-flex align-items-center"><i
                                                class="ti ti-circle-filled fs-5 me-1"></i>UnPaid</span></td>
                                    <td><a href="{{url('student-fees')}}" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#add_fees_collect">Collect Fees</a></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- /Student List -->
                </div>
            </div>
            <!-- /Fees List -->

        </div>
    </div>
    <!-- /Page Wrapper -->
    @component('components.modal-popup')        
    @endcomponent
@endsection
