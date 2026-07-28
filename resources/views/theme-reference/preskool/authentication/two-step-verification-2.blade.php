<?php $page = 'two-step-verification-2'; ?>
@extends('layout.mainlayout')
@section('content')
<div class="account-content">
    <div class="w-100 overflow-hidden position-relative flex-wrap d-block vh-100">
        <div class="row">
            <div class="col-lg-6">
                <div class="d-lg-flex align-items-center justify-content-center bg-light-300 d-lg-block d-none flex-wrap vh-100 overflowy-auto bg-01">
                    <div>
                        <img src="{{URL::asset('build/img/authentication/authentication-06.svg')}}" alt="Img">
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-12 col-sm-12">
                <div class="row justify-content-center align-items-center vh-100 overflow-auto flex-wrap ">
                    <div class="col-md-8 mx-auto p-4">
                        <form method="get" class="digit-group login-form-control" data-group-name="digits" data-autosubmit="false" autocomplete="off" action="{{url('index')}}">
                            <div class=" mx-auto mb-5 text-center">
                                <img src="{{URL::asset('build/img/authentication/authentication-logo.svg')}}"
                                    class="img-fluid" alt="Logo">
                            </div>
                            <div class="card">
                                <div class="card-body p-4">
                                    <div class=" mb-4">
                                        <h2 class="mb-2">Login with your Email Address</h2>
                                        <p class="mb-0">We sent a verification code to your email. Enter the code from the email in the field below</p>
                                    </div>	
                                    <div class="text-center">
                                        <div class="d-flex align-items-center mb-3">
                                            <input type="text" class="border rounded w-100 py-sm-3 py-2 text-center fs-26 hw-bold me-3" id="digit-1" name="digit-1" data-next="digit-2" maxlength="1">
                                            <input type="text" class="border rounded w-100 py-sm-3 py-2 text-center fs-26 hw-bold me-3" id="digit-2" name="digit-2" data-next="digit-3" data-previous="digit-1" maxlength="1">
                                            <input type="text" class="border rounded w-100 py-sm-3 py-2 text-center fs-26 hw-bold me-3" id="digit-3" name="digit-3" data-next="digit-4" data-previous="digit-2" maxlength="1">
                                            <input type="text" class="border rounded w-100 py-sm-3 py-2 text-center fs-26 hw-bold" id="digit-4" name="digit-4" data-next="digit-5" data-previous="digit-3" maxlength="1">
                                        </div>
                                        <div>
                                            <div class="badge bg-soft-danger mb-3">
                                                <p>Otp will expire in 09:59</p>
                                            </div>
                                            <div class="form-wrap mb-0">
                                                <button type="submit" class="btn btn-primary w-100">Verify My Account</button>
                                            </div>
                                        </div>
                                    </div>						
                                </div>
                            </div>
                            <div class="mt-5 text-center">
                                <p class="mb-0 ">Copyright &copy; 2024 - Preskool</p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</div>

@endsection