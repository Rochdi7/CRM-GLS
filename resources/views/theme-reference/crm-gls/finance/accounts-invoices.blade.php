<?php $page = 'accounts-invoices'; ?>
@extends('layout.mainlayout')
@section('content')
  
	<!-- Page Wrapper -->
	<div class="page-wrapper">
		<div class="content">
		@component('components.breadcrumb')
        @slot('title')
		Invoices
        @endslot
        @slot('item1')
        Dashboard
        @endslot
        @slot('item2')
		Finance & Accounts
        @endslot
        @slot('item3')
		Invoices
        @endslot
       @endcomponent
			
<!-- Filter Section -->
			<div class="card">
				<div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
					<h4 class="mb-3">Invoices List</h4>
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
								<form action="{{url('accounts-invoices')}}">
									<div class="d-flex align-items-center border-bottom p-3">
										<h4>Filter</h4>
									</div>
									<div class="p-3 pb-0 border-bottom">
										<div class="row">
											<div class="col-md-6">
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
											<div class="col-md-6">
												<div class="mb-3">
													<label class="form-label">Date</label>
													<select class="select">
														<option>Select</option>
														<option>25 Apr 2024</option>
														<option>29 Apr 2024</option>
														<option>11 May 2024</option>
													</select>
												</div>
											</div>
											<div class="col-md-12">
												<div class="mb-3">
													<label class="form-label">Payment Method</label>
													<select class="select">
														<option>Select</option>
														<option>Cash</option>
														<option>Credit</option>
														<option>Cash</option>
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
					<!-- Invoice List -->
					<div class="custom-datatable-filter table-responsive">
						<table class="table datatable">
							<thead class="thead-light">
								<tr>
									<th class="no-sort">
										<div class="form-check form-check-md">
											<input class="form-check-input" type="checkbox" id="select-all">
										</div>
									</th>
									<th>Invoice Number</th>
									<th>Date</th>
									<th>Description</th>
									<th>Amount</th>
									<th>Payment Method</th>
									<th>Due Date</th>
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
									<td><a href="#" class="link-primary" data-bs-toggle="modal"
											data-bs-target="#view_invoice">INV681537</a></td>
									<td>25 Apr 2024</td>
									<td>April Month Fees</td>
									<td>
										$15,000
									</td>
									<td>Cash</td>
									<td>30 Apr 2024</td>
									<td>
										<span class="badge badge-soft-success d-inline-flex align-items-center"><i
											class="ti ti-circle-filled fs-5 me-1"></i>Paid</span>
									</td>
									<td>
										<div class="dropdown">
											<a href="#"
												class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
												data-bs-toggle="dropdown" aria-expanded="false">
												<i class="ti ti-dots-vertical fs-14"></i>
											</a>
											<ul class="dropdown-menu dropdown-menu-right p-3">
												<li>
													<a class="dropdown-item rounded-1"
														href="#" data-bs-toggle="modal"data-bs-target="#view_invoice"><i
															class="ti ti-menu me-2"></i>View Invoice</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="{{url('edit-invoice')}}"><i
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
											data-bs-target="#view_invoice">INV681536</a></td>
									<td>29 Apr 2024</td>
									<td>STEM Program Grant</td>
									<td>
										$20,000
									</td>
									<td>Credit</td>
									<td>05 May 2024</td>
									<td>
										<span class="badge badge-soft-warning d-inline-flex align-items-center"><i
											class="ti ti-circle-filled fs-5 me-1"></i>Overdue</span>
									</td>
									<td>
										<div class="dropdown">
											<a href="#"
												class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
												data-bs-toggle="dropdown" aria-expanded="false">
												<i class="ti ti-dots-vertical fs-14"></i>
											</a>
											<ul class="dropdown-menu dropdown-menu-right p-3">
												<li>
													<a class="dropdown-item rounded-1"
														href="#" data-bs-toggle="modal"data-bs-target="#view_invoice"><i
															class="ti ti-menu me-2"></i>View Invoice</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="{{url('edit-invoice')}}"><i
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
											data-bs-target="#view_invoice">INV681535</a></td>
									<td>11 May 2024</td>
									<td>Alumni Scholarship</td>
									<td>
										$1000
									</td>
									<td>Cash</td>
									<td>16 May 2024</td>
									<td>
										<span class="badge badge-soft-success d-inline-flex align-items-center"><i
											class="ti ti-circle-filled fs-5 me-1"></i>Paid</span>
									</td>
									<td>
										<div class="dropdown">
											<a href="#"
												class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
												data-bs-toggle="dropdown" aria-expanded="false">
												<i class="ti ti-dots-vertical fs-14"></i>
											</a>
											<ul class="dropdown-menu dropdown-menu-right p-3">
												<li>
													<a class="dropdown-item rounded-1"
														href="#" data-bs-toggle="modal"data-bs-target="#view_invoice"><i
															class="ti ti-menu me-2"></i>View Invoice</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="{{url('edit-invoice')}}"><i
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
											data-bs-target="#view_invoice">INV681534</a></td>
									<td>16 May 2024</td>
									<td>Uniform Sales</td>
									<td>
										$10,500
									</td>
									<td>Cash</td>
									<td>21 May 2024</td>
									<td>
										<span class="badge badge-soft-pending d-inline-flex align-items-center"><i
											class="ti ti-circle-filled fs-5 me-1"></i>Pending</span>
									</td>
									<td>
										<div class="dropdown">
											<a href="#"
												class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
												data-bs-toggle="dropdown" aria-expanded="false">
												<i class="ti ti-dots-vertical fs-14"></i>
											</a>
											<ul class="dropdown-menu dropdown-menu-right p-3">
												<li>
													<a class="dropdown-item rounded-1"
														href="#" data-bs-toggle="modal"data-bs-target="#view_invoice"><i
															class="ti ti-menu me-2"></i>View Invoice</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="{{url('edit-invoice')}}"><i
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
											data-bs-target="#view_invoice">INV681533</a></td>
									<td>21 May 2024</td>
									<td>Event Parking Fees</td>
									<td>
										$8000
									</td>
									<td>Cash</td>
									<td>26 May 2024</td>
									<td>
										<span class="badge badge-soft-success d-inline-flex align-items-center"><i
											class="ti ti-circle-filled fs-5 me-1"></i>Paid</span>
									</td>
									<td>
										<div class="dropdown">
											<a href="#"
												class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
												data-bs-toggle="dropdown" aria-expanded="false">
												<i class="ti ti-dots-vertical fs-14"></i>
											</a>
											<ul class="dropdown-menu dropdown-menu-right p-3">
												<li>
													<a class="dropdown-item rounded-1"
														href="#" data-bs-toggle="modal"data-bs-target="#view_invoice"><i
															class="ti ti-menu me-2"></i>View Invoice</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="{{url('edit-invoice')}}"><i
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
											data-bs-target="#view_invoice">INV681532</a></td>
									<td>14 Jun 2024</td>
									<td>Football Season Pass</td>
									<td>
										$7000
									</td>
									<td>Credit</td>
									<td>19 Jun 2024</td>
									<td>
										<span class="badge badge-soft-success d-inline-flex align-items-center"><i
											class="ti ti-circle-filled fs-5 me-1"></i>Paid</span>
									</td>
									<td>
										<div class="dropdown">
											<a href="#"
												class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
												data-bs-toggle="dropdown" aria-expanded="false">
												<i class="ti ti-dots-vertical fs-14"></i>
											</a>
											<ul class="dropdown-menu dropdown-menu-right p-3">
												<li>
													<a class="dropdown-item rounded-1"
														href="#" data-bs-toggle="modal"data-bs-target="#view_invoice"><i
															class="ti ti-menu me-2"></i>View Invoice</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="{{url('edit-invoice')}}"><i
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
											data-bs-target="#view_invoice">INV681531</a></td>
									<td>20 Jun 2024</td>
									<td>Summer Reading Sale</td>
									<td>
										$3000
									</td>
									<td>Cash</td>
									<td>25 Jun 2024</td>
									<td>
										<span class="badge badge-soft-warning d-inline-flex align-items-center"><i
											class="ti ti-circle-filled fs-5 me-1"></i>Overdue</span>
									</td>
									<td>
										<div class="dropdown">
											<a href="#"
												class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
												data-bs-toggle="dropdown" aria-expanded="false">
												<i class="ti ti-dots-vertical fs-14"></i>
											</a>
											<ul class="dropdown-menu dropdown-menu-right p-3">
												<li>
													<a class="dropdown-item rounded-1"
														href="#" data-bs-toggle="modal"data-bs-target="#view_invoice"><i
															class="ti ti-menu me-2"></i>View Invoice</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="{{url('edit-invoice')}}"><i
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
											data-bs-target="#view_invoice">INV681530</a></td>
									<td>24 Jun 2024</td>
									<td>Library Donation</td>
									<td>
										$2000
									</td>
									<td>Cash</td>
									<td>29 Jun 2024</td>
									<td>
										<span class="badge badge-soft-success d-inline-flex align-items-center"><i
											class="ti ti-circle-filled fs-5 me-1"></i>Paid</span>
									</td>
									<td>
										<div class="dropdown">
											<a href="#"
												class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
												data-bs-toggle="dropdown" aria-expanded="false">
												<i class="ti ti-dots-vertical fs-14"></i>
											</a>
											<ul class="dropdown-menu dropdown-menu-right p-3">
												<li>
													<a class="dropdown-item rounded-1"
														href="#" data-bs-toggle="modal"data-bs-target="#view_invoice"><i
															class="ti ti-menu me-2"></i>View Invoice</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="{{url('edit-invoice')}}"><i
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
											data-bs-target="#view_invoice">INV681529</a></td>
									<td>08 Jul 2024</td>
									<td>Cafeteria Income</td>
									<td>
										$15,000
									</td>
									<td>Credit</td>
									<td>13 Jul 2024</td>
									<td>
										<span class="badge badge-soft-success d-inline-flex align-items-center"><i
											class="ti ti-circle-filled fs-5 me-1"></i>Paid</span>
									</td>
									<td>
										<div class="dropdown">
											<a href="#"
												class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
												data-bs-toggle="dropdown" aria-expanded="false">
												<i class="ti ti-dots-vertical fs-14"></i>
											</a>
											<ul class="dropdown-menu dropdown-menu-right p-3">
												<li>
													<a class="dropdown-item rounded-1"
														href="#" data-bs-toggle="modal"data-bs-target="#view_invoice"><i
															class="ti ti-menu me-2"></i>View Invoice</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="{{url('edit-invoice')}}"><i
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
											data-bs-target="#view_invoice">INV681537</a></td>
									<td>13 Jul 2024</td>
									<td>Daily Visitor Parking</td>
									<td>
										$4000
									</td>
									<td>Cash</td>
									<td>18 Jul 2024</td>
									<td>
										<span class="badge badge-soft-pending d-inline-flex align-items-center"><i
											class="ti ti-circle-filled fs-5 me-1"></i>Pending</span>
									</td>
									<td>
										<div class="dropdown">
											<a href="#"
												class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
												data-bs-toggle="dropdown" aria-expanded="false">
												<i class="ti ti-dots-vertical fs-14"></i>
											</a>
											<ul class="dropdown-menu dropdown-menu-right p-3">
												<li>
													<a class="dropdown-item rounded-1"
														href="#" data-bs-toggle="modal"data-bs-target="#view_invoice"><i
															class="ti ti-menu me-2"></i>View Invoice</a>
												</li>
												<li>
													<a class="dropdown-item rounded-1"
														href="{{url('edit-invoice')}}"><i
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
					<!-- /Invoice List -->
				</div>
			</div>
		</div>
	</div>
	<!-- /Page Wrapper -->

    @component('components.modal-popup')
    @endcomponent
@endsection
