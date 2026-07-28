<?php $page = 'lock-screen'; ?>
@extends('layout.mainlayout')
@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <form action="{{url('index')}}">
                <div class="d-flex flex-column justify-content-between vh-100">
                    <div class=" mx-auto p-4 text-center">
                        <img src="{{URL::asset('build/img/authentication/authentication-logo.svg')}}"
                            class="img-fluid" alt="Logo">
                            
                    </div>
                    <div class="card">
                        <div class="card-body p-4">
                            <div class=" mb-4 text-center">
                                <h2 class="mb-2">Welcome back! </h2>
                                <img src="{{URL::asset('build/img/profiles/avatar-27.jpg')}}" alt="img" class="img-fluid avatar avatar-xxl rounded-pill my-3">
                                <h6 class="text-dark">Adrian Davies</h6>
                            </div>
                            <div class="mb-3 ">
                                <div class="pass-group">
                                    <input type="password" class="pass-input form-control" placeholder="Enter Your Password">
                                    <span class="ti toggle-password ti-eye-off"></span>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Login</button>
                        </div>								
                    </div>
                    <div class="p-4 text-center">
                        <div class="d-flex justify-content-center">
                            <a href="#" class="me-3 text-default">Terms & Condition</a>
                            <a href="#" class="me-3 text-default">Privacy</a>
                            <a href="#" class="me-3 text-default">Help</a>
                            <div class="dropdown">
                                <a href="#" class="dropdown-toggle text-default" data-bs-toggle="dropdown">English</a>
                                <ul class="dropdown-menu">
                                    <li class="dropdown-item">
                                        <a href="#" class="text-default">Japanese</a>
                                    </li>
                                    <li class="dropdown-item">
                                        <a href="#" class="text-default">Chinese</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="p-2 text-center">
                            <p class="mb-0 ">Copyright &copy; 2024 - Preskool</p>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection