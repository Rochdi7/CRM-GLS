<?php $page = 'staff-leaves'; ?>
@extends('layout.mainlayout')
@section('content')
<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content">
        <div class="row">
            <!-- Page Header -->
            <div class="col-md-12">
                <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
                    <div class="my-auto mb-2">
                        <h3 class="page-title mb-1">Staff Details</h3>
                        <nav>
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item">
                                    <a href="{{url('index')}}">Dashboard</a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="{{url('students')}}">HRM</a>
                                </li>
                                <li class="breadcrumb-item active" aria-current="page">Staff Details</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="d-flex my-xl-auto right-content align-items-center  flex-wrap">
                        <a href="{{url('edit-staff')}}" class="btn btn-primary d-flex align-items-center mb-2"><i
                                class="ti ti-edit-circle me-2"></i>Edit Staff</a>
                    </div>
                </div>

            </div>
            <!-- /Page Header -->
            <div class="col-xxl-3 col-lg-4 theiaStickySidebar">
                <div class="card border-white">
                    <div class="card-header">
                        <div class="d-flex align-items-center  row-gap-3">
                            <div
                                class="d-flex align-items-center justify-content-center avatar avatar-xxl border border-dashed me-2 flex-shrink-0 text-dark frames">
                                <img src="{{URL::asset('build/img/profiles/avatar-27.jpg')}}" class="img-fluid" alt="img">
                            </div>
                            <div>
                                <span class="badge badge-soft-success d-inline-flex align-items-center mb-1"><i
                                        class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
                                <h5 class="mb-1">Kevin Larry</h5>
                                <p class="text-primary m-0">AD1256589</p>
                                <p class="p-0">Joined On : 10 Mar 2024</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <h5 class="mb-3">Basic Information</h5>
                        <dl class="row mb-0">
                            <dt class="col-6 fw-normal mb-3">Staff ID</dt>
                            <dd class="col-6 text-dark mb-3">35013</dd>
                            <dt class="col-6 fw-normal mb-3">Gender</dt>
                            <dd class="col-6 text-dark mb-3">Male</dd>
                            <dt class="col-6 fw-normal mb-3">Designation</dt>
                            <dd class="col-6 text-dark mb-3">25 Jan 2008</dd>
                            <dt class="col-6 fw-normal mb-3">Department</dt>
                            <dd class="col-6 text-dark mb-3">Technical Lead</dd>
                            <dt class="col-6 fw-normal mb-3">Date Of Birth</dt>
                            <dd class="col-6 text-dark mb-3">Admin</dd>
                            <dt class="col-6 fw-normal mb-3">Blood Group</dt>
                            <dd class="col-6 text-dark mb-3">15 Aug 1987</dd>
                            <dt class="col-6 fw-normal mb-3">Blood Group</dt>
                            <dd class="col-6 text-dark mb-3">O+</dd>
                            <dt class="col-6 fw-normal mb-3">Mother tongue</dt>
                            <dd class="col-6 text-dark mb-3">English</dd>
                            <dt class="col-6 fw-normal mb-0">Language</dt>
                            <dd class="col-6 text-dark mb-0"><span
                                    class="badge badge-light text-dark me-2">English</span><span
                                    class="badge badge-light text-dark">Spanish</span></dd>
                        </dl>
                    </div>
                </div>
                <div class="card border-white">
                    <div class="card-body">
                        <h5 class="mb-3">Primary Contact Info</h5>
                        <div class="d-flex align-items-center mb-3">
                            <span
                                class="avatar avatar-md bg-light-300 rounded me-2 flex-shrink-0 text-default"><i
                                    class="ti ti-phone"></i></span>
                            <div>
                                <span class="fs-12 mb-1">Phone Number</span>
                                <p class="text-dark">+1 46548 84498</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <span
                                class="avatar avatar-md bg-light-300 rounded me-2 flex-shrink-0 text-default"><i
                                    class="ti ti-mail"></i></span>
                            <div>
                                <span class="fs-12 mb-1">Email Address</span>
                                <p class="text-dark">jan@example.com</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="col-xxl-9 col-lg-8">
                <div class="col-md-12">
                    <ul class="nav nav-tabs nav-tabs-bottom mb-4">
                        <li>
                            <a href="{{url('staff-details')}}" class="nav-link "><i
                                    class="ti ti-info-square-rounded me-2"></i>Basic
                                Details</a>
                        </li>
                        <li>
                            <a href="{{url('staff-payroll')}}" class="nav-link "><i
                                    class="ti ti-file-dollar me-2"></i>Payroll</a>
                        </li>
                        <li>
                            <a href="{{url('staff-leaves')}}" class="nav-link active"><i
                                    class="ti ti-calendar-due me-2"></i>Leaves</a>
                        </li>
                        <li>
                            <a href="{{url('staffs-attendance')}}" class="nav-link"><i
                                    class="ti ti-calendar-due me-2"></i>Attendance</a>
                        </li>
                    </ul>
                </div>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="pills-leave" role="tabpanel"
                        aria-labelledby="pills-leave">
                        <div class="row gx-3">
                            <div class="col-lg-6 col-xxl-3 d-flex">
                                <div class="card flex-fill">
                                    <div class="card-body">
                                        <h5 class="mb-2">Medical Leave (10)</h5>
                                        <div class="d-flex align-items-center flex-wrap">
                                            <p class="border-end pe-2 me-2 mb-0">Used : 5</p>
                                            <p class="mb-0">Available : 5</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6 col-xxl-3 d-flex">
                                <div class="card flex-fill">
                                    <div class="card-body">
                                        <h5 class="mb-2">Casual Leave (12)</h5>
                                        <div class="d-flex align-items-center flex-wrap">
                                            <p class="border-end pe-2 me-2 mb-0">Used : 1</p>
                                            <p class="mb-0">Available : 11</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6 col-xxl-3 d-flex">
                                <div class="card flex-fill">
                                    <div class="card-body">
                                        <h5 class="mb-2">Maternity Leave (10)</h5>
                                        <div class="d-flex align-items-center flex-wrap">
                                            <p class="border-end pe-2 me-2 mb-0">Used : 0</p>
                                            <p class="mb-0">Available : 10</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6 col-xxl-3 d-flex">
                                <div class="card flex-fill">
                                    <div class="card-body">
                                        <h5 class="mb-2">Paternity Leave (0)</h5>
                                        <div class="d-flex align-items-center flex-wrap">
                                            <p class="border-end pe-2 me-2 mb-0">Used : 0</p>
                                            <p class="mb-0">Available : 0</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <!-- Leaves List -->
                            <div
                                class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
                                <h4 class="mb-3">Leaves</h4>
                                <a href="#" data-bs-target="#apply_leave" data-bs-toggle="modal"
                                    class="btn btn-primary d-inline-flex align-items-center mb-3"><i
                                        class="ti ti-calendar-event me-2"></i>Apply Leave</a>
                            </div>
                            <div class="card-body p-0 py-3">
                                <div class="custom-datatable-filter table-responsive">
                                    <table class="table datatable">
                                        <thead class="thead-light">
                                            <tr>
                                                <th class="no-sort">
                                                    <label class="checkboxs"><input type="checkbox"
                                                            id="select-all"><span
                                                            class="checkmarks"></span></label>
                                                </th>
                                                <th class="no-sort">Leave Type</th>
                                                <th class="no-sort">Leave Date</th>
                                                <th class="no-sort">No of Days</th>
                                                <th class="no-sort">Applied On</th>
                                                <th class="no-sort">Status</th>
                                                <th class="no-sort">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>
                                                    <label class="checkboxs">
                                                        <input type="checkbox">
                                                        <span class="checkmarks"></span>
                                                    </label>
                                                </td>
                                                <td>Medical Leave</td>
                                                <td>05 May 2024 - 09 may 2024</td>
                                                <td>5</td>
                                                <td>05 May 2024 </td>
                                                <td>
                                                    <span
                                                        class="badge badge-soft-success d-inline-flex align-items-center"><i
                                                            class="ti ti-circle-filled fs-5 me-1"></i>Approved</span>
                                                </td>
                                                <td>
                                                    <div class="dropdown">
                                                        <a href="#" class="action-icon "
                                                            data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="ti ti-dots-vertical"></i>
                                                        </a>
                                                        <div class="dropdown-menu dropdown-menu-right">
                                                            <ul>
                                                                <li>
                                                                    <a class="dropdown-item"
                                                                        href="javascript:void(0);"><i
                                                                            class="ti ti-edit-circle"></i>Edit
                                                                    </a>
                                                                </li>

                                                                <li>
                                                                    <a class="dropdown-item" href="#"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#delete-modal"><i
                                                                            class="ti ti-trash-x"></i>
                                                                        Delete</a>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <label class="checkboxs">
                                                        <input type="checkbox">
                                                        <span class="checkmarks"></span>
                                                    </label>
                                                </td>
                                                <td>Casual Leave</td>
                                                <td>07 May 2024 - 07 may 2024</td>
                                                <td>1</td>
                                                <td>07 May 2024 </td>
                                                <td>
                                                    <span
                                                        class="badge badge-soft-success d-inline-flex align-items-center"><i
                                                            class="ti ti-circle-filled fs-5 me-1"></i>Approved</span>
                                                </td>
                                                <td>
                                                    <div class="dropdown">
                                                        <a href="#" class="action-icon "
                                                            data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="ti ti-dots-vertical"></i>
                                                        </a>
                                                        <div class="dropdown-menu dropdown-menu-right">
                                                            <ul>
                                                                <li>
                                                                    <a class="dropdown-item"
                                                                        href="javascript:void(0);"><i
                                                                            class="ti ti-edit-circle"></i>Edit
                                                                    </a>
                                                                </li>

                                                                <li>
                                                                    <a class="dropdown-item" href="#"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#delete-modal"><i
                                                                            class="ti ti-trash-x"></i>
                                                                        Delete</a>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <label class="checkboxs">
                                                        <input type="checkbox">
                                                        <span class="checkmarks"></span>
                                                    </label>
                                                </td>
                                                <td>Special Leave</td>
                                                <td>09 May 2024 - 09 may 2024</td>
                                                <td>1</td>
                                                <td>09 May 2024 </td>
                                                <td>
                                                    <span
                                                        class="badge badge-soft-pending d-inline-flex align-items-center"><i
                                                            class="ti ti-circle-filled fs-5 me-1"></i>Pending</span>
                                                </td>
                                                <td>
                                                    <div class="dropdown">
                                                        <a href="#" class="action-icon "
                                                            data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="ti ti-dots-vertical"></i>
                                                        </a>
                                                        <div class="dropdown-menu dropdown-menu-right">
                                                            <ul>
                                                                <li>
                                                                    <a class="dropdown-item"
                                                                        href="javascript:void(0);"><i
                                                                            class="ti ti-edit-circle"></i>Edit
                                                                    </a>
                                                                </li>

                                                                <li>
                                                                    <a class="dropdown-item" href="#"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#delete-modal"><i
                                                                            class="ti ti-trash-x"></i>
                                                                        Delete</a>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <label class="checkboxs">
                                                        <input type="checkbox">
                                                        <span class="checkmarks"></span>
                                                    </label>
                                                </td>
                                                <td>Casual Leave</td>
                                                <td>08 May 2024 - 08 may 2024</td>
                                                <td>1</td>
                                                <td>04 May 2024 </td>
                                                <td>
                                                    <span
                                                        class="badge badge-soft-success d-inline-flex align-items-center"><i
                                                            class="ti ti-circle-filled fs-5 me-1"></i>Approved</span>
                                                </td>
                                                <td>
                                                    <div class="dropdown">
                                                        <a href="#" class="action-icon "
                                                            data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="ti ti-dots-vertical"></i>
                                                        </a>
                                                        <div class="dropdown-menu dropdown-menu-right">
                                                            <ul>
                                                                <li>
                                                                    <a class="dropdown-item"
                                                                        href="javascript:void(0);"><i
                                                                            class="ti ti-edit-circle"></i>Edit
                                                                    </a>
                                                                </li>

                                                                <li>
                                                                    <a class="dropdown-item" href="#"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#delete-modal"><i
                                                                            class="ti ti-trash-x"></i>
                                                                        Delete</a>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <label class="checkboxs">
                                                        <input type="checkbox">
                                                        <span class="checkmarks"></span>
                                                    </label>
                                                </td>
                                                <td>Medical Leave</td>
                                                <td>08 May 2024 - 11 may 2024</td>
                                                <td>4</td>
                                                <td>08 May 2024 </td>
                                                <td>
                                                    <span
                                                        class="badge badge-soft-pending d-inline-flex align-items-center"><i
                                                            class="ti ti-circle-filled fs-5 me-1"></i>Pending</span>
                                                </td>
                                                <td>
                                                    <div class="dropdown">
                                                        <a href="#" class="action-icon "
                                                            data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="ti ti-dots-vertical"></i>
                                                        </a>
                                                        <div class="dropdown-menu dropdown-menu-right">
                                                            <ul>
                                                                <li>
                                                                    <a class="dropdown-item"
                                                                        href="javascript:void(0);"><i
                                                                            class="ti ti-edit-circle"></i>Edit
                                                                    </a>
                                                                </li>

                                                                <li>
                                                                    <a class="dropdown-item" href="#"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#delete-modal"><i
                                                                            class="ti ti-trash-x"></i>
                                                                        Delete</a>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <label class="checkboxs">
                                                        <input type="checkbox">
                                                        <span class="checkmarks"></span>
                                                    </label>
                                                </td>
                                                <td>Casual Leave</td>
                                                <td>20 May 2024 - 20 may 2024</td>
                                                <td>1</td>
                                                <td>19 May 2024 </td>
                                                <td>
                                                    <span
                                                        class="badge badge-soft-pending d-inline-flex align-items-center"><i
                                                            class="ti ti-circle-filled fs-5 me-1"></i>Pending</span>
                                                </td>
                                                <td>
                                                    <div class="dropdown">
                                                        <a href="#" class="action-icon "
                                                            data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="ti ti-dots-vertical"></i>
                                                        </a>
                                                        <div class="dropdown-menu dropdown-menu-right">
                                                            <ul>
                                                                <li>
                                                                    <a class="dropdown-item"
                                                                        href="javascript:void(0);"><i
                                                                            class="ti ti-edit-circle"></i>Edit
                                                                    </a>
                                                                </li>

                                                                <li>
                                                                    <a class="dropdown-item" href="#"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#delete-modal"><i
                                                                            class="ti ti-trash-x"></i>
                                                                        Delete</a>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <!-- /Leaves List -->
                        </div>

                    </div>

                </div>

            </div>
        </div>
    </div>
</div>
<!-- /Page Wrapper -->



@component('components.modal-popup')        
@endcomponent
@endsection