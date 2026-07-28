<?php $page = 'hostel-rooms'; ?>
@extends('layout.mainlayout')
@section('content')
 	<!-- Page Wrapper -->
	 <div class="page-wrapper">
		<div class="content">

			@component('components.breadcrumb')
            @slot('title')
			Hostel Rooms
            @endslot
            @slot('item1')
            Dashboard
            @endslot
            @slot('item2')
            Management
            @endslot
            @slot('item3')
			Hostel Rooms
            @endslot
           @endcomponent

			<!-- Students List -->
			<div class="card">
				<div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
					<h4 class="mb-3">Hostel Rooms</h4>
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
								<form action="{{url('hostel-rooms')}}">
									<div class="d-flex align-items-center border-bottom p-3">
										<h4>Filter</h4>
									</div>
									<div class="p-3 border-bottom">
										<div class="row">
											<div class="col-md-6">
												<div class="mb-3">
													<label class="form-label">Room No</label>
													<select class="select">
														<option>Select</option>
														<option>A1</option>
														<option>A2</option>
														<option>A3</option>
														<option>A4</option>
														<option>B1</option>
													</select>
												</div>
											</div>
											<div class="col-md-6">
												<div class="mb-3">
													<label class="form-label">Name</label>
													<select class="select">
														<option>Select</option>
														<option>Phoenix Residence</option>
														<option>Tranquil Haven</option>
														<option>Radiant Towers</option>
														<option>Nova Nest</option>
														<option>Vista Villa</option>
													</select>
												</div>
											</div>
											<div class="col-md-6">
												<div class="mb-0">
													<label class="form-label">Type</label>
													<select class="select">
														<option>One Bed</option>
														<option>One Bed AC</option>
														<option>Two Bed</option>
														<option>Two Bed AC</option>
													</select>
												</div>
											</div>
											<div class="col-md-6">
												<div class="mb-0">
													<label class="form-label">More Filter</label>
													<select class="select">
														<option>Select</option>
														<option>Filters</option>
														<option>Room No </option>
														<option>Hostel Name</option>
														<option>Room Type</option>
														<option>No of Bed</option>
														<option>Cost per Bed</option>
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
									<th>Room No</th>
									<th>Hostel Name</th>
									<th>Room Type</th>
									<th>No of Bed</th>
									<th>Cost per Bed</th>
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
									<td><a href="#" class="link-primary">HR819382</a></td>
									<td>A1</td>
									<td>Phoenix Residence</td>
									<td>One Bed</td>
									<td>1</td>
									<td>$200</td>
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
															data-bs-target="#edit_hostel_rooms"><i
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
									<td><a href="#" class="link-primary">HR819381</a></td>
									<td>A2</td>
									<td>Tranquil Haven</td>
									<td>One Bed AC</td>
									<td>1</td>
									<td>$300</td>
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
															data-bs-target="#edit_hostel_rooms"><i
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
									<td><a href="#" class="link-primary">HR819380</a></td>
									<td>A3</td>
									<td>Radiant Towers</td>
									<td>Two Bed</td>
									<td>2</td>
									<td>$400</td>
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
															data-bs-target="#edit_hostel_rooms"><i
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
									<td><a href="#" class="link-primary">HR819379</a></td>
									<td>A4</td>
									<td>Nova Nest</td>
									<td>One Bed</td>
									<td>1</td>
									<td>$200</td>
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
															data-bs-target="#edit_hostel_rooms"><i
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
									<td><a href="#" class="link-primary">HR819378</a></td>
									<td>B1</td>
									<td>Vista Villa</td>
									<td>One Bed AC</td>
									<td>1</td>
									<td>$300</td>
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
															data-bs-target="#edit_hostel_rooms"><i
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
									<td><a href="#" class="link-primary">HR819377</a></td>
									<td>B2</td>
									<td>Zenith Zone</td>
									<td>Two Bed</td>
									<td>2</td>
									<td>$400</td>
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
															data-bs-target="#edit_hostel_rooms"><i
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
									<td><a href="#" class="link-primary">HR819376</a></td>
									<td>B3</td>
									<td>Summit Springs</td>
									<td>One Bed</td>
									<td>1</td>
									<td>$200e</td>
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
															data-bs-target="#edit_hostel_rooms"><i
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
									<td><a href="#" class="link-primary">HR819375</a></td>
									<td>B4</td>
									<td>Beacon Breeze</td>
									<td>Two Bed AC</td>
									<td>2</td>
									<td>$600</td>
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
															data-bs-target="#edit_hostel_rooms"><i
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
									<td><a href="#" class="link-primary">HR819374</a></td>
									<td>C1</td>
									<td>Empyrean Estate</td>
									<td>One Bed</td>
									<td>1</td>
									<td>$200</td>
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
															data-bs-target="#edit_hostel_rooms"><i
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
									<td><a href="#" class="link-primary">HR819373</a></td>
									<td>C2</td>
									<td>Nexus Nook</td>
									<td>Two Bed AC</td>
									<td>2</td>
									<td>$600</td>
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
															data-bs-target="#edit_hostel_rooms"><i
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
