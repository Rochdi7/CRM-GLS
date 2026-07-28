<?php $page = 'testimonials'; ?>
@extends('layout.mainlayout')
@section('content')
   <!-- Page Wrapper -->
		<div class="page-wrapper">
			<div class="content">
				@component('components.breadcrumb')
				@slot('title')
				Testimonials
				@endslot
				@slot('item1')
				Dashboard
				@endslot
				@slot('item2')
				Content
				@endslot
				@slot('item3')
				Testimonials
				@endslot
			   @endcomponent
				<div class="card">
					<div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
						<h4 class="mb-3">Testimonials List</h4>
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
									<form action="{{url('testimonials')}}">
										<div class="d-flex align-items-center border-bottom p-3">
											<h4>Filter</h4>
										</div>
										<div class="p-3 border-bottom">
											<div class="row">
												<div class="col-md-12">
													<div class="mb-3">
														<label class="form-label">Author</label>
														<select class="select">
															<option>Select</option>
															<option>Thomas</option>
															<option>Teresa</option>
															<option>Veronica</option>
														</select>
													</div>
												</div>
												<div class="col-md-12">
													<div class="mb-0">
														<label class="form-label">Role</label>
														<select class="select">
															<option>Select</option>
															<option>Parent</option>
															<option>Teacher</option>
															<option>Student</option>
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
						<!-- Testimonials List -->
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
										<th>ID</th>
										<th>Author</th>
										<th>Role</th>
										<th>Content</th>
										<th>Date Added</th>
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
										<td><a href="#" class="link-primary">T346285</a></td>
										<td>
											<div class="d-flex align-items-center">
												<a href="#" class="avatar avatar-md"><img src="{{URL::asset('build/img/parents/parent-01.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="#">Thomas</a>
													</p>
												</div>
											</div>
										</td>
										<td>Parent</td>
										<td>"I'm impressed with how easy it is to track my child's progress using this
											system."
										</td>
										<td>25 Apr 2024</td>
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
															<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#edit_testimonials"><i
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
										<td><a href="#" class="link-primary">T346284</a></td>
										<td>
											<div class="d-flex align-items-center">
												<a href="#" class="avatar avatar-md"><img src="{{URL::asset('build/img/teachers/teacher-01.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="#">Teresa</a>
													</p>
												</div>
											</div>
										</td>
										<td>Teacher</td>
										<td>"The gradebook feature has streamlined my grading process”
										</td>
										<td>28 Apr 2024</td>
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
															<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#edit_testimonials"><i
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
										<td><a href="#" class="link-primary">T346283</a></td>
										<td>
											<div class="d-flex align-items-center">
												<a href="#" class="avatar avatar-md"><img src="{{URL::asset('build/img/students/student-11.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="#">Veronica</a>
													</p>
												</div>
											</div>
										</td>
										<td>Student</td>
										<td>"I find the timetable feature very helpful in keeping track of my classes
											and
											assignments."</td>
										<td>02 May 2024</td>
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
															<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#edit_testimonials"><i
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
										<td><a href="#" class="link-primary">T346282</a></td>
										<td>
											<div class="d-flex align-items-center">
												<a href="#" class="avatar avatar-md"><img src="{{URL::asset('build/img/profiles/avatar-27.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="#">Kevin</a>
													</p>
												</div>
											</div>
										</td>
										<td>Admin</td>
										<td>"Our school has seen a significant improvement in efficiency since
											implementing this
											system."</td>
										<td>14 May 2024</td>
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
															<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#edit_testimonials"><i
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
										<td><a href="#" class="link-primary">T346281</a></td>
										<td>
											<div class="d-flex align-items-center">
												<a href="#" class="avatar avatar-md"><img src="{{URL::asset('build/img/parents/parent-04.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="#">Claudia</a>
													</p>
												</div>
											</div>
										</td>
										<td>Parent</td>
										<td>"The communication tools have made it much easier to stay informed about
											school
											events”</td>
										<td>20 May 2024</td>
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
															<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#edit_testimonials"><i
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
										<td><a href="#" class="link-primary">T346280</a></td>
										<td>
											<div class="d-flex align-items-center">
												<a href="#" class="avatar avatar-md"><img src="{{URL::asset('build/img/teachers/teacher-03.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="#">Hellana</a>
													</p>
												</div>
											</div>
										</td>
										<td>Teacher</td>
										<td>"I appreciate the support provided by the technical team in customizing the
											system”
										</td>
										<td>05 Jun 2024</td>
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
															<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#edit_testimonials"><i
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
										<td><a href="#" class="link-primary">T346279</a></td>
										<td>
											<div class="d-flex align-items-center">
												<a href="#" class="avatar avatar-md"><img src="{{URL::asset('build/img/parents/parent-07.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="#">Robert </a>
													</p>
												</div>
											</div>
										</td>
										<td>Parent</td>
										<td>"The online payment system has made fee payments hassle-free. Great addition
											to the
											system!"</td>
										<td>16 Jun 2024</td>
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
															<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#edit_testimonials"><i
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
										<td><a href="#" class="link-primary">T346278</a></td>
										<td>
											<div class="d-flex align-items-center">
												<a href="#" class="avatar avatar-md"><img src="{{URL::asset('build/img/profiles/avatar-28.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="#">Daniel</a>
													</p>
												</div>
											</div>
										</td>
										<td>Admin</td>
										<td>"The system's user interface is intuitive and easy to navigate, even for
											non-technical users."</td>
										<td>21 Jun 2024</td>
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
															<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#edit_testimonials"><i
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
										<td><a href="#" class="link-primary">T346277</a></td>
										<td>
											<div class="d-flex align-items-center">
												<a href="#" class="avatar avatar-md"><img src="{{URL::asset('build/img/parents/parent-09.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="#">Michael </a>
													</p>
												</div>
											</div>
										</td>
										<td>Parent</td>
										<td>"The parent-teacher communication portal has facilitated open dialogue and
											collaboration”</td>
										<td>10 Jul 2024</td>
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
															<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#edit_testimonials"><i
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
										<td><a href="#" class="link-primary">T346276</a></td>
										<td>
											<div class="d-flex align-items-center">
												<a href="#" class="avatar avatar-md"><img src="{{URL::asset('build/img/teachers/teacher-06.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="#">Aaron</a>
													</p>
												</div>
											</div>
										</td>
										<td>Teacher</td>
										<td>"The system's lesson planning tools have helped me organize and deliver more
											effective lessons."</td>
										<td>12 Jul 2024</td>
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
															<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#edit_testimonials"><i
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
						<!-- /Testimonials List -->
					</div>
				</div>
			</div>
		</div>
		<!-- /Page Wrapper -->
    @component('components.modal-popup')
    @endcomponent
@endsection
