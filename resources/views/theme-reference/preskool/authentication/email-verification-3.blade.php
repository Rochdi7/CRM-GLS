<?php $page = 'email-verification-3'; ?>
@extends('layout.mainlayout')
@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5 mx-auto">
            <form action="{{url('login-3')}}">
                <div class="d-flex flex-column justify-content-between vh-100">
                    <div class=" mx-auto p-4 text-center">
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
                    <div class="p-4 text-center">
                        <p class="mb-0 ">Copyright &copy; 2024 - Preskool</p>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection