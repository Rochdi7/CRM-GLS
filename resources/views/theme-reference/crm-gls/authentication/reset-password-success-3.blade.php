<?php $page = 'reset-password-success-3'; ?>
@extends('layout.mainlayout')
@section('content')
<div class="d-flex justify-content-center align-items-center vh-100">
    <div class="row justify-content-center flex-fill">
        <div class="col-md-4 mx-auto p-4">
            <div class=" mx-auto mb-5 text-center">
                <img src="{{URL::asset('build/img/authentication/authentication-logo.svg')}}"
                    class="img-fluid" alt="Logo">
            </div>
           <div class="card">
                <div class="card-body text-center">
                    <span class="avatar avatar-lg rounded-circle bg-success mb-3"><i class="ti ti-check fs-24"></i></span>
                    <h3 class="mb-2">Success</h3>
                    <p>Your Password Reset Successfully</p>
                    <a href="{{url('login-3')}}" class="btn btn-primary w-100">Back to Login</a>
                </div>
           </div>
           <div class="mt-5 text-center">
                <p class="mb-0 ">Copyright &copy; 2024 - Preskool</p>
            </div>
       </div>
    </div>
</div>


@endsection
