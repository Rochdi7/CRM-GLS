<?php $page = 'sports'; ?>
@extends('layout.mainlayout')
@section('content')
 	<!-- Page Wrapper -->
     <div class="page-wrapper">
        <div class="content">

            @component('components.breadcrumb')
            @slot('title')
            Sports
            @endslot
            @slot('item1')
            Dashboard
            @endslot
            @slot('item2')
            Management
            @endslot
            @slot('item3')
            Sports
            @endslot
           @endcomponent

            <!-- Students List -->
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
                    <h4 class="mb-3">Sports</h4>
                    <div class="d-flex align-items-center flex-wrap">		
                        <div class="input-icon-start mb-3 me-2 position-relative">
                            <span class="icon-addon">
                                <i class="ti ti-calendar"></i>
                            </span>
                            <input type="text" class="form-control  date-range bookingrange" placeholder="Select" value="Academic Year : 2024 / 2025">
                        </div>
                        <div class="dropdown mb-3 me-2">
                            <a href="javascript:void(0);" class="btn btn-outline-light bg-white dropdown-toggle"  data-bs-toggle="dropdown"  data-bs-auto-close="outside"><i class="ti ti-filter me-2"></i>Filter</a>
                            <div class="dropdown-menu drop-width">
                                <form action="{{url('sports')}}">
                                    <div class="d-flex align-items-center border-bottom p-3">
                                        <h4>Filter</h4>
                                    </div>
                                    <div class="p-3 border-bottom">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Name</label>
                                                    <select class="select">
                                                        <option>Select</option>
                                                        <option>Cricket</option>
                                                        <option>Throwball</option>
                                                        <option>Football</option>
                                                        <option>Tennis</option>
                                                        <option>Basketball</option>
                                                        
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Coach</label>
                                                    <select class="select">
                                                        <option>Select</option>
                                                        <option>Thomas</option>
                                                        <option>Georgia</option>
                                                        <option>Nicholas</option>
                                                        <option>Sandra </option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="mb-0">
                                                    <label class="form-label">More Filter</label>
                                                    <select class="select">
                                                        <option>Filters</option>
                                                        <option>ID</option>
                                                        <option>Name</option>
                                                        <option>Coach</option>
                                                        <option>Started Year</option>
                                                        <option>Action</option>
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
                            <a href="javascript:void(0);" class="btn btn-outline-light bg-white dropdown-toggle"  data-bs-toggle="dropdown"><i class="ti ti-sort-ascending-2 me-2"></i>Sort by A-Z </a>
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
                                    <th class="no-sort">
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox" id="select-all">
                                        </div>
                                    </th>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Coach</th>
                                    <th>Started Year</th>
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
                                    <td><a href="#" class="link-primary">SP826329</a></td>
                                    <td>Cricket</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="#" class="avatar avatar-md"><img src="{{URL::asset('build/img/coach/coach-01.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a href="#">Thomas</a>
                                                </p>
                                            </div>
                                        </div>		
                                    </td>
                                    <td>2004</td>
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
                                                            data-bs-toggle="modal" data-bs-target="#edit_sports"><i
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
                                    <td><a href="#" class="link-primary">SP826328</a></td>
                                    <td>Throwball</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="#" class="avatar avatar-md"><img src="{{URL::asset('build/img/coach/coach-02.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a href="#">Georgia</a>
                                                </p>
                                            </div>
                                        </div>	
                                    </td>
                                    <td>2005</td>
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
                                                            data-bs-toggle="modal" data-bs-target="#edit_sports"><i
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
                                    <td><a href="#" class="link-primary">SP826327</a></td>
                                    <td>Football</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="#" class="avatar avatar-md"><img src="{{URL::asset('build/img/coach/coach-03.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a href="#">	Nicholas</a>
                                                </p>
                                            </div>
                                        </div>	
                                    </td>
                                    <td>2006</td>
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
                                                            data-bs-toggle="modal" data-bs-target="#edit_sports"><i
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
                                    <td><a href="#" class="link-primary">SP826326</a></td>
                                    <td>Tennis</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="#" class="avatar avatar-md"><img src="{{URL::asset('build/img/coach/coach-04.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a href="#">Sandra</a>
                                                </p>
                                            </div>
                                        </div>		
                                    </td>
                                    <td>2007</td>
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
                                                            data-bs-toggle="modal" data-bs-target="#edit_sports"><i
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
                                    <td><a href="#" class="link-primary">SP826325</a></td>
                                    <td>Basketball</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="#" class="avatar avatar-md"><img src="{{URL::asset('build/img/coach/coach-05.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a href="#">Jon</a>
                                                </p>
                                            </div>
                                        </div>		
                                    </td>
                                    <td>2008</td>
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
                                                            data-bs-toggle="modal" data-bs-target="#edit_sports"><i
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
                                    <td><a href="#" class="link-primary">SP826324</a></td>
                                    <td>Badminton</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="#" class="avatar avatar-md"><img src="{{URL::asset('build/img/coach/coach-06.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a href="#">Shannon</a>
                                                </p>
                                            </div>
                                        </div>	
                                    </td>
                                    <td>2009</td>
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
                                                            data-bs-toggle="modal" data-bs-target="#edit_sports"><i
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
                                    <td><a href="#" class="link-primary">SP826323</a></td>
                                    <td>Carrom</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="#" class="avatar avatar-md"><img src="{{URL::asset('build/img/coach/coach-07.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a href="#">Wilson</a>
                                                </p>
                                            </div>
                                        </div>	
                                    </td>
                                    <td>2010</td>
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
                                                            data-bs-toggle="modal" data-bs-target="#edit_sports"><i
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
                                    <td><a href="#" class="link-primary">SP826322</a></td>
                                    <td>Chess</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="#" class="avatar avatar-md"><img src="{{URL::asset('build/img/coach/coach-08.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a href="#">Sonia</a>
                                                </p>
                                            </div>
                                        </div>	
                                    </td>
                                    <td>2011</td>
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
                                                            data-bs-toggle="modal" data-bs-target="#edit_sports"><i
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
                                    <td><a href="#" class="link-primary">SP826321</a></td>
                                    <td>Volleyball</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="#" class="avatar avatar-md"><img src="{{URL::asset('build/img/coach/coach-09.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a href="#">Adams</a>
                                                </p>
                                            </div>
                                        </div>	
                                    </td>
                                    <td>2012</td>
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
                                                            data-bs-toggle="modal" data-bs-target="#edit_sports"><i
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
                                    <td><a href="#" class="link-primary">SP826320</a></td>
                                    <td>Hockey</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="#" class="avatar avatar-md"><img src="{{URL::asset('build/img/coach/coach-10.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a href="#">Lydia</a>
                                                </p>
                                            </div>
                                        </div>	
                                    </td>
                                    <td>2013</td>
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
                                                            data-bs-toggle="modal" data-bs-target="#edit_sports"><i
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
                    <!-- /Student List -->
                </div>
            </div>
            <!-- /Students List -->				

        </div>
    </div>
    <!-- /Page Wrapper -->
    @component('components.modal-popup')        
    @endcomponent
@endsection
