<?php $page = 'email-verification-2'; ?>
@extends('layout.mainlayout')
@section('content')

<div class="container-fuild">
    <div class="login-wrapper w-100 overflow-hidden position-relative flex-wrap d-block vh-100">
        <div class="row">
            <div class="col-lg-6">
                <div class="d-lg-flex align-items-center justify-content-center bg-light-300 d-lg-block d-none flex-wrap vh-100 overflowy-auto bg-01">
                    <div>
                        <img src="{{URL::asset('build/img/authentication/authentication-10.svg')}}" alt="Img">
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-12 col-sm-12">
                <div class="row justify-content-center align-items-center vh-100 overflow-auto flex-wrap ">
                    <div class="col-md-9 mx-auto p-4">
                        <form action="{{url('login-2')}}">
                            <div>
                                <div class=" mx-auto mb-5 text-center">
                                    <img src="{{URL::asset('build/img/authentication/authentication-logo.svg')}}"
                                        class="img-fluid" alt="Logo">
                                </div>
                                <div class="card">
                                    <div class="card-body p-4">
                                        <div class=" mb-3">
                                            <h2 class="mb-2 text-center">Verify your Email</h2>
                                            <p class="mb-0 text-center">We've sent a link to your  email ter4@example.com. Please follow the link inside to continue</p>
                                        </div>
                                        <div class="text-center mb-3">
                                            <h6 class="fw-normal text-dark mb-0">Didn’t receive an email?<a
                                                    href="#" class="hover-a "> Resend Link</a>
                                            </h6>
                                        </div>
                                        <button type="submit" class="btn btn-primary w-100">Skip Now</button>
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