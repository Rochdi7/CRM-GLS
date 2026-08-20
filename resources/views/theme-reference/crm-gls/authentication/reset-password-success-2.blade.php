<?php $page = 'reset-password-success-2'; ?>
@extends('layout.mainlayout')
@section('content')

<div class="container-fuild">
    <div class="login-wrapper w-100 overflow-hidden position-relative flex-wrap d-block vh-100">
        <div class="row">
            <div class="col-lg-6">
                <div class="d-lg-flex align-items-center justify-content-center bg-light-300 d-lg-block d-none flex-wrap vh-100 overflowy-auto bg-01">
                    <div>
                        <img src="{{URL::asset('build/img/authentication/authentication-09.svg')}}" alt="Img">
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-12 col-sm-12">
                <div class="row justify-content-center align-items-center vh-100 overflow-auto flex-wrap ">
                    <div class="col-md-8 mx-auto p-4">
                        <div class=" mx-auto mb-5 text-center">
                            <img src="{{URL::asset('build/img/authentication/authentication-logo.svg')}}"
                                class="img-fluid" alt="Logo">
                        </div>
                       <div class="card">
                            <div class="card-body text-center">
                                <span class="avatar avatar-lg rounded-circle bg-success mb-3"><i class="ti ti-check fs-24"></i></span>
                                <h3 class="mb-2">Success</h3>
                                <p>Your Password Reset Successfully</p>
                                <a href="{{url('login-2')}}" class="btn btn-primary w-100">Back to Login</a>
                            </div>
                       </div>
                       <div class="mt-5 text-center">
                            <p class="mb-0 ">Copyright &copy; 2024 - Preskool</p>
                        </div>
                   </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection