<?php $page = 'invoice'; ?>
@extends('layout.mainlayout')
@section('content')
<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content content-two">

        <!-- Page Header -->
        <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
            <div class="my-auto mb-2">
                <h3 class="page-title mb-1">Invoice</h3>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="{{url('index')}}">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{url('teachers')}}">Finance & Accounts</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">View Invoice</li>
                    </ol>
                </nav>
            </div>
        </div>
        <!-- /Page Header -->

        <div class="row">

            <div class="col-md-12">
                <div class="invoice-popup-head d-flex align-items-center justify-content-between mb-4">
                    <a href="{{url('index')}}" class="invoice-logo-dark">
                        <span class="logo-lg">
                            <img src="{{URL::asset('build/img/logo.svg')}}" alt="Logo" height="42">
                        </span>
                    </a>
                    <a href="{{url('index')}}" class="invoice-logo-white">
                        <span class="logo-lg"><img src="{{URL::asset('build/img/logo-dark.svg')}}" alt="logo" height="42"></span>
                    </a>
                    <div class="popup-title">
                        <h2>UNIVERSITY NAME</h2>
                        <p>Original For Recipient</p>
                    </div>
                </div>
                <div class="tax-info mb-2">
                    <div class="mb-4 text-center">
                        <h1>Tax Invoice</h1>
                    </div>
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="tax-invoice-info d-flex align-items-center justify-content-between">
                                <h5>Student Name :</h5>
                                <h6>Walter Roberson</h6>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="tax-invoice-info d-flex align-items-center justify-content-between">
                                <h5>Student ID :</h5>
                                <h6>DD465123</h6>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="tax-invoice-info d-flex align-items-center justify-content-between">
                                <h5>Term :</h5>
                                <h6>Term 1</h6>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="tax-invoice-info d-flex align-items-center justify-content-between">
                                <h5>Invoice No :</h5>
                                <h6>INV681531</h6>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="tax-invoice-info d-flex align-items-center justify-content-between">
                                <h5>Invoice Date :</h5>
                                <h6>24 Apr 2024</h6>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="tax-invoice-info d-flex align-items-center justify-content-between">
                                <h5>Due Date :</h5>
                                <h6>30 Apr 2024</h6>
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <h6 class="mb-1">Bill To :</h6>
                        <p><span class="text-dark">Walter Roberson</span> <br>
                            299 Star Trek Drive, Panama City, Florida, 32405, USA. <br>
                            walter@gmail.com <br>
                            +45 5421 4523
                        </p>
                    </div>
                    <div class="invoice-product-table">
                        <div class="table-responsive invoice-table">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Description</th>
                                        <th>Due Date</th>
                                        <th>Amount</th>
                                    </tr>

                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            Semester Fees
                                        </td>
                                        <td>
                                            25 Apr 2024
                                        </td>
                                        <td>
                                            $5,000
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            Exam Fees
                                        </td>
                                        <td>
                                            25 Apr 2024
                                        </td>
                                        <td>
                                            $1000
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            Transport Fees
                                        </td>
                                        <td>
                                            25 Apr 2024
                                        </td>
                                        <td>
                                            $4,000
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <h5 class="mb-1">Important Note: </h5>
                                <p class="text-dark mb-0">Delivery dates are not guaranteed and Seller has</p>
                                <p class="text-dark">no liability for damages that may be incurred
                                    due to any delay. has
                                </p>
                            </div>
                            <div>
                                <h5 class="mb-1">Total amount ( in words):</h5>
                                <p class="text-dark fw-medium">USD Ten Thousand One Hundred Sixty Five Only</p>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="total-amount-tax">
                                <ul>
                                    <li class="fw-medium text-dark">Subtotal</li>

                                    <li class="fw-medium text-dark">Discount 0%</li>

                                    <li class="fw-medium text-dark">IGST 18.0%</li>

                                </ul>
                                <ul>
                                    <li>$10,000.00</li>
                                    <li>+ $0.00</li>
                                    <li>$10,000.00</li>
                                </ul>
                            </div>
                            <div class="total-amount-tax mb-3">
                                <ul class="total-amount">
                                    <li class="text-dark">Amount Payable</li>
                                </ul>
                                <ul class="total-amount">
                                    <li class="text-dark">$10,165.00</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="payment-info">
                        <div class="row align-items-center">
                            <div class="col-lg-6 mb-4 pt-4">
                                <h5 class="mb-2">Payment Info:</h5>
                                <p class="mb-1">Debit Card : <span class="fw-medium text-dark">465
                                        *************645</span></p>
                                <p class="mb-0">Amount : <span class="fw-medium text-dark">$10,165</span></p>
                            </div>
                            <div class="col-lg-6 text-end mb-4 pt-4 ">
                                <h6 class="mb-2">For Dreamguys</h6>
                                <img src="{{URL::asset('build/img/icons/signature.svg')}}" alt="Img">
                            </div>
                        </div>
                    </div>
                    <div class="border-bottom text-center pt-4 pb-4">
                        <span class="text-dark fw-medium">Terms & Conditions : </span>
                        <p>Here we can write a additional notes for the client to get a better understanding of
                            this invoice.</p>
                    </div>
                    <p class="text-center pt-3">Thanks for your Business</p>
                </div>

            </div>

        </div>

    </div>
</div>
<!-- /Page Wrapper -->

@endsection