<?php $page = 'teacher-grid'; ?>
@extends('layout.mainlayout')
@section('content')
   <!-- Page Wrapper -->
		<div class="page-wrapper">
			<div class="content content-two">
				@component('components.breadcrumb')
				@slot('title')
				Teachers 
				@endslot
				@slot('item1')
				Dashboard
				@endslot
				@slot('item2')
				Peoples
				@endslot
				@slot('item3')
				Teachers 
				@endslot
			   @endcomponent
				

				<div
					class="bg-white p-3 border rounded-1 d-flex align-items-center justify-content-between flex-wrap mb-4 pb-0">
					<h4 class="mb-3">Teachers Grid</h4>
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
								<form action="{{url('guardians')}}">
									<div class="d-flex align-items-center border-bottom p-3">
										<h4>Filter</h4>
									</div>
									<div class="p-3 pb-0 border-bottom">
										<div class="row">
											<div class="col-md-6">
												<div class="mb-3">
													<label class="form-label">Name</label>
													<select class="select">
														<option>Select</option>
														<option>William</option>
														<option>Stacey</option>
														<option>George</option>
													</select>
												</div>
											</div>
											<div class="col-md-6">
												<div class="mb-3">
													<label class="form-label">Class</label>
													<select class="select">
														<option>Select</option>
														<option>III A</option>
														<option>II (A)</option>
														<option>VI (A)</option>
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
							<a href="{{url('teachers')}}" class=" btn btn-icon btn-sm me-1 bg-light primary-hover"><i
								class="ti ti-list-tree"></i></a>
							<a href="{{url('teacher-grid')}}" class=" active btn btn-icon btn-sm  primary-hover"><i
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
								<li><a href="javascript:void(0);" class="dropdown-item rounded-1">
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

					<!-- Teacher Grid -->
					<div class="col-xxl-3 col-xl-4 col-md-6 d-flex">
						<div class="card flex-fill">
							<div class="card-header d-flex align-items-center justify-content-between">
								<a href="#" class="link-primary" data-bs-toggle="modal"
								data-bs-target="#view_details">T849127</a>
								<div class="d-flex align-items-center">
									<span class="badge badge-soft-success d-inline-flex align-items-center me-1"><i class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
									<div class="dropdown">
										<a href="#"
											class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
											data-bs-toggle="dropdown" aria-expanded="false">
											<i class="ti ti-dots-vertical fs-14"></i>
										</a>
										<ul class="dropdown-menu dropdown-menu-right p-3">
											<li>
												<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
													data-bs-target="#edit_teacher"><i
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
										<a href="{{url('teacher-details')}}" class="avatar avatar-lg flex-shrink-0"><img
												src="{{URL::asset('build/img/teachers/teacher-01.jpg')}}" class="img-fluid rounded-circle"
												alt="img"></a>
										<div class="ms-2">
											<h6 class="text-dark text-truncate mb-0"><a
													href="{{url('teacher-details')}}">Teresa</a></h6>
											<p>III A</p>
										</div>
									</div>
								</div>
								<div>
									<div class="mb-2">
										<p class="mb-0">Email</p>
										<p class="text-dark">teresa@example.com</p>
									</div>
									<div>
										<p class="mb-0">Phone</p>
										<p class="text-dark">+1 82392 37359</p>
									</div>
								</div>
							</div>
							<div class="card-footer d-flex align-items-center justify-content-between">
                                <span class="badge badge-soft-danger">Physics</span>
                                <a href="{{url('teacher-details')}}" class="btn btn-light btn-sm" >View Details</a>
                            </div>
						</div>
					</div>
					<!-- /Teacher Grid -->

					<!-- Teacher Grid -->
					<div class="col-xxl-3 col-xl-4 col-md-6 d-flex">
						<div class="card flex-fill">
							<div class="card-header d-flex align-items-center justify-content-between">
								<a href="#" class="link-primary" data-bs-toggle="modal"
								data-bs-target="#view_details">T849126</a>
								<div class="d-flex align-items-center">
									<span class="badge badge-soft-success d-inline-flex align-items-center me-1"><i class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
									<div class="dropdown">
										<a href="#"
											class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
											data-bs-toggle="dropdown" aria-expanded="false">
											<i class="ti ti-dots-vertical fs-14"></i>
										</a>
										<ul class="dropdown-menu dropdown-menu-right p-3">
											<li>
												<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
													data-bs-target="#edit_teacher"><i
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
										<a href="{{url('teacher-details')}}" class="avatar avatar-lg flex-shrink-0"><img
												src="{{URL::asset('build/img/teachers/teacher-02.jpg')}}" class="img-fluid rounded-circle"
												alt="img"></a>
										<div class="ms-2">
											<h6  class="text-dark text-truncate mb-0"><a
													href="{{url('teacher-details')}}">Daniel</a></h6>
											<p>II (A)</p>
										</div>
									</div>
								</div>
								<div>
									<div class="mb-2">
										<p class="mb-0">Email</p>
										<p class="text-dark">Daniel@example.com</p>
									</div>
									<div>
										<p class="mb-0">Phone</p>
										<p class="text-dark">+1 56752 86742</p>
									</div>
								</div>
							</div>
							<div class="card-footer d-flex align-items-center justify-content-between">
                                <span class="badge badge-soft-danger">Computer</span>
                                <a href="{{url('teacher-details')}}" class="btn btn-light btn-sm" >View Details</a>
                            </div>
						</div>
					</div>
					<!-- /Teacher Grid -->

					<!-- Teacher Grid -->
					<div class="col-xxl-3 col-xl-4 col-md-6 d-flex">
						<div class="card flex-fill">
							<div class="card-header d-flex align-items-center justify-content-between">
								<a href="#" class="link-primary" data-bs-toggle="modal"
								data-bs-target="#view_details">T849125</a>
								<div class="d-flex align-items-center">
									<span class="badge badge-soft-success d-inline-flex align-items-center me-1"><i class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
									<div class="dropdown">
										<a href="#"
											class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
											data-bs-toggle="dropdown" aria-expanded="false">
											<i class="ti ti-dots-vertical fs-14"></i>
										</a>
										<ul class="dropdown-menu dropdown-menu-right p-3">
											<li>
												<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
													data-bs-target="#edit_teacher"><i
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
										<a href="{{url('teacher-details')}}" class="avatar avatar-lg flex-shrink-0"><img
												src="{{URL::asset('build/img/teachers/teacher-03.jpg')}}" class="img-fluid rounded-circle"
												alt="img"></a>
										<div class="ms-2">
											<h6  class="text-dark text-truncate mb-0"><a
													href="{{url('teacher-details')}}">Hellana</a></h6>
											<p>VI (A)</p>
										</div>
									</div>
								</div>
								<div>
									<div class="mb-2">
										<p class="mb-0">Email</p>
										<p class="text-dark">Hellana@example.com</p>
									</div>
									<div>
										<p class="mb-0">Phone</p>
										<p class="text-dark">+1 23566 52683</p>
									</div>
								</div>
							</div>
							<div class="card-footer d-flex align-items-center justify-content-between">
                                <span class="badge badge-soft-danger">English</span>
                                <a href="{{url('teacher-details')}}" class="btn btn-light btn-sm" >View Details</a>
                            </div>
						</div>
					</div>
					<!-- /Teacher Grid -->

					<!-- Teacher Grid -->
					<div class="col-xxl-3 col-xl-4 col-md-6 d-flex">
						<div class="card flex-fill">
							<div class="card-header d-flex align-items-center justify-content-between">
								<a href="#" class="link-primary" data-bs-toggle="modal"
								data-bs-target="#view_details">T849124</a>
								<div class="d-flex align-items-center">
									<span class="badge badge-soft-success d-inline-flex align-items-center me-1"><i class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
									<div class="dropdown">
										<a href="#"
											class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
											data-bs-toggle="dropdown" aria-expanded="false">
											<i class="ti ti-dots-vertical fs-14"></i>
										</a>
										<ul class="dropdown-menu dropdown-menu-right p-3">
											<li>
												<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
													data-bs-target="#edit_teacher"><i
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
										<a href="{{url('teacher-details')}}" class="avatar avatar-lg flex-shrink-0"><img
												src="{{URL::asset('build/img/teachers/teacher-04.jpg')}}" class="img-fluid rounded-circle"
												alt="img"></a>
										<div class="ms-2">
											<h6  class="text-dark text-truncate mb-0"><a
													href="{{url('teacher-details')}}">Erickson</a></h6>
											<p>VI (B) , V (A)</p>
										</div>
									</div>
								</div>
								<div>
									<div class="mb-2">
										<p class="mb-0">Email</p>
										<p class="text-dark">Erickson@example.com</p>
									</div>
									<div>
										<p class="mb-0">Phone</p>
										<p class="text-dark">+1 14259 85573</p>
									</div>
								</div>
							</div>
							<div class="card-footer d-flex align-items-center justify-content-between">
                                <span class="badge badge-soft-danger">Spanish</span>
                                <a href="{{url('teacher-details')}}" class="btn btn-light btn-sm" >View Details</a>
                            </div>
						</div>
					</div>
					<!-- /Teacher Grid -->

					<!-- Teacher Grid -->
					<div class="col-xxl-3 col-xl-4 col-md-6 d-flex">
						<div class="card flex-fill">
							<div class="card-header d-flex align-items-center justify-content-between">
								<a href="#" class="link-primary" data-bs-toggle="modal"
								data-bs-target="#view_details">T849123</a>
								<div class="d-flex align-items-center">
									<span class="badge badge-soft-success d-inline-flex align-items-center me-1"><i class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
									<div class="dropdown">
										<a href="#"
											class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
											data-bs-toggle="dropdown" aria-expanded="false">
											<i class="ti ti-dots-vertical fs-14"></i>
										</a>
										<ul class="dropdown-menu dropdown-menu-right p-3">
											<li>
												<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
													data-bs-target="#edit_teacher"><i
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
										<a href="{{url('teacher-details')}}" class="avatar avatar-lg flex-shrink-0"><img
												src="{{URL::asset('build/img/teachers/teacher-05.jpg')}}" class="img-fluid rounded-circle"
												alt="img"></a>
										<div class="ms-2">
											<h6  class="text-dark text-truncate mb-0"><a
													href="{{url('teacher-details')}}">Morgan</a></h6>
											<p>VIII</p>
										</div>
									</div>
								</div>
								<div>
									<div class="mb-2">
										<p class="mb-0">Email</p>
										<p class="text-dark">Morgan@example.com</p>
									</div>
									<div>
										<p class="mb-0">Phone</p>
										<p class="text-dark">+1 63204 35730</p>
									</div>
								</div>
							</div>
							<div class="card-footer d-flex align-items-center justify-content-between">
                                <span class="badge badge-soft-danger">Env Science</span>
                                <a href="{{url('teacher-details')}}" class="btn btn-light btn-sm" >View Details</a>
                            </div>
						</div>
					</div>
					<!-- /Teacher Grid -->

					<!-- Teacher Grid -->
					<div class="col-xxl-3 col-xl-4 col-md-6 d-flex">
						<div class="card flex-fill">
							<div class="card-header d-flex align-items-center justify-content-between">
								<a href="#" class="link-primary" data-bs-toggle="modal"
								data-bs-target="#view_details">T849122</a>
								<div class="d-flex align-items-center">
									<span class="badge badge-soft-danger d-inline-flex align-items-center me-1"><i class="ti ti-circle-filled fs-5 me-1"></i>Inactive</span>
									<div class="dropdown">
										<a href="#"
											class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
											data-bs-toggle="dropdown" aria-expanded="false">
											<i class="ti ti-dots-vertical fs-14"></i>
										</a>
										<ul class="dropdown-menu dropdown-menu-right p-3">
											<li>
												<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
													data-bs-target="#edit_teacher"><i
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
										<a href="{{url('teacher-details')}}" class="avatar avatar-lg flex-shrink-0"><img
												src="{{URL::asset('build/img/teachers/teacher-06.jpg')}}" class="img-fluid rounded-circle"
												alt="img"></a>
										<div class="ms-2">
											<h6  class="text-dark text-truncate mb-0"><a
													href="{{url('teacher-details')}}">Aaron</a></h6>
											<p>I (A)</p>
										</div>
									</div>
								</div>
								<div>
									<div class="mb-2">
										<p class="mb-0">Email</p>
										<p class="text-dark">Aaron@example.com</p>
									</div>
									<div>
										<p class="mb-0">Phone</p>
										<p class="text-dark">+1 26267 80542</p>
									</div>
								</div>
							</div>
							<div class="card-footer d-flex align-items-center justify-content-between">
                                <span class="badge badge-soft-danger">Chemistry</span>
                                <a href="{{url('teacher-details')}}" class="btn btn-light btn-sm" >View Details</a>
                            </div>
						</div>
					</div>
					<!-- /Teacher Grid -->

					<!-- Teacher Grid -->
					<div class="col-xxl-3 col-xl-4 col-md-6 d-flex">
						<div class="card flex-fill">
							<div class="card-header d-flex align-items-center justify-content-between">
								<a href="#" class="link-primary" data-bs-toggle="modal"
								data-bs-target="#view_details">T849121</a>
								<div class="d-flex align-items-center">
									<span class="badge badge-soft-success d-inline-flex align-items-center me-1"><i class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
									<div class="dropdown">
										<a href="#"
											class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
											data-bs-toggle="dropdown" aria-expanded="false">
											<i class="ti ti-dots-vertical fs-14"></i>
										</a>
										<ul class="dropdown-menu dropdown-menu-right p-3">
											<li>
												<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
													data-bs-target="#edit_teacher"><i
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
										<a href="{{url('teacher-details')}}" class="avatar avatar-lg flex-shrink-0"><img
												src="{{URL::asset('build/img/teachers/teacher-07.jpg')}}" class="img-fluid rounded-circle"
												alt="img"></a>
										<div class="ms-2">
											<h6  class="text-dark text-truncate mb-0"><a
													href="{{url('teacher-details')}}">Jacquelin</a></h6>
											<p>IV</p>
										</div>
									</div>
								</div>
								<div>
									<div class="mb-2">
										<p class="mb-0">Email</p>
										<p class="text-dark">Jacquelin@example.com</p>
									</div>
									<div>
										<p class="mb-0">Phone</p>
										<p class="text-dark">+1 77502 54845</p>
									</div>
								</div>
							</div>
							<div class="card-footer d-flex align-items-center justify-content-between">
                                <span class="badge badge-soft-danger">Maths</span>
                                <a href="{{url('teacher-details')}}" class="btn btn-light btn-sm" >View Details</a>
                            </div>
						</div>
					</div>
					<!-- /Teacher Grid -->

					<!-- Teacher Grid -->
					<div class="col-xxl-3 col-xl-4 col-md-6 d-flex">
						<div class="card flex-fill">
							<div class="card-header d-flex align-items-center justify-content-between">
								<a href="#" class="link-primary" data-bs-toggle="modal"
								data-bs-target="#view_details">T849120</a>
								<div class="d-flex align-items-center">
									<span class="badge badge-soft-success d-inline-flex align-items-center me-1"><i class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
									<div class="dropdown">
										<a href="#"
											class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
											data-bs-toggle="dropdown" aria-expanded="false">
											<i class="ti ti-dots-vertical fs-14"></i>
										</a>
										<ul class="dropdown-menu dropdown-menu-right p-3">
											<li>
												<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
													data-bs-target="#edit_teacher"><i
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
										<a href="{{url('teacher-details')}}" class="avatar avatar-lg flex-shrink-0"><img
												src="{{URL::asset('build/img/teachers/teacher-08.jpg')}}" class="img-fluid rounded-circle"
												alt="img"></a>
										<div class="ms-2">
											<h6  class="text-dark text-truncate mb-0"><a
													href="{{url('teacher-details')}}">Raul</a></h6>
											<p>IV</p>
										</div>
									</div>
								</div>
								<div>
									<div class="mb-2">
										<p class="mb-0">Email</p>
										<p class="text-dark">Raul@example.com</p>
									</div>
									<div>
										<p class="mb-0">Phone</p>
										<p class="text-dark">+1 67845 78784</p>
									</div>
								</div>
							</div>
							<div class="card-footer d-flex align-items-center justify-content-between">
                                <span class="badge badge-soft-danger">Biology</span>
                                <a href="{{url('teacher-details')}}" class="btn btn-light btn-sm" >View Details</a>
                            </div>
						</div>
					</div>
					<!-- /Teacher Grid -->

					<!-- Teacher Grid -->
					<div class="col-xxl-3 col-xl-4 col-md-6 d-flex">
						<div class="card flex-fill">
							<div class="card-header d-flex align-items-center justify-content-between">
								<a href="#" class="link-primary" data-bs-toggle="modal"
								data-bs-target="#view_details">T849119</a>
								<div class="d-flex align-items-center">
									<span class="badge badge-soft-success d-inline-flex align-items-center me-1"><i class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
									<div class="dropdown">
										<a href="#"
											class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
											data-bs-toggle="dropdown" aria-expanded="false">
											<i class="ti ti-dots-vertical fs-14"></i>
										</a>
										<ul class="dropdown-menu dropdown-menu-right p-3">
											<li>
												<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
													data-bs-target="#edit_teacher"><i
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
										<a href="{{url('teacher-details')}}" class="avatar avatar-lg flex-shrink-0"><img
												src="{{URL::asset('build/img/teachers/teacher-09.jpg')}}" class="img-fluid rounded-circle"
												alt="img"></a>
										<div class="ms-2">
											<h6  class="text-dark text-truncate mb-0"><a
													href="{{url('teacher-details')}}">Elizabeth</a></h6>
											<p>VII</p>
										</div>
									</div>
								</div>
								<div>
									<div class="mb-2">
										<p class="mb-0">Email</p>
										<p class="text-dark">Elizabeth@example.com</p>
									</div>
									<div>
										<p class="mb-0">Phone</p>
										<p class="text-dark">+1 23566 52683</p>
									</div>
								</div>
							</div>
							<div class="card-footer d-flex align-items-center justify-content-between">
                                <span class="badge badge-soft-danger">Finance</span>
                                <a href="{{url('teacher-details')}}" class="btn btn-light btn-sm">View Details</a>
                            </div>
						</div>
					</div>
					<!-- /Teacher Grid -->

					<!-- Teacher Grid -->
					<div class="col-xxl-3 col-xl-4 col-md-6 d-flex">
						<div class="card flex-fill">
							<div class="card-header d-flex align-items-center justify-content-between">
								<a href="#" class="link-primary" data-bs-toggle="modal"
								data-bs-target="#view_details">T849118</a>
								<div class="d-flex align-items-center">
									<span class="badge badge-soft-success d-inline-flex align-items-center me-1"><i class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
									<div class="dropdown">
										<a href="#"
											class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
											data-bs-toggle="dropdown" aria-expanded="false">
											<i class="ti ti-dots-vertical fs-14"></i>
										</a>
										<ul class="dropdown-menu dropdown-menu-right p-3">
											<li>
												<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
													data-bs-target="#edit_teacher"><i
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
										<a href="{{url('teacher-details')}}" class="avatar avatar-lg flex-shrink-0"><img
												src="{{URL::asset('build/img/teachers/teacher-10.jpg')}}" class="img-fluid rounded-circle"
												alt="img"></a>
										<div class="ms-2">
											<h6  class="text-dark text-truncate mb-0"><a
													href="{{url('teacher-details')}}">Edward</a></h6>
											<p>IX (C) , X (C)</p>
										</div>
									</div>
								</div>
								<div>
									<div class="mb-2">
										<p class="mb-0">Email</p>
										<p class="text-dark">Edward@example.com</p>
									</div>
									<div>
										<p class="mb-0">Phone</p>
										<p class="text-dark">+1 14259 85573 </p>
									</div>
								</div>
							</div>
							<div class="card-footer d-flex align-items-center justify-content-between">
                                <span class="badge badge-soft-danger">Economics</span>
                                <a href="{{url('teacher-details')}}" class="btn btn-light btn-sm" >View Details</a>
                            </div>
						</div>
					</div>
					<!-- /Teacher Grid -->

					<!-- Teacher Grid -->
					<div class="col-xxl-3 col-xl-4 col-md-6 d-flex">
						<div class="card flex-fill">
							<div class="card-header d-flex align-items-center justify-content-between">
								<a href="#" class="link-primary" data-bs-toggle="modal"
								data-bs-target="#view_details">T849117</a>
								<div class="d-flex align-items-center">
									<span class="badge badge-soft-success d-inline-flex align-items-center me-1"><i class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
									<div class="dropdown">
										<a href="#"
											class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
											data-bs-toggle="dropdown" aria-expanded="false">
											<i class="ti ti-dots-vertical fs-14"></i>
										</a>
										<ul class="dropdown-menu dropdown-menu-right p-3">
											<li>
												<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
													data-bs-target="#edit_teacher"><i
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
										<a href="{{url('teacher-details')}}" class="avatar avatar-lg flex-shrink-0"><img
												src="{{URL::asset('build/img/teachers/teacher-07.jpg')}}" class="img-fluid rounded-circle"
												alt="img"></a>
										<div class="ms-2">
											<h6  class="text-dark text-truncate mb-0"><a
													href="{{url('teacher-details')}}">Maria</a></h6>
											<p>IX (C) , X (C)</p>
										</div>
									</div>
								</div>
								<div>
									<div class="mb-2">
										<p class="mb-0">Email</p>
										<p class="text-dark">Maria@example.com</p>
									</div>
									<div>
										<p class="mb-0">Phone</p>
										<p class="text-dark">+1 97846 84518</p>
									</div>
								</div>
							</div>
							<div class="card-footer d-flex align-items-center justify-content-between">
                                <span class="badge badge-soft-danger">Spanish</span>
                                <a href="{{url('teacher-details')}}" class="btn btn-light btn-sm" >View Details</a>
                            </div>
						</div>
					</div>
					<!-- /Teacher Grid -->

					<!-- Teacher Grid -->
					<div class="col-xxl-3 col-xl-4 col-md-6 d-flex">
						<div class="card flex-fill">
							<div class="card-header d-flex align-items-center justify-content-between">
								<a href="#" class="link-primary" data-bs-toggle="modal"
								data-bs-target="#view_details">T849116</a>
								<div class="d-flex align-items-center">
									<span class="badge badge-soft-success d-inline-flex align-items-center me-1"><i class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
									<div class="dropdown">
										<a href="#"
											class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
											data-bs-toggle="dropdown" aria-expanded="false">
											<i class="ti ti-dots-vertical fs-14"></i>
										</a>
										<ul class="dropdown-menu dropdown-menu-right p-3">
											<li>
												<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
													data-bs-target="#edit_teacher"><i
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
										<a href="{{url('teacher-details')}}" class="avatar avatar-lg flex-shrink-0"><img
												src="{{URL::asset('build/img/teachers/teacher-07.jpg')}}" class="img-fluid rounded-circle"
												alt="img"></a>
										<div class="ms-2">
											<h6  class="text-dark text-truncate mb-0"><a
													href="{{url('teacher-details')}}">Jacky</a></h6>
											<p>VI (A)</p>
										</div>
									</div>
								</div>
								<div>
									<div class="mb-2">
										<p class="mb-0">Email</p>
										<p class="text-dark">Jacky@example.com</p>
									</div>
									<div>
										<p class="mb-0">Phone</p>
										<p class="text-dark">+1 98392 37378</p>
									</div>
								</div>
							</div>
							<div class="card-footer d-flex align-items-center justify-content-between">
                                <span class="badge badge-soft-danger">English</span>
                                <a href="{{url('teacher-details')}}" class="btn btn-light btn-sm" >View Details</a>
                            </div>
						</div>
					</div>
					<!-- /Teacher Grid -->

					<div class="load-more text-center">
						<a href="#" class="btn btn-primary"><i class="ti ti-loader-3"></i>Load More</a>
					</div>

				</div>

			</div>
		</div>
		<!-- /Page Wrapper -->
        @component('components.modal-popup')
        @endcomponent
@endsection
