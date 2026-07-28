<?php $page = 'fees-report'; ?>
@extends('layout.mainlayout')
@section('content')
<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content">
        @component('components.breadcrumb')
        @slot('title')
        Fees Report
        @endslot
        @slot('item1')
        Dashboard
        @endslot
        @slot('item2')
        Report
        @endslot
        @slot('item3')
        Fees Report
        @endslot
       @endcomponent
       

        <!-- Student List -->
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
                <h4 class="mb-3">Fees Report List</h4>
                <div class="d-flex align-items-center flex-wrap">
                    <div class="input-icon-start mb-3 me-2 position-relative">
                        <span class="icon-addon">
                            <i class="ti ti-calendar"></i>
                        </span>
                        <input type="text" class="form-control  date-range bookingrange"
                            placeholder="Select" value="Academic Year : 2024 / 2025">
                    </div>
                    <div class="dropdown mb-3 me-2">
                        <a href="javascript:void(0);"
                            class="btn btn-outline-light bg-white dropdown-toggle"
                            data-bs-toggle="dropdown" data-bs-auto-close="outside"><i
                                class="ti ti-filter me-2"></i>Filter</a>
                        <div class="dropdown-menu drop-width">
                            <form action="{{url('fees-report')}}">
                                <div class="d-flex align-items-center border-bottom p-3">
                                    <h4>Filter</h4>
                                </div>
                                <div class="p-3 border-bottom">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="mb-3">
                                                <label class="form-label">Class</label>
                                                <select class="select">
                                                    <option>Select</option>
                                                    <option>I</option>
                                                    <option>II</option>
                                                    <option>III</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="mb-3">
                                                <label class="form-label">Section</label>
                                                <select class="select">
                                                    <option>Select</option>
                                                    <option>A</option>
                                                    <option>B</option>
                                                    <option>C</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="mb-0">
                                                <label class="form-label">Students</label>
                                                <select class="select">
                                                    <option>Select</option>
                                                    <option>Janet</option>
                                                    <option>Joann</option>
                                                    <option>Kathleen</option>
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
                        <a href="javascript:void(0);"
                            class="btn btn-outline-light bg-white dropdown-toggle"
                            data-bs-toggle="dropdown"><i
                                class="ti ti-sort-ascending-2 me-2"></i>Sort by A-Z </a>
                        <ul class="dropdown-menu p-3">
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1">
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
                <!-- Student List -->
                <div class="custom-datatable-filter table-responsive">
                    <table class="table datatable">
                        <thead class="thead-light">
                            <tr>
                                <th>Fees Group</th>
                                <th>Fees Code</th>
                                <th>Due Date</th>
                                <th>Amount $ </th>
                                <th>Status</th>
                                <th>Ref ID</th>
                                <th>Mode</th>
                                <th>Date Paid</th>
                                <th>Discount ($)</th>
                                <th>Fine ($)</th>
                                <th>Balance ($)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <p class="link-primary">Class 1 General <span class="d-block"> (Admission Fees)</span></p>
                                </td>
                                <td>admission-fees</td>
                                <td>25 Mar 2024</td>
                                <td>2000</td>
                                <td><span class="badge badge-soft-success d-inline-flex align-items-center"><i class="ti ti-circle-filled fs-5 me-1"></i>Paid</span></td>
                                <td>#435454</td>
                                <td>Cash</td>
                                <td>25 Jan 2024</td>
                                <td>10%</td>
                                <td>200</td>
                                <td>0</td>
                            </tr>
                            <tr>
                                <td>
                                    <p class="link-primary">Class 1 General <span class="d-block"> (Mar month fees)</span></p>
                                </td>
                                <td>mar-month-fees</td>
                                <td>10 Apr 2024</td>
                                <td>2500</td>
                                <td><span class="badge badge-soft-success d-inline-flex align-items-center"><i class="ti ti-circle-filled fs-5 me-1"></i>Paid</span></td>
                                <td>#435453</td>
                                <td>Cash</td>
                                <td>03 Apr 2024</td>
                                <td>10%</td>
                                <td>0</td>
                                <td>0</td>
                            </tr>
                            <tr>
                                <td>
                                    <p class="link-primary">Class 1 General <span class="d-block"> (Apr month Fees)</span></p>
                                </td>
                                <td>apr-month-fees</td>
                                <td>10 May 2024</td>
                                <td>2500</td>
                                <td><span class="badge badge-soft-success d-inline-flex align-items-center"><i class="ti ti-circle-filled fs-5 me-1"></i>Paid</span></td>
                                <td>#435453</td>
                                <td>Cash</td>
                                <td>03 Apr 2024</td>
                                <td>10%</td>
                                <td>0</td>
                                <td>0</td>
                            </tr>
                            <tr>
                                <td>
                                    <p class="link-primary">Class 1 General <span class="d-block"> (May month Fees)</span></p>
                                </td>
                                <td>may-month-fees</td>
                                <td>10 Jun 2024</td>
                                <td>2500</td>
                                <td><span class="badge badge-soft-success d-inline-flex align-items-center"><i class="ti ti-circle-filled fs-5 me-1"></i>Paid</span></td>
                                <td>#435451</td>
                                <td>Cash</td>
                                <td>02 Jun 2024</td>
                                <td>10%</td>
                                <td>200</td>
                                <td>0</td>
                            </tr>
                            <tr>
                                <td>
                                    <p class="link-primary">Class 1 General <span class="d-block"> (Jun month Fees)</span></p>
                                </td>
                                <td>jun-month-fees</td>
                                <td>10 Jul 2024</td>
                                <td>2500</td>
                                <td><span class="badge badge-soft-success d-inline-flex align-items-center"><i class="ti ti-circle-filled fs-5 me-1"></i>Paid</span></td>
                                <td>#435450</td>
                                <td>Cash</td>
                                <td>05 Jul 2024</td>
                                <td>10%</td>
                                <td>200</td>
                                <td>0</td>
                            </tr>
                            <tr>
                                <td>
                                    <p class="link-primary">Class 1 General <span class="d-block"> (Jul month Fees)</span></p>
                                </td>
                                <td>jul-month-fees</td>
                                <td>10 Aug 2024</td>
                                <td>2500</td>
                                <td><span class="badge badge-soft-success d-inline-flex align-items-center"><i class="ti ti-circle-filled fs-5 me-1"></i>Paid</span></td>
                                <td>#435449</td>
                                <td>Cash</td>
                                <td>01 Aug 2024</td>
                                <td>10%</td>
                                <td>200</td>
                                <td>0</td>
                            </tr>
                            <tr>
                                <td>
                                    <p class="link-primary">Class 1 General <span class="d-block"> (Dec month Fees)</span></p>
                                </td>
                                <td>dec-month-fees</td>
                                <td>10 Jan 2024</td>
                                <td>2500</td>
                                <td><span class="badge badge-soft-success d-inline-flex align-items-center"><i class="ti ti-circle-filled fs-5 me-1"></i>Paid</span></td>
                                <td>#435443</td>
                                <td>Cash</td>
                                <td>05 Jan 2024</td>
                                <td>10%</td>
                                <td>0</td>
                                <td>0</td>
                            </tr>
                            <tr>
                                <td>
                                    <p class="link-primary">Class 1 General <span class="d-block"> (Jan month Fees)</span></p>
                                </td>
                                <td>jan-month-fees</td>
                                <td>10 Feb 2024</td>
                                <td>2000</td>
                                <td><span class="badge badge-soft-success d-inline-flex align-items-center"><i class="ti ti-circle-filled fs-5 me-1"></i>Paid</span></td>
                                <td>#435443</td>
                                <td>Cash</td>
                                <td>01 Feb 2024</td>
                                <td>10%</td>
                                <td>200</td>
                                <td>0</td>
                            </tr>
                            <tr class="bg-dark">
                                <td></td>
                                <td></td>
                                <td></td>
                                <td>2000</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td>200</td>
                                <td>200</td>
                                <td>0</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- /Student List -->
            </div>
        </div>
        <!-- /Student List -->

    </div>
</div>
<!-- /Page Wrapper -->

@endsection