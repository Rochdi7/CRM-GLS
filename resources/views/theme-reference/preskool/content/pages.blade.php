<?php $page = 'pages'; ?>
@extends('layout.mainlayout')
@section('content')
<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content">
        @component('components.breadcrumb')
        @slot('title')
        Pages
        @endslot
        @slot('item1')
        Dashboard
        @endslot
        @slot('item2')
        Content
        @endslot
        @slot('item3')
        Pages
        @endslot
       @endcomponent
        
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
                <h4 class="mb-3">Pages List</h4>
                <div class="d-flex align-items-center flex-wrap">
                    <div class="input-icon-start mb-3 me-2 position-relative">
                        <span class="icon-addon">
                            <i class="ti ti-calendar"></i>
                        </span>
                        <input type="text" class="form-control  date-range bookingrange" placeholder="Select"
                            value="Academic Year : 2024 / 2025">
                    </div>
                    <div class="dropdown mb-3 me-2">
                        <a href="javascript:void(0);" class="btn btn-outline-light bg-white dropdown-toggle"
                            data-bs-toggle="dropdown" data-bs-auto-close="outside"><i
                                class="ti ti-filter me-2"></i>Filter</a>
                        <div class="dropdown-menu drop-width">
                            <form action="{{url('pages')}}">
                                <div class="d-flex align-items-center border-bottom p-3">
                                    <h4>Filter</h4>
                                </div>
                                <div class="p-3 border-bottom">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="mb-3">
                                                <label class="form-label">Page</label>
                                                <select class="select">
                                                    <option>Select</option>
                                                    <option>Students</option>
                                                    <option>Teachers</option>
                                                    <option>Parents</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="mb-0">
                                                <label class="form-label">Page Slug</label>
                                                <select class="select">
                                                    <option>Select</option>
                                                    <option>students</option>
                                                    <option>teachers</option>
                                                    <option>parents</option>
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
                <!-- Pages List -->
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
                                <th>Page</th>
                                <th>Page Slug</th>
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
                                <td>Students</td>
                                <td>students</td>
                                <td>
                                    <div class="status-toggle modal-status">
                                        <input type="checkbox" id="user1" class="check">
                                        <label for="user1" class="checktoggle"> </label>
                                    </div>
                                </td>

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
                                                    <a class="dropdown-item rounded-1" href="#"><i
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
                                <td>Teachers</td>
                                <td>teachers</td>
                                <td>
                                    <div class="status-toggle modal-status">
                                        <input type="checkbox" id="user2" class="check">
                                        <label for="user2" class="checktoggle"> </label>
                                    </div>
                                </td>

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
                                                    <a class="dropdown-item rounded-1" href="#"><i
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
                                <td>Parents</td>
                                <td>parents</td>
                                <td>
                                    <div class="status-toggle modal-status">
                                        <input type="checkbox" id="user3" class="check">
                                        <label for="user3" class="checktoggle"> </label>
                                    </div>
                                </td>

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
                                                    <a class="dropdown-item rounded-1" href="#"><i
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
                                <td>Guardians</td>
                                <td>guardians</td>
                                <td>
                                    <div class="status-toggle modal-status">
                                        <input type="checkbox" id="user4" class="check">
                                        <label for="user4" class="checktoggle"> </label>
                                    </div>
                                </td>

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
                                                    <a class="dropdown-item rounded-1" href="#"><i
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
                                <td>Classes</td>
                                <td>classes</td>
                                <td>
                                    <div class="status-toggle modal-status">
                                        <input type="checkbox" id="user5" class="check">
                                        <label for="user5" class="checktoggle"> </label>
                                    </div>
                                </td>

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
                                                    <a class="dropdown-item rounded-1" href="#"><i
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
                                <td>Hostel</td>
                                <td>hostel</td>
                                <td>
                                    <div class="status-toggle modal-status">
                                        <input type="checkbox" id="user6" class="check">
                                        <label for="user6" class="checktoggle"> </label>
                                    </div>
                                </td>

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
                                                    <a class="dropdown-item rounded-1" href="#"><i
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
                <!-- /Pages List -->
            </div>
        </div>
    </div>
</div>
<!-- /Page Wrapper -->


@component('components.modal-popup')        
@endcomponent
@endsection