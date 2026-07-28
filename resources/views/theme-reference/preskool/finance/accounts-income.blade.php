<?php $page = 'accounts-income'; ?>
@extends('layout.mainlayout')
@section('content')
  <!-- Page Wrapper -->
  <div class="page-wrapper">
	<div class="content">
		@component('components.breadcrumb')
        @slot('title')
		Income
        @endslot
        @slot('item1')
        Dashboard
        @endslot
        @slot('item2')
		Finance & Accounts
        @endslot
        @slot('item3')
		Income
        @endslot
       @endcomponent		
		<div class="card">
			<div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
				<h4 class="mb-3">Income List</h4>
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
							<form action="{{url('accounts-income')}}">
								<div class="d-flex align-items-center border-bottom p-3">
									<h4>Filter</h4>
								</div>
								<div class="p-3 pb-0 border-bottom">
									<div class="row">
										<div class="col-md-6">
											<div class="mb-3">
												<label class="form-label">Income Name</label>
												<select class="select">
													<option>Select</option>
													<option>April Month Fees</option>
													<option>STEM Program Grant</option>
													<option>Alumni Scholarship</option>
												</select>
											</div>
										</div>
										
										<div class="col-md-6">
											<div class="mb-3">
												<label class="form-label">Source</label>
												<select class="select">
													<option>Select</option>
													<option>Tuition Fees</option>
													<option>Government Grants</option>
													<option>Donations</option>
												</select>
											</div>
										</div>
										<div class="col-md-12">
											<div class="mb-3">
												<label class="form-label">Invoice Number</label>
												<select class="select">
													<option>Select</option>
													<option>INV681537</option>
													<option>INV681536</option>
													<option>INV681535</option>
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
				<!-- Income List -->
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
								<th>Income Name</th>
								<th>Description</th>
								<th>Source</th>
								<th>Date</th>
								<th>Amount</th>
								<th>Invoice No</th>
								<th>Payment Method</th>
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
								<td><a href="#" class="link-primary" data-bs-toggle="modal"
										data-bs-target="#view_invoice">I639248</a></td>
								<td>April Month Fees</td>
								<td>Tuition for Term 1, Class II</td>
								<td>
									Tuition Fees
								</td>
								<td>25 Apr 2024</td>
								<td>$15,000</td>
								<td><a href="#" class="link-primary" data-bs-toggle="modal"
										data-bs-target="#view_invoice">INV681537</a></td>
								<td>Cash</td>
								<td>
									<div class="dropdown">
										<a href="#"
											class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
											data-bs-toggle="dropdown" aria-expanded="false">
											<i class="ti ti-dots-vertical fs-14"></i>
										</a>
										<ul class="dropdown-menu dropdown-menu-right p-3">
											<li>
												<a class="dropdown-item rounded-1" href="#"
													data-bs-toggle="modal" data-bs-target="#edit_income"><i
														class="ti ti-edit-circle me-2"></i>Edit</a>
											</li>
											<li>
												<a class="dropdown-item rounded-1" href="#"
													data-bs-toggle="modal" data-bs-target="#delete-modal"><i
														class="ti ti-trash-x me-2"></i>Delete</a>
											</li>
										</ul>
									</div>
								</td>
							</tr>
							<tr>
								<td>
									<div class="form-check form-check-md">
										<input class="form-check-input" type="checkbox">
									</div>
								</td>
								<td><a href="#" class="link-primary" data-bs-toggle="modal"
										data-bs-target="#view_invoice">I639247</a></td>
								<td>STEM Program Grant</td>
								<td>Annual funding for STEM programs</td>
								<td>
									Government Grants
								</td>
								<td>29 Apr 2024</td>
								<td>$20,000</td>
								<td><a href="#" class="link-primary" data-bs-toggle="modal"
										data-bs-target="#view_invoice">INV681536</a></td>
								<td>Credit</td>
								<td>
									<div class="dropdown">
										<a href="#"
											class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
											data-bs-toggle="dropdown" aria-expanded="false">
											<i class="ti ti-dots-vertical fs-14"></i>
										</a>
										<ul class="dropdown-menu dropdown-menu-right p-3">
											<li>
												<a class="dropdown-item rounded-1" href="#"
													data-bs-toggle="modal" data-bs-target="#edit_income"><i
														class="ti ti-edit-circle me-2"></i>Edit</a>
											</li>
											<li>
												<a class="dropdown-item rounded-1" href="#"
													data-bs-toggle="modal" data-bs-target="#delete-modal"><i
														class="ti ti-trash-x me-2"></i>Delete</a>
											</li>
										</ul>
									</div>
								</td>
							</tr>
							<tr>
								<td>
									<div class="form-check form-check-md">
										<input class="form-check-input" type="checkbox">
									</div>
								</td>
								<td><a href="#" class="link-primary" data-bs-toggle="modal"
										data-bs-target="#view_invoice">I639246</a></td>
								<td>Alumni Scholarship</td>
								<td>Donation from Alumni for scholarships</td>
								<td>
									Donations
								</td>
								<td>11 May 2024</td>
								<td>$1000</td>
								<td><a href="#" class="link-primary" data-bs-toggle="modal"
										data-bs-target="#view_invoice">INV681535</a></td>
								<td>Cash</td>
								<td>
									<div class="dropdown">
										<a href="#"
											class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
											data-bs-toggle="dropdown" aria-expanded="false">
											<i class="ti ti-dots-vertical fs-14"></i>
										</a>
										<ul class="dropdown-menu dropdown-menu-right p-3">
											<li>
												<a class="dropdown-item rounded-1" href="#"
													data-bs-toggle="modal" data-bs-target="#edit_income"><i
														class="ti ti-edit-circle me-2"></i>Edit</a>
											</li>
											<li>
												<a class="dropdown-item rounded-1" href="#"
													data-bs-toggle="modal" data-bs-target="#delete-modal"><i
														class="ti ti-trash-x me-2"></i>Delete</a>
											</li>
										</ul>
									</div>
								</td>
							</tr>
							<tr>
								<td>
									<div class="form-check form-check-md">
										<input class="form-check-input" type="checkbox">
									</div>
								</td>
								<td><a href="#" class="link-primary" data-bs-toggle="modal"
										data-bs-target="#view_invoice">I639245</a></td>
								<td>Uniform Sales</td>
								<td>Sale of school uniforms</td>
								<td>
									Merchandise
								</td>
								<td>16 May 2024</td>
								<td>$10,500</td>
								<td><a href="#" class="link-primary" data-bs-toggle="modal"
										data-bs-target="#view_invoice">INV681534</a></td>
								<td>Cash</td>
								<td>
									<div class="dropdown">
										<a href="#"
											class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
											data-bs-toggle="dropdown" aria-expanded="false">
											<i class="ti ti-dots-vertical fs-14"></i>
										</a>
										<ul class="dropdown-menu dropdown-menu-right p-3">
											<li>
												<a class="dropdown-item rounded-1" href="#"
													data-bs-toggle="modal" data-bs-target="#edit_income"><i
														class="ti ti-edit-circle me-2"></i>Edit</a>
											</li>
											<li>
												<a class="dropdown-item rounded-1" href="#"
													data-bs-toggle="modal" data-bs-target="#delete-modal"><i
														class="ti ti-trash-x me-2"></i>Delete</a>
											</li>
										</ul>
									</div>
								</td>
							</tr>
							<tr>
								<td>
									<div class="form-check form-check-md">
										<input class="form-check-input" type="checkbox">
									</div>
								</td>
								<td><a href="#" class="link-primary" data-bs-toggle="modal"
										data-bs-target="#view_invoice">I639244</a></td>
								<td>Event Parking Fees</td>
								<td>Monthly parking fees for external users</td>
								<td>
									Parking Fees
								</td>
								<td>21 May 2024</td>
								<td>$8000</td>
								<td><a href="#" class="link-primary" data-bs-toggle="modal"
										data-bs-target="#view_invoice">INV681533</a></td>
								<td>Cash</td>
								<td>
									<div class="dropdown">
										<a href="#"
											class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
											data-bs-toggle="dropdown" aria-expanded="false">
											<i class="ti ti-dots-vertical fs-14"></i>
										</a>
										<ul class="dropdown-menu dropdown-menu-right p-3">
											<li>
												<a class="dropdown-item rounded-1" href="#"
													data-bs-toggle="modal" data-bs-target="#edit_income"><i
														class="ti ti-edit-circle me-2"></i>Edit</a>
											</li>
											<li>
												<a class="dropdown-item rounded-1" href="#"
													data-bs-toggle="modal" data-bs-target="#delete-modal"><i
														class="ti ti-trash-x me-2"></i>Delete</a>
											</li>
										</ul>
									</div>
								</td>
							</tr>
							<tr>
								<td>
									<div class="form-check form-check-md">
										<input class="form-check-input" type="checkbox">
									</div>
								</td>
								<td><a href="#" class="link-primary" data-bs-toggle="modal"
										data-bs-target="#view_invoice">I639243</a></td>
								<td>Football Season Pass</td>
								<td>Season passes for school football games</td>
								<td>
									Sports
								</td>
								<td>14 Jun 2024</td>
								<td>$7000</td>
								<td><a href="#" class="link-primary" data-bs-toggle="modal"
										data-bs-target="#view_invoice">INV681532</a></td>
								<td>Credit</td>
								<td>
									<div class="dropdown">
										<a href="#"
											class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
											data-bs-toggle="dropdown" aria-expanded="false">
											<i class="ti ti-dots-vertical fs-14"></i>
										</a>
										<ul class="dropdown-menu dropdown-menu-right p-3">
											<li>
												<a class="dropdown-item rounded-1" href="#"
													data-bs-toggle="modal" data-bs-target="#edit_income"><i
														class="ti ti-edit-circle me-2"></i>Edit</a>
											</li>
											<li>
												<a class="dropdown-item rounded-1" href="#"
													data-bs-toggle="modal" data-bs-target="#delete-modal"><i
														class="ti ti-trash-x me-2"></i>Delete</a>
											</li>
										</ul>
									</div>
								</td>
							</tr>
							<tr>
								<td>
									<div class="form-check form-check-md">
										<input class="form-check-input" type="checkbox">
									</div>
								</td>
								<td><a href="#" class="link-primary" data-bs-toggle="modal"
										data-bs-target="#view_invoice">I639242</a></td>
								<td>Summer Reading Sale</td>
								<td>Sales from summer reading book fair</td>
								<td>
									Book Fair
								</td>
								<td>20 Jun 2024</td>
								<td>$3000</td>
								<td><a href="#" class="link-primary" data-bs-toggle="modal"
										data-bs-target="#view_invoice">INV681531</a></td>
								<td>Cash</td>
								<td>
									<div class="dropdown">
										<a href="#"
											class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
											data-bs-toggle="dropdown" aria-expanded="false">
											<i class="ti ti-dots-vertical fs-14"></i>
										</a>
										<ul class="dropdown-menu dropdown-menu-right p-3">
											<li>
												<a class="dropdown-item rounded-1" href="#"
													data-bs-toggle="modal" data-bs-target="#edit_income"><i
														class="ti ti-edit-circle me-2"></i>Edit</a>
											</li>
											<li>
												<a class="dropdown-item rounded-1" href="#"
													data-bs-toggle="modal" data-bs-target="#delete-modal"><i
														class="ti ti-trash-x me-2"></i>Delete</a>
											</li>
										</ul>
									</div>
								</td>
							</tr>
							<tr>
								<td>
									<div class="form-check form-check-md">
										<input class="form-check-input" type="checkbox">
									</div>
								</td>
								<td><a href="#" class="link-primary" data-bs-toggle="modal"
										data-bs-target="#view_invoice">I639241</a></td>
								<td>Library Donation</td>
								<td>Funds donated for library improvements</td>
								<td>
									Donations
								</td>
								<td>24 Jun 2024</td>
								<td>$2000</td>
								<td><a href="#" class="link-primary" data-bs-toggle="modal"
										data-bs-target="#view_invoice">INV681530</a></td>
								<td>Cash</td>
								<td>
									<div class="dropdown">
										<a href="#"
											class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
											data-bs-toggle="dropdown" aria-expanded="false">
											<i class="ti ti-dots-vertical fs-14"></i>
										</a>
										<ul class="dropdown-menu dropdown-menu-right p-3">
											<li>
												<a class="dropdown-item rounded-1" href="#"
													data-bs-toggle="modal" data-bs-target="#edit_income"><i
														class="ti ti-edit-circle me-2"></i>Edit</a>
											</li>
											<li>
												<a class="dropdown-item rounded-1" href="#"
													data-bs-toggle="modal" data-bs-target="#delete-modal"><i
														class="ti ti-trash-x me-2"></i>Delete</a>
											</li>
										</ul>
									</div>
								</td>
							</tr>
							<tr>
								<td>
									<div class="form-check form-check-md">
										<input class="form-check-input" type="checkbox">
									</div>
								</td>
								<td><a href="#" class="link-primary" data-bs-toggle="modal"
										data-bs-target="#view_invoice">I639240</a></td>
								<td>Cafeteria Income</td>
								<td>Monthly Cafeteria Income</td>
								<td>
									Cafeteria
								</td>
								<td>08 Jul 2024</td>
								<td>$15,000</td>
								<td><a href="#" class="link-primary" data-bs-toggle="modal"
										data-bs-target="#view_invoice">INV681529</a></td>
								<td>Credit</td>
								<td>
									<div class="dropdown">
										<a href="#"
											class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
											data-bs-toggle="dropdown" aria-expanded="false">
											<i class="ti ti-dots-vertical fs-14"></i>
										</a>
										<ul class="dropdown-menu dropdown-menu-right p-3">
											<li>
												<a class="dropdown-item rounded-1" href="#"
													data-bs-toggle="modal" data-bs-target="#edit_income"><i
														class="ti ti-edit-circle me-2"></i>Edit</a>
											</li>
											<li>
												<a class="dropdown-item rounded-1" href="#"
													data-bs-toggle="modal" data-bs-target="#delete-modal"><i
														class="ti ti-trash-x me-2"></i>Delete</a>
											</li>
										</ul>
									</div>
								</td>
							</tr>
							<tr>
								<td>
									<div class="form-check form-check-md">
										<input class="form-check-input" type="checkbox">
									</div>
								</td>
								<td><a href="#" class="link-primary" data-bs-toggle="modal"
										data-bs-target="#view_invoice">I639239</a></td>
								<td>Daily Visitor Parking</td>
								<td>Parking fees collected from visitors</td>
								<td>
									Parking Fees
								</td>
								<td>13 Jul 2024</td>
								<td>$4000</td>
								<td><a href="#" class="link-primary" data-bs-toggle="modal"
										data-bs-target="#view_invoice">INV681537</a></td>
								<td>Cash</td>
								<td>
									<div class="dropdown">
										<a href="#"
											class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
											data-bs-toggle="dropdown" aria-expanded="false">
											<i class="ti ti-dots-vertical fs-14"></i>
										</a>
										<ul class="dropdown-menu dropdown-menu-right p-3">
											<li>
												<a class="dropdown-item rounded-1" href="#"
													data-bs-toggle="modal" data-bs-target="#edit_income"><i
														class="ti ti-edit-circle me-2"></i>Edit</a>
											</li>
											<li>
												<a class="dropdown-item rounded-1" href="#"
													data-bs-toggle="modal" data-bs-target="#delete-modal"><i
														class="ti ti-trash-x me-2"></i>Delete</a>
											</li>
										</ul>
									</div>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
				<!-- /Income List -->
			</div>
		</div>

	</div>
</div>
<!-- /Page Wrapper -->
    @component('components.modal-popup')
    @endcomponent
@endsection
