<?php $page = 'coming-soon'; ?>
@extends('layout.mainlayout')
@section('content')
<div class="text-center mt-4">
    <img src="{{URL::asset('build/img/logo.svg')}}" alt="img" class="img-fluid">
</div>
<div class="comming-soon-pg w-100">
    <div>
        <div class="error-img m-auto">
            <img src="{{URL::asset('build/img/authentication/coming-soon.svg')}}" class="img-fluid" alt="Img">
        </div>
        <ul class="d-inline-flex align-items-center justify-content-center mb-3">
            <li class="me-sm-3 me-2">
                <div class="d-flex align-items-center justify-content-center flex-column border rounded bg-white px-sm-4 py-sm-3 p-2">
                    <h3 class="days fs-sm-30 fs-30">54</h3>
                    <p class="fs-16">Day</p>
                </div>
            </li>
            <li class="text-gray fw-medium me-sm-3 me-2">:</li>
            <li class="me-sm-3 me-2">
                <div class="d-flex align-items-center justify-content-center flex-column border rounded bg-white px-sm-4 py-sm-3 p-2">
                    <h3 class="hours fs-sm-30 fs-30">10</h3>
                    <p class="fs-16">Hrs</p>
                </div>
            </li>
            <li class="text-gray fw-medium me-sm-3 me-2">:</li>
            <li class="me-sm-3 me-2">
                <div class="d-flex align-items-center justify-content-center flex-column border rounded bg-white px-sm-4 py-sm-3 p-2">
                    <h3 class="minutes fs-sm-30 fs-30">47</h3>
                    <p class="fs-16">Min</p>
                </div>
            </li>
            <li class="text-gray fw-medium me-sm-3 me-2">:</li>
            <li>
                <div class="d-flex align-items-center justify-content-center flex-column border rounded bg-white px-sm-4 py-sm-3 p-2">
                    <h3 class="seconds fs-sm-30 fs-30">00</h3>
                    <p class="fs-16">Sec</p>
                </div>
            </li>
        </ul>
        <div class="subscribe-form">
            <div class="mb-3 position-relative">
                <label class="d-flex form-label">Subscribe to get notified!</label>
                <div class="bg-white border border-2 p-1 d-flex align-items-center rounded ps-0">
                    <input type="email" class="form-control border-0" placeholder="Enter Your Email">
                    <a href="javascript:void(0);" class="btn btn-primary">Subscribe</a>
                </div>
            </div>
        </div>
        <div class="d-flex flex-wrap justify-content-center align-items-center">
            <a href="#" class="btn btn-dark btn-icon btn-sm me-2"><i class="ti ti-brand-facebook fs-16"></i></a>
            <a href="#" class="btn btn-dark btn-icon btn-sm me-2"><i class="ti ti-brand-instagram fs-16"></i></a>
            <a href="#" class="btn btn-dark btn-icon btn-sm me-2"><i class="ti ti-brand-twitter fs-16"></i></a>
            <a href="#" class="btn btn-dark btn-icon btn-sm me-2"><i class="ti ti-brand-pinterest fs-16"></i></a>
            <a href="#" class="btn btn-dark btn-icon btn-sm"><i class="ti ti-brand-linkedin fs-16"></i></a>
        </div>
    </div>
</div>
<div class="text-center mb-5">
    <p>Copyright &copy; 2024 - Preskool</p>
</div>

@endsection