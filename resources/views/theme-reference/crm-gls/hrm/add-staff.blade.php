<?php $page = 'add-staff'; ?>
@extends('layout.mainlayout')
@section('content')
	<!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content content-two">

            <!-- Page Header -->
            <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
                <div class="my-auto mb-2">
                    <h3 class="mb-1">Add Staff</h3>
                     <nav>
                           <ol class="breadcrumb mb-0">
                              <li class="breadcrumb-item">
                                <a href="{{url('index')}}">Dashboard</a>
                              </li>
                              <li class="breadcrumb-item">HRM</li>
                              <li class="breadcrumb-item active" aria-current="page">Add Staff</li>
                        </ol>
                      </nav>
                </div>
            </div>
            <!-- /Page Header -->
            
            <div class="row">

                <div class="col-md-12">

                    <form action="{{url('staffs')}}">

                        <!-- Personal Information -->
                        <div class="card">
                            <div class="card-header bg-light">
                                <div class="d-flex align-items-center">
                                    <span class="bg-white avatar avatar-sm me-2 text-gray-7 flex-shrink-0">
                                        <i class="ti ti-info-square-rounded fs-16"></i>
                                    </span>
                                    <h4 class="text-dark">Personal Information</h4>
                                </div>
                            </div>
                            <div class="card-body pb-1">
                                <div class="add-section">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="d-flex align-items-center flex-wrap row-gap-3 mb-3">                                                
                                                <div class="d-flex align-items-center justify-content-center avatar avatar-xxl border border-dashed me-2 flex-shrink-0 text-dark frames">
                                                    <i class="ti ti-photo-plus fs-16"></i>
                                                </div>                                              
                                                <div class="profile-upload">
                                                    <div class="profile-uploader d-flex align-items-center">
                                                        <div class="drag-upload-btn mb-3">
                                                            Upload
                                                            <input type="file" class="form-control image-sign" multiple="">
                                                        </div>
                                                        <a href="javascript:void(0);" class="btn btn-primary mb-3">Remove</a>
                                                    </div>
                                                    <p class="fs-12">Upload image size 4MB, Format JPG, PNG, SVG</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row row-cols-xxl-5 row-cols-md-6">
                                        <div class="col-xxl col-xl-3 col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">First Name</label>
                                                <input type="text" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-xxl col-xl-3 col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Last Name</label>
                                                <input type="text" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-xxl col-xl-3 col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Role</label>
                                                <select class="select">
                                                    <option>Select</option>
                                                    <option>Teacher</option>
                                                    <option>Accountant</option>
                                                    <option>Librarian</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-xxl col-xl-3 col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Department</label>
                                                <select class="select">
                                                    <option>Select</option>
                                                    <option>Admin</option>
                                                    <option>Management</option>
                                                    <option>Academic</option>
                                                    <option>Library</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-xxl col-xl-3 col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Designation</label>
                                                <select class="select">
                                                    <option>Select</option>
                                                    <option>Teacher</option>
                                                    <option>Accountant</option>
                                                    <option>Librarian</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-xxl col-xl-3 col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Gender</label>
                                                <select class="select">
                                                    <option>Select</option>
                                                    <option>Female</option>
                                                    <option>Male</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-xxl col-xl-3 col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Primary Contact Number</label>
                                                <input type="text" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-xxl col-xl-3 col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Email Address</label>
                                                <input type="email" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-xxl col-xl-3 col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Blood Group</label>
                                                <select class="select">
                                                    <option>Select</option>
                                                    <option>O +ve</option>
                                                    <option>B +ve</option>
                                                    <option>B -ve</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-xxl col-xl-3 col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Marital Status</label>
                                                <select class="select">
                                                    <option>Select</option>
                                                    <option>Single</option>
                                                    <option>Married</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-xxl col-xl-3 col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Father’s Name</label>
                                                <input type="text" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-xxl col-xl-3 col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Mother’s Name</label>
                                                <input type="text" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-xxl col-xl-3 col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Date of Birth</label>											
                                                <div class="input-icon position-relative">
                                                    <span class="input-icon-addon">
                                                        <i class="ti ti-calendar"></i>
                                                    </span>
                                                    <input type="text" class="form-control datetimepicker" placeholder="dd-mm-yyyy">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xxl col-xl-3 col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Date of Joining</label>																							
                                                <div class="input-icon position-relative">
                                                    <span class="input-icon-addon">
                                                        <i class="ti ti-calendar"></i>
                                                    </span>
                                                    <input type="text" class="form-control datetimepicker" placeholder="dd-mm-yyyy">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xxl col-xl-3 col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Language Known</label>
                                                <input class="input-tags form-control" type="text" data-role="tagsinput"  name="Label" value="English">
                                            </div>
                                        </div>
                                        <div class="col-xxl-4 col-xl-3 col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Qualification</label>
                                                <input type="text" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-xxl-4 col-xl-3 col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Work Experience</label>
                                                <input type="text" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-xxl-4  col-xl-3 col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Note</label>
                                                <input type="text" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-xxl-6 col-xl-3  col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Address</label>
                                                <input type="text" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-xxl-6 col-xl-3  col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Permanent Address</label>
                                                <input type="text" class="form-control">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /Personal Information -->

                        <!-- Payroll -->
                        <div class="card ">
                            <div class="card-header bg-light">
                                <div class="d-flex align-items-center">
                                    <span class="bg-white avatar avatar-sm me-2 text-gray-7 flex-shrink-0">
                                        <i class="ti ti-user-shield fs-16"></i>
                                    </span>
                                    <h4 class="text-dark">Payroll</h4>
                                </div>
                            </div>
                            <div class="card-body pb-1">
                                <div class="row">
                                    <div class="col-lg-3 col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">EPF No</label>
                                            <input type="text" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Basic Salary</label>
                                            <input type="text" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Contract Type</label>
                                            <select class="select">
                                                <option>Select</option>
                                                <option>Permanent</option>
                                                <option>Temporary</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Work Shift</label>
                                            <select class="select">
                                                <option>Select</option>
                                                <option>Morning</option>
                                                <option>Afternoon</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Work Location</label>
                                            <input type="text" class="form-control">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /Payroll -->

                        <!-- Leaves -->
                        <div class="card">
                            <div class="card-header bg-light">
                                <div class="d-flex align-items-center">
                                    <span class="bg-white avatar avatar-sm me-2 text-gray-7 flex-shrink-0">
                                        <i class="ti ti-users fs-16"></i>
                                    </span>
                                    <h4 class="text-dark">Leaves</h4>
                                </div>
                            </div>
                            <div class="card-body pb-1">
                                <div class="row">
                                    <div class="col-lg-3 col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Medical Leaves</label>
                                            <input type="text" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Casual Leaves</label>
                                            <input type="text" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Maternity Leaves</label>
                                            <input type="text" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Sick Leaves</label>
                                            <input type="text" class="form-control">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /Leaves -->

                        <!-- Bank Details -->
                        <div class="card">
                            <div class="card-header bg-light">
                                <div class="d-flex align-items-center">
                                    <span class="bg-white avatar avatar-sm me-2 text-gray-7 flex-shrink-0">
                                        <i class="ti ti-users fs-16"></i>
                                    </span>
                                    <h4 class="text-map">Bank Account Detail</h4>
                                </div>
                            </div>
                            <div class="card-body pb-1">
                                <div class="row">
                                    <div class="col-lg-4 col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Account Name</label>
                                            <input type="text" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Account Number</label>
                                            <input type="text" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Bank Name</label>
                                            <input type="text" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">IFSC Code</label>
                                            <input type="text" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Branch Name</label>
                                            <input type="text" class="form-control">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /Bank Details -->

                        <!-- Transport Information -->
                        <div class="card">
                            <div class="card-header bg-light d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span class="bg-white avatar avatar-sm me-2 text-gray-7 flex-shrink-0">
                                        <i class="ti ti-bus-stop fs-16"></i>
                                    </span>
                                    <h4 class="text-dark">Transport Information</h4>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch">
                                </div>
                            </div>
                            <div class="card-body pb-1">
                                <div class="row">
                                    <div class="col-lg-4 col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Route</label>
                                            <select class="select">
                                                <option>Select</option>
                                                <option>Newyork</option>
                                                <option>Denver</option>
                                                <option>Chicago</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Vehicle Number</label>
                                            <select class="select">
                                                <option>Select</option>
                                                <option>AM 54548</option>
                                                <option>AM 64528</option>
                                                <option>AM 123548</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Pickup Point</label>
                                            <select class="select">
                                                <option>Select</option>
                                                <option>Cincinatti</option>
                                                <option>Illinois</option>
                                                <option>Morgan</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /Transport Information -->

                        <!-- Hostel Information -->
                        <div class="card">
                            <div class="card-header bg-light d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span class="bg-white avatar avatar-sm me-2 text-gray-7 flex-shrink-0">
                                        <i class="ti ti-building-fortress fs-16"></i>
                                    </span>
                                    <h4 class="text-dark">Hostel Information</h4>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch">
                                </div>
                            </div>
                            <div class="card-body pb-1">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Hostel</label>
                                            <select class="select">
                                                <option>Select</option>
                                                <option>Phoenix Residence</option>
                                                <option>Tranquil Haven</option>
                                                <option>Radiant Towers</option>
                                                <option>Nova Nest</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Room No</label>
                                            <select class="select">
                                                <option>Select</option>
                                                <option>20</option>
                                                <option>22</option>
                                                <option>24</option>
                                                <option>26</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /Hostel Information -->

                        <!-- Social Media Links -->
                        <div class="card">
                            <div class="card-header bg-light">
                                <div class="d-flex align-items-center">
                                    <span class="bg-white avatar avatar-sm me-2 text-gray-7 flex-shrink-0">
                                        <i class="ti ti-building fs-16"></i>
                                    </span>
                                    <h4 class="text-dark">Social Media Links</h4>
                                </div>
                            </div>
                            <div class="card-body pb-1">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Facebook URL</label>
                                            <input type="text" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Twitter URL</label>
                                            <input type="text" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Linkediin URL</label>
                                            <input type="text" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Instagram URL</label>
                                            <input type="text" class="form-control">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /Social Media Links -->

                        <!-- Documents -->
                        <div class="card">
                            <div class="card-header bg-light">
                                <div class="d-flex align-items-center">
                                    <span class="bg-white avatar avatar-sm me-2 text-gray-7 flex-shrink-0">
                                        <i class="ti ti-file fs-16"></i>
                                    </span>
                                    <h4 class="text-dark">Documents</h4>
                                </div>
                            </div>
                            <div class="card-body pb-1">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="mb-2">
                                            <div class="mb-3">
                                                <label class="form-label">Upload Resume</label>
                                                <p>Upload image size of 4MB, Accepted Format PDF</p>
                                            </div>
                                            <div class="d-flex align-items-center flex-wrap">
                                                <div class="btn btn-primary drag-upload-btn mb-2 me-2">
                                                    <i class="ti ti-file-upload me-1"></i>Change
                                                    <input type="file" class="form-control image_sign" multiple="">
                                                </div>
                                                <p class="mb-2">Resume.pdf</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-2">
                                            <div class="mb-3">
                                                <label class="form-label">Upload Joining Letter</label>
                                                <p>Upload image size of 4MB, Accepted Format PDF</p>
                                            </div>
                                            <div class="d-flex align-items-center flex-wrap">
                                                <div class="btn btn-primary drag-upload-btn mb-2">
                                                    <i class="ti ti-file-upload me-1"></i>Upload Document
                                                    <input type="file" class="form-control image_sign" multiple="">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /Documents -->

                        <!-- Password -->
                        <div class="card">
                            <div class="card-header bg-light">
                                <div class="d-flex align-items-center">
                                    <span class="bg-white avatar avatar-sm me-2 text-gray-7 flex-shrink-0">
                                        <i class="ti ti-file fs-16"></i>
                                    </span>
                                    <h4 class="text-dark">Password</h4>
                                </div>
                            </div>
                            <div class="card-body pb-1">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">New Password</label>
                                            <input type="password" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Confirm Password</label>
                                            <input type="password" class="form-control">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /Password -->

                        <div class="text-end">
                            <button type="button" class="btn btn-light me-3">Cancel</button>
                            <button type="submit" class="btn btn-primary">Add Staff</button>
                        </div>

                    </form>

                </div>

            </div>

        </div>
    </div>
    <!-- /Page Wrapper -->


@endsection