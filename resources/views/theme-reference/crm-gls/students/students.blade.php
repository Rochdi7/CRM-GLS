<?php $page = 'students'; ?>
@extends('layout.mainlayout')
@section('content')
   
<!-- Page Wrapper -->
<div class="page-wrapper">
	<div class="content">
		@component('components.breadcrumb')
		@slot('title')
		Students List
		@endslot
		@slot('item1')
		Dashboard
		@endslot
		@slot('item2')
		Students
		@endslot
		@slot('item3')
		All Students
		@endslot
	@endcomponent

		

		<!-- Students List -->
		<div class="card">
			<div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
				<h4 class="mb-3">Students List</h4>
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
							<form action="{{url('students')}}">
								<div class="d-flex align-items-center border-bottom p-3">
									<h4>Filter</h4>
								</div>
								<div class="p-3 pb-0 border-bottom">
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
											<div class="mb-3">
												<label class="form-label">Name</label>
												<select class="select">
													<option>Select</option>
													<option>Janet</option>
													<option>Joann</option>
													<option>Kathleen</option>
												</select>
											</div>
										</div>
										<div class="col-md-6">
											<div class="mb-3">
												<label class="form-label">Gender</label>
												<select class="select">
													<option>Select</option>
													<option>Male</option>
													<option>Female</option>
												</select>
											</div>
										</div>
										<div class="col-md-6">
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
						<a href="{{url('students')}}" class="active btn btn-icon btn-sm me-1 primary-hover"><i
								class="ti ti-list-tree"></i></a>
						<a href="{{url('student-grid')}}" class="btn btn-icon btn-sm bg-light primary-hover"><i
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
								<th>Admission No</th>
								<th>Roll No</th>
								<th>Name</th>
								<th>Class </th>
								<th>Section</th>
								<th>Gender</th>
								<th>Status</th>
								<th>Date of Join</th>
								<th>DOB</th>
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
								<td><a href="{{url('student-details')}}" class="link-primary">AD9892434</a></td>
								<td>35013</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-01.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="mb-0" class="text-dark"><a
													href="{{url('student-details')}}">Janet</a></p>
										</div>
									</div>
								</td>
								<td>III</td>
								<td>A</td>
								<td>Female</td>
								<td>
									<span class="badge badge-soft-success d-inline-flex align-items-center"><i
											class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
								</td>
								<td>25 Mar 2024</td>
								<td>10 Jan 2015</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="#" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i class="ti ti-brand-hipchat"></i></a>
										<a href="#" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i class="ti ti-phone"></i></a>
										<a href="#" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3"><i class="ti ti-mail"></i></a>
										<a href="#" data-bs-toggle="modal" data-bs-target="#add_fees_collect" class="btn btn-light fs-12 fw-semibold me-3">Collect Fees</a>
										<div class="dropdown">
											<a href="#" class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0" data-bs-toggle="dropdown" aria-expanded="false">
												<i class="ti ti-dots-vertical fs-14"></i>
											</a>
											<ul class="dropdown-menu dropdown-menu-right p-3">
												<li>
													<a class="dropdown-item rounded-1"
														href="{{url('student-details')}}"><i
															class="ti ti-menu me-2"></i>View Student</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="{{url('edit-student')}}"><i
															class="ti ti-edit-circle me-2"></i>Edit</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="javascript:void(0);" data-bs-toggle="modal"
														data-bs-target="#login_detail"><i
															class="ti ti-lock me-2"></i>Login Details</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="javascript:void(0);"><i
															class="ti ti-toggle-right me-2"></i>Disable</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="{{url('student-promotion')}}"><i
															class="ti ti-arrow-ramp-right-2 me-2"></i>Promote
														Student</a>
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
								<td><a href="{{url('student-details')}}" class="link-primary">AD9892433</a></td>
								<td>35013</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-02.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="mb-0" class="text-dark"><a
													href="{{url('staff-details')}}">Joann</a></p>
										</div>
									</div>
								</td>
								<td>IV</td>
								<td>B</td>
								<td>Male</td>
								<td>
									<span class="badge badge-soft-success d-inline-flex align-items-center"><i
											class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
								</td>
								<td>18 Mar 2024</td>
								<td>19 Aug 2014</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="#"
											class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i
												class="ti ti-brand-hipchat"></i></a>
										<a href="#"
											class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i
												class="ti ti-phone"></i></a>
										<a href="#"
											class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3"><i
												class="ti ti-mail"></i></a>
										<a href="#" data-bs-toggle="modal" data-bs-target="#add_fees_collect"
											class="btn btn-light fs-12 fw-semibold me-3">Collect Fees</a>
										<div class="dropdown">
											<a href="#"
												class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
												data-bs-toggle="dropdown" aria-expanded="false">
												<i class="ti ti-dots-vertical fs-14"></i>
											</a>
											<ul class="dropdown-menu dropdown-menu-right p-3">
												<li>
													<a class="dropdown-item rounded-1"
														href="{{url('student-details')}}"><i
															class="ti ti-menu me-2"></i>View Student</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="{{url('edit-student')}}"><i
															class="ti ti-edit-circle me-2"></i>Edit</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="javascript:void(0);" data-bs-toggle="modal"
														data-bs-target="#login_detail"><i
															class="ti ti-lock me-2"></i>Login Details</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="javascript:void(0);"><i
															class="ti ti-toggle-right me-2"></i>Disable</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="{{url('student-promotion')}}"><i
															class="ti ti-arrow-ramp-right-2 me-2"></i>Promote
														Student</a>
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
								<td><a href="{{url('student-details')}}" class="link-primary">AD9892432</a></td>
								<td>35011</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-03.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="mb-0" class="text-dark"><a
													href="{{url('student-details')}}">Kathleen</a></p>
										</div>
									</div>
								</td>
								<td>II</td>
								<td>A</td>
								<td>Female</td>
								<td>
									<span class="badge badge-soft-success d-inline-flex align-items-center"><i
											class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
								</td>
								<td>14 Mar 2024</td>
								<td>05 Dec 2017</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="#"
											class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i
												class="ti ti-brand-hipchat"></i></a>
										<a href="#"
											class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i
												class="ti ti-phone"></i></a>
										<a href="#"
											class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3"><i
												class="ti ti-mail"></i></a>
										<a href="#" data-bs-toggle="modal" data-bs-target="#add_fees_collect"
											class="btn btn-light fs-12 fw-semibold me-3">Collect Fees</a>
										<div class="dropdown">
											<a href="#"
												class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
												data-bs-toggle="dropdown" aria-expanded="false">
												<i class="ti ti-dots-vertical fs-14"></i>
											</a>
											<ul class="dropdown-menu dropdown-menu-right p-3">
												<li>
													<a class="dropdown-item rounded-1"
														href="{{url('student-details')}}"><i
															class="ti ti-menu me-2"></i>View Student</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="{{url('edit-student')}}"><i
															class="ti ti-edit-circle me-2"></i>Edit</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="javascript:void(0);" data-bs-toggle="modal"
														data-bs-target="#login_detail"><i
															class="ti ti-lock me-2"></i>Login Details</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="javascript:void(0);"><i
															class="ti ti-toggle-right me-2"></i>Disable</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="{{url('student-promotion')}}"><i
															class="ti ti-arrow-ramp-right-2 me-2"></i>Promote
														Student</a>
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
								<td><a href="{{url('student-details')}}" class="link-primary">AD9892431</a></td>
								<td>35010</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-04.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="mb-0" class="text-dark"><a
													href="{{url('student-details')}}">Gifford</a></p>
										</div>
									</div>
								</td>
								<td>I</td>
								<td>B</td>
								<td>Male</td>
								<td>
									<span class="badge badge-soft-success d-inline-flex align-items-center"><i
											class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
								</td>
								<td>27 Feb 2024</td>
								<td>22 Mar 2018</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="#"
											class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i
												class="ti ti-brand-hipchat"></i></a>
										<a href="#"
											class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i
												class="ti ti-phone"></i></a>
										<a href="#"
											class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3"><i
												class="ti ti-mail"></i></a>
										<a href="#" data-bs-toggle="modal" data-bs-target="#add_fees_collect"
											class="btn btn-light fs-12 fw-semibold me-3">Collect Fees</a>
										<div class="dropdown">
											<a href="#"
												class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
												data-bs-toggle="dropdown" aria-expanded="false">
												<i class="ti ti-dots-vertical fs-14"></i>
											</a>
											<ul class="dropdown-menu dropdown-menu-right p-3">
												<li>
													<a class="dropdown-item rounded-1"
														href="{{url('student-details')}}"><i
															class="ti ti-menu me-2"></i>View Student</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="{{url('edit-student')}}"><i
															class="ti ti-edit-circle me-2"></i>Edit</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="javascript:void(0);" data-bs-toggle="modal"
														data-bs-target="#login_detail"><i
															class="ti ti-lock me-2"></i>Login Details</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="javascript:void(0);"><i
															class="ti ti-toggle-right me-2"></i>Disable</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="{{url('student-promotion')}}"><i
															class="ti ti-arrow-ramp-right-2 me-2"></i>Promote
														Student</a>
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
								<td><a href="{{url('student-details')}}" class="link-primary">AD9892430</a></td>
								<td>35009</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-05.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="mb-0" class="text-dark"><a
													href="{{url('student-details')}}">Lisa</a></p>
										</div>
									</div>
								</td>
								<td>II</td>
								<td>B</td>
								<td>Female</td>
								<td>
									<span class="badge badge-soft-danger d-inline-flex align-items-center"><i
											class="ti ti-circle-filled fs-5 me-1"></i>Inactive</span>
								</td>
								<td>13 Feb 2024</td>
								<td>13 May 2017</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="#"
											class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i
												class="ti ti-brand-hipchat"></i></a>
										<a href="#"
											class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i
												class="ti ti-phone"></i></a>
										<a href="#"
											class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3"><i
												class="ti ti-mail"></i></a>
										<a href="#" data-bs-toggle="modal" data-bs-target="#add_fees_collect"
											class="btn btn-light fs-12 fw-semibold me-3">Collect Fees</a>
										<div class="dropdown">
											<a href="#"
												class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
												data-bs-toggle="dropdown" aria-expanded="false">
												<i class="ti ti-dots-vertical fs-14"></i>
											</a>
											<ul class="dropdown-menu dropdown-menu-right p-3">
												<li>
													<a class="dropdown-item rounded-1"
														href="{{url('student-details')}}"><i
															class="ti ti-menu me-2"></i>View Student</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="{{url('edit-student')}}"><i
															class="ti ti-edit-circle me-2"></i>Edit</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="javascript:void(0);" data-bs-toggle="modal"
														data-bs-target="#login_detail"><i
															class="ti ti-lock me-2"></i>Login Details</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="javascript:void(0);"><i
															class="ti ti-toggle-right me-2"></i>Disable</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="{{url('student-promotion')}}"><i
															class="ti ti-arrow-ramp-right-2 me-2"></i>Promote
														Student</a>
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
								<td><a href="{{url('student-details')}}" class="link-primary">AD9892429</a></td>
								<td>35008</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-06.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="mb-0" class="text-dark"><a
													href="{{url('student-details')}}">Ralph</a></p>
										</div>
									</div>
								</td>
								<td>III</td>
								<td>B</td>
								<td>Male</td>
								<td>
									<span class="badge badge-soft-success d-inline-flex align-items-center"><i
											class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
								</td>
								<td>11 Feb 2024</td>
								<td>20 Jun 2015</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="#"
											class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i
												class="ti ti-brand-hipchat"></i></a>
										<a href="#"
											class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i
												class="ti ti-phone"></i></a>
										<a href="#"
											class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3"><i
												class="ti ti-mail"></i></a>
										<a href="#" data-bs-toggle="modal" data-bs-target="#add_fees_collect"
											class="btn btn-light fs-12 fw-semibold me-3">Collect Fees</a>
										<div class="dropdown">
											<a href="#"
												class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
												data-bs-toggle="dropdown" aria-expanded="false">
												<i class="ti ti-dots-vertical fs-14"></i>
											</a>
											<ul class="dropdown-menu dropdown-menu-right p-3">
												<li>
													<a class="dropdown-item rounded-1"
														href="{{url('student-details')}}"><i
															class="ti ti-menu me-2"></i>View Student</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="{{url('edit-student')}}"><i
															class="ti ti-edit-circle me-2"></i>Edit</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="javascript:void(0);" data-bs-toggle="modal"
														data-bs-target="#login_detail"><i
															class="ti ti-lock me-2"></i>Login Details</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="javascript:void(0);"><i
															class="ti ti-toggle-right me-2"></i>Disable</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="{{url('student-promotion')}}"><i
															class="ti ti-arrow-ramp-right-2 me-2"></i>Promote
														Student</a>
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
								<td><a href="{{url('student-details')}}" class="link-primary">AD9892428</a></td>
								<td>35007</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-07.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="mb-0" class="text-dark"><a
													href="{{url('student-details')}}">Julie</a></p>
										</div>
									</div>
								</td>
								<td>V</td>
								<td>A</td>
								<td>Female</td>
								<td>
									<span class="badge badge-soft-success d-inline-flex align-items-center"><i
											class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
								</td>
								<td>24 Jan 2024</td>
								<td>18 Sep 2013</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="#"
											class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i
												class="ti ti-brand-hipchat"></i></a>
										<a href="#"
											class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i
												class="ti ti-phone"></i></a>
										<a href="#"
											class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3"><i
												class="ti ti-mail"></i></a>
										<a href="#" data-bs-toggle="modal" data-bs-target="#add_fees_collect"
											class="btn btn-light fs-12 fw-semibold me-3">Collect Fees</a>
										<div class="dropdown">
											<a href="#"
												class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
												data-bs-toggle="dropdown" aria-expanded="false">
												<i class="ti ti-dots-vertical fs-14"></i>
											</a>
											<ul class="dropdown-menu dropdown-menu-right p-3">
												<li>
													<a class="dropdown-item rounded-1"
														href="{{url('student-details')}}"><i
															class="ti ti-menu me-2"></i>View Student</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="{{url('edit-student')}}"><i
															class="ti ti-edit-circle me-2"></i>Edit</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="javascript:void(0);" data-bs-toggle="modal"
														data-bs-target="#login_detail"><i
															class="ti ti-lock me-2"></i>Login Details</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="javascript:void(0);"><i
															class="ti ti-toggle-right me-2"></i>Disable</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="{{url('student-promotion')}}"><i
															class="ti ti-arrow-ramp-right-2 me-2"></i>Promote
														Student</a>
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
								<td><a href="{{url('student-details')}}" class="link-primary">AD9892427</a></td>
								<td>35006</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-08.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="mb-0" class="text-dark"><a
													href="{{url('student-details')}}">Ryan</a></p>
										</div>
									</div>
								</td>
								<td>VI</td>
								<td>A</td>
								<td>Male</td>
								<td>
									<span class="badge badge-soft-success d-inline-flex align-items-center"><i
											class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
								</td>
								<td>19 Jan 2024</td>
								<td>26 Nov 2012</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="#"
											class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i
												class="ti ti-brand-hipchat"></i></a>
										<a href="#"
											class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i
												class="ti ti-phone"></i></a>
										<a href="#"
											class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3"><i
												class="ti ti-mail"></i></a>
										<a href="#" data-bs-toggle="modal" data-bs-target="#add_fees_collect"
											class="btn btn-light fs-12 fw-semibold me-3">Collect Fees</a>
										<div class="dropdown">
											<a href="#"
												class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
												data-bs-toggle="dropdown" aria-expanded="false">
												<i class="ti ti-dots-vertical fs-14"></i>
											</a>
											<ul class="dropdown-menu dropdown-menu-right p-3">
												<li>
													<a class="dropdown-item rounded-1"
														href="{{url('student-details')}}"><i
															class="ti ti-menu me-2"></i>View Student</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="{{url('edit-student')}}"><i
															class="ti ti-edit-circle me-2"></i>Edit</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="javascript:void(0);" data-bs-toggle="modal"
														data-bs-target="#login_detail"><i
															class="ti ti-lock me-2"></i>Login Details</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="javascript:void(0);"><i
															class="ti ti-toggle-right me-2"></i>Disable</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="{{url('student-promotion')}}"><i
															class="ti ti-arrow-ramp-right-2 me-2"></i>Promote
														Student</a>
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
								<td><a href="{{url('student-details')}}" class="link-primary">AD9892426</a></td>
								<td>35005</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-09.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="mb-0" class="text-dark"><a
													href="{{url('student-details')}}">Susan</a></p>
										</div>
									</div>
								</td>
								<td>VIII</td>
								<td>B</td>
								<td>Female</td>
								<td>
									<span class="badge badge-soft-danger d-inline-flex align-items-center"><i
											class="ti ti-circle-filled fs-5 me-1"></i>Inactive</span>
								</td>
								<td>08 Jan 2024</td>
								<td>26 May 2010</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="#"
											class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i
												class="ti ti-brand-hipchat"></i></a>
										<a href="#"
											class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i
												class="ti ti-phone"></i></a>
										<a href="#"
											class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3"><i
												class="ti ti-mail"></i></a>
										<a href="#" data-bs-toggle="modal" data-bs-target="#add_fees_collect"
											class="btn btn-light fs-12 fw-semibold me-3">Collect Fees</a>
										<div class="dropdown">
											<a href="#"
												class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
												data-bs-toggle="dropdown" aria-expanded="false">
												<i class="ti ti-dots-vertical fs-14"></i>
											</a>
											<ul class="dropdown-menu dropdown-menu-right p-3">
												<li>
													<a class="dropdown-item rounded-1"
														href="{{url('student-details')}}"><i
															class="ti ti-menu me-2"></i>View Student</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="{{url('edit-student')}}"><i
															class="ti ti-edit-circle me-2"></i>Edit</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="javascript:void(0);" data-bs-toggle="modal"
														data-bs-target="#login_detail"><i
															class="ti ti-lock me-2"></i>Login Details</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="javascript:void(0);"><i
															class="ti ti-toggle-right me-2"></i>Disable</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="{{url('student-promotion')}}"><i
															class="ti ti-arrow-ramp-right-2 me-2"></i>Promote
														Student</a>
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
								<td><a href="{{url('student-details')}}" class="link-primary">AD9892425</a></td>
								<td>35004</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-10.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="mb-0" class="text-dark"><a
													href="{{url('student-details')}}">Richard</a></p>
										</div>
									</div>
								</td>
								<td>VII</td>
								<td>B</td>
								<td>Male</td>
								<td>
									<span class="badge badge-soft-success d-inline-flex align-items-center"><i
											class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
								</td>
								<td>22 Dec 2024</td>
								<td>06 Oct 2011</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="#"
											class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i
												class="ti ti-brand-hipchat"></i></a>
										<a href="#"
											class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i
												class="ti ti-phone"></i></a>
										<a href="#"
											class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3"><i
												class="ti ti-mail"></i></a>
										<a href="#" data-bs-toggle="modal" data-bs-target="#add_fees_collect"
											class="btn btn-light fs-12 fw-semibold me-3">Collect Fees</a>
										<div class="dropdown">
											<a href="#"
												class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
												data-bs-toggle="dropdown" aria-expanded="false">
												<i class="ti ti-dots-vertical fs-14"></i>
											</a>
											<ul class="dropdown-menu dropdown-menu-right p-3">
												<li>
													<a class="dropdown-item rounded-1"
														href="{{url('student-details')}}"><i
															class="ti ti-menu me-2"></i>View Student</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="{{url('edit-student')}}"><i
															class="ti ti-edit-circle me-2"></i>Edit</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="javascript:void(0);" data-bs-toggle="modal"
														data-bs-target="#login_detail"><i
															class="ti ti-lock me-2"></i>Login Details</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="javascript:void(0);"><i
															class="ti ti-toggle-right me-2"></i>Disable</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="{{url('student-promotion')}}"><i
															class="ti ti-arrow-ramp-right-2 me-2"></i>Promote
														Student</a>
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
								<td><a href="{{url('student-details')}}" class="link-primary">AD9892424</a></td>
								<td>35003</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-11.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="mb-0" class="text-dark"><a
													href="{{url('student-details')}}">Veronica</a></p>
										</div>
									</div>
								</td>
								<td>IX</td>
								<td>A</td>
								<td>Female</td>
								<td>
									<span class="badge badge-soft-success d-inline-flex align-items-center"><i
											class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
								</td>
								<td>15 Dec 2024</td>
								<td>27 Dec 2009</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="#"
											class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i
												class="ti ti-brand-hipchat"></i></a>
										<a href="#"
											class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i
												class="ti ti-phone"></i></a>
										<a href="#"
											class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3"><i
												class="ti ti-mail"></i></a>
										<a href="#" data-bs-toggle="modal" data-bs-target="#add_fees_collect"
											class="btn btn-light fs-12 fw-semibold me-3">Collect Fees</a>
										<div class="dropdown">
											<a href="#"
												class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
												data-bs-toggle="dropdown" aria-expanded="false">
												<i class="ti ti-dots-vertical fs-14"></i>
											</a>
											<ul class="dropdown-menu dropdown-menu-right p-3">
												<li>
													<a class="dropdown-item rounded-1"
														href="{{url('student-details')}}"><i
															class="ti ti-menu me-2"></i>View Student</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="{{url('edit-student')}}"><i
															class="ti ti-edit-circle me-2"></i>Edit</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="javascript:void(0);" data-bs-toggle="modal"
														data-bs-target="#login_detail"><i
															class="ti ti-lock me-2"></i>Login Details</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="javascript:void(0);"><i
															class="ti ti-toggle-right me-2"></i>Disable</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="{{url('student-promotion')}}"><i
															class="ti ti-arrow-ramp-right-2 me-2"></i>Promote
														Student</a>
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
