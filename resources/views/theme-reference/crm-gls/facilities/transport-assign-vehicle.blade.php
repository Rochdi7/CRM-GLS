<?php $page = 'transport-assign-vehicle'; ?>
@extends('layout.mainlayout')
@section('content')
   	<!-- Page Wrapper -->
		<div class="page-wrapper">
			<div class="content">

				@component('components.breadcrumb')
				@slot('title')
				Assign Vehicle
				@endslot
				@slot('item1')
				Dashboard
				@endslot
				@slot('item2')
				Management
				@endslot
				@slot('item3')
				Assign Vehicle
				@endslot
			   @endcomponent
	

				<!-- Students List -->
				<div class="card">
					<div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
						<h4 class="mb-3">Assign Vehicle List</h4>
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
									<form action="{{url('transport-assign-vehicle')}}">
										<div class="d-flex align-items-center border-bottom p-3">
											<h4>Filter</h4>
										</div>
										<div class="p-3 border-bottom">
											<div class="row">
												<div class="col-md-6">
													<div class="mb-3">
														<label class="form-label">Route</label>
														<select class="select">
															<option>Select</option>
															<option>Seattle</option>
															<option>Camden</option>
															<option>Detroit</option>
															<option>Nashville</option>
															<option>Port Graham</option>
														</select>
													</div>
												</div>
												<div class="col-md-6">
													<div class="mb-3">
														<label class="form-label">Pickup point</label>
														<select class="select">
															<option>Select</option>
															<option>2233 Wood Street, Slidell, LA</option>
															<option>4650 Aviation Way, Los Angeles, CA</option>
															<option>3167 Stadium Drive, Worcester, MA</option>
															
														</select>
													</div>
												</div>
												<div class="col-md-12">
													<div class="mb-3">
														<label class="form-label">Vehicle Number</label>
														<select class="select">
															<option>Select</option>
															<option>8930</option>
															<option>1235</option>
															<option>6465</option>
															<option>7895</option>
														</select>
													</div>
												</div>
												<div class="col-md-6">
													<div class="mb-3">
														<label class="form-label">Driver</label>
														<select class="select">
															<option>Select</option>
															<option>Thomas</option>
															<option>Mary</option>
															<option>Michael</option>
															<option>Jessie</option>
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
												<div class="col-md-12">
													<div class="mb-0">
														<label class="form-label">More Filter</label>
														<select class="select">
															<option>Select</option>
															<option>ID</option>
															<option>Route</option>
															<option>Pickup Point</option>
															<option>Vehicle</option>
															<option>Driver</option>
															<option>Status</option>
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
										<th>Route</th>
										<th>Pickup Point</th>
										<th>Vehicle</th>
										<th>Driver</th>
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
										<td><a href="#" class="link-primary">B80482</a></td>
										<td>
											Seattle
										</td>
										<td>2233 Wood Street, Slidell, LA</td>
										<td>8930</td>
										<td>
											<div class="d-flex align-items-center">
												<a href="#" class="avatar avatar-md"><img
														src="{{URL::asset('build/img/parents/parent-01.jpg')}}"
														class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="#">Thomas</a></p>
													<span class="fs-12">+1 64044 748904</span>
												</div>
											</div>
										</td>
										<td>
											<span class="badge badge-soft-success d-inline-flex align-items-center"><i
													class=" ti ti-circle-filled fs-5 me-1"></i>Active</span>
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
															<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#edit_assign_vehicle"><i
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
										<td><a href="#" class="link-primary">B80481</a></td>
										<td>
											Camden
										</td>
										<td>2693 Parker Drive, Cleveland, OH</td>
										<td>1235</td>
										<td>
											<div class="d-flex align-items-center">
												<a href="#" class="avatar avatar-md"><img
														src="{{URL::asset('build/img/parents/parent-10.jpg')}}"
														class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="#">Mary</a></p>
													<span class="fs-12">+1 14541 55665</span>
												</div>
											</div>
										</td>
										<td>
											<span class="badge badge-soft-success d-inline-flex align-items-center"><i
													class=" ti ti-circle-filled fs-5 me-1"></i>Active</span>
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
															<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#edit_assign_vehicle"><i
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
										<td><a href="#" class="link-primary">B80482</a></td>
										<td>
											Detroit
										</td>
										<td>4650 Aviation Way, Los Angeles, CA</td>
										<td>6465</td>
										<td>
											<div class="d-flex align-items-center">
												<a href="#" class="avatar avatar-md"><img
														src="{{URL::asset('build/img/parents/parent-09.jpg')}}"
														class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="#">Michael</a></p>
													<span class="fs-12">+1 78954 85461</span>
												</div>
											</div>
										</td>
										<td>
											<span class="badge badge-soft-success d-inline-flex align-items-center"><i
													class=" ti ti-circle-filled fs-5 me-1"></i>Active</span>
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
															<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#edit_assign_vehicle"><i
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
										<td><a href="#" class="link-primary">B80481</a></td>
										<td>
											Nashville
										</td>
										<td>3167 Stadium Drive, Worcester, MA</td>
										<td>7895</td>
										<td>
											<div class="d-flex align-items-center">
												<a href="#" class="avatar avatar-md"><img
														src="{{URL::asset('build/img/parents/parent-08.jpg')}}"
														class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="#">Jessie</a></p>
													<span class="fs-12">+1 12345 68891 </span>
												</div>
											</div>
										</td>
										<td>
											<span class="badge badge-soft-success d-inline-flex align-items-center"><i
													class=" ti ti-circle-filled fs-5 me-1"></i>Active</span>
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
															<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#edit_assign_vehicle"><i
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
										<td><a href="#" class="link-primary">B80482</a></td>
										<td>
											Port Graham
										</td>
										<td>1609 Smith Street, Worcester, MA</td>
										<td>4625</td>
										<td>
											<div class="d-flex align-items-center">
												<a href="#" class="avatar avatar-md"><img
														src="{{URL::asset('build/img/parents/parent-07.jpg')}}"
														class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="#">Robert</a></p>
													<span class="fs-12">+1 78454 78841</span>
												</div>
											</div>
										</td>
										<td>
											<span class="badge badge-soft-success d-inline-flex align-items-center"><i
													class=" ti ti-circle-filled fs-5 me-1"></i>Active</span>
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
															<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#edit_assign_vehicle"><i
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
										<td><a href="#" class="link-primary">B80481</a></td>
										<td>
											Brooklyn North
										</td>
										<td>3341 Palmer Road, Columbus, OH</td>
										<td>7854</td>
										<td>
											<div class="d-flex align-items-center">
												<a href="#" class="avatar avatar-md"><img
														src="{{URL::asset('build/img/parents/parent-06.jpg')}}"
														class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="#">Colleen</a></p>
													<span class="fs-12">+1 78546 97894</span>
												</div>
											</div>
										</td>
										<td>
											<span class="badge badge-soft-success d-inline-flex align-items-center"><i
													class=" ti ti-circle-filled fs-5 me-1"></i>Active</span>
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
															<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#edit_assign_vehicle"><i
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
										<td><a href="#" class="link-primary">B80482</a></td>
										<td>
											Kansas City
										</td>
										<td>2261 Sweetwood Drive, Denver, CO</td>
										<td>9789</td>
										<td>
											<div class="d-flex align-items-center">
												<a href="#" class="avatar avatar-md"><img
														src="{{URL::asset('build/img/parents/parent-05.jpg')}}"
														class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="#">Arthur</a></p>
													<span class="fs-12">+1 97878 87854</span>
												</div>
											</div>
										</td>
										<td>
											<span class="badge badge-soft-success d-inline-flex align-items-center"><i
													class=" ti ti-circle-filled fs-5 me-1"></i>Active</span>
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
															<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#edit_assign_vehicle"><i
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
										<td><a href="#" class="link-primary">B80481</a></td>
										<td>
											Rochester
										</td>
										<td>4025 Khale Street, Folly Beach, SC</td>
										<td>4569</td>
										<td>
											<div class="d-flex align-items-center">
												<a href="#" class="avatar avatar-md"><img
														src="{{URL::asset('build/img/parents/parent-06.jpg')}}"
														class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="#">Claudia</a></p>
													<span class="fs-12">+1 64599 78542</span>
												</div>
											</div>
										</td>
										<td>
											<span class="badge badge-soft-danger d-inline-flex align-items-center"><i
													class=" ti ti-circle-filled fs-5 me-1"></i>Inactive</span>
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
															<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#edit_assign_vehicle"><i
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
										<td><a href="#" class="link-primary">B80482</a></td>
										<td>
											Brooklyn Central
										</td>
										<td>3521 Harvest Lane Kansas City, MO</td>
										<td>7857</td>
										<td>
											<div class="d-flex align-items-center">
												<a href="#" class="avatar avatar-md"><img
														src="{{URL::asset('build/img/parents/parent-03.jpg')}}"
														class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="#">Johnson</a></p>
													<span class="fs-12">+1 45781 45145</span>
												</div>
											</div>
										</td>
										<td>
											<span class="badge badge-soft-success d-inline-flex align-items-center"><i
													class=" ti ti-circle-filled fs-5 me-1"></i>Active</span>
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
															<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#edit_assign_vehicle"><i
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
										<td><a href="#" class="link-primary">B80481</a></td>
										<td>
											Seattle
										</td>
										<td>2603 Wood Duck Drive Marquette, MI</td>
										<td>6879</td>
										<td>
											<div class="d-flex align-items-center">
												<a href="#" class="avatar avatar-md"><img
														src="{{URL::asset('build/img/parents/parent-02.jpg')}}"
														class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="#">Marquita</a></p>
													<span class="fs-12">+1 45112 48879</span>
												</div>
											</div>
										</td>
										<td>
											<span class="badge badge-soft-success d-inline-flex align-items-center"><i
													class=" ti ti-circle-filled fs-5 me-1"></i>Active</span>
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
															<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#edit_assign_vehicle"><i
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
