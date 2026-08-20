<?php $page = 'parents'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
		<div class="content">
  
		  <!-- Page Header -->
		  <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
			<div class="my-auto mb-2">
			  <h3 class="page-title mb-1">Parents</h3>
			  <nav>
				<ol class="breadcrumb mb-0">
				  <li class="breadcrumb-item">
					<a href="{{url('index')}}">Dashboard</a>
				  </li>
				  <li class="breadcrumb-item">
					<a href="javascript:void(0);">People</a>
				  </li>
				  <li class="breadcrumb-item active" aria-current="page">Parents</li>
				</ol>
			  </nav>
			</div>
			<div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
			  <div class="pe-1 mb-2">
				<a href="#" class="btn btn-outline-light bg-white btn-icon me-1" data-bs-toggle="tooltip"
				  data-bs-placement="top" aria-label="Refresh" data-bs-original-title="Refresh">
				  <i class="ti ti-refresh"></i>
				</a>
			  </div>
			  <div class="pe-1 mb-2">
				<button type="button" class="btn btn-outline-light bg-white btn-icon me-1" data-bs-toggle="tooltip"
				  data-bs-placement="top" aria-label="Print" data-bs-original-title="Print">
				  <i class="ti ti-printer"></i>
				</button>
			  </div>
			  <div class="dropdown me-2 mb-2">
				<a href="javascript:void(0);"
				  class="dropdown-toggle btn btn-light fw-medium d-inline-flex align-items-center"
				  data-bs-toggle="dropdown">
				  <i class="ti ti-file-export me-2"></i>Export
				</a>
				<ul class="dropdown-menu  dropdown-menu-end p-3">
				  <li>
					<a href="javascript:void(0);" class="dropdown-item rounded-1"><i
						class="ti ti-file-type-pdf me-1"></i>Export as PDF</a>
				  </li>
				  <li>
					<a href="javascript:void(0);" class="dropdown-item rounded-1"><i
						class="ti ti-file-type-xls me-1"></i>Export as Excel </a>
				  </li>
				</ul>
			  </div>
			  <div class="mb-2">
				<a href="#" data-bs-toggle="modal" data-bs-target="#add_parent" class="btn btn-primary"><i
					class="ti ti-square-rounded-plus me-2"></i>Add Parent</a>
			  </div>
			</div>
		  </div>
		  <!-- /Page Header -->
  
		  <!-- Parent List -->
		  <div class="card">
			<div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
			  <h4 class="mb-3">Parents List</h4>
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
					data-bs-toggle="dropdown" data-bs-auto-close="outside"><i class="ti ti-filter me-2"></i>Filter</a>
				  <div class="dropdown-menu drop-width">
					<form action="{{url('parents')}}">
					  <div class="d-flex align-items-center border-bottom p-3">
						<h4>Filter</h4>
					  </div>
					  <div class="p-3 border-bottom pb-0">
						<div class="row">
						  <div class="col-md-12">
							<div class="mb-3">
							  <label class="form-label">Parent</label>
							  <select class="select">
								<option>Select</option>
								<option>Thomas</option>
								<option>Marquita</option>
								<option>Johnson</option>
							  </select>
							</div>
						  </div>
						  <div class="col-md-12">
							<div class="mb-3">
							  <label class="form-label">Child</label>
							  <select class="select">
								<option>Select</option>
								<option>Janet</option>
								<option>Joann</option>
								<option>Kathleen</option>
							  </select>
							</div>
						  </div>
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
				<div class="d-flex align-items-center bg-white border rounded-2 p-1 mb-3 me-2">
				  <a href="{{url('parents')}}" class="active btn btn-icon btn-sm me-1 primary-hover"><i
					  class="ti ti-list-tree"></i></a>
				  <a href="{{url('parent-grid')}}" class="btn btn-icon btn-sm bg-light primary-hover"><i
					  class="ti ti-grid-dots"></i></a>
				</div>
				<div class="dropdown mb-3">
				  <a href="javascript:void(0);" class="btn btn-outline-light bg-white dropdown-toggle"
					data-bs-toggle="dropdown"><i class="ti ti-sort-ascending-2 me-2"></i>Sort by A-Z </a>
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
						<div class="form-check form-check-md">
						  <input class="form-check-input" type="checkbox" id="select-all">
						</div>
					  </th>
					  <th>ID</th>
					  <th>Parent Name</th>
					  <th>Child</th>
					  <th>Phone </th>
					  <th>Email</th>
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
					  <td><a href="#" class="link-primary" data-bs-toggle="modal"
						  data-bs-target="#view_details">P124556</a></td>
					  <td>
						<div class="d-flex align-items-center">
						  <a href="#" class="avatar avatar-md" data-bs-toggle="modal" data-bs-target="#view_details"><img
							  src="{{URL::asset('build/img/parents/parent-01.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
						  <div class="ms-2">
							<p class="text-dark mb-0"><a href="#" data-bs-toggle="modal"
								data-bs-target="#view_details">Thomas</a></p>
							<span class="fs-12">Added on 25 Mar 2024</span>
						  </div>
						</div>
					  </td>
					  <td>
						<div class="d-flex align-items-center">
						  <a href="{{url('student-details')}}" class="avatar avatar-md"><img
							  src="{{URL::asset('build/img/students/student-01.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
						  <div class="ms-2">
							<p class="text-dark mb-0"><a href="#">Janet</a></p>
							<span class="fs-12">III, A</span>
						  </div>
						</div>
					  </td>
					  <td>+1 65738 58937</td>
					  <td>thomas@example.com</td>
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
								<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
								  data-bs-target="#view_details"><i class="ti ti-menu me-2"></i>View Parent</a>
							  </li>
							  <li>
								<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
								  data-bs-target="#edit_parent"><i class="ti ti-edit-circle me-2"></i>Edit</a>
							  </li>
							  <li>
								<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
								  data-bs-target="#delete-modal"><i class="ti ti-trash-x me-2"></i>Delete</a>
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
					  <td><a href="#" class="link-primary" data-bs-toggle="modal"
						  data-bs-target="#view_details">P124555</a></td>
					  <td>
						<div class="d-flex align-items-center">
						  <a href="#" class="avatar avatar-md" data-bs-toggle="modal" data-bs-target="#view_details"><img
							  src="{{URL::asset('build/img/parents/parent-02.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
						  <div class="ms-2">
							<p class="text-dark mb-0"><a href="#" data-bs-toggle="modal"
								data-bs-target="#view_details">Marquita</a></p>
							<span class="fs-12">Added on 18 Mar 2024</span>
						  </div>
						</div>
					  </td>
					  <td>
						<div class="d-flex align-items-center">
						  <a href="{{url('student-details')}}" class="avatar avatar-md"><img
							  src="{{URL::asset('build/img/students/student-02.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
						  <div class="ms-2">
							<p class="text-dark mb-0"><a href="{{url('student-details')}}">Joann</a></p>
							<span class="fs-12">IV, B</span>
						  </div>
						</div>
					  </td>
					  <td>+1 47958 41398</td>
					  <td>marquita@example.com</td>
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
								<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
								  data-bs-target="#view_details"><i class="ti ti-menu me-2"></i>View Parent</a>
							  </li>
							  <li>
								<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
								  data-bs-target="#edit_parent"><i class="ti ti-edit-circle me-2"></i>Edit</a>
							  </li>
							  <li>
								<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
								  data-bs-target="#delete-modal"><i class="ti ti-trash-x me-2"></i>Delete</a>
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
					  <td><a href="#" class="link-primary" data-bs-toggle="modal"
						  data-bs-target="#view_details">P124554</a></td>
					  <td>
						<div class="d-flex align-items-center">
						  <a href="#" class="avatar avatar-md" data-bs-toggle="modal" data-bs-target="#view_details"><img
							  src="{{URL::asset('build/img/parents/parent-03.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
						  <div class="ms-2">
							<p class="text-dark mb-0"><a href="#" data-bs-toggle="modal"
								data-bs-target="#view_details">Johnson</a></p>
							<span class="fs-12">Added on 14 Mar 2024</span>
						  </div>
						</div>
					  </td>
					  <td>
						<div class="d-flex align-items-center">
						  <a href="{{url('student-details')}}" class="avatar avatar-md"><img
							  src="{{URL::asset('build/img/students/student-03.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
						  <div class="ms-2">
							<p class="text-dark mb-0"><a href="{{url('student-details')}}">Kathleen</a></p>
							<span class="fs-12">III, A</span>
						  </div>
						</div>
					  </td>
					  <td>+1 78688 17496</td>
					  <td>johnson@example.com</td>
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
								<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
								  data-bs-target="#view_details"><i class="ti ti-menu me-2"></i>View Parent</a>
							  </li>
							  <li>
								<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
								  data-bs-target="#edit_parent"><i class="ti ti-edit-circle me-2"></i>Edit</a>
							  </li>
							  <li>
								<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
								  data-bs-target="#delete-modal"><i class="ti ti-trash-x me-2"></i>Delete</a>
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
					  <td><a href="#" class="link-primary" data-bs-toggle="modal"
						  data-bs-target="#view_details">P124553</a></td>
					  <td>
						<div class="d-flex align-items-center">
						  <a href="#" class="avatar avatar-md" data-bs-toggle="modal" data-bs-target="#view_details"><img
							  src="{{URL::asset('build/img/parents/parent-04.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
						  <div class="ms-2">
							<p class="text-dark mb-0"><a href="#" data-bs-toggle="modal"
								data-bs-target="#view_details">Claudia</a></p>
							<span class="fs-12">Added on 27 Feb 2024</span>
						  </div>
						</div>
					  </td>
					  <td>
						<div class="d-flex align-items-center">
						  <a href="{{url('student-details')}}" class="avatar avatar-md"><img
							  src="{{URL::asset('build/img/students/student-04.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
						  <div class="ms-2">
							<p class="text-dark mb-0"><a href="{{url('student-details')}}">Gifford</a></p>
							<span class="fs-12">I, B</span>
						  </div>
						</div>
					  </td>
					  <td>+1 72323 05904</td>
					  <td>claudia@example.com</td>
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
								<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
								  data-bs-target="#view_details"><i class="ti ti-menu me-2"></i>View Parent</a>
							  </li>
							  <li>
								<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
								  data-bs-target="#edit_parent"><i class="ti ti-edit-circle me-2"></i>Edit</a>
							  </li>
							  <li>
								<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
								  data-bs-target="#delete-modal"><i class="ti ti-trash-x me-2"></i>Delete</a>
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
					  <td><a href="#" class="link-primary" data-bs-toggle="modal"
						  data-bs-target="#view_details">P124552</a></td>
					  <td>
						<div class="d-flex align-items-center">
						  <a href="#" class="avatar avatar-md" data-bs-toggle="modal" data-bs-target="#view_details"><img
							  src="{{URL::asset('build/img/parents/parent-05.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
						  <div class="ms-2">
							<p class="text-dark mb-0"><a href="#" data-bs-toggle="modal"
								data-bs-target="#view_details">Arthur</a></p>
							<span class="fs-12">Added on 11 Feb 2024</span>
						  </div>
						</div>
					  </td>
					  <td>
						<div class="d-flex align-items-center">
						  <a href="{{url('student-details')}}" class="avatar avatar-md"><img
							  src="{{URL::asset('build/img/students/student-05.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
						  <div class="ms-2">
							<p class="text-dark mb-0"><a href="{{url('student-details')}}">Lisa</a></p>
							<span class="fs-12">II, B</span>
						  </div>
						</div>
					  </td>
					  <td>+1 74820 48201</td>
					  <td>arthur@example.com</td>
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
								<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
								  data-bs-target="#view_details"><i class="ti ti-menu me-2"></i>View Parent</a>
							  </li>
							  <li>
								<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
								  data-bs-target="#edit_parent"><i class="ti ti-edit-circle me-2"></i>Edit</a>
							  </li>
							  <li>
								<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
								  data-bs-target="#delete-modal"><i class="ti ti-trash-x me-2"></i>Delete</a>
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
					  <td><a href="#" class="link-primary" data-bs-toggle="modal"
						  data-bs-target="#view_details">P124551</a></td>
					  <td>
						<div class="d-flex align-items-center">
						  <a href="#" class="avatar avatar-md" data-bs-toggle="modal" data-bs-target="#view_details"><img
							  src="{{URL::asset('build/img/parents/parent-06.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
						  <div class="ms-2">
							<p class="text-dark mb-0"><a href="#" data-bs-toggle="modal"
								data-bs-target="#view_details">Colleen</a></p>
							<span class="fs-12">Added on 24 Jan 2024</span>
						  </div>
						</div>
					  </td>
					  <td>
						<div class="d-flex align-items-center">
						  <a href="{{url('student-details')}}" class="avatar avatar-md"><img
							  src="{{URL::asset('build/img/students/student-06.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
						  <div class="ms-2">
							<p class="text-dark mb-0"><a href="{{url('student-details')}}">Ralph</a></p>
							<span class="fs-12">III, B</span>
						  </div>
						</div>
					  </td>
					  <td>+1 46169 47202</td>
					  <td>colleen@example.com</td>
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
								<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
								  data-bs-target="#view_details"><i class="ti ti-menu me-2"></i>View Parent</a>
							  </li>
							  <li>
								<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
								  data-bs-target="#edit_parent"><i class="ti ti-edit-circle me-2"></i>Edit</a>
							  </li>
							  <li>
								<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
								  data-bs-target="#delete-modal"><i class="ti ti-trash-x me-2"></i>Delete</a>
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
					  <td><a href="#" class="link-primary" data-bs-toggle="modal"
						  data-bs-target="#view_details">P124550</a></td>
					  <td>
						<div class="d-flex align-items-center">
						  <a href="#" class="avatar avatar-md" data-bs-toggle="modal" data-bs-target="#view_details"><img
							  src="{{URL::asset('build/img/parents/parent-07.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
						  <div class="ms-2">
							<p class="text-dark mb-0"><a href="#" data-bs-toggle="modal"
								data-bs-target="#view_details">Robert</a></p>
							<span class="fs-12">Added on 19 Jan 2024</span>
						  </div>
						</div>
					  </td>
					  <td>
						<div class="d-flex align-items-center">
						  <a href="{{url('student-details')}}" class="avatar avatar-md"><img
							  src="{{URL::asset('build/img/students/student-07.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
						  <div class="ms-2">
							<p class="text-dark mb-0"><a href="{{url('student-details')}}">Julie</a></p>
							<span class="fs-12">V, A</span>
						  </div>
						</div>
					  </td>
					  <td>+1 44990 27830</td>
					  <td>robert@example.com</td>
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
								<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
								  data-bs-target="#view_details"><i class="ti ti-menu me-2"></i>View Parent</a>
							  </li>
							  <li>
								<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
								  data-bs-target="#edit_parent"><i class="ti ti-edit-circle me-2"></i>Edit</a>
							  </li>
							  <li>
								<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
								  data-bs-target="#delete-modal"><i class="ti ti-trash-x me-2"></i>Delete</a>
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
					  <td><a href="#" class="link-primary" data-bs-toggle="modal"
						  data-bs-target="#view_details">P124549</a></td>
					  <td>
						<div class="d-flex align-items-center">
						  <a href="#" class="avatar avatar-md" data-bs-toggle="modal" data-bs-target="#view_details"><img
							  src="{{URL::asset('build/img/parents/parent-08.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
						  <div class="ms-2">
							<p class="text-dark mb-0"><a href="#" data-bs-toggle="modal"
								data-bs-target="#view_details">Jessie</a></p>
							<span class="fs-12">Added on 08 Jan 2024</span>
						  </div>
						</div>
					  </td>
					  <td>
						<div class="d-flex align-items-center">
						  <a href="{{url('student-details')}}" class="avatar avatar-md"><img
							  src="{{URL::asset('build/img/students/student-08.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
						  <div class="ms-2">
							<p class="text-dark mb-0"><a href="{{url('student-details')}}">Janet</a></p>
							<span class="fs-12">III, A</span>
						  </div>
						</div>
					  </td>
					  <td>+1 84634 04823</td>
					  <td>jessie@example.com</td>
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
								<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
								  data-bs-target="#view_details"><i class="ti ti-menu me-2"></i>View Parent</a>
							  </li>
							  <li>
								<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
								  data-bs-target="#edit_parent"><i class="ti ti-edit-circle me-2"></i>Edit</a>
							  </li>
							  <li>
								<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
								  data-bs-target="#delete-modal"><i class="ti ti-trash-x me-2"></i>Delete</a>
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
					  <td><a href="#" class="link-primary" data-bs-toggle="modal"
						  data-bs-target="#view_details">P124548</a></td>
					  <td>
						<div class="d-flex align-items-center">
						  <a href="#" class="avatar avatar-md" data-bs-toggle="modal" data-bs-target="#view_details"><img
							  src="{{URL::asset('build/img/parents/parent-09.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
						  <div class="ms-2">
							<p class="text-dark mb-0"><a href="#" data-bs-toggle="modal"
								data-bs-target="#view_details">Michael</a></p>
							<span class="fs-12">Added on 22 Dec 2024</span>
						  </div>
						</div>
					  </td>
					  <td>
						<div class="d-flex align-items-center">
						  <a href="{{url('student-details')}}" class="avatar avatar-md"><img
							  src="{{URL::asset('build/img/students/student-09.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
						  <div class="ms-2">
							<p class="text-dark mb-0"><a href="{{url('student-details')}}">Susan</a></p>
							<span class="fs-12">VIII, B</span>
						  </div>
						</div>
					  </td>
					  <td>+1 91038 68460</td>
					  <td>michael@example.com</td>
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
								<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
								  data-bs-target="#view_details"><i class="ti ti-menu me-2"></i>View Parent</a>
							  </li>
							  <li>
								<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
								  data-bs-target="#edit_parent"><i class="ti ti-edit-circle me-2"></i>Edit</a>
							  </li>
							  <li>
								<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
								  data-bs-target="#delete-modal"><i class="ti ti-trash-x me-2"></i>Delete</a>
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
					  <td><a href="#" class="link-primary" data-bs-toggle="modal"
						  data-bs-target="#view_details">P124547</a></td>
					  <td>
						<div class="d-flex align-items-center">
						  <a href="#" class="avatar avatar-md" data-bs-toggle="modal" data-bs-target="#view_details"><img
							  src="{{URL::asset('build/img/parents/parent-10.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
						  <div class="ms-2">
							<p class="text-dark mb-0"><a href="#" data-bs-toggle="modal"
								data-bs-target="#view_details">Mary</a></p>
							<span class="fs-12">Added on 15 Dec 2024</span>
						  </div>
						</div>
					  </td>
					  <td>
						<div class="d-flex align-items-center">
						  <a href="{{url('student-details')}}" class="avatar avatar-md"><img
							  src="{{URL::asset('build/img/students/student-10.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
						  <div class="ms-2">
							<p class="text-dark mb-0"><a href="{{url('student-details')}}">Richard</a></p>
							<span class="fs-12">VII, B</span>
						  </div>
						</div>
					  </td>
					  <td>+1 35835 71839</td>
					  <td>mary@example.com</td>
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
								<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
								  data-bs-target="#view_details"><i class="ti ti-menu me-2"></i>View Parent</a>
							  </li>
							  <li>
								<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
								  data-bs-target="#edit_parent"><i class="ti ti-edit-circle me-2"></i>Edit</a>
							  </li>
							  <li>
								<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
								  data-bs-target="#delete-modal"><i class="ti ti-trash-x me-2"></i>Delete</a>
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
					  <td><a href="#" class="link-primary" data-bs-toggle="modal"
						  data-bs-target="#view_details">P124546</a></td>
					  <td>
						<div class="d-flex align-items-center">
						  <a href="#" class="avatar avatar-md" data-bs-toggle="modal" data-bs-target="#view_details"><img
							  src="{{URL::asset('build/img/parents/parent-11.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
						  <div class="ms-2">
							<p class="text-dark mb-0"><a href="#" data-bs-toggle="modal"
								data-bs-target="#view_details">Edwin</a></p>
							<span class="fs-12">Added on 10 Dec 2023</span>
						  </div>
						</div>
					  </td>
					  <td>
						<div class="d-flex align-items-center">
						  <a href="{{url('student-details')}}" class="avatar avatar-md"><img
							  src="{{URL::asset('build/img/students/student-11.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
						  <div class="ms-2">
							<p class="text-dark mb-0"><a href="{{url('student-details')}}">Veronica</a></p>
							<span class="fs-12">IX, A</span>
						  </div>
						</div>
					  </td>
					  <td>+1 65738 58940</td>
					  <td>edw@example.com</td>
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
								<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
								  data-bs-target="#view_details"><i class="ti ti-menu me-2"></i>View Parent</a>
							  </li>
							  <li>
								<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
								  data-bs-target="#edit_parent"><i class="ti ti-edit-circle me-2"></i>Edit</a>
							  </li>
							  <li>
								<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
								  data-bs-target="#delete-modal"><i class="ti ti-trash-x me-2"></i>Delete</a>
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
