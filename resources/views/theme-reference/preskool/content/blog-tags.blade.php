<?php $page = 'blog-tags'; ?>
@extends('layout.mainlayout')
@section('content')
<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content">
        @component('components.breadcrumb')
        @slot('title')
        Tags
        @endslot
        @slot('item1')
        Dashboard
        @endslot
        @slot('item2')
        Blog
        @endslot
        @slot('item3')
        Tags
        @endslot
       @endcomponent
        
        <!-- Filter Section -->
        <div class="card">

            <!-- Table Filter -->
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
                <h4 class="mb-3">Tags List</h4>
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
                            <form action="{{url('blog-tags')}}">
                                <div class="d-flex align-items-center border-bottom p-3">
                                    <h4>Filter</h4>
                                </div>
                                <div class="p-3 border-bottom">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Tags</label>
                                                <select class="select">
                                                    <option>Select</option>
                                                    <option>Guide</option>
                                                    <option>Development</option>
                                                    <option>Education</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Added Date</label>
                                                <select class="select">
                                                    <option>Select</option>
                                                    <option>25 May 2024</option>
                                                    <option>24 May 2024</option>
                                                    <option>15 Mar 2024</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="mb-0">
                                                <label class="form-label">Status</label>
                                                <select class="select">
                                                    <option>Select</option>
                                                    <option>Active</option>
                                                    <option>Inactive</option>
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
            <!-- /Table Filter -->

            <div class="card-body p-0 py-3">
                <!-- Tags List -->
                <div class="custom-datatable-filter
                table-responsive">
                    <table class="table datatable">
                        <thead class="thead-light">
                            <tr>
                                <th class="no-sort">
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox" id="select-all">
                                    </div>
                                </th>
                                <th>Tags</th>
                                <th>Added on</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td class="text-gray-9">Guide</td>
                                <td class="text-gray-9">25 May 2024</td>
                                <td><span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                            class="ti ti-circle-filled fs-5 me-1"></i>Active</span></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="dropdown">
                                            <a href="#"
                                                class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="ti ti-dots-vertical fs-14"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-right p-3">
                                                <li>
                                                    <a class="dropdown-item rounded-1" href="#"
                                                        data-bs-toggle="modal" data-bs-target="#edit_tags"><i
                                                            class="ti ti-edit-circle me-2"></i>Edit</a>
                                                </li>


                                                <li>
                                                    <a class="dropdown-item rounded-1" href="#"
                                                        data-bs-toggle="modal" data-bs-target="#delete-modal"><i
                                                            class="ti ti-trash-x me-2"></i>Delete</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td class="text-gray-9">Development</td>
                                <td class="text-gray-9">24 May 2024</td>
                                <td><span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                            class="ti ti-circle-filled fs-5 me-1"></i>Active</span></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="dropdown">
                                            <a href="#"
                                                class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="ti ti-dots-vertical fs-14"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-right p-3">
                                                <li>
                                                    <a class="dropdown-item rounded-1" href="#"
                                                        data-bs-toggle="modal" data-bs-target="#edit_tags"><i
                                                            class="ti ti-edit-circle me-2"></i>Edit</a>
                                                </li>


                                                <li>
                                                    <a class="dropdown-item rounded-1" href="#"
                                                        data-bs-toggle="modal" data-bs-target="#delete-modal"><i
                                                            class="ti ti-trash-x me-2"></i>Delete</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td class="text-gray-9">Education</td>
                                <td class="text-gray-9">15 Mar 2024</td>
                                <td><span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                            class="ti ti-circle-filled fs-5 me-1"></i>Active</span></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="dropdown">
                                            <a href="#"
                                                class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="ti ti-dots-vertical fs-14"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-right p-3">
                                                <li>
                                                    <a class="dropdown-item rounded-1" href="#"
                                                        data-bs-toggle="modal" data-bs-target="#edit_tags"><i
                                                            class="ti ti-edit-circle me-2"></i>Edit</a>
                                                </li>


                                                <li>
                                                    <a class="dropdown-item rounded-1" href="#"
                                                        data-bs-toggle="modal" data-bs-target="#delete-modal"><i
                                                            class="ti ti-trash-x me-2"></i>Delete</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td class="text-gray-9">Chances</td>
                                <td class="text-gray-9">21 Feb 2024</td>
                                <td><span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                            class="ti ti-circle-filled fs-5 me-1"></i>Active</span></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="dropdown">
                                            <a href="#"
                                                class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="ti ti-dots-vertical fs-14"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-right p-3">
                                                <li>
                                                    <a class="dropdown-item rounded-1" href="#"
                                                        data-bs-toggle="modal" data-bs-target="#edit_tags"><i
                                                            class="ti ti-edit-circle me-2"></i>Edit</a>
                                                </li>


                                                <li>
                                                    <a class="dropdown-item rounded-1" href="#"
                                                        data-bs-toggle="modal" data-bs-target="#delete-modal"><i
                                                            class="ti ti-trash-x me-2"></i>Delete</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td class="text-gray-9">Management</td>

                                <td class="text-gray-9">15 Feb 2024</td>
                                <td><span class="badge badge-soft-danger d-inline-flex align-items-center"><i
                                            class="ti ti-circle-filled fs-5 me-1"></i>Inctive</span></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="dropdown">
                                            <a href="#"
                                                class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="ti ti-dots-vertical fs-14"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-right p-3">
                                                <li>
                                                    <a class="dropdown-item rounded-1" href="#"
                                                        data-bs-toggle="modal" data-bs-target="#edit_tags"><i
                                                            class="ti ti-edit-circle me-2"></i>Edit</a>
                                                </li>


                                                <li>
                                                    <a class="dropdown-item rounded-1" href="#"
                                                        data-bs-toggle="modal" data-bs-target="#delete-modal"><i
                                                            class="ti ti-trash-x me-2"></i>Delete</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td class="text-gray-9">Sports</td>
                                <td class="text-gray-9">12 Feb 2024</td>
                                <td><span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                            class="ti ti-circle-filled fs-5 me-1"></i>Active</span></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="dropdown">
                                            <a href="#"
                                                class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="ti ti-dots-vertical fs-14"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-right p-3">
                                                <li>
                                                    <a class="dropdown-item rounded-1" href="#"
                                                        data-bs-toggle="modal" data-bs-target="#edit_tags"><i
                                                            class="ti ti-edit-circle me-2"></i>Edit</a>
                                                </li>


                                                <li>
                                                    <a class="dropdown-item rounded-1" href="#"
                                                        data-bs-toggle="modal" data-bs-target="#delete-modal"><i
                                                            class="ti ti-trash-x me-2"></i>Delete</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td class="text-gray-9">Guidance</td>
                                <td class="text-gray-9">14 Jan 2024</td>
                                <td><span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                            class="ti ti-circle-filled fs-5 me-1"></i>Active</span></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="dropdown">
                                            <a href="#"
                                                class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="ti ti-dots-vertical fs-14"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-right p-3">
                                                <li>
                                                    <a class="dropdown-item rounded-1" href="#"
                                                        data-bs-toggle="modal" data-bs-target="#edit_tags"><i
                                                            class="ti ti-edit-circle me-2"></i>Edit</a>
                                                </li>


                                                <li>
                                                    <a class="dropdown-item rounded-1" href="#"
                                                        data-bs-toggle="modal" data-bs-target="#delete-modal"><i
                                                            class="ti ti-trash-x me-2"></i>Delete</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td class="text-gray-9">Location</td>
                                <td class="text-gray-9">12 Jan 2024</td>
                                <td><span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                            class="ti ti-circle-filled fs-5 me-1"></i>Active</span></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="dropdown">
                                            <a href="#"
                                                class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="ti ti-dots-vertical fs-14"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-right p-3">
                                                <li>
                                                    <a class="dropdown-item rounded-1" href="#"
                                                        data-bs-toggle="modal" data-bs-target="#edit_tags"><i
                                                            class="ti ti-edit-circle me-2"></i>Edit</a>
                                                </li>


                                                <li>
                                                    <a class="dropdown-item rounded-1" href="#"
                                                        data-bs-toggle="modal" data-bs-target="#delete-modal"><i
                                                            class="ti ti-trash-x me-2"></i>Delete</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td class="text-gray-9">Hostel</td>
                                <td class="text-gray-9">16 Dec 2023</td>
                                <td><span class="badge badge-soft-danger d-inline-flex align-items-center"><i
                                            class="ti ti-circle-filled fs-5 me-1"></i>Inactive</span></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="dropdown">
                                            <a href="#"
                                                class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="ti ti-dots-vertical fs-14"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-right p-3">
                                                <li>
                                                    <a class="dropdown-item rounded-1" href="#"
                                                        data-bs-toggle="modal" data-bs-target="#edit_tags"><i
                                                            class="ti ti-edit-circle me-2"></i>Edit</a>
                                                </li>


                                                <li>
                                                    <a class="dropdown-item rounded-1" href="#"
                                                        data-bs-toggle="modal" data-bs-target="#delete-modal"><i
                                                            class="ti ti-trash-x me-2"></i>Delete</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td class="text-gray-9">Academic</td>
                                <td class="text-gray-9">12 Dec 2023</td>
                                <td><span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                            class="ti ti-circle-filled fs-5 me-1"></i>Active</span></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="dropdown">
                                            <a href="#"
                                                class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="ti ti-dots-vertical fs-14"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-right p-3">
                                                <li>
                                                    <a class="dropdown-item rounded-1" href="#"
                                                        data-bs-toggle="modal" data-bs-target="#edit_tags"><i
                                                            class="ti ti-edit-circle me-2"></i>Edit</a>
                                                </li>


                                                <li>
                                                    <a class="dropdown-item rounded-1" href="#"
                                                        data-bs-toggle="modal" data-bs-target="#delete-modal"><i
                                                            class="ti ti-trash-x me-2"></i>Delete</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- /Tags List -->
            </div>

        </div>
        <!-- /Filter Section -->
    </div>
</div>
<!-- /Page Wrapper -->

@component('components.modal-popup')        
@endcomponent

@endsection