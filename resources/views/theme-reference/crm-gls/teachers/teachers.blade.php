<?php $page = 'teachers'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
		<div class="page-wrapper">
			<div class="content">
				@component('components.breadcrumb')
				@slot('title')
				Teacher List
				@endslot
				@slot('item1')
				Dashboard
				@endslot
				@slot('item2')
				Peoples
				@endslot
				@slot('item3')
				Teacher List
				@endslot
			   @endcomponent
				
				<!-- Students List -->
				<div class="card">
					<div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
						<h4 class="mb-3">Teachers List</h4>
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
									<form action="{{url('teachers')}}">
										<div class="d-flex align-items-center border-bottom p-3">
											<h4>Filter</h4>
										</div>
										<div class="p-3 border-bottom pb-0">
											<div class="row">
												<div class="col-md-12">
													<div class="mb-3">
														<label class="form-label">Name</label>
														<select class="select">
															<option>Select</option>
															<option>Teresa</option>
															<option>Daniel</option>
															<option>Hellana</option>
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
								<a href="{{url('teachers')}}" class="active btn btn-icon btn-sm me-1 primary-hover"><i
										class="ti ti-list-tree"></i></a>
								<a href="{{url('teacher-grid')}}" class="btn btn-icon btn-sm bg-light primary-hover"><i
										class="ti ti-grid-dots"></i></a>
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
											<div class="form-check form-check-md">
												<input class="form-check-input" type="checkbox" id="select-all">
											</div>
										</th>
										<th>ID</th>
										<th>Name</th>
										<th>Class </th>
										<th>Subject</th>
										<th>Email</th>
										<th>Phone</th>
										<th>Date of Join</th>
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
										<td><a href="{{url('teacher-details')}}" class="link-primary">T849127</a></td>
										<td>
											<div class="d-flex align-items-center">
												<a href="{{url('teacher-details')}}" class="avatar avatar-md"><img
														src="{{URL::asset('build/img/teachers/teacher-01.jpg')}}"
														class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="{{url('teacher-details')}}">Teresa</a>
													</p>
												</div>
											</div>
										</td>
										<td>III A</td>
										<td>Physics</td>
										<td>teresa@example.com</td>
										<td>+1 82392 37359</td>
										<td>25 Mar 2024</td>
										<td>
											<span class="badge badge-soft-success d-inline-flex align-items-center"><i
													class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
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
															<a class="dropdown-item rounded-1"
																href="{{url('teacher-details')}}"><i
																	class="ti ti-menu me-2"></i>View Teacher</a>
														</li>
														<li>
															<a class="dropdown-item rounded-1"
																href="{{url('edit-teacher')}}"><i
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
										<td><a href="{{url('teacher-details')}}" class="link-primary">T849126</a></td>
										<td>
											<div class="d-flex align-items-center">
												<a href="{{url('teacher-details')}}" class="avatar avatar-md"><img
														src="{{URL::asset('build/img/teachers/teacher-02.jpg')}}"
														class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="{{url('teacher-details')}}">Daniel</a>
													</p>
												</div>
											</div>
										</td>
										<td>II (A)</td>
										<td>Computer</td>
										<td>daniel@example.com</td>
										<td>+1 56752 86742</td>
										<td>28 Mar 2024</td>
										<td>
											<span class="badge badge-soft-success d-inline-flex align-items-center"><i
													class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
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
															<a class="dropdown-item rounded-1"
																href="{{url('teacher-details')}}"><i
																	class="ti ti-menu me-2"></i>View Teacher</a>
														</li>
														<li>
															<a class="dropdown-item rounded-1"
																href="{{url('edit-teacher')}}"><i
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
										<td><a href="{{url('teacher-details')}}" class="link-primary">T849125</a></td>
										<td>
											<div class="d-flex align-items-center">
												<a href="{{url('teacher-details')}}" class="avatar avatar-md"><img
														src="{{URL::asset('build/img/teachers/teacher-03.jpg')}}"
														class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="{{url('teacher-details')}}">Hellana</a>
													</p>
												</div>
											</div>
										</td>
										<td>VI (A)</td>
										<td>English</td>
										<td>hellana@example.com</td>
										<td>+1 23566 52683</td>
										<td>11 Apr 2024</td>
										<td>
											<span class="badge badge-soft-danger d-inline-flex align-items-center"><i
													class="ti ti-circle-filled fs-5 me-1"></i>Inactive</span>
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
															<a class="dropdown-item rounded-1"
																href="{{url('teacher-details')}}"><i
																	class="ti ti-menu me-2"></i>View Teacher</a>
														</li>
														<li>
															<a class="dropdown-item rounded-1"
																href="{{url('edit-teacher')}}"><i
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
										<td><a href="{{url('teacher-details')}}" class="link-primary">T849124</a></td>
										<td>
											<div class="d-flex align-items-center">
												<a href="{{url('teacher-details')}}" class="avatar avatar-md"><img
														src="{{URL::asset('build/img/teachers/teacher-06.jpg')}}"
														class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a
															href="{{url('teacher-details')}}">Erickson</a></p>
												</div>
											</div>
										</td>
										<td>I (A)</td>
										<td>Chemistry</td>
										<td>aaron@example.com</td>
										<td>+1 26267 80542</td>
										<td>12 May 2024</td>
										<td>
											<span class="badge badge-soft-danger d-inline-flex align-items-center"><i
													class="ti ti-circle-filled fs-5 me-1"></i>Inactive</span>
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
															<a class="dropdown-item rounded-1"
																href="{{url('teacher-details')}}"><i
																	class="ti ti-menu me-2"></i>View Teacher</a>
														</li>
														<li>
															<a class="dropdown-item rounded-1"
																href="{{url('edit-teacher')}}"><i
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
										<td><a href="{{url('teacher-details')}}" class="link-primary">T849121</a></td>
										<td>
											<div class="d-flex align-items-center">
												<a href="{{url('teacher-details')}}" class="avatar avatar-md"><img
														src="{{URL::asset('build/img/teachers/teacher-07.jpg')}}"
														class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="{{url('teacher-details')}}">Morgan</a>
													</p>
												</div>
											</div>
										</td>
										<td>IV</td>
										<td>Maths</td>
										<td>jacquelin@example.com</td>
										<td>+1 77502 54845</td>
										<td>20 May 2024</td>
										<td>
											<span class="badge badge-soft-success d-inline-flex align-items-center"><i
													class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
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
															<a class="dropdown-item rounded-1"
																href="{{url('teacher-details')}}"><i
																	class="ti ti-menu me-2"></i>View Teacher</a>
														</li>
														<li>
															<a class="dropdown-item rounded-1"
																href="{{url('edit-teacher')}}"><i
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
										<td><a href="{{url('teacher-details')}}" class="link-primary">T849122</a></td>
										<td>
											<div class="d-flex align-items-center">
												<a href="{{url('teacher-details')}}" class="avatar avatar-md"><img
														src="{{URL::asset('build/img/teachers/teacher-06.jpg')}}"
														class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="{{url('teacher-details')}}">Aaron</a>
													</p>
												</div>
											</div>
										</td>
										<td>I (A)</td>
										<td>Chemistry</td>
										<td>aaron@example.com</td>
										<td>+1 26267 80542</td>
										<td>12 May 2024</td>
										<td>
											<span class="badge badge-soft-success d-inline-flex align-items-center"><i
													class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
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
															<a class="dropdown-item rounded-1"
																href="{{url('teacher-details')}}"><i
																	class="ti ti-menu me-2"></i>View Teacher</a>
														</li>
														<li>
															<a class="dropdown-item rounded-1"
																href="{{url('edit-teacher')}}"><i
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
										<td><a href="{{url('teacher-details')}}" class="link-primary">T849127</a></td>
										<td>
											<div class="d-flex align-items-center">
												<a href="{{url('teacher-details')}}" class="avatar avatar-md"><img
														src="{{URL::asset('build/img/teachers/teacher-09.jpg')}}"
														class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="{{url('teacher-details')}}">Daniel</a>
													</p>
												</div>
											</div>
										</td>
										<td>IV</td>
										<td>Maths</td>
										<td>jacquelin@example.com</td>
										<td>+1 77502 54845</td>
										<td>20 May 2024</td>
										<td>
											<span class="badge badge-soft-success d-inline-flex align-items-center"><i
													class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
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
															<a class="dropdown-item rounded-1"
																href="{{url('teacher-details')}}"><i
																	class="ti ti-menu me-2"></i>View Teacher</a>
														</li>
														<li>
															<a class="dropdown-item rounded-1"
																href="{{url('edit-teacher')}}"><i
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
										<td><a href="{{url('teacher-details')}}" class="link-primary">T849120</a></td>
										<td>
											<div class="d-flex align-items-center">
												<a href="{{url('teacher-details')}}" class="avatar avatar-md"><img
														src="{{URL::asset('build/img/students/student-10.jpg')}}"
														class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="{{url('teacher-details')}}">Raul</a>
													</p>
												</div>
											</div>
										</td>
										<td>IX</td>
										<td>Biology</td>
										<td>raul@example.com</td>
										<td>+1 67845 78784</td>
										<td>27 May 2024</td>
										<td>
											<span class="badge badge-soft-danger d-inline-flex align-items-center"><i
													class="ti ti-circle-filled fs-5 me-1"></i>Inactive</span>
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
															<a class="dropdown-item rounded-1"
																href="{{url('teacher-details')}}"><i
																	class="ti ti-menu me-2"></i>View Teacher</a>
														</li>
														<li>
															<a class="dropdown-item rounded-1"
																href="{{url('edit-teacher')}}"><i
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
										<td><a href="{{url('teacher-details')}}" class="link-primary">T849119</a></td>
										<td>
											<div class="d-flex align-items-center">
												<a href="{{url('teacher-details')}}" class="avatar avatar-md"><img
														src="{{URL::asset('build/img/students/student-11.jpg')}}"
														class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a
															href="{{url('teacher-details')}}">Elizabeth</a></p>
												</div>
											</div>
										</td>
										<td>VII</td>
										<td>Economics</td>
										<td>elizabeth@example.com</td>
										<td>+1 97846 84518</td>
										<td>10 Jun 2024</td>
										<td>
											<span class="badge badge-soft-success d-inline-flex align-items-center"><i
													class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
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
															<a class="dropdown-item rounded-1"
																href="{{url('teacher-details')}}"><i
																	class="ti ti-menu me-2"></i>View Teacher</a>
														</li>
														<li>
															<a class="dropdown-item rounded-1"
																href="{{url('edit-teacher')}}"><i
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
										<td><a href="{{url('teacher-details')}}" class="link-primary">T849118</a></td>
										<td>
											<div class="d-flex align-items-center">
												<a href="{{url('teacher-details')}}" class="avatar avatar-md"><img
														src="{{URL::asset('build/img/teachers/teacher-03.jpg')}}"
														class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p  class="text-dark mb-0"><a
															href="{{url('teacher-details')}}">Edward</a></p>
												</div>
											</div>
										</td>
										<td>IX (C) , X (C)</td>
										<td>Finance</td>
										<td>edward@example.com</td>
										<td>+1 56187 87489</td>
										<td>18 Jun 2024</td>
										<td>
											<span class="badge badge-soft-success d-inline-flex align-items-center"><i
													class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
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
															<a class="dropdown-item rounded-1"
																href="{{url('teacher-details')}}"><i
																	class="ti ti-menu me-2"></i>View Teacher</a>
														</li>
														<li>
															<a class="dropdown-item rounded-1"
																href="{{url('edit-teacher')}}"><i
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
										<td><a href="{{url('teacher-details')}}" class="link-primary">T849117</a></td>
										<td>
											<div class="d-flex align-items-center">
												<a href="{{url('teacher-details')}}" class="avatar avatar-md"><img
														src="{{URL::asset('build/img/teachers/teacher-04.jpg')}}"
														class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="{{url('teacher-details')}}">Maria</a>
													</p>
												</div>
											</div>
										</td>
										<td>I (A)</td>
										<td>English</td>
										<td>maria@example.com</td>
										<td>+1 97846 84518</td>
										<td>22 Mar 2018</td>
										<td>
											<span class="badge badge-soft-success d-inline-flex align-items-center"><i
													class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
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
															<a class="dropdown-item rounded-1"
																href="{{url('teacher-details')}}"><i
																	class="ti ti-menu me-2"></i>View Teacher</a>
														</li>
														<li>
															<a class="dropdown-item rounded-1"
																href="{{url('edit-teacher')}}"><i
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
