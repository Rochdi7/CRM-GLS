<?php $page = '500-error'; ?>
@extends('layout.mainlayout')
@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-xxl-5 col-xl-5 col-md-6">
            <div class="d-flex flex-column justify-content-between vh-100">
                <div class="text-center p-4">
                    <img src="{{URL::asset('build/img/logo.svg')}}" alt="img" class="img-fluid">
                </div>
                <div class="d-flex flex-column align-items-center justify-content-center mb-4">
                    <div class="mb-4">
                        <img src="{{URL::asset('build/img/authentication/error-500.svg')}}" class="error-img img-fluid" alt="Img">
                    </div>
                    <h3 class="h2 mb-3">Oops, something went wrong</h3>
                    <p class="text-center">Server Error 500. We apologise and are fixing the problem.</p>
                    <a href="{{url('index')}}" class="btn btn-primary d-flex align-items-center"><i
                            class="ti ti-arrow-left me-2"></i>Back to Dashboard</a>
                </div>
                <div class="text-center p-4"> 
                    <p>Copyright &copy; 2024 - Preskool</p>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection