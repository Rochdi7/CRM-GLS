<?php $page = 'gdpr-cookies'; ?>
@extends('layout.mainlayout')
@section('content')
 	<!-- Page Wrapper -->
     <div class="page-wrapper">
        <div class="content bg-white">
            <div class="d-md-flex d-block align-items-center justify-content-between border-bottom pb-3">
                <div class="my-auto mb-2">
                    <h3 class="page-title mb-1">System Settings</h3>
                     <nav>
                           <ol class="breadcrumb mb-0">
                              <li class="breadcrumb-item">
                                <a href="{{url('index')}}">Dashboard</a>
                              </li>
                              <li class="breadcrumb-item">
                                <a href="javascript:void(0);">Settings</a>
                              </li>
                              <li class="breadcrumb-item active" aria-current="page">System Settings</li>
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
                        <a href="{{url('email-settings')}}" class="d-block rounded p-2">Email Settings</a>
                        <a href="{{url('email-templates')}}" class="d-block rounded p-2">Email Templates</a>
                        <a href="{{url('sms-settings')}}" class="d-block rounded p-2">SMS Settings</a>
                        <a href="{{url('otp-settings')}}" class="d-block rounded p-2">OTP</a>
                        <a href="{{url('gdpr-cookies')}}" class="d-block rounded active p-2">GDPR Cookies</a>
                    </div>
                </div>
                <div class="col-xxl-10 col-xl-9">
                    <div class="flex-fill border-start ps-3">
                        <form action="{{url('gdpr-cookies')}}">
                            <div class="d-flex align-items-center justify-content-between flex-wrap border-bottom pt-3 mb-3">
                                <div class="mb-3">
                                    <h5 class="mb-1">GDPR Cookies</h5>
                                    <p>GDPR Cookies</p>
                                </div>
                                <div class="mb-3">
                                    <button class="btn btn-light me-2" type="button">Cancel</button>
                                    <button class="btn btn-primary" type="submit">Save</button>
                                </div>
                            </div>
                            <div class="d-md-flex">
                                <div class="row flex-fill">
                                    <div class="col-xl-10">
                                        <div>
                                            <div class="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded">
                                                <div class="row align-items-center flex-fill">
                                                    <div class="col-xxl-8 col-lg-6">
                                                        <div class="mb-3">
                                                            <h6>Cookies Content Text</h6>
                                                            <p>You can configure the text here</p>
                                                        </div>
                                                    </div>
                                                    <div class="col-xxl-4 col-lg-6">
                                                        <div class="mb-3">
                                                            <textarea rows="3" class="form-control"></textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded">
                                                <div class="row align-items-center flex-fill">
                                                    <div class="col-xxl-8 col-lg-6">
                                                        <div class="mb-3">
                                                            <h6>Cookies Position</h6>
                                                            <p>You can configure the position</p>
                                                        </div>
                                                    </div>
                                                    <div class="col-xxl-4 col-lg-6">
                                                        <div class="mb-3">
                                                            <select class="select">
                                                                <option>Right</option>
                                                                <option>Left</option>
                                                                <option>Center</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded">
                                                <div class="row align-items-center flex-fill">
                                                    <div class="col-xxl-8 col-lg-6">
                                                        <div class="mb-3">
                                                            <h6>Agree Button Text</h6>
                                                            <p>You can configure the text here</p>
                                                        </div>
                                                    </div>
                                                    <div class="col-xxl-4 col-lg-6">
                                                        <div class="mb-3">
                                                            <input type="text" class="form-control" placeholder="Agree">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>	
                                            <div class="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded">
                                                <div class="row align-items-center flex-fill">
                                                    <div class="col-xxl-8 col-lg-6">
                                                        <div class="mb-3">
                                                            <h6>Decline Button Text</h6>
                                                            <p>You can configure the text here</p>
                                                        </div>
                                                    </div>
                                                    <div class="col-xxl-4 col-lg-6">
                                                        <div class="mb-3">
                                                            <input type="text" class="form-control" placeholder="Decline">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>	
                                            <div class="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded">
                                                <div class="row align-items-center flex-fill">
                                                    <div class="col-xxl-8 col-lg-6">
                                                        <div class="mb-3">
                                                            <h6>Show Decline Button</h6>
                                                            <p>To display decline button</p>
                                                        </div>
                                                    </div>
                                                    <div class="col-xxl-4 col-lg-6">
                                                        <div class="mb-3">
                                                            <div class="status-toggle modal-status">
                                                                <input type="checkbox" id="user2" class="check">
                                                                <label for="user2" class="checktoggle"> </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>	
                                            <div class="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded">
                                                <div class="row align-items-center flex-fill">
                                                    <div class="col-xxl-8 col-lg-6">
                                                        <div class="mb-3">
                                                            <h6>Lin for Cookies Page</h6>
                                                            <p>You can configure the link here</p>
                                                        </div>
                                                    </div>
                                                    <div class="col-xxl-4 col-lg-6">
                                                        <div class="mb-3">
                                                            <input type="text" class="form-control" placeholder="Decline">
                                                        </div>
                                                    </div>
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
    </div>
    <!-- /Page Wrapper -->
@endsection
