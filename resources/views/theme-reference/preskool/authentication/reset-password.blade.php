<?php $page = 'reset-password'; ?>
@extends('layout.mainlayout')
@section('content')

<div class="container-fuild">
    <div class="login-wrapper w-100 overflow-hidden position-relative flex-wrap d-block vh-100">
        <div class="row">
            <div class="col-lg-6">
                <div class="login-background position-relative d-lg-flex align-items-center justify-content-center d-lg-block d-none flex-wrap vh-100 overflowy-auto">
                    <div>
                        <img src="{{URL::asset('build/img/authentication/authentication-04.jpg')}}" alt="Img">
                    </div>
                    <div class="authen-overlay-item  w-100 p-4">
                        <h4 class="text-white mb-3">What's New on Preskool !!!</h4>
                        <div
                            class="d-flex align-items-center flex-row mb-3 justify-content-between p-3 br-5 gap-3 card">
                            <div>
                                <h6>Summer Vacation Holiday Homework</h6>
                                <p class="mb-0 text-truncate">The school will remain closed from April 20th to June...</p>
                            </div>
                            <a href="javascript:void(0);"><i class="ti ti-chevrons-right"></i></a>
                        </div>
                        <div
                            class="d-flex align-items-center flex-row mb-3 justify-content-between p-3 br-5 gap-3 card">
                            <div>
                                <h6>New Academic Session Admission Start(2024-25)</h6>
                                <p class="mb-0 text-truncate">An academic term is a portion of an academic year, the time ....
                                </p>
                            </div>
                            <a href="javascript:void(0);"><i class="ti ti-chevrons-right"></i></a>
                        </div>
                        <div
                            class="d-flex align-items-center flex-row mb-3 justify-content-between p-3 br-5 gap-3 card">
                            <div>
                                <h6>Date sheet Final Exam Nursery to Sr.Kg</h6>
                                <p class="mb-0 text-truncate">Dear Parents, As the final examination for the session 2024-25
                                    is ...</p>
                            </div>
                            <a href="javascript:void(0);"><i class="ti ti-chevrons-right"></i></a>
                        </div>
                        <div
                            class="d-flex align-items-center flex-row mb-3 justify-content-between p-3 br-5 gap-3 card">
                            <div>
                                <h6>Annual Day Function</h6>
                                <p class="mb-0 text-truncate">Annual functions provide a platform for students to showcase
                                    their...</p>
                            </div>
                            <a href="javascript:void(0);"><i class="ti ti-chevrons-right"></i></a>
                        </div>
                        <div
                            class="d-flex align-items-center flex-row mb-0 justify-content-between p-3 br-5 gap-3 card">
                            <div>
                                <h6>Summer Vacation Holiday Homework</h6>
                                <p class="mb-0 text-truncate">The school will remain closed from April 20th to June 15th for
                                    summer...</p>
                            </div>
                            <a href="javascript:void(0);"><i class="ti ti-chevrons-right"></i></a>
                        </div>

                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-12 col-sm-12">
                <div class="row justify-content-center align-items-center vh-100 overflow-auto flex-wrap ">
                    <div class="col-md-8 mx-auto p-4">
                        <form action="{{url('reset-password-success')}}">
                            <div>
                                <div class=" mx-auto mb-5 text-center">
                                    <img src="{{URL::asset('build/img/authentication/authentication-logo.svg')}}"
                                        class="img-fluid" alt="Logo">
                                </div>
                                <div class="card">
                                    <div class="card-body p-4">
                                        <div class=" mb-4">
                                            <h2 class="mb-2">Reset Password?</h2>
                                            <p class="mb-0">Enter New Password & Confirm Password to get inside</p>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Old Password</label>
                                            <div class="pass-group">
                                                <input type="password" class="pass-input form-control">
                                                <span class="ti toggle-password ti-eye-off"></span>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">New Password</label>
                                            <div class="pass-group">
                                                <input type="password" class="pass-input form-control">
                                                <span class="ti toggle-password ti-eye-off"></span>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label ">New Confirm Password</label>
                                            <div class="pass-group">
                                                <input type="password" class="pass-input form-control">
                                                <span class="ti toggle-password ti-eye-off"></span>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <button type="submit" class="btn btn-primary w-100">Change Password</button>
                                        </div>
                                        <div class="text-center">
                                            <h6 class="fw-normal text-dark mb-0">Return to<a
                                                    href="{{url('login-2')}}" class="hover-a "> Login</a>
                                            </h6>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-5 text-center">
                                    <p class="mb-0 ">Copyright &copy; 2024 - Preskool</p>
                                </div>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>



    </div>
</div>


@endsection