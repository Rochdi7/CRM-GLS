<?php $page = 'class-report'; ?>
@extends('layout.mainlayout')
@section('content')
<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content">
        @component('components.breadcrumb')
        @slot('title')
		Class Report
        @endslot
        @slot('item1')
        Dashboard
        @endslot
        @slot('item2')
		Report
        @endslot
        @slot('item3')
		Class Report
        @endslot
       @endcomponent
        
       

        <!-- Student List -->
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
                <h4 class="mb-3">Class Report List</h4>
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
                            <form action="{{url('class-report')}}">
                                <div class="d-flex align-items-center border-bottom p-3">
                                    <h4>Filter</h4>
                                </div>
                                <div class="p-3 border-bottom">
                                    <div class="row">
                                        <div class="col-md-6">
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
                                        <div class="col-md-6">
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
                                                <label class="form-label">No Of Students</label>
                                                <select class="select">
                                                    <option>Select</option>
                                                    <option>30</option>
                                                    <option>35</option>
                                                    <option>40</option>
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
                <!-- Student List -->
                <div class="custom-datatable-filter table-responsive">
                    <table class="table datatable">
                        <thead class="thead-light">
                            <tr>
                                <th class="no-sort">
                                    <label class="checkboxs"><input type="checkbox" id="select-all"><span
                                            class="checkmarks"></span></label>
                                </th>
                                <th>ID</th>
                                <th>Class</th>
                                <th>Section</th>
                                <th>No of Students</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <label class="checkboxs">
                                        <input type="checkbox">
                                        <span class="checkmarks"></span>
                                    </label>
                                </td>
                                <td><a href="#" class="link-primary">C138038</a></td>
                                <td>I</td>
                                <td>A</td>
                                <td>30</td>
                                <td>
                                    <a href="#" class="btn btn-light view details" data-bs-toggle="modal"
                                        data-bs-target="#view_class_report">View Details</a>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label class="checkboxs">
                                        <input type="checkbox">
                                        <span class="checkmarks"></span>
                                    </label>
                                </td>
                                <td><a href="#" class="link-primary">C138037</a></td>
                                <td>I</td>
                                <td>B</td>
                                <td>25</td>
                                <td>
                                    <a href="#" class="btn btn-light view detail" data-bs-toggle="modal"
                                        data-bs-target="#view_class_report">View Details</a>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label class="checkboxs">
                                        <input type="checkbox">
                                        <span class="checkmarks"></span>
                                    </label>
                                </td>
                                <td><a href="#" class="link-primary">C138036</a></td>
                                <td>II</td>
                                <td>A</td>
                                <td>40</td>
                                <td>
                                    <a href="#" class="btn btn-light view detail" data-bs-toggle="modal"
                                        data-bs-target="#view_class_report">View Details</a>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label class="checkboxs">
                                        <input type="checkbox">
                                        <span class="checkmarks"></span>
                                    </label>
                                </td>
                                <td><a href="#" class="link-primary">C138035</a></td>
                                <td>II</td>
                                <td>B</td>
                                <td>35</td>
                                <td>
                                    <a href="#" class="btn btn-light view detail" data-bs-toggle="modal"
                                        data-bs-target="#view_class_report">View Details</a>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label class="checkboxs">
                                        <input type="checkbox">
                                        <span class="checkmarks"></span>
                                    </label>
                                </td>
                                <td><a href="#" class="link-primary">C138034</a></td>
                                <td>II</td>
                                <td>C</td>
                                <td>25</td>
                                <td>
                                    <a href="#" class="btn btn-light view detail" data-bs-toggle="modal"
                                        data-bs-target="#view_class_report">View Details</a>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label class="checkboxs">
                                        <input type="checkbox">
                                        <span class="checkmarks"></span>
                                    </label>
                                </td>
                                <td><a href="#" class="link-primary">C138033</a></td>
                                <td>III</td>
                                <td>A</td>
                                <td>30</td>
                                <td>
                                    <a href="#" class="btn btn-light view detail" data-bs-toggle="modal"
                                        data-bs-target="#view_class_report">View Details</a>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label class="checkboxs">
                                        <input type="checkbox">
                                        <span class="checkmarks"></span>
                                    </label>
                                </td>
                                <td><a href="#" class="link-primary">C138032</a></td>
                                <td>III</td>
                                <td>B</td>
                                <td>25</td>
                                <td>
                                    <a href="#" class="btn btn-light view detail" data-bs-toggle="modal"
                                        data-bs-target="#view_class_report">View Details</a>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label class="checkboxs">
                                        <input type="checkbox">
                                        <span class="checkmarks"></span>
                                    </label>
                                </td>
                                <td><a href="#" class="link-primary">C138031</a></td>
                                <td>IV</td>
                                <td>A</td>
                                <td>20</td>
                                <td>
                                    <a href="#" class="btn btn-light view detail" data-bs-toggle="modal"
                                        data-bs-target="#view_class_report">View Details</a>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label class="checkboxs">
                                        <input type="checkbox">
                                        <span class="checkmarks"></span>
                                    </label>
                                </td>
                                <td><a href="#" class="link-primary">C138030</a></td>
                                <td>IV</td>
                                <td>B</td>
                                <td>30</td>
                                <td>
                                    <a href="#" class="btn btn-light view detail" data-bs-toggle="modal"
                                        data-bs-target="#view_class_report">View Details</a>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label class="checkboxs">
                                        <input type="checkbox">
                                        <span class="checkmarks"></span>
                                    </label>
                                </td>
                                <td><a href="#" class="link-primary">C138029</a></td>
                                <td>V</td>
                                <td>A</td>
                                <td>35</td>
                                <td>
                                    <a href="#" class="btn btn-light view detail" data-bs-toggle="modal"
                                        data-bs-target="#view_class_report">View Details</a>
                                </td>
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
	  @component('components.modal-popup')
    @endcomponent
@endsection
