<?php $page = 'expenses'; ?>
@extends('layout.mainlayout')
@section('content')
 
<!-- Page Wrapper -->
<div class="page-wrapper">
	<div class="content">
		@component('components.breadcrumb')
        @slot('title')
		Expense
        @endslot
        @slot('item1')
        Dashboard
        @endslot
        @slot('item2')
		Finance & Accounts
        @endslot
        @slot('item3')
		Expense
        @endslot
       @endcomponent
		
		<div class="card">
			<div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
				<h4 class="mb-3">Expense List</h4>
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
							<form action="{{url('expenses')}}">
								<div class="d-flex align-items-center border-bottom p-3">
									<h4>Filter</h4>
								</div>
								<div class="p-3 pb-0 border-bottom">
									<div class="row">
										<div class="col-md-6">
											<div class="mb-3">
												<label class="form-label">Expense Name</label>
												<select class="select">
													<option>Select</option>
													<option>Monthly Electricity</option>
													<option>Teacher Salary</option>
													<option>AC Repair</option>
												</select>
											</div>
										</div>
										<div class="col-md-6">
											<div class="mb-3">
												<label class="form-label">Category</label>
												<select class="select">
													<option>Select</option>
													<option>Utilities</option>
													<option>Salaries</option>
													<option>Maintenance</option>
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
				<!-- Expenses List -->
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
								<th>Expense Name</th>
								<th>Description</th>
								<th>Category</th>
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
								<td><a href="#" class="link-primary">EX628148</a></td>
								<td>Monthly Electricity</td>
								<td>Electricity of April month</td>
								<td>
									Utilities
								</td>
								<td>25 Apr 2024</td>
								<td>$1000</td>
								<td><a href="#" class="link-primary">INV681537</a></td>
								<td>Cash</td>
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
														data-bs-toggle="modal" data-bs-target="#edit_expenses"><i
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
								<td><a href="#" class="link-primary">EX628147</a></td>
								<td>Teacher Salary</td>
								<td>April payroll for teching staffs</td>
								<td>
									Salaries
								</td>
								<td>29 Apr 2024</td>
								<td>$20,000</td>
								<td><a href="#" class="link-primary">INV681536</a></td>
								<td>Credit</td>
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
														data-bs-toggle="modal" data-bs-target="#edit_expenses"><i
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
								<td><a href="#" class="link-primary">EX628146</a></td>
								<td>AC Repair</td>
								<td>Air Conditioning repair</td>
								<td>
									Maintenance
								</td>
								<td>11 May 2024</td>
								<td>$400</td>
								<td><a href="#" class="link-primary">INV681535</a></td>
								<td>Cash</td>
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
														data-bs-toggle="modal" data-bs-target="#edit_expenses"><i
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
								<td><a href="#" class="link-primary">EX628145</a></td>
								<td>Fuel Expense</td>
								<td>Fuel for school buses</td>
								<td>
									Transportation
								</td>
								<td>16 May 2024</td>
								<td>$1200</td>
								<td><a href="#" class="link-primary">INV681534</a></td>
								<td>Cash</td>
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
														data-bs-toggle="modal" data-bs-target="#edit_expenses"><i
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
								<td><a href="#" class="link-primary">EX628144</a></td>
								<td>Sports Uniform</td>
								<td>New uniform for soccer team</td>
								<td>
									Sports
								</td>
								<td>21 May 2024</td>
								<td>$2000</td>
								<td><a href="#" class="link-primary">INV681533</a></td>
								<td>Cash</td>
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
														data-bs-toggle="modal" data-bs-target="#edit_expenses"><i
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
								<td><a href="#" class="link-primary">EX628143</a></td>
								<td>Water Bill</td>
								<td>Monthly water usage bill</td>
								<td>
									Utilities
								</td>
								<td>14 Jun 2024</td>
								<td>$700</td>
								<td><a href="#" class="link-primary">INV681532</a></td>
								<td>Credit</td>
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
														data-bs-toggle="modal" data-bs-target="#edit_expenses"><i
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
								<td><a href="#" class="link-primary">EX628142</a></td>
								<td>Projector purchase</td>
								<td>Projector for auditorium</td>
								<td>
									Equipment
								</td>
								<td>20 Jun 2024</td>
								<td>$600</td>
								<td><a href="#" class="link-primary">INV681531</a></td>
								<td>Cash</td>
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
														data-bs-toggle="modal" data-bs-target="#edit_expenses"><i
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
								<td><a href="#" class="link-primary">EX628141</a></td>
								<td>Vehicle Repair</td>
								<td>Repairs for school buses</td>
								<td>
									Transportation
								</td>
								<td>24 Jun 2024</td>
								<td>$800</td>
								<td><a href="#" class="link-primary">INV681530</a></td>
								<td>Cash</td>
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
														data-bs-toggle="modal" data-bs-target="#edit_expenses"><i
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
								<td><a href="#" class="link-primary">EX628140</a></td>
								<td>Graduation Setup</td>
								<td>Setup costs for graduation </td>
								<td>
									Events
								</td>
								<td>08 Jul 2024</td>
								<td>$5000</td>
								<td><a href="#" class="link-primary">INV681529</a></td>
								<td>Credit</td>
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
														data-bs-toggle="modal" data-bs-target="#edit_expenses"><i
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
								<td><a href="#" class="link-primary">EX628139</a></td>
								<td>Lab Equipments </td>
								<td>New microscopes for labs</td>
								<td>
									Equipment
								</td>
								<td>13 Jul 2024</td>
								<td>$300</td>
								<td><a href="#" class="link-primary">INV681537</a></td>
								<td>Cash</td>
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
														data-bs-toggle="modal" data-bs-target="#edit_expenses"><i
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
				<!-- /Expenses List -->
			</div>
		</div>
	</div>
</div>
<!-- /Page Wrapper -->


    @component('components.modal-popup')        
    @endcomponent
@endsection
