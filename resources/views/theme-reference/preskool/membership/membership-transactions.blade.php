<?php $page = 'membership-transactions'; ?>
@extends('layout.mainlayout')
@section('content')
<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content">
        @component('components.breadcrumb')
            @slot('title')
            Transactions
            @endslot
            @slot('item1')
            Dashboard
            @endslot
            @slot('item2')
            Membership
            @endslot
            @slot('item3')
            Transactions
            @endslot
           @endcomponent
       
    <!-- Filter Section -->
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
                <h4 class="mb-3">Transactions List</h4>
                <div class="d-flex align-items-center flex-wrap">
                    <div class="input-icon-start mb-3 me-2 position-relative">
                        <span class="icon-addon">
                            <i class="ti ti-calendar"></i>
                        </span>
                        <input type="text" class="form-control date-range bookingrange" placeholder="Select"
                        value="Academic Year : 2024 / 2025">
                    </div>
                    <div class="dropdown mb-3 me-2">
                        <a href="javascript:void(0);" class="btn btn-outline-light bg-white dropdown-toggle"
                            data-bs-toggle="dropdown" data-bs-auto-close="outside"><i
                                class="ti ti-filter me-2"></i>Filter</a>
                        <div class="dropdown-menu drop-width">
                            <form action="{{url('membership-transactions')}}">
                                <div class="d-flex align-items-center border-bottom p-3">
                                    <h4>Filter</h4>
                                </div>
                                <div class="p-3 border-bottom">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="mb-3">
                                                <label class="form-label">Provider Name</label>
                                                <select class="select">
                                                    <option>Select</option>
                                                    <option>Green Valley High School</option>
                                                    <option>Riverside Academy</option>
                                                    <option>Sunshine School</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="mb-0">
                                                <label class="form-label">Plan Type</label>
                                                <select class="select">
                                                    <option>Select</option>
                                                    <option>Starter</option>
                                                    <option>Enterprise</option>
                                                    <option>Starter</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-3 d-flex align-items-center justify-content-end">
                                    <a href="#" class="btn btn-light me-3">Reset</a>
                                    <button type="submit" class="btn btn-primary">Apply</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="dropdown mb-3">
                        <a href="javascript:void(0);" class="btn btn-outline-light bg-white dropdown-toggle"
                            data-bs-toggle="dropdown"><i class="ti ti-sort-ascending-2 me-2"></i>Sort by A-Z
                        </a>
                        <ul class="dropdown-menu p-3">
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1 active">
                                    Ascending
                                </a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                    Descending
                                </a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                    Recently Viewed
                                </a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                    Recently Added
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="card-body p-0 py-3">
                <!-- Transaction List -->
                <div class="custom-datatable-filter table-responsive">
                    <table class="table datatable">
                        <thead class="thead-light">
                            <tr>
                                <th class="no-sort">
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox" id="select-all">
                                    </div>
                                </th>
                                <th>ID</th>
                                <th>Provider Name</th>
                                <th>Plan Type</th>
                                <th>Transaction Date</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td><a href="#" class="link-primary">MT156328</a></td>
                                <td>
                                    Green Valley High School
                                </td>
                                <td>Starter</td>
                                <td>25 Apr 2024</td>
                                <td>$99</td>
                                <td>
                                    Cash
                                </td>
                                <td>
                                    25 Apr 2024
                                </td>
                                <td>24 May 2024</td>
                                <td>
                                    <span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                        class="ti ti-circle-filled fs-5 me-1"></i>Completed</span>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td><a href="#" class="link-primary">MT156327</a></td>
                                <td>
                                    Riverside Academy
                                </td>
                                <td>Enterprise</td>
                                <td>29 Apr 2024</td>
                                <td>$149</td>
                                <td>
                                    Credit
                                </td>
                                <td>
                                    29 Apr 2024
                                </td>
                                <td>28 May 2024</td>
                                <td>
                                    <span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                        class="ti ti-circle-filled fs-5 me-1"></i>Completed</span>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td><a href="#" class="link-primary">MT156326</a></td>
                                <td>
                                    Sunshine School
                                </td>
                                <td>Starter</td>
                                <td>03 May 2024</td>
                                <td>$99</td>
                                <td>
                                    Cash
                                </td>
                                <td>
                                    03 May 2024
                                </td>
                                <td>02 Jun 2024</td>
                                <td>
                                    <span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                        class="ti ti-circle-filled fs-5 me-1"></i>Completed</span>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td><a href="#" class="link-primary">MT156325</a></td>
                                <td>
                                    Maplewood High School
                                </td>
                                <td>Premium</td>
                                <td>15 May 2024</td>
                                <td>$199</td>
                                <td>
                                    Cash
                                </td>
                                <td>
                                    15 May 2024
                                </td>
                                <td>14 Jun 2024</td>
                                <td>
                                    <span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                        class="ti ti-circle-filled fs-5 me-1"></i>Completed</span>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td><a href="#" class="link-primary">MT156324</a></td>
                                <td>
                                    Mountain High School
                                </td>
                                <td>Enterprise</td>
                                <td>15 May 2024</td>
                                <td>$149</td>
                                <td>
                                    Cash
                                </td>
                                <td>
                                    25 May 2024
                                </td>
                                <td>24 Jun 2024</td>
                                <td>
                                    <span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                        class="ti ti-circle-filled fs-5 me-1"></i>Completed</span>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td><a href="#" class="link-primary">MT156323</a></td>
                                <td>
                                    Hillside Elementary School
                                </td>
                                <td>Starter</td>
                                <td>12 Jun 2024</td>
                                <td>$99</td>
                                <td>
                                    Credit
                                </td>
                                <td>
                                    12 Jun 2024
                                </td>
                                <td>11 Jul 2024</td>
                                <td>
                                    <span class="badge badge-soft-warning d-inline-flex align-items-center"><i
                                        class="ti ti-circle-filled fs-5 me-1"></i>Pending</span>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td><a href="#" class="link-primary">MT156322</a></td>
                                <td>
                                    Willow Creek High School
                                </td>
                                <td>Starter</td>
                                <td>17 Jun 2024</td>
                                <td>$99</td>
                                <td>
                                    Cash
                                </td>
                                <td>
                                    17 Jun 2024
                                </td>
                                <td>16 Jul 2024</td>
                                <td>
                                    <span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                        class="ti ti-circle-filled fs-5 me-1"></i>Completed</span>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td><a href="#" class="link-primary">MT156321</a></td>
                                <td>
                                    Brookside High School
                                </td>
                                <td>Premium</td>
                                <td>26 Jun 2024</td>
                                <td>$199</td>
                                <td>
                                    Cash
                                </td>
                                <td>
                                    26 Jun 2024
                                </td>
                                <td>25 Jul 2024</td>
                                <td>
                                    <span class="badge badge-soft-warning d-inline-flex align-items-center"><i
                                        class="ti ti-circle-filled fs-5 me-1"></i>Pending</span>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td><a href="#" class="link-primary">MT156320</a></td>
                                <td>
                                    Cedar Grove Academy
                                </td>
                                <td>Enterprise</td>
                                <td>03 Jul 2024</td>
                                <td>$149</td>
                                <td>
                                    Credit
                                </td>
                                <td>
                                    03 Jul 2024
                                </td>
                                <td>02 Aug 2024</td>
                                <td>
                                    <span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                        class="ti ti-circle-filled fs-5 me-1"></i>Completed</span>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td><a href="#" class="link-primary">MT156319</a></td>
                                <td>
                                    Westfield Elementary School
                                </td>
                                <td>Starter</td>
                                <td>12 Jul 2024</td>
                                <td>$99</td>
                                <td>
                                    Cash
                                </td>
                                <td>
                                    12 Jul 2024
                                </td>
                                <td>11 Aug 2024</td>
                                <td>
                                    <span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                        class="ti ti-circle-filled fs-5 me-1"></i>Completed</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- /Transaction List -->
            </div>

        </div>
        <!-- /Filter Section -->



    </div>
</div>
<!-- /Page Wrapper -->	

@component('components.modal-popup')        
@endcomponent
@endsection