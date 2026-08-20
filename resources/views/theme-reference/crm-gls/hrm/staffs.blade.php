<?php $page = 'staffs'; ?>
@extends('layout.mainlayout')
@section('content')
 
<!-- Page Wrapper -->
<div class="page-wrapper">
	<div class="content">

		<!-- Page Header -->
		<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
			<div class="my-auto mb-2">
				<h3 class="page-title mb-1">Staffs</h3>
				 <nav>
					   <ol class="breadcrumb mb-0">
						  <li class="breadcrumb-item">
							<a href="{{url('index')}}">Dashboard</a>
						  </li>
						  <li class="breadcrumb-item">
							<a href="javascript:void(0);">HRM
							</a>
						  </li>
						  <li class="breadcrumb-item active" aria-current="page">Staffs</li>
					</ol>
				  </nav>
			</div>
			<div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
				  <div class="pe-1 mb-2">
					   <a href="#" class="btn btn-outline-light bg-white btn-icon me-1" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Refresh" data-bs-original-title="Refresh">
						 <i class="ti ti-refresh"></i>
					</a>
				</div>
				  <div class="pe-1 mb-2">
					<button type="button" class="btn btn-outline-light bg-white btn-icon me-1" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Print" data-bs-original-title="Print">
						 <i class="ti ti-printer"></i>
					</button>
				 </div>    
				<div class="dropdown me-2 mb-2">
					<a href="javascript:void(0);" class="dropdown-toggle btn btn-light fw-medium d-inline-flex align-items-center"  data-bs-toggle="dropdown">
						<i class="ti ti-file-export me-2"></i>Export
					</a>
					<ul class="dropdown-menu  dropdown-menu-end p-3">
						<li>
							<a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-file-type-pdf me-1"></i>Export as PDF</a>
						</li>
						<li>
							<a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-file-type-xls me-1"></i>Export as Excel </a>
						</li>
					</ul>
				</div>                  
				  <div class="mb-2">
				
					<a href="{{url('add-staff')}}" class="btn btn-primary d-flex align-items-center"><i
						class="ti ti-square-rounded-plus me-2"></i>Add Staff</a>
				  </div>
			</div>
		</div>
		<!-- /Page Header -->

		
		<div class="card">
			<div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
				<h4 class="mb-3">Staff List</h4>
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
							<form action="{{url('staffs')}}">
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
													<option>Kevin</option>
													<option>Willie</option>
													<option>Daniel</option>
													<option>Teresa</option>
													<option>Johnson</option>
												</select>
											</div>
										</div>
										<div class="col-md-6">
											<div class="mb-3">
												<label class="form-label">Department</label>
												<select class="select">
													<option>Select</option>
													<option>Admin</option>
													<option>Management</option>
													<option>Finance</option>
												</select>
											</div>
										</div>
										<div class="col-md-6">
											<div class="mb-0">
												<label class="form-label">Designation</label>
												<select class="select">
													<option>Select</option>
													<option>Technical Head</option>
													<option>Receptionist</option>
													<option>Admin</option>
													<option>Accountant</option>
													<option>HR Manager</option>
												</select>
											</div>
										</div>
										<div class="col-md-6">
											<div class="mb-0">
												<label class="form-label">More Filter</label>
												<select class="select">
													<option>Select</option>
													<option>ID</option>
													<option>Name</option>
													<option>Department</option>
													<option>Designation</option>
													<option>Phone</option>
													<option>Email</option>
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

				<!-- Staffs List -->
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
							<th>Department</th>
							<th>Designation </th>
							<th>Phone</th>
							<th>Email</th>
							<th>Date of Join</th>
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
								<td><a href="{{url('staff-details')}}" class="link-primary">8198</a></td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('staff-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/profiles/avatar-27.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('staff-details')}}">Kevin</a>
											</p>
										</div>
									</div>
								</td>
								<td>Admin</td>
								<td>Technical Head</td>
								<td>+1 63423 72397</td>
								<td>
									kevin@example.com
								</td>
								<td>
									10 Mar 2024
								</td>
								<td>
									<div class="dropdown">
										<a href="#" class="action-icon " data-bs-toggle="dropdown"
											aria-expanded="false">
											<i class="ti ti-dots-vertical"></i>
										</a>
										<div class="dropdown-menu dropdown-menu-right">
											<ul>
												<li>
													<a class="dropdown-item" href="{{url('staff-details')}}"><i class="ti ti-menu"></i>View Staff</a>
												</li>
												<li>
													<a class="dropdown-item" href="{{url('edit-staff')}}"><i class="ti ti-edit-circle"></i>Edit</a>
												</li>
												<li>
													<a class="dropdown-item" href="#" data-bs-toggle="modal"
														data-bs-target="#delete-modal"><i class="ti ti-trash-x"></i>
														Delete</a>
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
								<td><a href="{{url('staff-details')}}" class="link-primary">8197</a></td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('staff-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/teachers/teacher-05.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('staff-details')}}">Willie</a>
											</p>
										</div>
									</div>
								</td>
								<td>Management</td>
								<td>Receptionist</td>
								<td>+1 82913 61371</td>
								<td>
									willie@example.com
								</td>
								<td>
									16 Mar 2024
								</td>
								<td>
									<div class="dropdown">
										<a href="#" class="action-icon " data-bs-toggle="dropdown"
											aria-expanded="false">
											<i class="ti ti-dots-vertical"></i>
										</a>
										<div class="dropdown-menu dropdown-menu-right">
											<ul>
												<li>
													<a class="dropdown-item" href="{{url('staff-details')}}"><i class="ti ti-menu"></i>View Staff</a>
												</li>
												<li>
													<a class="dropdown-item" href="{{url('edit-staff')}}"><i class="ti ti-edit-circle"></i>Edit</a>
												</li>
												<li>
													<a class="dropdown-item" href="#" data-bs-toggle="modal"
														data-bs-target="#delete-modal"><i class="ti ti-trash-x"></i>
														Delete</a>
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
								<td><a href="{{url('staff-details')}}" class="link-primary">8196</a></td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('staff-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/teachers/teacher-02.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('staff-details')}}">Daniel</a>
											</p>
										</div>
									</div>
								</td>
								<td>Management</td>
								<td>Admin</td>
								<td>+1 56752 86742</td>
								<td>
									daniel@example.com
								</td>
								<td>
									28 Mar 2024
								</td>
								<td>
									<div class="dropdown">
										<a href="#" class="action-icon " data-bs-toggle="dropdown"
											aria-expanded="false">
											<i class="ti ti-dots-vertical"></i>
										</a>
										<div class="dropdown-menu dropdown-menu-right">
											<ul>
												<li>
													<a class="dropdown-item" href="{{url('staff-details')}}"><i class="ti ti-menu"></i>View Staff</a>
												</li>
												<li>
													<a class="dropdown-item" href="{{url('edit-staff')}}"><i class="ti ti-edit-circle"></i>Edit</a>
												</li>
												<li>
													<a class="dropdown-item" href="#" data-bs-toggle="modal"
														data-bs-target="#delete-modal"><i class="ti ti-trash-x"></i>
														Delete</a>
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
								<td><a href="{{url('staff-details')}}" class="link-primary">8195</a></td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('staff-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/teachers/teacher-01.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('staff-details')}}">Teresa</a>
											</p>
										</div>
									</div>
								</td>
								<td>Management</td>
								<td>Admin</td>
								<td>+1 82392 37359</td>
								<td>
									teresa@example.com
								</td>
								<td>
									25 Mar 2024
								</td>
								<td>
									<div class="dropdown">
										<a href="#" class="action-icon " data-bs-toggle="dropdown"
											aria-expanded="false">
											<i class="ti ti-dots-vertical"></i>
										</a>
										<div class="dropdown-menu dropdown-menu-right">
											<ul>
												<li>
													<a class="dropdown-item" href="{{url('staff-details')}}"><i class="ti ti-menu"></i>View Staff</a>
												</li>
												<li>
													<a class="dropdown-item" href="{{url('edit-staff')}}"><i class="ti ti-edit-circle"></i>Edit</a>
												</li>
												<li>
													<a class="dropdown-item" href="#" data-bs-toggle="modal"
														data-bs-target="#delete-modal"><i class="ti ti-trash-x"></i>
														Delete</a>
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
								<td><a href="{{url('staff-details')}}" class="link-primary">8194</a></td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('staff-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/teachers/teacher-06.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('staff-details')}}">Johnson</a>
											</p>
										</div>
									</div>
								</td>
								<td>Finance</td>
								<td>Accountant</td>
								<td>+1 53619 54691</td>
								<td>
									johnson@example.com
								</td>
								<td>
									04 Apr 2024
								</td>
								<td>
									<div class="dropdown">
										<a href="#" class="action-icon " data-bs-toggle="dropdown"
											aria-expanded="false">
											<i class="ti ti-dots-vertical"></i>
										</a>
										<div class="dropdown-menu dropdown-menu-right">
											<ul>
												<li>
													<a class="dropdown-item" href="{{url('staff-details')}}"><i class="ti ti-menu"></i>View Staff</a>
												</li>
												<li>
													<a class="dropdown-item" href="{{url('edit-staff')}}"><i class="ti ti-edit-circle"></i>Edit</a>
												</li>
												<li>
													<a class="dropdown-item" href="#" data-bs-toggle="modal"
														data-bs-target="#delete-modal"><i class="ti ti-trash-x"></i>
														Delete</a>
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
								<td><a href="{{url('staff-details')}}" class="link-primary">8193</a></td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('staff-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/teachers/teacher-03.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('staff-details')}}">Hellana</a>
											</p>
										</div>
									</div>
								</td>
								<td>Management</td>
								<td>HR Manager</td>
								<td>+1 23566 52683</td>
								<td>
									hellana@example.com
								</td>
								<td>
									11 Apr 2024
								</td>
								<td>
									<div class="dropdown">
										<a href="#" class="action-icon " data-bs-toggle="dropdown"
											aria-expanded="false">
											<i class="ti ti-dots-vertical"></i>
										</a>
										<div class="dropdown-menu dropdown-menu-right">
											<ul>
												<li>
													<a class="dropdown-item" href="{{url('staff-details')}}"><i class="ti ti-menu"></i>View Staff</a>
												</li>
												<li>
													<a class="dropdown-item" href="{{url('edit-staff')}}"><i class="ti ti-edit-circle"></i>Edit</a>
												</li>
												<li>
													<a class="dropdown-item" href="#" data-bs-toggle="modal"
														data-bs-target="#delete-modal"><i class="ti ti-trash-x"></i>
														Delete</a>
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
								<td><a href="{{url('staff-details')}}" class="link-primary">8192</a></td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('staff-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/members/members-01.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('staff-details')}}">James</a>
											</p>
										</div>
									</div>
								</td>
								<td>Library</td>
								<td>Librarian</td>
								<td>+1 78429 82414</td>
								<td>
									james@example.com
								</td>
								<td>
									22 Apr 2024
								<td>
									<div class="dropdown">
										<a href="#" class="action-icon " data-bs-toggle="dropdown"
											aria-expanded="false">
											<i class="ti ti-dots-vertical"></i>
										</a>
										<div class="dropdown-menu dropdown-menu-right">
											<ul>
												<li>
													<a class="dropdown-item" href="{{url('staff-details')}}"><i class="ti ti-menu"></i>View Staff</a>
												</li>
												<li>
													<a class="dropdown-item" href="{{url('edit-staff')}}"><i class="ti ti-edit-circle"></i>Edit</a>
												</li>
												<li>
													<a class="dropdown-item" href="#" data-bs-toggle="modal"
														data-bs-target="#delete-modal"><i class="ti ti-trash-x"></i>
														Delete</a>
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
								<td><a href="{{url('staff-details')}}" class="link-primary">8191</a></td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('staff-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/teachers/teacher-07.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('staff-details')}}">Jacquelin</a>
											</p>
										</div>
									</div>
								</td>
								<td>Transport</td>
								<td>Driver</td>
								<td>+1 77502 54845</td>
								<td>
									jacquelin@example.com
								</td>
								<td>
									20 May 2024
								</td>
								<td>
									<div class="dropdown">
										<a href="#" class="action-icon " data-bs-toggle="dropdown"
											aria-expanded="false">
											<i class="ti ti-dots-vertical"></i>
										</a>
										<div class="dropdown-menu dropdown-menu-right">
											<ul>
												<li>
													<a class="dropdown-item" href="{{url('staff-details')}}"><i class="ti ti-menu"></i>View Staff</a>
												</li>
												<li>
													<a class="dropdown-item" href="{{url('edit-staff')}}"><i class="ti ti-edit-circle"></i>Edit</a>
												</li>
												<li>
													<a class="dropdown-item" href="#" data-bs-toggle="modal"
														data-bs-target="#delete-modal"><i class="ti ti-trash-x"></i>
														Delete</a>
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
								<td><a href="{{url('staff-details')}}" class="link-primary">8190</a></td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('staff-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/teachers/teacher-10.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('staff-details')}}">Edward</a>
											</p>
										</div>
									</div>
								</td>
								<td>Finance</td>
								<td>Accounts Manager</td>
								<td>+1 56187 87489</td>
								<td>
									edward@example.com
								</td>
								<td>
									10 Jun 2024
								</td>
								<td>
									<div class="dropdown">
										<a href="#" class="action-icon " data-bs-toggle="dropdown"
											aria-expanded="false">
											<i class="ti ti-dots-vertical"></i>
										</a>
										<div class="dropdown-menu dropdown-menu-right">
											<ul>
												<li>
													<a class="dropdown-item" href="{{url('staff-details')}}"><i class="ti ti-menu"></i>View Staff</a>
												</li>
												<li>
													<a class="dropdown-item" href="{{url('edit-staff')}}"><i class="ti ti-edit-circle"></i>Edit</a>
												</li>
												<li>
													<a class="dropdown-item" href="#" data-bs-toggle="modal"
														data-bs-target="#delete-modal"><i class="ti ti-trash-x"></i>
														Delete</a>
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
								<td><a href="{{url('staff-details')}}" class="link-primary">8189</a></td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('staff-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/teachers/teacher-09.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('staff-details')}}">Elizabeth</a>
											</p>
										</div>
									</div>
								</td>
								<td>Management</td>
								<td>Receptionist</td>
								<td>+1 97846 84518</td>
								<td>
									elizabeth@example.com
								</td>
								<td>
									18 Jun 2024
								</td>
								<td>
									<div class="dropdown">
										<a href="#" class="action-icon " data-bs-toggle="dropdown"
											aria-expanded="false">
											<i class="ti ti-dots-vertical"></i>
										</a>
										<div class="dropdown-menu dropdown-menu-right">
											<ul>
												<li>
													<a class="dropdown-item" href="{{url('staff-details')}}"><i class="ti ti-menu"></i>View Staff</a>
												</li>
												<li>
													<a class="dropdown-item" href="{{url('edit-staff')}}"><i class="ti ti-edit-circle"></i>Edit</a>
												</li>
												<li>
													<a class="dropdown-item" href="#" data-bs-toggle="modal"
														data-bs-target="#delete-modal"><i class="ti ti-trash-x"></i>
														Delete</a>
												</li>
											</ul>
										</div>
									</div>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
				<!-- /Staffs List -->
			</div>
		</div>
			

	</div>
</div>
<!-- /Page Wrapper -->
    @component('components.modal-popup')        
    @endcomponent
@endsection
