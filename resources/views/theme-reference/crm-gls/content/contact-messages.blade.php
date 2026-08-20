<?php $page = 'contact-messages'; ?>
@extends('layout.mainlayout')
@section('content')
		<!-- Page Wrapper -->
		<div class="page-wrapper">
			<div class="content">
				<!-- Page Header -->
				<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
					<div class="my-auto mb-2">
						<h3 class="page-title mb-1">Contact Messages</h3>
						<nav>
							<ol class="breadcrumb mb-0">
								<li class="breadcrumb-item">
									<a href="javascript:void(0);">Dashboard</a>
								</li>
								<li class="breadcrumb-item">
									<a href="javascript:void(0);">Support</a>
								</li>
								<li class="breadcrumb-item active" aria-current="page">Contact Messages</li>
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
							<a href="#" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal"
								data-bs-target="#add_messages"><i class="ti ti-square-rounded-plus me-2"></i>Add Message</a>
						</div>
					</div>
				</div>
				<!-- /Page Header -->

				<!-- Filter Section -->
				<div class="card">
					<div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
						<h4 class="mb-3">Contact Messages List</h4>
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
									<form action="{{url('contact-messages')}}">
										<div class="d-flex align-items-center border-bottom p-3">
											<h4>Filter</h4>
										</div>
										<div class="p-3 pb-0 border-bottom">
											<div class="row">
												<div class="col-md-12">
													<div class="mb-3">
														<label class="form-label">Questions</label>
														<select class="select">
															<option>Select</option>
															<option>Reminder: Staff meeting tomorrow.</option>
															<option>Sure, let's discuss it in class.</option>
															<option>Requesting a meeting for next week.</option>
														</select>
													</div>
												</div>
												<div class="col-md-12">
													<div class="mb-3">
														<label class="form-label">Category</label>
														<select class="select">
															<option>Select</option>
															<option>Date</option>
															<option>Message</option>
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
						<!-- Contant Message -->
						<div class="custom-datatable-filter
						table-responsive">
							<table class="table datatable">
								<thead class="thead-light">
									<tr>
										<th class="no-sort">
											<label class="checkboxs"><input type="checkbox" id="select-all"><span
													class="checkmarks"></span></label>
										</th>
										<th>ID</th>
										<th>Name</th>
										<th>Phone</th>
										<th>Email </th>
										<th>Message</th>
										<th>Date</th>
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
										<td><a href="#" class="link-primary">CM482902</a></td>
										<td>
											<div class="d-flex align-items-center">
												<a href="{{url('teacher-details')}}" class="avatar avatar-md"><img src="{{URL::asset('build/img/teachers/teacher-01.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="mb-0"><a href="{{url('teacher-details')}}">teressa</a></p>
												</div>
											</div>
										</td>
										<td>+1 82392 37359</td>
										<td>teresa@example.com</td>
										<td>Reminder: Staff meeting tomorrow.</td>
										<td>
											25 Mar 2024
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
															<a class="dropdown-item rounded-1" href="#"
																data-bs-toggle="modal" data-bs-target="#edit_messages"><i
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
											<label class="checkboxs">
												<input type="checkbox">
												<span class="checkmarks"></span>
											</label>
										</td>
										<td><a href="#" class="link-primary">CM482901</a></td>
										<td>
											<div class="d-flex align-items-center">
												<a href="{{url('teacher-details')}}" class="avatar avatar-md"><img src="{{URL::asset('build/img/teachers/teacher-03.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="mb-0"><a href="{{url('teacher-details')}}">Hellana</a></p>
												</div>
											</div>
										</td>
										<td>+1 23566 52683</td>
										<td>hellena@example.com</td>
										<td>Sure, let's discuss it in class.</td>
										<td>
											14 Apr 2024
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
															<a class="dropdown-item rounded-1" href="#"
																data-bs-toggle="modal" data-bs-target="#edit_messages"><i
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
											<label class="checkboxs">
												<input type="checkbox">
												<span class="checkmarks"></span>
											</label>
										</td>
										<td><a href="#l" class="link-primary">CM482900</a></td>
										<td>
											<div class="d-flex align-items-center">
												<a href="{{url('teacher-details')}}" class="avatar avatar-md"><img src="{{URL::asset('build/img/users/user-01.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="mb-0"><a href="{{url('teacher-details')}}">William</a></p>
												</div>
											</div>
										</td>
										<td>+1 63792 50310</td>
										<td>william@example.com</td>
										<td>
											Requesting a meeting for next week.
										</td>
										<td>
											28 Apr 2024
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
															<a class="dropdown-item rounded-1" href="#"
																data-bs-toggle="modal" data-bs-target="#edit_messages"><i
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
											<label class="checkboxs">
												<input type="checkbox">
												<span class="checkmarks"></span>
											</label>
										</td>
										<td><a href="#" class="link-primary">CM482899</a></td>
										<td>
											<div class="d-flex align-items-center">
												<a href="{{url('teacher-details')}}" class="avatar avatar-md"><img src="{{URL::asset('build/img/teachers/teacher-02.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="mb-0"><a href="{{url('teacher-details')}}">Daniel</a></p>
												</div>
											</div>
										</td>
										<td>+1 56752 86742</td>
										<td>daniel@example.com</td>
										<td>Meeting scheduled for June 10th.</td>
										<td>
											04 May 2024
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
															<a class="dropdown-item rounded-1" href="#"
																data-bs-toggle="modal" data-bs-target="#edit_messages"><i
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
											<label class="checkboxs">
												<input type="checkbox">
												<span class="checkmarks"></span>
											</label>
										</td>
										<td><a href="#" class="link-primary">CM482898</a></td>
										<td>
											<div class="d-flex align-items-center">
												<a href="#" class="avatar avatar-md"><img src="{{URL::asset('build/img/members/members-02.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="mb-0"><a href="#">Galina</a></p>
												</div>
											</div>
										</td>
										<td>+1 91304 34834</td>
										<td>galina@example.com</td>
										<td>Reminder: Staff meeting tomorrow.</td>
										<td>
											17 May 2024
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
															<a class="dropdown-item rounded-1" href="#"
																data-bs-toggle="modal" data-bs-target="#edit_messages"><i
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
											<label class="checkboxs">
												<input type="checkbox">
												<span class="checkmarks"></span>
											</label>
										</td>
										<td><a href="#" class="link-primary">CM482897</a></td>
										<td>
											<div class="d-flex align-items-center">
												<a href="#" class="avatar avatar-md"><img src="{{URL::asset('build/img/teachers/teacher-10.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="mb-0"><a href="#">Edward</a></p>
												</div>
											</div>
										</td>
										<td>+1 56187 87489</td>
										<td>edward@example.com</td>
										<td>Don't forget to submit the slip.</td>
										<td>
											20 May 2024
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
															<a class="dropdown-item rounded-1" href="#"
																data-bs-toggle="modal" data-bs-target="#edit_messages"><i
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
											<label class="checkboxs">
												<input type="checkbox">
												<span class="checkmarks"></span>
											</label>
										</td>
										<td><a href="#" class="link-primary">CM482896</a></td>
										<td>
											<div class="d-flex align-items-center">
												<a href="#" class="avatar avatar-md"><img src="{{URL::asset('build/img/members/members-07.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="mb-0"><a href="#">Galina</a></p>
												</div>
											</div>
										</td>
										<td>+1 77502 54845</td>
										<td>jacquelin@example.com</td>
										<td>Can we review the test tomorrow?</td>
										<td>
											03 Jun 2024
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
															<a class="dropdown-item rounded-1" href="#"
																data-bs-toggle="modal" data-bs-target="#edit_messages"><i
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
											<label class="checkboxs">
												<input type="checkbox">
												<span class="checkmarks"></span>
											</label>
										</td>
										<td><a href="#" class="link-primary">CM482895</a></td>
										<td>
											<div class="d-flex align-items-center">
												<a href="#" class="avatar avatar-md"><img src="{{URL::asset('build/img/users/user-09.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="mb-0"><a href="#">Garly</a></p>
												</div>
											</div>
										</td>
										<td>+1 82239 42492</td>
										<td>gary@example.com</td>
										<td>Is there a summer camp program?</td>
										<td>
											15 Jun 2024
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
															<a class="dropdown-item rounded-1" href="#"
																data-bs-toggle="modal" data-bs-target="#edit_messages"><i
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
											<label class="checkboxs">
												<input type="checkbox">
												<span class="checkmarks"></span>
											</label>
										</td>
										<td><a href="#" class="link-primary">CM482894</a></td>
										<td>
											<div class="d-flex align-items-center">
												<a href="#" class="avatar avatar-md"><img src="{{URL::asset('build/img/teachers/teacher-06.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="mb-0"><a href="#">Morgan</a></p>
												</div>
											</div>
										</td>
										<td>+1 63204 35730</td>
										<td>morgan@example.com</td>
										<td>Yes, registration starts next week.</td>
										<td>
											27 Jun 2024
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
															<a class="dropdown-item rounded-1" href="#"
																data-bs-toggle="modal" data-bs-target="#edit_messages"><i
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
											<label class="checkboxs">
												<input type="checkbox">
												<span class="checkmarks"></span>
											</label>
										</td>
										<td><a href="#" class="link-primary">CM482893</a></td>
										<td>
											<div class="d-flex align-items-center">
												<a href="#" class="avatar avatar-md"><img src="{{URL::asset('build/img/teachers/teacher-06.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="mb-0"><a href="#">AAron</a></p>
												</div>
											</div>
										</td>
										<td>+1 26267 80542</td>
										<td>aaron@example.com</td>
										<td>You have a missing assignment.</td>
										<td>
											10 Jul 2024
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
															<a class="dropdown-item rounded-1" href="#"
																data-bs-toggle="modal" data-bs-target="#edit_messages"><i
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
						<!-- /Contant Message -->
					</div>
				</div>
				<!-- /Filter Section -->

			</div>
		</div>
		<!-- /Page Wrapper -->


@component('components.modal-popup')
@endcomponent
@endsection