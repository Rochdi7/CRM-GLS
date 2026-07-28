<?php $page = 'library-members'; ?>
@extends('layout.mainlayout')
@section('content')
   <!-- Page Wrapper -->
		<div class="page-wrapper">
			<div class="content">
				@component('components.breadcrumb')
				@slot('title')
				Library Members
				@endslot
				@slot('item1')
				Dashboard
				@endslot
				@slot('item2')
				Management
                @endslot
				@slot('item3')
				Library Members
				@endslot
			   @endcomponent
				
				
				<div class="card">
					<div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
						<h4 class="mb-3">
							Library Members List</h4>
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
									<form action="{{url('library-members')}}">
										<div class="d-flex align-items-center border-bottom p-3">
											<h4>Filter</h4>
										</div>
										<div class="p-3 border-bottom">
											<div class="row">
												<div class="col-md-6">
													<div class="mb-3">
														<label class="form-label">Member</label>
														<select class="select">
															<option>Select</option>
															<option>James</option>
															<option>Garcia</option>
															<option>Frank</option>
															<option>Kevin</option>
															<option>Jennie</option>
														</select>
													</div>
												</div>
												<div class="col-md-6">
													<div class="mb-3">
														<label class="form-label">Card No</label>
														<select class="select">
															<option>Select</option>
															<option>501</option>
															<option>502</option>
															<option>503</option>
															<option>504</option>
														</select>
													</div>
												</div>
												<div class="col-md-12">
													<div class="mb-0">
														<label class="form-label">More Filter</label>
														<select class="select">
															<option>Filters</option>
															<option>Member</option>
															<option>Card No</option>
															<option>Email</option>
															<option>Date of Join</option>
															<option>Mobile</option>
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

						<!-- Library Members List -->
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
								<th>Member</th>
								<th>Card No</th>
								<th>Email</th>
								<th>Date of Join</th>
								<th>Mobile</th>
								<th>Action</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td>
											<div class="form-check form-check-md">
												<input class="form-check-input" type="checkbox" >
											</div>
										</td>
										<td><a href="#" class="link-primary">LM823748</a></td>
										<td>
											<div class="d-flex align-items-center">
												<a href="#" class="avatar avatar-md"><img
														src="{{URL::asset('build/img/members/members-01.jpg')}}"
														class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="#">James</a>
													</p>
												</div>
											</div>
										</td>
										<td>501</td>
										<td>"james@example.com
										</td>
										<td>22 Apr 2024</td>
										<td>+1 78429 82414</td>
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
																data-bs-toggle="modal"
																data-bs-target="#edit_library_members"><i
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
												<input class="form-check-input" type="checkbox" >
											</div>
										</td>
										<td><a href="#" class="link-primary">LM823747</a></td>
										<td>
											<div class="d-flex align-items-center">
												<a href="#" class="avatar avatar-md"><img
														src="{{URL::asset('build/img/members/members-02.jpg')}}"
														class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="#">Garcia</a>
													</p>
												</div>
											</div>
										</td>
										<td>502</td>
										<td>garcia@example.com
										</td>
										<td>30 Apr 2024</td>
										<td>+1 37489 46485</td>
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
																data-bs-toggle="modal"
																data-bs-target="#edit_library_members"><i
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
												<input class="form-check-input" type="checkbox" >
											</div>
										</td>
										<td><a href="#" class="link-primary">LM823746</a></td>
										<td>
											<div class="d-flex align-items-center">
												<a href="#" class="avatar avatar-md"><img
														src="{{URL::asset('build/img/members/members-03.jpg')}}"
														class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="#">Frank</a>
													</p>
												</div>
											</div>
										</td>
										<td>503</td>
										<td>frank@example.com</td>
										<td>05 May 2024</td>
										<td>+1 87651 64816</td>
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
																data-bs-toggle="modal"
																data-bs-target="#edit_library_members"><i
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
												<input class="form-check-input" type="checkbox" >
											</div>
										</td>
										<td><a href="#" class="link-primary">LM823745</a></td>
										<td>
											<div class="d-flex align-items-center">
												<a href="#" class="avatar avatar-md"><img
														src="{{URL::asset('build/img/members/members-04.jpg')}}"
														class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="#">Jennie</a>
													</p>
												</div>
											</div>
										</td>
										<td>504</td>
										<td>jennie@example.com</td>
										<td>16 May 2024</td>
										<td>+1 49879 86498</td>
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
																data-bs-toggle="modal"
																data-bs-target="#edit_library_members"><i
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
												<input class="form-check-input" type="checkbox" >
											</div>
										</td>
										<td><a href="#" class="link-primary">LM823744</a></td>
										<td>
											<div class="d-flex align-items-center">
												<a href="#" class="avatar avatar-md"><img
														src="{{URL::asset('build/img/members/members-05.jpg')}}"
														class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="#">Paul</a>
													</p>
												</div>
											</div>
										</td>
										<td>505</td>
										<td>paul@example.com</td>
										<td>28 May 2024</td>
										<td>+1 69787 87984</td>
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
																data-bs-toggle="modal"
																data-bs-target="#edit_library_members"><i
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
												<input class="form-check-input" type="checkbox" >
											</div>
										</td>
										<td><a href="#" class="link-primary">LM823743</a></td>
										<td>
											<div class="d-flex align-items-center">
												<a href="#" class="avatar avatar-md"><img
														src="{{URL::asset('build/img/members/members-06.jpg')}}"
														class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="#">Elaine</a>
													</p>
												</div>
											</div>
										</td>
										<td>506</td>
										<td>elaine@example.com
										</td>
										<td>06 Jun 2024</td>
										<td>+1 98764 84984</td>
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
																data-bs-toggle="modal"
																data-bs-target="#edit_library_members"><i
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
												<input class="form-check-input" type="checkbox" >
											</div>
										</td>
										<td><a href="#" class="link-primary">LM823742</a></td>
										<td>
											<div class="d-flex align-items-center">
												<a href="#" class="avatar avatar-md"><img
														src="{{URL::asset('build/img/members/members-07.jpg')}}"
														class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="#">Jackson</a>
													</p>
												</div>
											</div>
										</td>
										<td>507</td>
										<td>jackson@example.com</td>
										<td>10 Jun 2024</td>
										<td>+1 46876 55498</td>
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
																data-bs-toggle="modal"
																data-bs-target="#edit_library_members"><i
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
												<input class="form-check-input" type="checkbox" >
											</div>
										</td>
										<td><a href="#" class="link-primary">LM823741</a></td>
										<td>
											<div class="d-flex align-items-center">
												<a href="#" class="avatar avatar-md"><img
														src="{{URL::asset('build/img/members/members-08.jpg')}}"
														class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="#">Kerry</a>
													</p>
												</div>
											</div>
										</td>
										<td>508</td>
										<td>kerry@example.com</td>
										<td>18 Jun 2024</td>
										<td>+1 79468 87467</td>
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
																data-bs-toggle="modal"
																data-bs-target="#edit_library_members"><i
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
												<input class="form-check-input" type="checkbox" >
											</div>
										</td>
										<td><a href="#" class="link-primary">LM823740</a></td>
										<td>
											<div class="d-flex align-items-center">
												<a href="#" class="avatar avatar-md"><img
														src="{{URL::asset('build/img/members/members-09.jpg')}}"
														class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="#">Roger</a>
													</p>
												</div>
											</div>
										</td>
										<td>509</td>
										<td>roger@example.com</td>
										<td>20 Jul 2024</td>
										<td>+1 65598 64658</td>
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
																data-bs-toggle="modal"
																data-bs-target="#edit_library_members"><i
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
												<input class="form-check-input" type="checkbox" >
											</div>
										</td>
										<td><a href="#" class="link-primary">LM823739</a></td>
										<td>
											<div class="d-flex align-items-center">
												<a href="#" class="avatar avatar-md"><img
														src="{{URL::asset('build/img/members/members-10.jpg')}}"
														class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="#">Denise</a>
													</p>
												</div>
											</div>
										</td>
										<td>510</td>
										<td>denise@example.com</td>
										<td>26 Jul 2024</td>
										<td>+1 57866 68746</td>
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
																data-bs-toggle="modal"
																data-bs-target="#edit_library_members"><i
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
						<!-- /Library Members List -->
					</div>
				</div>
			
			</div>
		</div>
		<!-- /Page Wrapper -->

    @component('components.modal-popup')        
    @endcomponent
@endsection
