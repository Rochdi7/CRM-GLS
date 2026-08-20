<?php $page = 'profile'; ?>
@extends('layout.mainlayout')
@section('content')
	<!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content">
            <div class="d-md-flex d-block align-items-center justify-content-between border-bottom pb-3">
                <div class="my-auto mb-2">
                    <h3 class="page-title mb-1">Profile</h3>
                    <nav>
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a href="{{url('index')}}">Dashboard</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="javascript:void(0);">Settings</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Profile</li>
                        </ol>
                    </nav>
                </div>
                <div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
                    <div class="pe-1 mb-2">
                        <a href="#" class="btn btn-outline-light bg-white btn-icon" data-bs-toggle="tooltip"
                            data-bs-placement="top" aria-label="Refresh" data-bs-original-title="Refresh">
                            <i class="ti ti-refresh"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="d-md-flex d-block mt-3">
                <div class="settings-right-sidebar me-md-3 border-0">
                    <div class="card">
                        <div class="card-header">
                            <h5>Personal Information</h5>

                        </div>
                        <div class="card-body">
                            <div class="settings-profile-upload">
                                <span class="profile-pic">
                                    <img src="{{URL::asset('build/img/profiles/avatar-27.jpg')}}" alt="Profile">
                                </span>
                                <div class="title-upload">
                                    <h5>Edit Your Photo</h5>
                                    <a href="#" class="me-2">Delete </a>
                                    <a href="#" class="text-primary">Update</a>
                                </div>
                            </div>
                            <div class="profile-uploader profile-uploader-two mb-0">
                                <span class="upload-icon"><i class="ti ti-upload"></i></span>
                                <div class="drag-upload-btn bg-transparent me-0 border-0">
                                    <p class="upload-btn"><span>Click to Upload</span> or drag and drop
                                    </p>
                                    <h6>JPG or PNG</h6>
                                    <h6>(Max 450 x 450 px)</h6>
                                </div>
                                <input type="file" class="form-control" multiple="" id="image_sign">
                                <div id="frames"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex-fill ps-0 border-0">
                    <form action="{{url('profile-settings')}}">
                        <div class="d-md-flex">
                            <div class="flex-fill">
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5>Personal Information</h5>
                                        <a href="#" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                            data-bs-target="#edit_personal_information"><i
                                                class="ti ti-edit me-2"></i>Edit</a>
                                    </div>
                                    <div class="card-body pb-1">
                                        <div class="d-block d-xl-flex">
                                            <div class="mb-3 flex-fill me-xl-3 me-0">
                                                <label class="form-label">First Name</label>
                                                <input type="text" class="form-control"
                                                    placeholder="Enter First Name">
                                            </div>
                                            <div class="mb-3 flex-fill">
                                                <label class="form-label">Last Name</label>
                                                <input type="text" class="form-control"
                                                    placeholder="Enter Last Name">
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Email Address</label>
                                            <input type="email" class="form-control" placeholder="Enter Email">
                                        </div>
                                        <div class="d-block d-xl-flex">
                                            <div class="mb-3 flex-fill me-xl-3 me-0">
                                                <label class="form-label">User Name</label>
                                                <input type="email" class="form-control"
                                                    placeholder="Enter User Name">
                                            </div>
                                            <div class="mb-3 flex-fill">
                                                <label class="form-label">Phone Number</label>
                                                <input type="email" class="form-control"
                                                    placeholder="Enter Phone Number">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5>Address Information</h5>
                                        <a href="#" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                            data-bs-target="#edit_address_information"><i
                                                class="ti ti-edit me-2"></i>Edit</a>
                                    </div>
                                    <div class="card-body pb-1">
                                        <div class="mb-3">
                                            <label class="form-label">Address</label>
                                            <input type="text" class="form-control" placeholder="Enter Address">
                                        </div>
                                        <div class="d-block d-xl-flex">
                                            <div class="mb-3 flex-fill me-xl-3 me-0">
                                                <label class="form-label">Country</label>
                                                <input type="text" class="form-control" placeholder="Enter Country">
                                            </div>
                                            <div class="mb-3 flex-fill">
                                                <label class="form-label">State / Province</label>
                                                <input type="email" class="form-control" placeholder="Enter State">
                                            </div>
                                        </div>
                                        <div class="d-block d-xl-flex">
                                            <div class="mb-3 flex-fill me-xl-3 me-0">
                                                <label class="form-label">City</label>
                                                <input type="email" class="form-control" placeholder="City">
                                            </div>
                                            <div class="mb-3 flex-fill">
                                                <label class="form-label">Postal Code</label>
                                                <input type="email" class="form-control"
                                                    placeholder="Enter Postal Code">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5>Password</h5>
                                        <a href="#" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                            data-bs-target="#change_password">Change</a>
                                    </div>
                                    <div class="card-body pb-1">
                                        <div class="mb-3 mb-3">
                                            <label class="form-label">Current Password</label>
                                            <div class="pass-group d-flex">
                                                <input type="password" class="pass-input form-control">
                                                <span class="ti toggle-password ti-eye-off"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- /Page Wrapper -->


@component('components.modal-popup')        
@endcomponent
@endsection