<?php $page = 'parent-grid'; ?>
@extends('layout.mainlayout')
@section('content')
   <!-- Page Wrapper -->
		<div class="page-wrapper">
			<div class="content content-two">
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
									Peoples
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
											class="ti ti-file-type-pdf me-2"></i>Export as PDF</a>
								</li>
								<li>
									<a href="javascript:void(0);" class="dropdown-item rounded-1"><i
											class="ti ti-file-type-xls me-2"></i>Export as Excel </a>
								</li>
							</ul>
						</div>
						<div class="mb-2">
							<a href="#" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal"
								data-bs-target="#add_parent"><i class="ti ti-square-rounded-plus me-2"></i>Add
								Parent</a>
						</div>
					</div>
				</div>
				<!-- /Page Header -->

				<div
					class="bg-white p-3 border rounded-1 d-flex align-items-center justify-content-between flex-wrap mb-4 pb-0">
					<h4 class="mb-3">Parents Grid</h4>
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
								<form action="{{url('parents')}}">
									<div class="d-flex align-items-center border-bottom p-3">
										<h4>Filter</h4>
									</div>
									<div class="p-3 pb-0 border-bottom">
										<div class="row">
											<div class="col-md-6">
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
											<div class="col-md-6">
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
							<a href="{{url('parents')}}" class=" btn btn-icon btn-sm me-1 bg-light primary-hover"><i
								class="ti ti-list-tree"></i></a>
							<a href="{{url('parent-grid')}}" class=" active btn btn-icon btn-sm  primary-hover"><i
								class="ti ti-grid-dots"></i></a>
						  </div>
						<div class="dropdown mb-3">
							<a href="javascript:void(0);" class="btn btn-outline-light bg-white dropdown-toggle"
								data-bs-toggle="dropdown"><i class="ti ti-sort-ascending-2 me-2"></i>Sort by A-Z </a>
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


				<div class="row">

					<!-- Parent Grid -->
					<div class="col-xl-4 col-md-6 d-flex">
						<div class="card flex-fill">
							<div class="card-header d-flex align-items-center justify-content-between">
								<a href="#" class="link-primary" data-bs-toggle="modal"
								data-bs-target="#view_details">P124556</a>
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
													data-bs-target="#edit_parent"><i
														class="ti ti-edit-circle me-2"></i>Edit</a>
											</li>
											<li>
												<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
													data-bs-target="#delete-modal"><i
														class="ti ti-trash-x me-2"></i>Delete</a>
											</li>
										</ul>
									</div>
								</div>
							</div>
							<div class="card-body">
								<div class="bg-light-300 rounded-2 p-3 mb-3">
									<div class="d-flex align-items-center">
										<a href="#"data-bs-toggle="modal"
										data-bs-target="#view_details" class="avatar avatar-lg flex-shrink-0"><img
												src="{{URL::asset('build/img/parents/parent-01.jpg')}}" class="img-fluid rounded-circle"
												alt="img"></a>
										<div class="ms-2">
											<h6 class="text-dark text-truncate mb-0"><a
													href="#" data-bs-toggle="modal"
													data-bs-target="#view_details">Thomas</a></h6>
											<p>Added on 25 Mar 2024</p>
										</div>
									</div>
								</div>
								<div class="d-flex align-items-center justify-content-between gx-2">
									<div>
										<p class="mb-0">Email</p>
										<p class="text-dark">tom@example.com</p>
									</div>
									<div>
										<p class="mb-0">Phone</p>
										<p class="text-dark">+1 65738 58937</p>
									</div>
								</div>
							</div>
							<div class="card-footer d-flex align-items-center justify-content-between">
								<div class="d-flex align-items-center">
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md flex-shrink-0 p-0 me-2">
											<img src="{{URL::asset('build/img/students/student-01.jpg')}}" alt="img"
												class="img-fluid rounded-circle">
										</a>
										<p class="text-dark">Janet</p>
									</div>
								</div>
								<a href="#" class="btn btn-light btn-sm" data-bs-toggle="modal"
									data-bs-target="#view_details">View Details</a>
							</div>
						</div>
					</div>
					<!-- /Parent Grid -->

					<!-- Parent Grid -->
					<div class="col-xl-4 col-md-6 d-flex">
						<div class="card flex-fill">
							<div class="card-header d-flex align-items-center justify-content-between">
								<a href="#" class="link-primary" data-bs-toggle="modal"
								data-bs-target="#view_details">P124555</a>
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
													data-bs-target="#edit_parent"><i
														class="ti ti-edit-circle me-2"></i>Edit</a>
											</li>
											<li>
												<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
													data-bs-target="#delete-modal"><i
														class="ti ti-trash-x me-2"></i>Delete</a>
											</li>
										</ul>
									</div>
								</div>
							</div>
							<div class="card-body">
								<div class="bg-light-300 rounded-2 p-3 mb-3">
									<div class="d-flex align-items-center">
										<a href="#" data-bs-toggle="modal"
										data-bs-target="#view_details" class="avatar avatar-lg flex-shrink-0"><img
												src="{{URL::asset('build/img/parents/parent-02.jpg')}}" class="img-fluid rounded-circle"
												alt="img"></a>
										<div class="ms-2">
											<h6  class="text-dark text-truncate mb-0"><a
													href="#" data-bs-toggle="modal"
													data-bs-target="#view_details">Marquita</a></h6>
											<p>Added on 18 Mar 2024</p>
										</div>
									</div>
								</div>
								<div class="d-flex align-items-center justify-content-between gx-2">
									<div>
										<p class="mb-0">Email</p>
										<p class="text-dark">mar@example.com</p>
									</div>
									<div>
										<p class="mb-0">Phone</p>
										<p class="text-dark">+1 65738 58937</p>
									</div>
								</div>
							</div>
							<div class="card-footer d-flex align-items-center justify-content-between">
								<div class="d-flex align-items-center">
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md flex-shrink-0 p-0 me-2">
											<img src="{{URL::asset('build/img/students/student-02.jpg')}}" alt="img"
												class="img-fluid rounded-circle">
										</a>
										<p class="text-dark">Joann</p>
									</div>
								</div>
								<a href="#" class="btn btn-light btn-sm" data-bs-toggle="modal"
									data-bs-target="#view_details">View Details</a>
							</div>
						</div>
					</div>
					<!-- /Parent Grid -->

					<!-- Parent Grid -->
					<div class="col-xl-4 col-md-6 d-flex">
						<div class="card flex-fill">
							<div class="card-header d-flex align-items-center justify-content-between">
								<a href="#" class="link-primary" data-bs-toggle="modal"
								data-bs-target="#view_details" >P124554</a>
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
													data-bs-target="#edit_parent"><i
														class="ti ti-edit-circle me-2"></i>Edit</a>
											</li>
											<li>
												<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
													data-bs-target="#delete-modal"><i
														class="ti ti-trash-x me-2"></i>Delete</a>
											</li>
										</ul>
									</div>
								</div>
							</div>
							<div class="card-body">
								<div class="bg-light-300 rounded-2 p-3 mb-3">
									<div class="d-flex align-items-center">
										<a href="#" class="avatar avatar-lg flex-shrink-0" data-bs-toggle="modal"
										data-bs-target="#view_details"><img
												src="{{URL::asset('build/img/parents/parent-03.jpg')}}" class="img-fluid rounded-circle"
												alt="img"></a>
										<div class="ms-2">
											<h6  class="text-dark text-truncate mb-0"><a
													href="#" data-bs-toggle="modal"
													data-bs-target="#view_details">Johnson</a></h6>
											<p>Added on 14 Mar 2024</p>
										</div>
									</div>
								</div>
								<div class="d-flex align-items-center justify-content-between gx-2">
									<div>
										<p class="mb-0">Email</p>
										<p class="text-dark">joh@example.com</p>
									</div>
									<div>
										<p class="mb-0">Phone</p>
										<p class="text-dark">+1 65738 58937</p>
									</div>
								</div>
							</div>
							<div class="card-footer d-flex align-items-center justify-content-between">
								<div class="d-flex align-items-center">
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md flex-shrink-0 p-0 me-2">
											<img src="{{URL::asset('build/img/students/student-03.jpg')}}" alt="img"
												class="img-fluid rounded-circle">
										</a>
										<p class="text-dark">Kathleen</p>
									</div>
								</div>
								<a href="#" class="btn btn-light btn-sm" data-bs-toggle="modal"
									data-bs-target="#view_details">View Details</a>
							</div>
						</div>
					</div>
					<!-- /Parent Grid -->

					<!-- Parent Grid -->
					<div class="col-xl-4 col-md-6 d-flex">
						<div class="card flex-fill">
							<div class="card-header d-flex align-items-center justify-content-between">
								<a href="#" class="link-primary" data-bs-toggle="modal"
								data-bs-target="#view_details">P124552</a>
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
													data-bs-target="#edit_parent"><i
														class="ti ti-edit-circle me-2"></i>Edit</a>
											</li>
											<li>
												<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
													data-bs-target="#delete-modal"><i
														class="ti ti-trash-x me-2"></i>Delete</a>
											</li>
										</ul>
									</div>
								</div>
							</div>
							<div class="card-body">
								<div class="bg-light-300 rounded-2 p-3 mb-3">
									<div class="d-flex align-items-center">
										<a href="#" data-bs-toggle="modal"
										data-bs-target="#view_details" class="avatar avatar-lg flex-shrink-0"><img
												src="{{URL::asset('build/img/parents/parent-05.jpg')}}" class="img-fluid rounded-circle"
												alt="img"></a>
										<div class="ms-2">
											<h6  class="text-dark text-truncate mb-0"><a
													href="#" data-bs-toggle="modal"
													data-bs-target="#view_details">Arthur</a></h6>
											<p>Added on 11 Feb 2024</p>
										</div>
									</div>
								</div>
								<div class="d-flex align-items-center justify-content-between gx-2">
									<div>
										<p class="mb-0">Email</p>
										<p class="text-dark">art@example.com</p>
									</div>
									<div>
										<p class="mb-0">Phone</p>
										<p class="text-dark">+1 65738 58937</p>
									</div>
								</div>
							</div>
							<div class="card-footer d-flex align-items-center justify-content-between">
								<div class="d-flex align-items-center">
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md flex-shrink-0 p-0 me-2">
											<img src="{{URL::asset('build/img/students/student-04.jpg')}}" alt="img"
												class="img-fluid rounded-circle">
										</a>
										<p class="text-dark">Gifford</p>
									</div>
								</div>
								<a href="#" class="btn btn-light btn-sm" data-bs-toggle="modal"
									data-bs-target="#view_details">View Details</a>
							</div>
						</div>
					</div>
					<!-- /Parent Grid -->

					<!-- Parent Grid -->
					<div class="col-xl-4 col-md-6 d-flex">
						<div class="card flex-fill">
							<div class="card-header d-flex align-items-center justify-content-between">
								<a href="#" class="link-primary" data-bs-toggle="modal"
								data-bs-target="#view_details">P124551</a>
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
													data-bs-target="#edit_parent"><i
														class="ti ti-edit-circle me-2"></i>Edit</a>
											</li>
											<li>
												<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
													data-bs-target="#delete-modal"><i
														class="ti ti-trash-x me-2"></i>Delete</a>
											</li>
										</ul>
									</div>
								</div>
							</div>
							<div class="card-body">
								<div class="bg-light-300 rounded-2 p-3 mb-3">
									<div class="d-flex align-items-center">
										<a href="#" data-bs-toggle="modal"
										data-bs-target="#view_details" class="avatar avatar-lg flex-shrink-0"><img
												src="{{URL::asset('build/img/parents/parent-06.jpg')}}" class="img-fluid rounded-circle"
												alt="img"></a>
										<div class="ms-2">
											<h6  class="text-dark text-truncate mb-0"><a
													href="#" data-bs-toggle="modal"
													data-bs-target="#view_details">Colleen</a></h6>
											<p>Added on 24 Jan 2024</p>
										</div>
									</div>
								</div>
								<div class="d-flex align-items-center justify-content-between gx-2">
									<div>
										<p class="mb-0">Email</p>
										<p class="text-dark">col@example.com</p>
									</div>
									<div>
										<p class="mb-0">Phone</p>
										<p class="text-dark">+1 65738 58937</p>
									</div>
								</div>
							</div>
							<div class="card-footer d-flex align-items-center justify-content-between">
								<div class="d-flex align-items-center">
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md flex-shrink-0 p-0 me-2">
											<img src="{{URL::asset('build/img/students/student-05.jpg')}}" alt="img"
												class="img-fluid rounded-circle">
										</a>
										<p class="text-dark">Lisa</p>
									</div>
								</div>
								<a href="#" class="btn btn-light btn-sm" data-bs-toggle="modal"
									data-bs-target="#view_details">View Details</a>
							</div>
						</div>
					</div>
					<!-- /Parent Grid -->

					<!-- Parent Grid -->
					<div class="col-xl-4 col-md-6 d-flex">
						<div class="card flex-fill">
							<div class="card-header d-flex align-items-center justify-content-between">
								<a href="#" class="link-primary" data-bs-toggle="modal"
								data-bs-target="#view_details"> P124550</a>
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
													data-bs-target="#edit_parent"><i
														class="ti ti-edit-circle me-2"></i>Edit</a>
											</li>
											<li>
												<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
													data-bs-target="#delete-modal"><i
														class="ti ti-trash-x me-2"></i>Delete</a>
											</li>
										</ul>
									</div>
								</div>
							</div>
							<div class="card-body">
								<div class="bg-light-300 rounded-2 p-3 mb-3">
									<div class="d-flex align-items-center">
										<a href="#" data-bs-toggle="modal"
										data-bs-target="#view_details" class="avatar avatar-lg flex-shrink-0"><img
												src="{{URL::asset('build/img/parents/parent-07.jpg')}}" class="img-fluid rounded-circle"
												alt="img"></a>
										<div class="ms-2">
											<h6  class="text-dark text-truncate mb-0"><a
													href="#" data-bs-toggle="modal"
													data-bs-target="#view_details">Robert</a></h6>
											<p>Added on 19 Jan 2024</p>
										</div>
									</div>
								</div>
								<div class="d-flex align-items-center justify-content-between gx-2">
									<div>
										<p class="mb-0">Email</p>
										<p class="text-dark">rob@example.com</p>
									</div>
									<div>
										<p class="mb-0">Phone</p>
										<p class="text-dark">+1 65738 58937</p>
									</div>
								</div>
							</div>
							<div class="card-footer d-flex align-items-center justify-content-between">
								<div class="d-flex align-items-center">
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md flex-shrink-0 p-0 me-2">
											<img src="{{URL::asset('build/img/students/student-06.jpg')}}" alt="img"
												class="img-fluid rounded-circle">
										</a>
										<p class="text-dark">Ralph</p>
									</div>
								</div>
								<a href="#" class="btn btn-light btn-sm" data-bs-toggle="modal"
									data-bs-target="#view_details">View Details</a>
							</div>
						</div>
					</div>
					<!-- /Parent Grid -->

					<!-- Parent Grid -->
					<div class="col-xl-4 col-md-6 d-flex">
						<div class="card flex-fill">
							<div class="card-header d-flex align-items-center justify-content-between">
								<a href="#" class="link-primary" data-bs-toggle="modal"
								data-bs-target="#view_details"> P124548</a>
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
													data-bs-target="#edit_parent"><i
														class="ti ti-edit-circle me-2"></i>Edit</a>
											</li>
											<li>
												<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
													data-bs-target="#delete-modal"><i
														class="ti ti-trash-x me-2"></i>Delete</a>
											</li>
										</ul>
									</div>
								</div>
							</div>
							<div class="card-body">
								<div class="bg-light-300 rounded-2 p-3 mb-3">
									<div class="d-flex align-items-center">
										<a href="#" data-bs-toggle="modal"
										data-bs-target="#view_details" class="avatar avatar-lg flex-shrink-0"><img
												src="{{URL::asset('build/img/parents/parent-09.jpg')}}" class="img-fluid rounded-circle"
												alt="img"></a>
										<div class="ms-2">
											<h6  class="text-dark text-truncate mb-0"><a
													href="#" data-bs-toggle="modal"
													data-bs-target="#view_details">Michael</a></h6>
											<p>Added on 22 Dec 2023</p>
										</div>
									</div>
								</div>
								<div class="d-flex align-items-center justify-content-between gx-2">
									<div>
										<p class="mb-0">Email</p>
										<p class="text-dark">mic@example.com</p>
									</div>
									<div>
										<p class="mb-0">Phone</p>
										<p class="text-dark">+1 65738 58937</p>
									</div>
								</div>
							</div>
							<div class="card-footer d-flex align-items-center justify-content-between">
								<div class="d-flex align-items-center">
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md flex-shrink-0 p-0 me-2">
											<img src="{{URL::asset('build/img/students/student-07.jpg')}}" alt="img"
												class="img-fluid rounded-circle">
										</a>
										<p class="text-dark">Julie</p>
									</div>
								</div>
								<a href="#" class="btn btn-light btn-sm" data-bs-toggle="modal"
									data-bs-target="#view_details">View Details</a>
							</div>
						</div>
					</div>
					<!-- /Parent Grid -->

					<!-- Parent Grid -->
					<div class="col-xl-4 col-md-6 d-flex">
						<div class="card flex-fill">
							<div class="card-header d-flex align-items-center justify-content-between">
								<a href="#" class="link-primary" data-bs-toggle="modal"
								data-bs-target="#view_details"> P124547</a>
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
													data-bs-target="#edit_parent"><i
														class="ti ti-edit-circle me-2"></i>Edit</a>
											</li>
											<li>
												<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
													data-bs-target="#delete-modal"><i
														class="ti ti-trash-x me-2"></i>Delete</a>
											</li>
										</ul>
									</div>
								</div>
							</div>
							<div class="card-body">
								<div class="bg-light-300 rounded-2 p-3 mb-3">
									<div class="d-flex align-items-center">
										<a href="#" data-bs-toggle="modal"
										data-bs-target="#view_details" class="avatar avatar-lg flex-shrink-0"><img
												src="{{URL::asset('build/img/parents/parent-10.jpg')}}" class="img-fluid rounded-circle"
												alt="img"></a>
										<div class="ms-2">
											<h6  class="text-dark text-truncate mb-0"><a
													href="#" data-bs-toggle="modal"
													data-bs-target="#view_details">Mary</a></h6>
											<p>Added on 15 Dec 2024</p>
										</div>
									</div>
								</div>
								<div class="d-flex align-items-center justify-content-between gx-2">
									<div>
										<p class="mb-0">Email</p>
										<p class="text-dark">mar@example.com</p>
									</div>
									<div>
										<p class="mb-0">Phone</p>
										<p class="text-dark">+1 65738 58937</p>
									</div>
								</div>
							</div>
							<div class="card-footer d-flex align-items-center justify-content-between">
								<div class="d-flex align-items-center">
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md flex-shrink-0 p-0 me-2">
											<img src="{{URL::asset('build/img/students/student-08.jpg')}}" alt="img"
												class="img-fluid rounded-circle">
										</a>
										<p class="text-dark">Ryan</p>
									</div>
								</div>
								<a href="#" class="btn btn-light btn-sm" data-bs-toggle="modal"
									data-bs-target="#view_details">View Details</a>
							</div>
						</div>
					</div>
					<!-- /Parent Grid -->

					<!-- Parent Grid -->
					<div class="col-xl-4 col-md-6 d-flex">
						<div class="card flex-fill">
							<div class="card-header d-flex align-items-center justify-content-between">
								<a href="#" class="link-primary" data-bs-toggle="modal"
								data-bs-target="#view_details"> P124546</a>
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
													data-bs-target="#edit_parent"><i
														class="ti ti-edit-circle me-2"></i>Edit</a>
											</li>
											<li>
												<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
													data-bs-target="#delete-modal"><i
														class="ti ti-trash-x me-2"></i>Delete</a>
											</li>
										</ul>
									</div>
								</div>
							</div>
							<div class="card-body">
								<div class="bg-light-300 rounded-2 p-3 mb-3">
									<div class="d-flex align-items-center">
										<a href="#" data-bs-toggle="modal"
										data-bs-target="#view_details" class="avatar avatar-lg flex-shrink-0"><img
												src="{{URL::asset('build/img/parents/parent-11.jpg')}}" class="img-fluid rounded-circle"
												alt="img"></a>
										<div class="ms-2">
											<h6  class="text-dark text-truncate mb-0"><a
													href="#" data-bs-toggle="modal"
													data-bs-target="#view_details">Edwin</a></h6>
											<p>Added on 10 Dec 2023</p>
										</div>
									</div>
								</div>
								<div class="d-flex align-items-center justify-content-between gx-2">
									<div>
										<p class="mb-0">Email</p>
										<p class="text-dark">edw@example.com</p>
									</div>
									<div>
										<p class="mb-0">Phone</p>
										<p class="text-dark">+1 65738 58937</p>
									</div>
								</div>
							</div>
							<div class="card-footer d-flex align-items-center justify-content-between">
								<div class="d-flex align-items-center">
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md flex-shrink-0 p-0 me-2">
											<img src="{{URL::asset('build/img/students/student-09.jpg')}}" alt="img"
												class="img-fluid rounded-circle">
										</a>
										<p class="text-dark">Susan</p>
									</div>
								</div>
								<a href="#" class="btn btn-light btn-sm" data-bs-toggle="modal"
									data-bs-target="#view_details">View Details</a>
							</div>
						</div>
					</div>
					<!-- /Parent Grid -->

					<!-- Parent Grid -->
					<div class="col-xl-4 col-md-6 d-flex">
						<div class="card flex-fill">
							<div class="card-header d-flex align-items-center justify-content-between">
								<a href="#" class="link-primary" data-bs-toggle="modal"
								data-bs-target="#view_details"> P124553</a>
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
													data-bs-target="#edit_parent"><i
														class="ti ti-edit-circle me-2"></i>Edit</a>
											</li>
											<li>
												<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
													data-bs-target="#delete-modal"><i
														class="ti ti-trash-x me-2"></i>Delete</a>
											</li>
										</ul>
									</div>
								</div>
							</div>
							<div class="card-body">
								<div class="bg-light-300 rounded-2 p-3 mb-3">
									<div class="d-flex align-items-center">
										<a href="#" data-bs-toggle="modal"
										data-bs-target="#view_details" class="avatar avatar-lg flex-shrink-0"><img
												src="{{URL::asset('build/img/parents/parent-04.jpg')}}" class="img-fluid rounded-circle"
												alt="img"></a>
										<div class="ms-2">
											<h6 class="text-dark text-truncate mb-0"><a
													href="#" data-bs-toggle="modal"
													data-bs-target="#view_details">Claudia</a></h6>
											<p>Added on 27 Feb 2024</p>
										</div>
									</div>
								</div>
								<div class="d-flex align-items-center justify-content-between gx-2">
									<div>
										<p class="mb-0">Email</p>
										<p class="text-dark">tom@example.com</p>
									</div>
									<div>
										<p class="mb-0">Phone</p>
										<p class="text-dark">+1 65738 58937</p>
									</div>
								</div>
							</div>
							<div class="card-footer d-flex align-items-center justify-content-between">
								<div class="d-flex align-items-center">
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md flex-shrink-0 p-0 me-2">
											<img src="{{URL::asset('build/img/students/student-09.jpg')}}" alt="img"
												class="img-fluid rounded-circle">
										</a>
										<p class="text-dark">Richard</p>
									</div>
								</div>
								<a href="#" class="btn btn-light btn-sm" data-bs-toggle="modal"
									data-bs-target="#view_details">View Details</a>
							</div>
						</div>
					</div>
					<!-- /Parent Grid -->

					<!-- Parent Grid -->
					<div class="col-xl-4 col-md-6 d-flex">
						<div class="card flex-fill">
							<div class="card-header d-flex align-items-center justify-content-between">
								<a href="#" class="link-primary" data-bs-toggle="modal"
								data-bs-target="#view_details"> P124549</a>
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
													data-bs-target="#edit_parent"><i
														class="ti ti-edit-circle me-2"></i>Edit</a>
											</li>
											<li>
												<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
													data-bs-target="#delete-modal"><i
														class="ti ti-trash-x me-2"></i>Delete</a>
											</li>
										</ul>
									</div>
								</div>
							</div>
							<div class="card-body">
								<div class="bg-light-300 rounded-2 p-3 mb-3">
									<div class="d-flex align-items-center">
										<a href="#" data-bs-toggle="modal"
										data-bs-target="#view_details" class="avatar avatar-lg flex-shrink-0"><img
												src="{{URL::asset('build/img/parents/parent-08.jpg')}}" class="img-fluid rounded-circle"
												alt="img"></a>
										<div class="ms-2">
											<h6 class="text-dark text-truncate mb-0"><a
													href="#" data-bs-toggle="modal"
													data-bs-target="#view_details">Jessie</a></h6>
											<p>Added on 08 Jan 2024</p>
										</div>
									</div>
								</div>
								<div class="d-flex align-items-center justify-content-between gx-2">
									<div>
										<p class="mb-0">Email</p>
										<p class="text-dark">tom@example.com</p>
									</div>
									<div>
										<p class="mb-0">Phone</p>
										<p class="text-dark">+1 65738 58937</p>
									</div>
								</div>
							</div>
							<div class="card-footer d-flex align-items-center justify-content-between">
								<div class="d-flex align-items-center">
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md flex-shrink-0 p-0 me-2">
											<img src="{{URL::asset('build/img/students/student-03.jpg')}}" alt="img"
												class="img-fluid rounded-circle">
										</a>
										<p class="text-dark">Kathleen</p>
									</div>
								</div>
								<a href="#" class="btn btn-light btn-sm" data-bs-toggle="modal"
									data-bs-target="#view_details">View Details</a>
							</div>
						</div>
					</div>
					<!-- /Parent Grid -->

					<!-- Parent Grid -->
					<div class="col-xl-4 col-md-6 d-flex">
						<div class="card flex-fill">
							<div class="card-header d-flex align-items-center justify-content-between">
								<a href="#" class="link-primary" data-bs-toggle="modal"
								data-bs-target="#view_details"> P124545</a>
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
													data-bs-target="#edit_parent"><i
														class="ti ti-edit-circle me-2"></i>Edit</a>
											</li>
											<li>
												<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
													data-bs-target="#delete-modal"><i
														class="ti ti-trash-x me-2"></i>Delete</a>
											</li>
										</ul>
									</div>
								</div>
							</div>
							<div class="card-body">
								<div class="bg-light-300 rounded-2 p-3 mb-3">
									<div class="d-flex align-items-center">
										<a href="#" data-bs-toggle="modal"
										data-bs-target="#view_details" class="avatar avatar-lg flex-shrink-0"><img
												src="{{URL::asset('build/img/parents/parent-14.jpg')}}" class="img-fluid rounded-circle"
												alt="img"></a>
										<div class="ms-2">
											<h6  class="text-dark text-truncate mb-0"><a
													href="#" data-bs-toggle="modal"
													data-bs-target="#view_details"> Avila</a></h6>
											<p>Added on 01 Dec 2023</p>
										</div>
									</div>
								</div>
								<div class="d-flex align-items-center justify-content-between gx-2">
									<div>
										<p class="mb-0">Email</p>
										<p class="text-dark">avi@example.com</p>
									</div>
									<div>
										<p class="mb-0">Phone</p>
										<p class="text-dark">+1 65738 58937</p>
									</div>
								</div>
							</div>
							<div class="card-footer d-flex align-items-center justify-content-between">
								<div class="d-flex align-items-center">
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md flex-shrink-0 p-0 me-2">
											<img src="{{URL::asset('build/img/students/student-04.jpg')}}" alt="img"
												class="img-fluid rounded-circle">
										</a>
										<p class="text-dark">Gifford</p>
									</div>
								</div>
								<a href="#" class="btn btn-light btn-sm" data-bs-toggle="modal"
									data-bs-target="#view_details">View Details</a>
							</div>
						</div>
					</div>
					<!-- /Parent Grid -->

					<div class="col-md-12">
						<div class="load-more text-center">
							<a href="#" class="btn btn-primary"><i class="ti ti-loader-3"></i>Load More</a>
						</div>
					</div>

				</div>

			</div>
		</div>
		<!-- /Page Wrapper -->

    @component('components.modal-popup')
    @endcomponent
@endsection
