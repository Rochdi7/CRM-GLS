<?php $page = 'accounts-transactions'; ?>
@extends('layout.mainlayout')
@section('content')
  <!-- Page Wrapper -->
  <div class="page-wrapper">
	<div class="content">
		@component('components.breadcrumb')
        @slot('title')
		Transactions
        @endslot
        @slot('item1')
        Dashboard
        @endslot
        @slot('item2')
		Finance & Accounts
        @endslot
        @slot('item3')
		Transactions
        @endslot
       @endcomponent
		

		<!-- Filter Section -->
		<div class="card">
			<div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
				<h4 class="mb-3">Transactions List</h4>
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
							<form action="{{url('accounts-transactions')}}">
								<div class="d-flex align-items-center border-bottom p-3">
									<h4>Filter</h4>
								</div>
								<div class="p-3 pb-0 border-bottom">
									<div class="row">
										<div class="col-md-6">
											<div class="mb-3">
												<label class="form-label">Transaction ID</label>
												<select class="select">
													<option>Select</option>
													<option>FT624893</option>
													<option>FT624892</option>
													<option>FT624891</option>
												</select>
											</div>
										</div>
										<div class="col-md-6">
											<div class="mb-3">
												<label class="form-label">Transaction Type</label>
												<select class="select">
													<option>Select</option>
													<option>Income</option>
													<option>Expense</option>
												</select>
											</div>
										</div>
										<div class="col-md-12">
											<div class="mb-3">
												<label class="form-label">Transaction Date</label>
												<select class="select">
													<option>Select</option>
													<option>25 Apr 2024</option>
													<option>27 Apr 2024</option>
													<option>03 May 2024</option>
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
				<!-- Transaction List -->
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
								<th>Description</th>
								<th>Transaction Date</th>
								<th>Amount</th>
								<th>Transaction Type</th>
								<th>Payment Method</th>
								<th>Status</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>
									<div class="form-check form-check-md">
										<input class="form-check-input" type="checkbox">
									</div>
								</td>
								<td><a href="#" class="link-primary">FT624893</a></td>
								<td>April Month Fees</td>
								<td>25 Apr 2024</td>
								<td>
									$15,000
								</td>
								<td>Income</td>
								<td>Cash</td>
								<td>
									<span class="badge badge-soft-success d-inline-flex align-items-center"><i
										class="ti ti-circle-filled fs-5 me-1"></i>Completed</span>
								</td>
							</tr>
							<tr>
								<td>
									<div class="form-check form-check-md">
										<input class="form-check-input" type="checkbox">
									</div>
								</td>
								<td><a href="#" class="link-primary">FT624892</a></td>
								<td>Monthly Electricity</td>
								<td>27 Apr 2024</td>
								<td>
									$1000
								</td>
								<td>Expense</td>
								<td>Credit</td>
								<td>
									<span class="badge badge-soft-success d-inline-flex align-items-center"><i
										class="ti ti-circle-filled fs-5 me-1"></i>Completed</span>
								</td>
							</tr>
							<tr>
								<td>
									<div class="form-check form-check-md">
										<input class="form-check-input" type="checkbox">
									</div>
								</td>
								<td><a href="#" class="link-primary">FT624891</a></td>
								<td>Alumni Scholarship</td>
								<td>03 May 2024</td>
								<td>
									$1000
								</td>
								<td>Income</td>
								<td>Cash</td>
								<td>
									<span class="badge badge-soft-warning d-inline-flex align-items-center"><i
										class="ti ti-circle-filled fs-5 me-1"></i>Pending</span>
								</td>
							</tr>
							<tr>
								<td>
									<div class="form-check form-check-md">
										<input class="form-check-input" type="checkbox">
									</div>
								</td>
								<td><a href="#" class="link-primary">FT624890</a></td>
								<td>AC Repair</td>
								<td>15 May 2024</td>
								<td>
									$400
								</td>
								<td>Expense</td>
								<td>Cash</td>
								<td>
									<span class="badge badge-soft-success d-inline-flex align-items-center"><i
										class="ti ti-circle-filled fs-5 me-1"></i>Completed</span>
								</td>
							</tr>
							<tr>
								<td>
									<div class="form-check form-check-md">
										<input class="form-check-input" type="checkbox">
									</div>
								</td>
								<td><a href="#" class="link-primary">FT624889</a></td>
								<td>Uniform Sales</td>
								<td>20 May 2024</td>
								<td>
									$10,500
								</td>
								<td>Income</td>
								<td>Credit</td>
								<td>
									<span class="badge badge-soft-success d-inline-flex align-items-center"><i
										class="ti ti-circle-filled fs-5 me-1"></i>Completed</span>
								</td>
							</tr>
							<tr>
								<td>
									<div class="form-check form-check-md">
										<input class="form-check-input" type="checkbox">
									</div>
								</td>
								<td><a href="#" class="link-primary">FT624888</a></td>
								<td>Water Bill</td>
								<td>06 Jun 2024</td>
								<td>
									$700
								</td>
								<td>Expense</td>
								<td>Cash</td>
								<td>
									<span class="badge badge-soft-warning d-inline-flex align-items-center"><i
										class="ti ti-circle-filled fs-5 me-1"></i>Pending</span>
								</td>
							</tr>
							<tr>
								<td>
									<div class="form-check form-check-md">
										<input class="form-check-input" type="checkbox">
									</div>
								</td>
								<td><a href="#" class="link-primary">FT624887</a></td>
								<td>Library Donation</td>
								<td>18 Jun 2024</td>
								<td>
									$2,000
								</td>
								<td>Income</td>
								<td>Cash</td>
								<td>
									<span class="badge badge-soft-success d-inline-flex align-items-center"><i
										class="ti ti-circle-filled fs-5 me-1"></i>Completed</span>
								</td>
							</tr>
							<tr>
								<td>
									<div class="form-check form-check-md">
										<input class="form-check-input" type="checkbox">
									</div>
								</td>
								<td><a href="#" class="link-primary">FT624886</a></td>
								<td>Vehicle Repair</td>
								<td>26 Jun 2024</td>
								<td>
									$800
								</td>
								<td>Expense</td>
								<td>Cash</td>
								<td>
									<span class="badge badge-soft-success d-inline-flex align-items-center"><i
										class="ti ti-circle-filled fs-5 me-1"></i>Completed</span>
								</td>
							</tr>
							<tr>
								<td>
									<div class="form-check form-check-md">
										<input class="form-check-input" type="checkbox">
									</div>
								</td>
								<td><a href="#" class="link-primary">FT624885</a></td>
								<td>Cafeteria Income</td>
								<td>08 Jul 2024</td>
								<td>
									$15,000
								</td>
								<td>Income</td>
								<td>Credit</td>
								<td>
									<span class="badge badge-soft-success d-inline-flex align-items-center"><i
										class="ti ti-circle-filled fs-5 me-1"></i>Completed</span>
								</td>
							</tr>
							<tr>
								<td>
									<div class="form-check form-check-md">
										<input class="form-check-input" type="checkbox">
									</div>
								</td>
								<td><a href="#" class="link-primary">FT624884</a></td>
								<td>Lab Equipments </td>
								<td>10 Jul 2024</td>
								<td>
									$300
								</td>
								<td>Expense</td>
								<td>Cash</td>
								<td>
									<span class="badge badge-soft-success d-inline-flex align-items-center"><i
										class="ti ti-circle-filled fs-5 me-1"></i>Completed</span>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
				<!-- /Transaction List -->
			</div>
		</div>
	</div>
</div>
<!-- /Page Wrapper --> 
@endsection
