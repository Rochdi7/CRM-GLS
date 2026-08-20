<?php $page = 'fees-assign'; ?>
@extends('layout.mainlayout')
@section('content')
  	<!-- Page Wrapper -->
	  <div class="page-wrapper">
		<div class="content">
			@component('components.breadcrumb')
			@slot('title')
			Fees Collection
			@endslot
			@slot('item1')
			Dashboard
			@endslot
			@slot('item2')
			Fees Collection
			@endslot
			@slot('item3')
			Assign Fees
			@endslot
		   @endcomponent
			

			<!-- Fees List -->
			<div class="card">
				<div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
					<h4 class="mb-3">Fees Collection</h4>
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
								<form action="{{url('fees-assign')}}">
									<div class="d-flex align-items-center border-bottom p-3">
										<h4>Filter</h4>
									</div>
									<div class="p-3 border-bottom">
										<div class="row">
											<div class="col-md-6">
												<div class="mb-3">
													<label class="form-label">Section</label>
													<select class="select">
														<option>Select </option>
														<option>A</option>
														<option>B</option>
														<option>C</option>
													</select>
												</div>
											</div>
											<div class="col-md-6">
												<div class="mb-3">
													<label class="form-label">Gender</label>
													<select class="select">
														<option>Select </option>
														<option>Male</option>
														<option>Female</option>
														<option>Both</option>
													</select>
												</div>
											</div>
											<div class="col-md-12">
												<div class="mb-0">
													<label class="form-label">Student Category</label>
													<select class="select">
														<option>Select</option>
														<option>BC</option>
														<option>MBC</option>
														<option>FC</option>
														<option>BC</option>
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
									<th>S. No</th>
									<th>Fees Group</th>
									<th>Fees Type</th>
									<th>Class</th>
									<th>Section</th>
									<th>Amount ($)</th>
									<th>Gender</th>
									<th>Category</th>
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
									<td>01</td>
									<td>Admission-Fees</td>
									<td>Tuition Fees</td>
									<td>I</td>
									<td>B</td>
									<td>1250</td>
									<td>Male</td>
									<td>BC</td>
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
															data-bs-toggle="modal" data-bs-target="#edit_fees_assign"><i
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
									<td>02</td>
									<td>Class 1 General</td>
									<td>Monthly Fees</td>
									<td>III</td>
									<td>C</td>
									<td>250</td>
									<td>Both</td>
									<td>MBC</td>
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
															data-bs-toggle="modal" data-bs-target="#edit_fees_assign"><i
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
									<td>03</td>
									<td>Monthly Fees</td>
									<td>Admission Fees</td>
									<td>IX</td>
									<td>F</td>
									<td>656</td>
									<td>Female</td>
									<td>FC</td>
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
															data-bs-toggle="modal" data-bs-target="#edit_fees_assign"><i
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
									<td>04</td>
									<td>Class 1 Lump Sum</td>
									<td>Bus Fees</td>
									<td>X</td>
									<td>R</td>
									<td>6225</td>
									<td>Male</td>
									<td>BC</td>
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
															data-bs-toggle="modal" data-bs-target="#edit_fees_assign"><i
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
									<td>05</td>
									<td>Class 1- I Installment</td>
									<td>Monthly Fees</td>
									<td>III</td>
									<td>E</td>
									<td>454</td>
									<td>Both</td>
									<td>MBC</td>
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
															data-bs-toggle="modal" data-bs-target="#edit_fees_assign"><i
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
									<td>06</td>
									<td>Class 1-II Installment</td>
									<td>Monthly Fees</td>
									<td>IV</td>
									<td>A</td>
									<td>214</td>
									<td>Male</td>
									<td>All</td>
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
															data-bs-toggle="modal" data-bs-target="#edit_fees_assign"><i
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
									<td>07</td>
									<td>Discount</td>
									<td>Topper Discount</td>
									<td>V</td>
									<td>B</td>
									<td>145</td>
									<td>Both</td>
									<td>FC</td>
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
															data-bs-toggle="modal" data-bs-target="#edit_fees_assign"><i
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
									<td>08</td>
									<td>Class 3- I Installment</td>
									<td>3rd-Installment-Fees</td>
									<td>X</td>
									<td>B</td>
									<td>147</td>
									<td>Male</td>
									<td>FC</td>
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
															data-bs-toggle="modal" data-bs-target="#edit_fees_assign"><i
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
									<td>09</td>
									<td>Class 2- I Installment</td>
									<td>3rd-Installment-Fees</td>
									<td>VI</td>
									<td>A</td>
									<td>457</td>
									<td>Female</td>
									<td>FC</td>
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
															data-bs-toggle="modal" data-bs-target="#edit_fees_assign"><i
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
									<td>10</td>
									<td>Class 4- I Installment</td>
									<td>3rd Installment Fees</td>
									<td>V</td>
									<td>A</td>
									<td>654</td>
									<td>Female</td>
									<td>All</td>
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
															data-bs-toggle="modal" data-bs-target="#edit_fees_assign"><i
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
			<!-- /Fees List -->

		</div>
	</div>
	<!-- /Page Wrapper -->

    @component('components.modal-popup')        
    @endcomponent
@endsection
