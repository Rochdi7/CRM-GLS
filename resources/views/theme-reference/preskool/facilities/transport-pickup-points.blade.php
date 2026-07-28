<?php $page = 'transport-pickup-points'; ?>
@extends('layout.mainlayout')
@section('content')
  		<!-- Page Wrapper -->
		<div class="page-wrapper">
			<div class="content">

			@component('components.breadcrumb')
            @slot('title')
			Pickup Points
            @endslot
            @slot('item1')
            Dashboard
            @endslot
            @slot('item2')
            Management
            @endslot
            @slot('item3')
            Pickup Points
            @endslot
           @endcomponent

				<!-- Students List -->
				<div class="card">
					<div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
						<h4 class="mb-3">Pickup Points List</h4>
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
									<form action="{{url('transport-pickup-points')}}">
										<div class="d-flex align-items-center border-bottom p-3">
											<h4>Filter</h4>
										</div>
										<div class="p-3 border-bottom">
											<div class="row">
												<div class="col-md-12">
													<div class="mb-3">
														<label class="form-label">Pickup Points</label>
														<select class="select">
															<option>Select</option>
															<option>2603 Wood Duck Drive Marquette, MI</option>
															<option>3521 Harvest Lane Kansas City, MO</option>
															<option>4025 Khale Street, Folly Beach, SC</option>
															<option>2261 Sweetwood Drive, Denver, CO</option>
															<option>3341 Palmer Road, Columbus, OH</option>
															
														</select>
													</div>
												</div>
												<div class="col-md-12">
													<div class="mb-0">
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
								<th>Pickup Point</th>
								<th>Status</th>
								<th>Added On</th>
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
										<td><a href="#" class="link-primary">PP124556</a></td>
										<td>
											2603 Wood Duck Drive Marquette, MI
										</td>
										<td>
											<span class="badge badge-soft-success d-inline-flex align-items-center"><i
													class="ti ti-circle-filled fs-5 me-1 fs-5 me-1"></i>Active</span>
										</td>
										<td>15 May 2024</td>
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
															<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#edit_pickup"><i
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
										<td><a href="#" class="link-primary">PP124555</a></td>
										<td>
											3521 Harvest Lane Kansas City, MO
										</td>
										<td> <span class="badge badge-soft-success d-inline-flex align-items-center"><i
													class="ti ti-circle-filled fs-5 me-1 fs-5 me-1"></i>Active</span></td>
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
															<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#edit_pickup"><i
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
										<td><a href="#" class="link-primary">PP124554</a></td>
										<td>
											4025 Khale Street, Folly Beach, SC
										</td>
										<td>
											<span class="badge badge-soft-success d-inline-flex align-items-center"><i
													class="ti ti-circle-filled fs-5 me-1 fs-5 me-1"></i>Active</span>
										</td>
										<td>13 May 2024</td>
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
															<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#edit_pickup"><i
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
										<td><a href="#" class="link-primary">PP124553</a></td>
										<td>
											2261 Sweetwood Drive, Denver, CO
										</td>
										<td>
											<span class="badge badge-soft-success d-inline-flex align-items-center"><i
													class="ti ti-circle-filled fs-5 me-1 fs-5 me-1"></i>Active</span>
										</td>
										<td>12 May 2024</td>
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
															<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#edit_pickup"><i
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
										<td><a href="#" class="link-primary">PP124552</a></td>
										<td>
											3341 Palmer Road, Columbus, OH
										</td>
										<td>
											<span class="badge badge-soft-success d-inline-flex align-items-center"><i
													class="ti ti-circle-filled fs-5 me-1 fs-5 me-1"></i>Active</span>
										</td>
										<td>11 May 2024</td>
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
															<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#edit_pickup"><i
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
										<td><a href="#" class="link-primary">PP124551</a></td>
										<td>
											1609 Smith Street, Worcester, MA
										</td>
										<td>
											<span class="badge badge-soft-success d-inline-flex align-items-center"><i
													class="ti ti-circle-filled fs-5 me-1 fs-5 me-1"></i>Active</span>
										</td>
										<td>10 May 2024</td>
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
															<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#edit_pickup"><i
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
										<td><a href="#" class="link-primary">PP24550</a></td>
										<td>
											3167 Stadium Drive, Worcester, MA
										</td>
										<td>
											<span class="badge badge-soft-success d-inline-flex align-items-center"><i
													class="ti ti-circle-filled fs-5 me-1 fs-5 me-1"></i>Active</span>
										</td>
										<td>09 May 2024</td>
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
															<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#edit_pickup"><i
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
										<td><a href="#" class="link-primary">PP24549</a></td>
										<td>
											4650 Aviation Way, Los Angeles, CA
										</td>
										<td>
											<span class="badge badge-soft-danger d-inline-flex align-items-center"><i
													class="ti ti-circle-filled fs-5 me-1 fs-5 me-1"></i>Inactive</span>
										</td>
										<td>08 May 2024</td>
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
															<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#edit_pickup"><i
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
										<td><a href="#" class="link-primary">PP124548</a></td>
										<td>
											2693 Parker Drive, Cleveland, OH
										</td>
										<td>
											<span class="badge badge-soft-success d-inline-flex align-items-center"><i
													class="ti ti-circle-filled fs-5 me-1 fs-5 me-1"></i>Active</span>
										</td>
										<td>07 May 2024</td>
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
															<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#edit_pickup"><i
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
										<td><a href="#" class="link-primary">PP124547</a></td>
										<td>
											2233 Wood Street, Slidell, LA
										</td>
										<td>
											<span class="badge badge-soft-success d-inline-flex align-items-center"><i
													class="ti ti-circle-filled fs-5 me-1 fs-5 me-1"></i>Active</span>
										</td>
										<td>07 May 2024</td>
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
															<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#edit_pickup"><i
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
