<?php $page ='add-invoice'; ?>
@extends('layout.mainlayout')
@section('content')
<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content content-two">

        <!-- Page Header -->
        <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
            <div class="my-auto mb-2">
                <h3 class="page-title mb-1">Add Invoice</h3>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="{{url('index')}}">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{url('teachers')}}">Finance & Accounts</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Add Invoice</li>
                    </ol>
                </nav>
            </div>
        </div>
        <!-- /Page Header -->
        
        <div class="row">

            <div class="col-md-12">
                        
                <form action="{{url('accounts-invoices')}}">

                    <div class="card">
                        <div class="card-body pb-0">

                            <!-- Customer Logo -->
                            <div class="card">
                                <div class="card-header bg-light">
                                    <div class="d-flex align-items-center">
                                        <span class="bg-white avatar avatar-sm me-2 text-gray-7 flex-shrink-0">
                                            <i class="ti ti-user-check fs-16"></i>
                                        </span>
                                        <h4 class="text-dark">Company Logo</h4>
                                    </div>
                                </div>
                                <div class="card-body pb-1">
                                    <div class="profile-wrap mb-3">																					
                                        <div class="frames bg-white">
                                            <i class="ti ti-photo-plus"></i>
                                        </div>
                                        <div class="profile-upload">
                                            <div class="profile-uploader d-flex align-items-center">
                                                <div class="drag-upload-btn mb-3">
                                                    Upload
                                                    <input type="file" class="form-control image-sign"
                                                        multiple="">
                                                </div>
                                                <a href="javascript:void(0);"
                                                    class="btn btn-primary mb-3">Remove</a>
                                            </div>
                                            <p class="fs-12">Upload image size 4MB, Format JPG, PNG, SVG</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- /Customer Logo -->

                            <!-- Customer Information -->
                            <div class="card">
                                <div class="card-header bg-light">
                                    <div class="d-flex align-items-center">
                                        <span class="bg-white avatar avatar-sm me-2 text-gray-7 flex-shrink-0">
                                            <i class="ti ti-user-check fs-16"></i>
                                        </span>
                                        <h4 class="text-dark">Customer Information</h4>
                                    </div>
                                </div>
                                <div class="card-body pb-0">
                                        <div class="info-section">
                                            <div class="row">
                                                <div class="col-lg-3 col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Customer Name</label>
                                                        <select class="select">
                                                            <option>Select</option>
                                                            <option>Ralph Claudia</option>
                                                            <option>Julie Scott</option>
                                                            <option>Maria</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-lg-3 col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Invoice Number</label>
                                                        <input type="text" class="form-control">
                                                    </div>
                                                </div>
                                                <div class="col-lg-3 col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Invoice Date</label>
                                                        <div class="input-icon position-relative">
                                                            <span class="input-icon-addon">
                                                                <i class="ti ti-calendar"></i>
                                                            </span>
                                                            <input type="text" class="form-control datetimepicker" placeholder="dd-mm-yyyy">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-lg-3 col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Due Date</label>
                                                        <div class="input-icon position-relative">
                                                            <span class="input-icon-addon">
                                                                <i class="ti ti-calendar"></i>
                                                            </span>
                                                            <input type="text" class="form-control datetimepicker" placeholder="dd-mm-yyyy">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                </div>
                            </div>
                            <!-- /Customer Information -->

                            <!-- Product Information -->
                            <div class="card">
                                <div class="card-header bg-light">
                                    <div class="d-flex align-items-center">
                                        <span class="bg-white avatar avatar-sm me-2 text-gray-7 flex-shrink-0">
                                            <i class="ti ti-shopping-cart-copy fs-16"></i>
                                        </span>
                                        <h4 class="text-dark">Product Information</h4>
                                    </div>
                                </div>
                                <div class="card-body pb-0">
                                        <div class="info-section">
                                            <div class="row align-items-end">
                                                <div class="col-lg-3 col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Product Name</label>
                                                        <select class="select">
                                                            <option>Select</option>
                                                            <option>STEM Program Grant</option>
                                                            <option>Uniform</option>
                                                            <option>Library Donation</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-lg-3 col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Quantity</label>
                                                        <input type="number" class="form-control" placeholder="Enter Quantity">
                                                    </div>
                                                </div>
                                                <div class="col-lg-2">
                                                    <div class="mb-3">
                                                        <a href="#" class="btn btn-primary"><i class="ti ti-plus me-2"></i>Add to Bill</a>
                                                    </div>
                                                </div>
                                                <div class="col-md-12">
                                                    <div class="invoice-product-table">
                                                        <div class="table-responsive invoice-table">
                                                            <table class="table">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Product Name</th>
                                                                        <th>Quantity</th>
                                                                        <th>Unit Price</th>
                                                                        <th>Discount</th>
                                                                        <th>Net Amount</th>
                                                                        <th>Action</th>
                                                                    </tr>
                                                                    
                                                                </thead>
                                                                <tbody>
                                                                    <tr>
                                                                        <td>Uniform</td>
                                                                        <td>
                                                                            <input type="number" class="form-control" placeholder="0" value="">
                                                                        </td>
                                                                        <td>
                                                                            <input type="text" class="form-control" placeholder="0" value="">
                                                                        </td>
                                                                        <td>
                                                                            <input type="text" class="form-control" placeholder="%" value="">
                                                                        </td>
                                                                        <td>$0</td>
                                                                        <td>
                                                                            <a href="#" class="delete-invoive-list" data-bs-toggle="modal" data-bs-target="#delete-modal"><i class="ti ti-trash"></i></a>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Description</td>
                                                                        <td>
                                                                            <input type="number" class="form-control" placeholder="0">
                                                                        </td>
                                                                        <td>
                                                                            <input type="text" class="form-control" placeholder="0">
                                                                        </td>
                                                                        <td>
                                                                            <input type="text" class="form-control" placeholder="%">
                                                                        </td>
                                                                        <td>$0</td>
                                                                        <td>
                                                                            <a href="#" class="delete-invoive-list" data-bs-toggle="modal" data-bs-target="#delete-modal"><i class="ti ti-trash"></i></a>
                                                                        </td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                </div>
                            </div>
                            <!-- /Product Information -->

                            <div class="invoice-info">
                                <div class="row">
                                    <div class="col-xxl-9 col-lg-8">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="mb-3">
                                                    <label class="form-label">Notes</label>
                                                    <div class="summernote"></div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Terms & Conditions</label>
                                                    <div class="summernote"></div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                    </div>
                                    <div class="col-xxl-3 col-lg-4">
                                        <div class="card invoice-amount-details">
                                            <ul>
                                                <li>
                                                    <span>Subtotal</span>
                                                    <h6>$00.00</h6>
                                                </li>
                                                <li>
                                                    <span>Discount</span>
                                                    <h6>$00.00</h6>
                                                </li>
                                                <li>
                                                    <span>Tax</span>
                                                    <h6>$00.00</h6>
                                                </li>
                                                <li>
                                                    <h5>Total</h5>
                                                    <h5>$00.00</h5>
                                                </li>
                                            </ul>
                                            <div class="mb-3">
                                                <label class="form-label">Signature Name</label>
                                                <input type="text" class="form-control">
                                            </div>
                                            <div class="input-block service-upload service-upload-info mb-0">
                                                <span><i class="ti ti-upload me-2"></i>Upload Signature</span>
                                                <input type="file" multiple="" id="image_sign">
                                                <div id="frames"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="text-end">
                        <button type="button" class="btn btn-light me-3">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Invoice</button>
                    </div>

                </form>						

            </div>

        </div>

    </div>
</div>
<!-- /Page Wrapper -->


@component('components.modal-popup')
@endcomponent

@endsection