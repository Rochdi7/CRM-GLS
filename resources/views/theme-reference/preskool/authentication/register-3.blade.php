<?php $page = 'register-3'; ?>
@extends('layout.mainlayout')
@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <form action="{{url('login-3')}}">
                <div class="d-flex flex-column justify-content-between vh-100">
                    <div class=" mx-auto p-4 text-center">
                        <img src="{{URL::asset('build/img/authentication/authentication-logo.svg')}}" class="img-fluid"
                            alt="Logo">
                    </div>
                    <div class="card">
                        <div class="card-body p-4">
                            <div class=" mb-4">
                                <h2 class="mb-2">Register</h2>
                                <p class="mb-0">Please enter your details to sign up</p>
                            </div>
                            <div class="mt-4">
                                <div class="d-flex align-items-center justify-content-center flex-wrap">
                                    <div class="text-center me-2 flex-fill">
                                        <a href="javascript:void(0);"
                                            class="bg-primary br-10 p-2 btn btn-primary  d-flex align-items-center justify-content-center">
                                            <img class="img-fluid m-1" src="{{URL::asset('build/img/icons/facebook-logo.svg')}}"
                                                alt="Facebook">
                                        </a>
                                    </div>
                                    <div class="text-center me-2 flex-fill">
                                        <a href="javascript:void(0);"
                                            class=" br-10 p-2 btn btn-outline-light  d-flex align-items-center justify-content-center">
                                            <img class="img-fluid  m-1" src="{{URL::asset('build/img/icons/google-logo.svg')}}"
                                                alt="Facebook">
                                        </a>
                                    </div>
                                    <div class="text-center flex-fill">
                                        <a href="javascript:void(0);"
                                            class="bg-dark br-10 p-2 btn btn-dark d-flex align-items-center justify-content-center">
                                            <img class="img-fluid  m-1" src="{{URL::asset('build/img/icons/apple-logo.svg')}}"
                                                alt="Apple">
                                        </a>
                                    </div>
                                </div>
                                <div class="login-or">
                                    <span class="span-or">Or</span>
                                </div>
                                <div class="mb-3 ">
                                    <label class="form-label">Name</label>
                                    <div class="input-icon mb-3 position-relative">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-user"></i>
                                        </span>
                                        <input type="text" value="" class="form-control">
                                    </div>
                                    <label class="form-label">Email Address</label>
                                    <div class="input-icon mb-3 position-relative">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-mail"></i>
                                        </span>
                                        <input type="text" value="" class="form-control">
                                    </div>
                                    <label class="form-label">Password</label>
                                    <div class="pass-group mb-3">
                                        <input type="password" class="pass-input form-control">
                                        <span class="ti toggle-password ti-eye-off"></span>
                                    </div>
                                    <label class="form-label">Confirm Password</label>
                                    <div class="pass-group">
                                        <input type="password" class="pass-input form-control">
                                        <span class="ti toggle-password ti-eye-off"></span>
                                    </div>
                                </div>
                                <div class="form-wrap form-wrap-checkbox mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="form-check form-check-md mb-0 me-2">
                                            <input class="form-check-input mt-0" type="checkbox">
                                        </div>
                                        <h6 class="fw-normal text-dark mb-0">I Agree to<a href="#"
                                                class="hover-a "> Terms & Privacy</a>
                                        </h6>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary w-100">Sign Up</button>
                            </div>
                            <div class="text-center">
                                <h6 class="fw-normal text-dark mb-0">Already have an account?<a
                                        href="{{url('login-3')}}" class="hover-a "> Sign In</a>
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