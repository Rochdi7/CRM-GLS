<?php $page = 'guardians'; ?>
@extends('layout.mainlayout')
@section('content')
  <!-- Page Wrapper -->
  <div class="page-wrapper">
	<div class="content">

		<!-- Page Header -->
		<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
			<div class="my-auto mb-2">
				<h3 class="page-title mb-1">Guardian</h3>
				<nav>
					<ol class="breadcrumb mb-0">
						<li class="breadcrumb-item">
							<a href="javascript:void(0);">Dashboard</a>
						</li>
						<li class="breadcrumb-item">
							<a href="javascript:void(0);">People</a>
						</li>
						<li class="breadcrumb-item active" aria-current="page">Guardian</li>
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
					<button type="button" class="btn btn-outline-light bg-white btn-icon me-1"
						data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Print"
						data-bs-original-title="Print">
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
					<a href="#" data-bs-toggle="modal" data-bs-target="#add_guardian" class="btn btn-primary"><i
							class="ti ti-square-rounded-plus me-2"></i>Add Guardian</a>
				</div>
			</div>
		</div>
		<!-- /Page Header -->

		<!-- Guardians List -->
		<div class="card">
			<div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
				<h4 class="mb-3">Guardians List</h4>
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
							<form action="{{url('guradians')}}">
								<div class="d-flex align-items-center border-bottom p-3">
									<h4>Filter</h4>
								</div>
								<div class="p-3 border-bottom pb-0">
									<div class="row">
										<div class="col-md-12">
											<div class="mb-3">
												<label class="form-label">Guardian</label>
												<select class="select">
													<option>Select</option>
													<option>William</option>
													<option>Stacey</option>
													<option>George</option>
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
						<a href="{{url('guardians')}}" class="active btn btn-icon btn-sm me-1 primary-hover"><i
								class="ti ti-list-tree"></i></a>
						<a href="{{url('guardian-grid')}}" class="btn btn-icon btn-sm bg-light primary-hover"><i
								class="ti ti-grid-dots"></i></a>
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

				<!-- Guardians List -->
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
								<th>Guardian Name</th>
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
								<td>
									<a href="#" class="link-primary" data-bs-toggle="modal"
										data-bs-target="#view_details">G153735</a>
								</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="#" class="avatar avatar-md" data-bs-toggle="modal"
											data-bs-target="#view_details"><img
												src="{{URL::asset('build/img/guardians/guardian-01.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="#" data-bs-toggle="modal"
													data-bs-target="#view_details">William</a></p>
											<span class="fs-12">Added on 25 Mar 2024</span>
										</div>
									</div>
								</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-01.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('student-details')}}">Janet</a>
											</p>
											<span class="fs-12">III, A</span>
										</div>
									</div>
								</td>
								<td>+1 82392 37359</td>
								<td>william@example.com</td>
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
														data-bs-toggle="modal" data-bs-target="#view_details"><i
															class="ti ti-menu me-2"></i>View Guardian</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1" href="#"
														data-bs-toggle="modal"
														data-bs-target="#edit_guardian"><i
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
								<td><a href="#" class="link-primary" data-bs-toggle="modal"
										data-bs-target="#view_details">G153734</a></td>
								<td>
									<div class="d-flex align-items-center">
										<a href="#" class="avatar avatar-md" data-bs-toggle="modal"
											data-bs-target="#view_details"><img
												src="{{URL::asset('build/img/guardians/guardian-02.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="#" data-bs-toggle="modal"
													data-bs-target="#view_details">Stacey</a></p>
											<span class="fs-12">Added on 18 Mar 2024</span>
										</div>
									</div>
								</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-02.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('student-details')}}">Joann</a>
											</p>
											<span class="fs-12">IV, B</span>
										</div>
									</div>
								</td>
								<td>+1 28249 13139</td>
								<td>stacey@example.com</td>
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
														data-bs-toggle="modal" data-bs-target="#view_details"><i
															class="ti ti-menu me-2"></i>View Guardian</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1" href="#"
														data-bs-toggle="modal"
														data-bs-target="#edit_guardian"><i
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
								<td><a href="#" class="link-primary" data-bs-toggle="modal"
										data-bs-target="#view_details">G153733</a></td>
								<td>
									<div class="d-flex align-items-center">
										<a href="#" class="avatar avatar-md" data-bs-toggle="modal"
											data-bs-target="#view_details"><img
												src="{{URL::asset('build/img/guardians/guardian-03.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="#" data-bs-toggle="modal"
													data-bs-target="#view_details">George</a></p>
											<span class="fs-12">Added on 14 Mar 2024</span>
										</div>
									</div>
								</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-03.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a
													href="{{url('student-details')}}">Kathleen</a>
											</p>
											<span class="fs-12">III, A</span>
										</div>
									</div>
								</td>
								<td>+1 74284 34849</td>
								<td>george@example.com</td>
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
														data-bs-toggle="modal" data-bs-target="#view_details"><i
															class="ti ti-menu me-2"></i>View Guardian</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1" href="#"
														data-bs-toggle="modal"
														data-bs-target="#edit_guardian"><i
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
								<td>
									<a href="#" class="link-primary" data-bs-toggle="modal"
										data-bs-target="#view_details">G153732</a>
								</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="#" class="avatar avatar-md" data-bs-toggle="modal"
											data-bs-target="#view_details">
											<img src="{{URL::asset('build/img/guardians/guardian-04.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="#" data-bs-toggle="modal"
													data-bs-target="#view_details">Sarah</a></p>
											<span class="fs-12">Added on 27 Feb 2024</span>
										</div>
									</div>
								</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-04.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('student-details')}}">Gifford</a>
											</p>
											<span class="fs-12">I, B</span>
										</div>
									</div>
								</td>
								<td>+1 82239 42492</td>
								<td>gary@example.com</td>
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
														data-bs-toggle="modal" data-bs-target="#view_details"><i
															class="ti ti-menu me-2"></i>View Guardian</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1" href="#"
														data-bs-toggle="modal"
														data-bs-target="#edit_guardian"><i
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
								<td>
									<a href="#" class="link-primary" data-bs-toggle="modal"
										data-bs-target="#view_details">G153731</a>
								</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="#" class="avatar avatar-md" data-bs-toggle="modal"
											data-bs-target="#view_details"><img
												src="{{URL::asset('build/img/guardians/guardian-05.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="#" data-bs-toggle="modal"
													data-bs-target="#view_details">Gary</a></p>
											<span class="fs-12">Added on 11 Feb 2024</span>
										</div>
									</div>
								</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-05.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('student-details')}}">Lisa</a>
											</p>
											<span class="fs-12">II, B</span>
										</div>
									</div>
								</td>
								<td>+1 82239 42492</td>
								<td>gary@example.com</td>
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
														data-bs-toggle="modal" data-bs-target="#view_details"><i
															class="ti ti-menu me-2"></i>View Guardian</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1" href="#"
														data-bs-toggle="modal"
														data-bs-target="#edit_guardian"><i
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
								<td>
									<a href="#" class="link-primary" data-bs-toggle="modal"
										data-bs-target="#view_details">G153730</a>
								</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="#" class="avatar avatar-md" data-bs-toggle="modal"
											data-bs-target="#view_details"><img
												src="{{URL::asset('build/img/guardians/guardian-06.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="#" data-bs-toggle="modal"
													data-bs-target="#view_details">Linda</a></p>
											<span class="fs-12">Added on 24 Jan 2024</span>
										</div>
									</div>
								</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-06.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('student-details')}}">Ralph</a>
											</p>
											<span class="fs-12">III, B</span>
										</div>
									</div>
								</td>
								<td>+1 90139 45835</td>
								<td>linda@example.com</td>
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
														data-bs-toggle="modal" data-bs-target="#view_details"><i
															class="ti ti-menu me-2"></i>View Guardian</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1" href="#"
														data-bs-toggle="modal"
														data-bs-target="#edit_guardian"><i
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
								<td>
									<a href="#" class="link-primary" data-bs-toggle="modal"
										data-bs-target="#view_details">G153729</a>
								</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="#" class="avatar avatar-md" data-bs-toggle="modal"
											data-bs-target="#view_details"><img
												src="{{URL::asset('build/img/guardians/guardian-07.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="#" data-bs-toggle="modal"
													data-bs-target="#view_details">Jeffrey</a></p>
											<span class="fs-12">Added on 19 Jan 2024</span>
										</div>
									</div>
								</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-07.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('student-details')}}">Julie</a>
											</p>
											<span class="fs-12">V, A</span>
										</div>
									</div>
								</td>
								<td>+1 23230 37402</td>
								<td>jeffrey@example.com</td>
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
														data-bs-toggle="modal" data-bs-target="#view_details"><i
															class="ti ti-menu me-2"></i>View Guardian</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1" href="#"
														data-bs-toggle="modal"
														data-bs-target="#edit_guardian"><i
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
								<td>
									<a href="#" class="link-primary" data-bs-toggle="modal"
										data-bs-target="#view_details">G153728</a>
								</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="#" class="avatar avatar-md" data-bs-toggle="modal"
											data-bs-target="#view_details"><img
												src="{{URL::asset('build/img/guardians/guardian-08.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="#" data-bs-toggle="modal"
													data-bs-target="#view_details">Galina</a></p>
											<span class="fs-12">Added on 08 Jan 2024</span>
										</div>
									</div>
								</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-08.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('student-details')}}">Janet</a>
											</p>
											<span class="fs-12">III, A</span>
										</div>
									</div>
								</td>
								<td>+1 91304 34834</td>
								<td>galina@example.com</td>
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
														data-bs-toggle="modal" data-bs-target="#view_details"><i
															class="ti ti-menu me-2"></i>View Guardian</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1" href="#"
														data-bs-toggle="modal"
														data-bs-target="#edit_guardian"><i
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
								<td>
									<a href="#" class="link-primary" data-bs-toggle="modal"
										data-bs-target="#view_details">G153727</a>
								</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="#" class="avatar avatar-md" data-bs-toggle="modal"
											data-bs-target="#view_details"><img
												src="{{URL::asset('build/img/guardians/guardian-09.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="#" data-bs-toggle="modal"
													data-bs-target="#view_details">Paul</a></p>
											<span class="fs-12">Added on 22 Dec 2023</span>
										</div>
									</div>
								</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-09.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('student-details')}}">Susan</a>
											</p>
											<span class="fs-12">VIII, B</span>
										</div>
									</div>
								</td>
								<td>+1 84394 28204</td>
								<td>paul@example.com</td>
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
														data-bs-toggle="modal" data-bs-target="#view_details"><i
															class="ti ti-menu me-2"></i>View Guardian</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1" href="#"
														data-bs-toggle="modal"
														data-bs-target="#edit_guardian"><i
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
								<td>
									<a href="#" class="link-primary" data-bs-toggle="modal"
										data-bs-target="#view_details">G153726</a>
								</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="#" class="avatar avatar-md" data-bs-toggle="modal"
											data-bs-target="#view_details"><img
												src="{{URL::asset('build/img/guardians/guardian-10.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="#" data-bs-toggle="modal"
													data-bs-target="#view_details">Tracy</a></p>
											<span class="fs-12">Added on 15 Dec 2024</span>
										</div>
									</div>
								</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-10.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('student-details')}}">Richard</a>
											</p>
											<span class="fs-12">VII, B</span>
										</div>
									</div>
								</td>
								<td>+1 93402 39342</td>
								<td>tracy@example.com</td>
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
														data-bs-toggle="modal" data-bs-target="#view_details"><i
															class="ti ti-menu me-2"></i>View Guardian</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1" href="#"
														data-bs-toggle="modal"
														data-bs-target="#edit_guardian"><i
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
								<td>
									<a href="#" class="link-primary" data-bs-toggle="modal"
										data-bs-target="#view_details">G153725</a>
								</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="#" data-bs-toggle="modal" data-bs-target="#view_details"
											class="avatar avatar-md">
											<span><img src="{{URL::asset('build/img/guardians/guardian-11.jpg')}}"
													class="img-fluid rounded-circle" alt="img"></span>
										</a>
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
												src="{{URL::asset('build/img/students/student-11.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a
													href="{{url('student-details')}}">Veronica</a></p>
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
													<a class="dropdown-item rounded-1" href="#"
														data-bs-toggle="modal" data-bs-target="#view_details"><i
															class="ti ti-menu me-2"></i>View Guardian</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1" href="#"
														data-bs-toggle="modal"
														data-bs-target="#edit_guardian"><i
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
				<!-- /Guardians List -->
			</div>
		</div>
		<!-- /Guardians List -->

	</div>
</div>
<!-- /Page Wrapper -->
    @component('components.modal-popup')
    @endcomponent
@endsection
