<?php $page = 'holidays'; ?>
@extends('layout.mainlayout')
@section('content')
  
<!-- Page Wrapper -->
<div class="page-wrapper">
	<div class="content">
		@component('components.breadcrumb')
        @slot('title')
        Holidays
        @endslot
        @slot('item1')
        Dashboard
        @endslot
        @slot('item2')
		HRM
        @endslot
        @slot('item3')
		Holidays
        @endslot
       @endcomponent
		

		<!-- Filter Section -->
		<div class="card">
			<div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
				<h4 class="mb-3">Holidays List</h4>
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
							<form action="{{url('holidays')}}">
								<div class="d-flex align-items-center border-bottom p-3">
									<h4>Filter</h4>
								</div>
								<div class="p-3 border-bottom">
									<div class="row">
										<div class="col-md-12">
											<div class="mb-3">
												<label class="form-label">Holiday Title</label>
												<select class="select">
													<option>Select</option>
													<option>New Year</option>
													<option>Martin Luther King Jr. Day</option>
													<option>Presidents' Day</option>
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
				<!-- Holidays List -->
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
								<th>Holiday Title</th>
								<th>Date</th>
								<th>Description </th>
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
								<td><a href="#" class="link-primary">H752762</a></td>
								<td>
									New Year
								</td>
								<td>01 Jan 2024</td>
								<td>First day of the new year</td>
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
													<a class="dropdown-item rounded-1" href="#"
														data-bs-toggle="modal" data-bs-target="#edit_holiday"><i
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
								<td><a href="#" class="link-primary">H752761</a></td>
								<td>
									Martin Luther King Jr. Day
								</td>
								<td>15 Jan 2024</td>
								<td>Celebrating the civil rights leader</td>
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
													<a class="dropdown-item rounded-1" href="#"
														data-bs-toggle="modal" data-bs-target="#edit_holiday"><i
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
								<td><a href="#" class="link-primary">H752760</a></td>
								<td>
									Presidents' Day
								</td>
								<td>19 Feb 2024</td>
								<td>Honoring past US Presidents</td>
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
													<a class="dropdown-item rounded-1" href="#"
														data-bs-toggle="modal" data-bs-target="#edit_holiday"><i
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
								<td><a href="#" class="link-primary">H752759</a></td>
								<td>
									Good Friday
								</td>
								<td>29 Mar 2024</td>
								<td>Holiday before Easter</td>
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
													<a class="dropdown-item rounded-1" href="#"
														data-bs-toggle="modal" data-bs-target="#edit_holiday"><i
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
								<td><a href="#" class="link-primary">H752758</a></td>
								<td>
									Easter Monday
								</td>
								<td>01 Apr 2024</td>
								<td>Holiday after Easter</td>
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
													<a class="dropdown-item rounded-1" href="#"
														data-bs-toggle="modal" data-bs-target="#edit_holiday"><i
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
								<td><a href="#" class="link-primary">H752757</a></td>
								<td>
									Memorial Day
								</td>
								<td>27 May 2024</td>
								<td>Honors military personnel</td>
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
													<a class="dropdown-item rounded-1" href="#"
														data-bs-toggle="modal" data-bs-target="#edit_holiday"><i
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
								<td><a href="#" class="link-primary">H752756</a></td>
								<td>
									Independence Day
								</td>
								<td>04 Jul 2024</td>
								<td>Celebrates Independence</td>
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
													<a class="dropdown-item rounded-1" href="#"
														data-bs-toggle="modal" data-bs-target="#edit_holiday"><i
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
								<td><a href="#" class="link-primary">H752755</a></td>
								<td>
									Labor Day
								</td>
								<td>02 Sep 2024</td>
								<td>Honors working people</td>
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
													<a class="dropdown-item rounded-1" href="#"
														data-bs-toggle="modal" data-bs-target="#edit_holiday"><i
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
								<td><a href="#" class="link-primary">H752754</a></td>
								<td>
									Veterans Day
								</td>
								<td>11 Nov 2024</td>
								<td>Honors military veterans</td>
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
													<a class="dropdown-item rounded-1" href="#"
														data-bs-toggle="modal" data-bs-target="#edit_holiday"><i
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
								<td><a href="#" class="link-primary">H752753</a></td>
								<td>
									Christmas Day
								</td>
								<td>25 Dec 2024</td>
								<td>Celebration of Christmas</td>
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
													<a class="dropdown-item rounded-1" href="#"
														data-bs-toggle="modal" data-bs-target="#edit_holiday"><i
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
				<!-- /Holidays List -->
			</div>
		</div>
	</div>
</div>
<!-- /Page Wrapper -->

    @component('components.modal-popup')
    @endcomponent
@endsection
