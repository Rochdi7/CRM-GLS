<?php $page = 'staff-payroll'; ?>
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
                            <dt class="col-6 fw-medium text-dark mb-3">Staff ID</dt>
                            <dd class="col-6  mb-3">35013</dd>
                            <dt class="col-6 fw-medium text-dark mb-3">Gender</dt>
                            <dd class="col-6  mb-3">Male</dd>
                            <dt class="col-6 fw-medium text-dark mb-3">Designation</dt>
                            <dd class="col-6  mb-3">25 Jan 2008</dd>
                            <dt class="col-6 fw-medium text-dark mb-3">Department</dt>
                            <dd class="col-6  mb-3">Technical Lead</dd>
                            <dt class="col-6 fw-medium text-dark mb-3">Date Of Birth</dt>
                            <dd class="col-6  mb-3">Admin</dd>
                            <dt class="col-6 fw-medium text-dark mb-3">Blood Group</dt>
                            <dd class="col-6  mb-3">15 Aug 1987</dd>
                            <dt class="col-6 fw-medium text-dark mb-3">Blood Group</dt>
                            <dd class="col-6  mb-3">O+</dd>
                            <dt class="col-6 fw-medium text-dark mb-3">Mother tongue</dt>
                            <dd class="col-6  mb-3">English</dd>
                            <dt class="col-6 fw-medium text-dark mb-0">Language</dt>
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
                            <span class="avatar avatar-md bg-light-300 rounded me-2 flex-shrink-0 text-default"><i class="ti ti-phone"></i></span>
                            <div>
                                <span class="fs-12 mb-1 fw-medium text-dark ">Phone Number</span>
                                <p>+1 46548 84498</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="avatar avatar-md bg-light-300 rounded me-2 flex-shrink-0 text-default"><i class="ti ti-mail"></i></span>
                            <div>
                                <span class="fs-12 mb-1 fw-medium text-dark ">Email Address</span>
                                <p>jan@example.com</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="col-xxl-9 col-lg-8">
                <div class="row">
                    <div class="col-md-12">
                        <ul class="nav nav-tabs nav-tabs-bottom mb-4">
                            <li>
                                <a href="{{url('staff-details')}}" class="nav-link "><i
                                        class="ti ti-info-square-rounded me-2"></i>Basic Details</a>
                            </li>
                            <li>
                                <a href="{{url('staff-payroll')}}" class="nav-link active"><i
                                        class="ti ti-file-dollar me-2"></i>Payroll</a>
                            </li>
                            <li>
                                <a href="{{url('staff-leaves')}}" class="nav-link"><i
                                        class="ti ti-calendar-due me-2"></i>Leaves</a>
                            </li>
                            <li>
                                <a href="{{url('staffs-attendance')}}" class="nav-link"><i
                                        class="ti ti-calendar-due me-2"></i>Attendance</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="students-leaves-tab">
                    <div class="row">
                        
                        <div class="col-md-6 col-xxl-3 d-flex">
                            <div
                                class="d-flex align-items-center justify-content-between rounded border p-3 mb-3 flex-fill">
                                <div class="ms-2">
                                    <p class="mb-1">Total Net Salary</p>
                                    <h5>$5,55,410</h5>
                                </div>
                                <span
                                    class="avatar avatar-lg bg-secondary-transparent rounded me-2 flex-shrink-0 text-secondary"><i
                                        class="ti ti-user-dollar fs-24"></i></span>
                            </div>
                        </div>
                        
                        
                        <div class="col-md-6 col-xxl-3 d-flex">
                            <div
                                class="d-flex align-items-center justify-content-between rounded border p-3 mb-3 flex-fill">
                                <div class="ms-2">
                                    <p class="mb-1">Total Gross Salary</p>
                                    <h5>$5,58,380</h5>
                                </div>
                                <span
                                    class="avatar avatar-lg bg-warning-transparent rounded me-2 flex-shrink-0 text-warning"><i
                                        class="ti ti-map-dollar fs-24"></i></span>
                            </div>
                        </div>
                        
                        <div class="col-md-6 col-xxl-3 d-flex">
                            <div
                                class="d-flex align-items-center justify-content-between rounded border p-3 mb-3 flex-fill">
                                <div class="ms-2">
                                    <p class="mb-1">Total Earning</p>
                                    <h5>$2,500</h5>
                                </div>
                                <span
                                    class="avatar avatar-lg bg-success-transparent rounded me-2 flex-shrink-0 text-success"><i
                                        class="ti ti-arrow-big-up-lines fs-24"></i></span>
                            </div>
                        </div>

                        <div class="col-md-6 col-xxl-3 d-flex">
                            <div
                                class="d-flex align-items-center justify-content-between rounded border p-3 mb-3 flex-fill">
                                <div class="ms-2">
                                    <p class="mb-1">Total Deduction</p>
                                    <h5>$2,500</h5>
                                </div>
                                <span
                                    class="avatar avatar-lg bg-danger-transparent rounded me-2 flex-shrink-0 text-danger"><i
                                        class="ti ti-arrow-big-down-lines fs-24"></i></span>
                            </div>
                        </div>
                    </div>

                    
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
                        <h4 class="mb-3">Payroll</h4>
                        <a href="#" data-bs-target="#add_fees_collect" data-bs-toggle="modal" class="btn btn-primary d-inline-flex align-items-center mb-3"><i class="ti ti-calendar-event me-2"></i>Apply Payroll</a>
                    </div>
                    <div class="card-body p-0 py-3">
                            <!-- Payroll List -->
                    <div class=" custom-datatable-filter table-responsive">
                        <table class="table datatable">
                            <thead class="thead-light">
                                <tr>
                                    <th class="no-sort">
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox" id="select-all">
                                        </div>
                                    </th>
                                    <th>ID</th>
                                    <th>Salary For</th>
                                    <th>Date</th>
                                    <th class="no-sort">Payment Method</th>
                                    <th class="no-sort">Net Salary</th>
                                    <th class="no-sort"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td><a href="#" class="link-primary">8198</a></td>
                                    <td>Apr - 2024</td>
                                    <td>04 May 2024</td>
                                    <td>Cash</td>
                                    <td>$20,000</td>
                                    <td><a href="#" class="btn btn-light add-fee">View Payslip</a></td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td><a href="#" class="link-primary">8197</a></td>
                                    <td>Mar - 2024</td>
                                    <td>05 Apr 2024</td>
                                    <td>Cheque</td>
                                    <td>$19,000</td>
                                    <td><a href="#" class="btn btn-light add-fee">View Payslip</a></td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td><a href="#" class="link-primary">8196</a></td>
                                    <td>Feb - 2024</td>
                                    <td>05 Mar 2024</td>
                                    <td>Cash</td>
                                    <td>$19,500</td>
                                    <td><a href="#" class="btn btn-light add-fee">View Payslip</a></td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td><a href="#" class="link-primary">8198</a></td>
                                    <td>Jan - 2024</td>
                                    <td>06 Feb 2024</td>
                                    <td>Cash</td>
                                    <td>$20,000</td>
                                    <td><a href="#" class="btn btn-light add-fee">View Payslip</a></td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td><a href="#" class="link-primary">8194</a></td>
                                    <td>Dec - 2023</td>
                                    <td>03 Jan 2024</td>
                                    <td>Cheque</td>
                                    <td>$19,480</td>
                                    <td><a href="#" class="btn btn-light add-fee">View Payslip</a></td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td><a href="#" class="link-primary">8193</a></td>
                                    <td>Nov - 2023</td>
                                    <td>05 Dec 2023</td>
                                    <td>Cheque</td>
                                    <td>$19,480</td>
                                    <td><a href="#" class="btn btn-light add-fee">View Payslip</a></td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td><a href="#" class="link-primary">8192</a></td>
                                    <td>Oct - 2023</td>
                                    <td>03 Nov 2023</td>
                                    <td>Cheque</td>
                                    <td>$19,480</td>
                                    <td><a href="#" class="btn btn-light add-fee">View Payslip</a></td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td><a href="#" class="link-primary">8191</a></td>
                                    <td>Sep - 2023</td>
                                    <td>04 Oct 2023</td>
                                    <td>Cheque</td>
                                    <td>$18,000</td>
                                    <td><a href="#" class="btn btn-light add-fee">View Payslip</a></td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td><a href="#" class="link-primary">8190</a></td>
                                    <td>Aug - 2023</td>
                                    <td>06 Sep 2023</td>
                                    <td>Cheque</td>
                                    <td>$20,000</td>
                                    <td><a href="#" class="btn btn-light add-fee">View Payslip</a></td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td><a href="#" class="link-primary">8189</a></td>
                                    <td>Jul - 2023</td>
                                    <td>05 Aug 2023</td>
                                    <td>Cheque</td>
                                    <td>$20,000</td>
                                    <td><a href="#" class="btn btn-light add-fee">View Payslip</a></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- /Payroll List -->
                    </div>
                </div>


                </div>
            </div>
        </div>
    </div>
</div>
<!-- /Page Wrapper -->

	



@endsection