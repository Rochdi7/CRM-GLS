<?php $page = 'two-step-verification'; ?>
@extends('layout.mainlayout')
@section('content')
<div class="container-fuild">
    <div class="w-100 overflow-hidden position-relative flex-wrap d-block vh-100">
        <div class="row">
            <div class="col-lg-6">
                <div class="login-background position-relative d-lg-flex align-items-center justify-content-center d-lg-block d-none flex-wrap vh-100 overflowy-auto">
                    <div>
                        <img src="{{URL::asset('build/img/authentication/authentication-03.jpg')}}" alt="Img">
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