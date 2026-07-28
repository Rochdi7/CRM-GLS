<?php $page = 'reset-password-2'; ?>
@extends('layout.mainlayout')
@section('content')
<div class="container-fuild">
    <div class="login-wrapper w-100 overflow-hidden position-relative flex-wrap d-block vh-100">
        <div class="row">
            <div class="col-lg-6">
                <div class="d-lg-flex align-items-center justify-content-center bg-light-300 d-lg-block d-none flex-wrap vh-100 overflowy-auto bg-01">
                    <div>
                        <img src="{{URL::asset('build/img/authentication/authentication-08.svg')}}" alt="Img">
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-12 col-sm-12">
                <div class="row justify-content-center align-items-center vh-100 overflow-auto flex-wrap ">
                    <div class="col-md-8 mx-auto p-4">
                        <form action="{{url('reset-password-success-2')}}">
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