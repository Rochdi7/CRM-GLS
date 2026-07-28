<?php $page = 'reset-password-3'; ?>
@extends('layout.mainlayout')
@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5 mx-auto">
            <form action="{{url('reset-password-success-3')}}">
                <div class="d-flex flex-column justify-content-between vh-100">
                    <div class=" mx-auto p-4 text-center">
                        <img src="{{URL::asset('build/img/authentication/authentication-logo.svg')}}"
                            class="img-fluid" alt="Logo">
                    </div>
                    <div class="card">
                        <div class="card-body p-4 pb-3">
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
                                        href="{{url('login-3')}}" class="hover-a "> Login</a>
                                </h6>
                            </div>
                        </div>
                    </div>
                    <div class="p-4 text-center">
                        <p class="mb-0 ">Copyright &copy; 2024 - Preskool</p>
                    </div>
                </div>
            </form>
        </div>

    </div>
</div>

@endsection
