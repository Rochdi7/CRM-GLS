<?php $page = 'payroll'; ?>
@extends('layout.mainlayout')
@section('content')
 <!-- Page Wrapper -->
 <div class="page-wrapper">
	<div class="content">
		@component('components.breadcrumb')
        @slot('title')
		Payroll
        @endslot
        @slot('item1')
        Dashboard
        @endslot
        @slot('item2')
		HRM
        @endslot
        @slot('item3')
		Payroll
        @endslot
       @endcomponent
		

		<!-- Filter Section -->
		<div class="card">
			<div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
				<h4 class="mb-3">Payroll List</h4>
				<div class="d-flex align-items-center flex-wrap">
					<div class="input-icon-start mb-3 me-2 position-relative">
						<span class="icon-addon">
							<i class="ti ti-calendar"></i>
						</span>
						<input type="text" class="form-control  date-range bookingrange" placeholder="Select"
							value="Academic Year : 2024 / 2025">
					</div>
					<div class="dropdown mb-3 me-2">
						<a href="javascript:void(0);" class="btn btn-outline-light bg-white dropdown-toggle"
							data-bs-toggle="dropdown" data-bs-auto-close="outside"><i
								class="ti ti-filter me-2"></i>Filter</a>
						<div class="dropdown-menu drop-width">
							<form action="{{url('payroll')}}">
								<div class="d-flex align-items-center border-bottom p-3">
									<h4>Filter</h4>
								</div>
								<div class="p-3 border-bottom">
									<div class="row">
										<div class="col-md-6">
											<div class="mb-3">
												<label class="form-label">All Staffs</label>
												<select class="select">
													<option>Select</option>
													<option>Kevin</option>
													<option>Willie</option>
													<option>Daniel</option>
												</select>
											</div>
										</div>
										<div class="col-md-6">
											<div class="mb-3">
												<label class="form-label">Month</label>
												<select class="select">
													<option>Select</option>
													<option>April</option>
													<option>May</option>
													<option>june</option>
												</select>
											</div>
										</div>
										<div class="col-md-12">
											<div class="mb-0">
												<label class="form-label">Year</label>
												<select class="select">
													<option>Select</option>
													<option>2024</option>
													<option>2023</option>
													<option>2022</option>
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
				<!-- Payroll List -->
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
								<th>Name</th>
								<th>Department</th>
								<th>Designation </th>
								<th>Phone</th>
								<th>Amount</th>
								<th>Status</th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>
									<div class="form-check form-check-md">
										<input class="form-check-input" type="checkbox">
									</div>
								</td>
								<td><a href="#" class="link-primary">P738197</a></td>
								<td>
									<div class="d-flex align-items-center">
										<a href="#" class="avatar avatar-md me-2"><span><img
													src="{{URL::asset('build/img/profiles/avatar-27.jpg')}}"
													alt="img"></span>Kevin</a>
									</div>
								</td>
								<td>Admin</td>
								<td>Technical Head</td>
								<td>+1 63423 72397</td>
								<td>
									$15,000
								</td>
								<td>
									<span class="badge badge-soft-success d-inline-flex align-items-center"><i
										class="ti ti-circle-filled fs-5 me-1"></i>Paid</span>
								</td>
								<td><a href="#" class="btn btn-light add-fee">View Payslip</a></td>
							</tr>
							<tr>
								<td>
									<div class="form-check form-check-md">
										<input class="form-check-input" type="checkbox">
									</div>
								</td>
								<td><a href="#" class="link-primary">P738197</a></td>
								<td>
									<div class="d-flex align-items-center">
										<a href="#" class="avatar avatar-md me-2"><span><img
													src="{{URL::asset('build/img/teachers/teacher-05.jpg')}}"
													alt="img"></span>Willie</a>
									</div>
								</td>
								<td>Management</td>
								<td>Receptionist</td>
								<td>+1 82913 61371</td>
								<td>
									$12,000
								</td>
								<td>
									<span class="badge badge-soft-warning d-inline-flex align-items-center"><i
										class="ti ti-circle-filled fs-5 me-1"></i>Generated</span>
								</td>
								<td><a href="#" class="btn btn-light add-fee">View Details</a></td>
							</tr>
							<tr>
								<td>
									<div class="form-check form-check-md">
										<input class="form-check-input" type="checkbox">
									</div>
								</td>
								<td><a href="#" class="link-primary">P738196</a></td>
								<td>
									<div class="d-flex align-items-center">
										<a href="#" class="avatar avatar-md me-2"><span><img
													src="{{URL::asset('build/img/teachers/teacher-02.jpg')}}"
													alt="img"></span>Daniel</a>
									</div>
								</td>
								<td>Management</td>
								<td>Admin</td>
								<td>+1 56752 86742</td>
								<td>
									$13,000
								</td>
								<td>
									<span class="badge badge-soft-success d-inline-flex align-items-center"><i
										class="ti ti-circle-filled fs-5 me-1"></i>Paid</span>
								</td>
								<td><a href="#" class="btn btn-light add-fee">View Payslip</a></td>
							</tr>
							<tr>
								<td>
									<div class="form-check form-check-md">
										<input class="form-check-input" type="checkbox">
									</div>
								</td>
								<td><a href="#" class="link-primary">P738195</a></td>
								<td>
									<div class="d-flex align-items-center">
										<a href="#" class="avatar avatar-md me-2"><span><img
													src="{{URL::asset('build/img/teachers/teacher-01.jpg')}}"
													alt="img"></span>Teresa</a>
									</div>
								</td>
								<td>Management</td>
								<td>Admin</td>
								<td>+1 82392 37359</td>
								<td>
									$13,000
								</td>
								<td>
									<span class="badge badge-soft-success d-inline-flex align-items-center"><i
										class="ti ti-circle-filled fs-5 me-1"></i>Paid</span>
								</td>
								<td><a href="#" class="btn btn-light add-fee">View Payslip</a></td>
							</tr>
							<tr>
								<td>
									<div class="form-check form-check-md">
										<input class="form-check-input" type="checkbox">
									</div>
								</td>
								<td><a href="#" class="link-primary">P738194</a></td>
								<td>
									<div class="d-flex align-items-center">
										<a href="#" class="avatar avatar-md me-2"><span><img
													src="{{URL::asset('build/img/teachers/teacher-08.jpg')}}"
													alt="img"></span>Johnson</a>
									</div>
								</td>
								<td>Finance</td>
								<td>Accountant</td>
								<td>+1 53619 54691</td>
								<td>
									$18,000
								</td>
								<td>
									<span class="badge badge-soft-success d-inline-flex align-items-center"><i
										class="ti ti-circle-filled fs-5 me-1"></i>Paid</span>
								</td>
								<td><a href="#" class="btn btn-light add-fee">View Payslip</a></td>
							</tr>
							<tr>
								<td>
									<div class="form-check form-check-md">
										<input class="form-check-input" type="checkbox">
									</div>
								</td>
								<td><a href="#" class="link-primary">P738193</a></td>
								<td>
									<div class="d-flex align-items-center">
										<a href="#" class="avatar avatar-md me-2"><span><img
													src="{{URL::asset('build/img/teachers/teacher-03.jpg')}}"
													alt="img"></span>Hellana</a>
									</div>
								</td>
								<td>Management</td>
								<td>HR Manager</td>
								<td>+1 23566 52683</td>
								<td>
									$12,000
								</td>
								<td>
									<span class="badge badge-soft-warning d-inline-flex align-items-center"><i
										class="ti ti-circle-filled fs-5 me-1"></i>Generated</span>
								</td>
								<td><a href="#" class="btn btn-light add-fee">View Details</a></td>
							</tr>
							<tr>
								<td>
									<div class="form-check form-check-md">
										<input class="form-check-input" type="checkbox">
									</div>
								</td>
								<td><a href="#" class="link-primary">P738192</a></td>
								<td>
									<div class="d-flex align-items-center">
										<a href="#" class="avatar avatar-md me-2"><span><img
													src="{{URL::asset('build/img/members/members-01.jpg')}}"
													alt="img"></span>James</a>
									</div>
								</td>
								<td>Library</td>
								<td>Librarian</td>
								<td>+1 78429 82414</td>
								<td>
									$10,000
								</td>
								<td>
									<span class="badge badge-soft-success d-inline-flex align-items-center"><i
										class="ti ti-circle-filled fs-5 me-1"></i>Paid</span>
								</td>
								<td><a href="#" class="btn btn-light add-fee">View Payslip</a></td>
							</tr>
							<tr>
								<td>
									<div class="form-check form-check-md">
										<input class="form-check-input" type="checkbox">
									</div>
								</td>
								<td><a href="#" class="link-primary">P738191</a></td>
								<td>
									<div class="d-flex align-items-center">
										<a href="#" class="avatar avatar-md me-2"><span><img
													src="{{URL::asset('build/img/teachers/teacher-07.jpg')}}"
													alt="img"></span>Jacquelin</a>
									</div>
								</td>
								<td>Transport</td>
								<td>Driver</td>
								<td>+1 77502 54845</td>
								<td>
									$8000
								</td>
								<td>
									<span class="badge badge-soft-success d-inline-flex align-items-center"><i
										class="ti ti-circle-filled fs-5 me-1"></i>Paid</span>
								</td>
								<td><a href="#" class="btn btn-light add-fee">View Payslip</a></td>
							</tr>
							<tr>
								<td>
									<div class="form-check form-check-md">
										<input class="form-check-input" type="checkbox">
									</div>
								</td>
								<td><a href="#" class="link-primary">P738190</a></td>
								<td>
									<div class="d-flex align-items-center">
										<a href="#" class="avatar avatar-md me-2"><span><img
													src="{{URL::asset('build/img/teachers/teacher-10.jpg')}} "
													alt="img"></span>Edward</a>
									</div>
								</td>
								<td>Finance</td>
								<td>Accounts Manager</td>
								<td>+1 56187 87489</td>
								<td>
									$12,000
								</td>
								<td>
									<span class="badge badge-soft-success d-inline-flex align-items-center"><i
										class="ti ti-circle-filled fs-5 me-1"></i>Paid</span>
								</td>
								<td><a href="#" class="btn btn-light add-fee">View Details</a></td>
							</tr>
							<tr>
								<td>
									<div class="form-check form-check-md">
										<input class="form-check-input" type="checkbox">
									</div>
								</td>
								<td><a href="#" class="link-primary">P738189</a></td>
								<td>
									<div class="d-flex align-items-center">
										<a href="#" class="avatar avatar-md me-2"><span><img
													src="{{URL::asset('build/img/teachers/teacher-09.jpg')}} "
													alt="img"></span>Elizabeth</a>
									</div>
								</td>
								<td>Management</td>
								<td>Receptionist</td>
								<td>+1 97846 84518</td>
								<td>
									$10,000
								</td>
								<td>
									<span class="badge badge-soft-success d-inline-flex align-items-center"><i
										class="ti ti-circle-filled fs-5 me-1"></i>Paid</span>
								</td>
								<td><a href="#" class="btn btn-light add-fee">View Payslip</a></td>
							</tr>
						</tbody>
					</table>
				</div>
				<!-- /Payroll List -->
			</div>
		</div>
		<!-- /Filter Section -->
	</div>
</div>
<!-- /Page Wrapper -->
@endsection
