<?php $page = 'security-settings'; ?>
@extends('layout.mainlayout')
@section('content')
  <!-- Page Wrapper -->
  <div class="page-wrapper">
    <div class="content">
        <div class="d-md-flex d-block align-items-center justify-content-between border-bottom pb-3">
            <div class="my-auto mb-2">
                <h3 class="page-title mb-1">General Settings</h3>
                 <nav>
                       <ol class="breadcrumb mb-0">
                          <li class="breadcrumb-item">
                            <a href="{{url('index')}}">Dashboard</a>
                          </li>
                          <li class="breadcrumb-item">
                            <a href="javascript:void(0);">Settings</a>
                          </li>
                          <li class="breadcrumb-item active" aria-current="page">General Settings</li>
                    </ol>
                  </nav>
            </div>
            <div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
                  <div class="pe-1 mb-2">
                       <a href="#" class="btn btn-outline-light bg-white btn-icon" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Refresh" data-bs-original-title="Refresh">
                         <i class="ti ti-refresh"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-xxl-2 col-xl-3">
                <div class="pt-3 d-flex flex-column list-group mb-4">
                    <a href="{{url('profile-settings')}}" class="d-block rounded p-2">Profile Settings</a>
                    <a href="{{url('security-settings')}}" class="d-block rounded active p-2">Security Settings</a>
                    <a href="{{url('notifications-settings')}}" class="d-block rounded p-2">Notifications</a>
                    <a href="{{url('connected-apps')}}" class="d-block rounded p-2">Connected Apps</a>
                </div>
            </div>
            <div class="col-xxl-10 col-xl-9">
                <div class="border-start ps-3 flex-fill">
                    <div class="d-flex align-items-center justify-content-between flex-wrap border-bottom mb-3 pt-3">
                        <div class="mb-3">
                            <h5>Security Settings</h5>
                            <p>Upload your photo & personal details here</p>
                        </div>
                    </div>
                    <div class="d-block">
                        <div class="d-flex justify-content-between align-items-center rounded flex-wrap bg-white border rounded p-3 pb-0 mb-3">
                            <div class="mb-3">
                                <h6>Password</h6>
                                <p>Set a unique password to protect the account</p>
                            </div>
                            <div class="mb-3">
                                <a href="#" class="btn btn-outline-primary">Change Pasword</a>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center rounded flex-wrap bg-white border rounded p-3 pb-0 mb-3">
                            <div class="mb-3">
                                <h6>Two Factor</h6>
                                <p>Receive codes via SMS or email every time you login</p>
                            </div>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="switch-sm">
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center rounded flex-wrap bg-white border rounded p-3 pb-0 mb-3">
                            <div class="mb-3">
                                <h6>Google Authentication</h6>
                                <p>Connect to Google</p>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge badge-soft-success me-3"><i class="ti ti-circle-filled fs-5 me-1"></i>Connected</span>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="switch-sm2">
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center rounded flex-wrap bg-white border rounded p-3 pb-0 mb-3">
                            <div class="mb-3">
                                <h6>Phone Number Verification</h6>
                                <p>The Phone Number associated with the account</p>
                            </div>
                            <div class="d-flex align-items-center flex-wrap">
                                <p class="mb-3 me-3">+1 73649 72648</p>
                                <span class="badge badge-soft-success me-3 mb-3"><i class="ti ti-checks me-1"></i>Verified</span>
                                <a href="#" class="btn btn-light mb-3"><i class="ti ti-edit me-2"></i>Edit</a>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center rounded flex-wrap bg-white border rounded p-3 pb-0 mb-3">
                            <div class="mb-3">
                                <h6>Email Address</h6>
                                <p>The email address associated with the account</p>
                            </div>
                            <div class="d-flex align-items-center flex-wrap">
                                <p class="mb-3 me-3">admin@example.com</p>
                                <span class="badge badge-soft-success me-3 mb-3"><i class="ti ti-checks me-1"></i>Verified</span>
                                <a href="#" class="btn btn-light mb-3"><i class="ti ti-edit me-2"></i>Edit</a>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center rounded flex-wrap bg-white border rounded p-3 pb-0 mb-3">
                            <div class="mb-3">
                                <h6>Device Management</h6>
                                <p>The devices associated with the account</p>
                            </div>
                            <div class="mb-3">
                                <a href="#" class="btn btn-outline-primary">Manage</a>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center rounded flex-wrap bg-white border rounded p-3 pb-0 mb-3">
                            <div class="mb-3">
                                <h6>Account Activity</h6>
                                <p>The activities of the account </p>
                            </div>
                            <div class="mb-3">
                                <a href="#" class="btn btn-outline-primary">View</a>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center rounded flex-wrap bg-white border rounded p-3 pb-0 mb-3">
                            <div class="mb-3">
                                <h6>Deactivate Account</h6>
                                <p>This will shutdown your account. Your account will be reactive when you sign in again </p>
                            </div>
                            <div class="mb-3">
                                <a href="#" class="btn btn-outline-primary">Deactivate</a>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center rounded flex-wrap bg-white border rounded p-3 pb-0 mb-3">
                            <div class="mb-3">
                                <h6>Delete Account</h6>
                                <p>Your account will be permanently deleted</p>
                            </div>
                            <div class="mb-3">
                                <a href="#" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#delete-modal">Delete</a>
                            </div>
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
