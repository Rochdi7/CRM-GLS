<?php $page = 'notifications-settings'; ?>
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
                    <a href="{{url('security-settings')}}" class="d-block rounded p-2">Security Settings</a>
                    <a href="{{url('notifications-settings')}}" class="d-block rounded active p-2">Notifications</a>
                    <a href="{{url('connected-apps')}}" class="d-block rounded p-2">Connected Apps</a>
                </div>
            </div>
            <div class="col-xxl-10 col-xl-9">
                <div class="flex-fill border-start ps-3">
                    <div class="d-flex align-items-center justify-content-between flex-wrap border-bottom mb-3 pt-3">
                        <div class="mb-3">
                            <h5>Notifications</h5>
                            <p>Get notification what happening right now, you can turn off at any time</p>
                        </div>
                    </div>
                    <div class="d-block">
                        <div class="card border-0 p-3 pb-0 mb-3 rounded">
                            <div class="d-flex justify-content-between align-items-center flex-wrap border-bottom mb-3">
                                <div class="mb-3">
                                    <h6>Email Notifications</h6>
                                    <p>Substance can send you email notifications for any new direct messages</p>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center flex-wrap border-bottom mb-3">
                                <div class="mb-3">
                                    <h6>News and Update Settings</h6>
                                    <p>The latest news about latest features and softaware update settings</p>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center flex-wrap border-bottom mb-3">
                                <div class="mb-3">
                                    <h6>Tips & Tutorials</h6>
                                    <p>Tips & Tricks in order to improve your performance efficiency </p>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center flex-wrap mb-0">
                                <div class="mb-3">
                                    <h6>Offers & Promotions</h6>
                                    <p>Promotion about  package prices and its latest discouts</p>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card border-0 p-3 pb-0 mb-3 rounded">
                            <div class="d-flex justify-content-between align-items-center flex-wrap border-bottom mb-3">
                                <div class="mb-3">
                                    <h6>More Activity</h6>
                                    <p>Substance can send you email notifications for any new direct messages</p>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="switch-sm">
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center flex-wrap border-bottom mb-3">
                                <div class="mb-3">
                                    <h6>All Remainders & Activity</h6>
                                    <p>Notify all system activats and remainders that have been created</p>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center flex-wrap border-bottom mb-3">
                                <div class="mb-3">
                                    <h6>Activity Only</h6>
                                    <p>Ony notify latest activity updates about increasing or decreasing data</p>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center flex-wrap mb-0">
                                <div class="mb-3">
                                    <h6>Important Remainders Only</h6>
                                    <p>Only notify all the remainders that have been made</p>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /Page Wrapper --> 	
@endsection
