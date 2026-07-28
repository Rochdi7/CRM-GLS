<?php $page = 'approve-request'; ?>
@extends('layout.mainlayout')
@section('content')
   	<!-- Page Wrapper -->
		<div class="page-wrapper">
			<div class="content">
				<!-- Page Header -->
				<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
					<div class="my-auto mb-2">
						<h3 class="page-title mb-1">Approved Leave Request</h3>
						<nav>
							<ol class="breadcrumb mb-0">
								<li class="breadcrumb-item">
									<a href="{{url('index')}}">Dashboard</a>
								</li>
								<li class="breadcrumb-item">
									<a href="javascript:void(0);">HRM</a>
								</li>
								<li class="breadcrumb-item active" aria-current="page">Approved Leave Request</li>
							</ol>
						</nav>
					</div>
					<div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
						<div class="pe-1 mb-2">
							<a href="#" class="btn btn-outline-light bg-white btn-icon me-1" data-bs-toggle="tooltip"
								data-bs-placement="top" aria-label="Refresh" data-bs-original-title="Refresh">
								<i class="ti ti-refresh"></i>
							</a>
						</div>
						<div class="pe-1 mb-2">
							<button type="button" class="btn btn-outline-light bg-white btn-icon me-1"
								data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Print"
								data-bs-original-title="Print">
								<i class="ti ti-printer"></i>
							</button>
						</div>
						<div class="dropdown me-2 mb-2">
							<a href="javascript:void(0);"
								class="dropdown-toggle btn btn-light fw-medium d-inline-flex align-items-center"
								data-bs-toggle="dropdown">
								<i class="ti ti-file-export me-2"></i>Export
							</a>
							<ul class="dropdown-menu  dropdown-menu-end p-3">
								<li>
									<a href="javascript:void(0);" class="dropdown-item rounded-1"><i
											class="ti ti-file-type-pdf me-1"></i>Export as PDF</a>
								</li>
								<li>
									<a href="javascript:void(0);" class="dropdown-item rounded-1"><i
											class="ti ti-file-type-xls me-1"></i>Export as Excel </a>
								</li>
							</ul>
						</div>
					</div>
				</div>
				<!-- Page Header-->

				<!-- Filter Section -->
				<div class="card">
					<div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
						<h4 class="mb-3">Approved Leave Request List</h4>
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
									<form action="{{url('approve-request')}}">
										<div class="d-flex align-items-center border-bottom p-3">
											<h4>Filter</h4>
										</div>
										<div class="p-3 border-bottom">
											<div class="row">
												<div class="col-md-6">
													<div class="mb-3">
														<label class="form-label">Leave Type</label>
														<select class="select">
															<option>Select</option>
															<option>Medical Leave</option>
															<option>Casual Leave</option>
															<option>Maternity Leave</option>
														</select>
													</div>
												</div>
												<div class="col-md-6">
													<div class="mb-3">
														<label class="form-label">Role</label>
														<select class="select">
															<option>Select</option>
															<option>Student</option>
															<option>Teacher</option>
															<option>Admin</option>
														</select>
													</div>
												</div>
												<div class="col-md-6">
													<div class="mb-0">
														<label class="form-label">From - To Date</label>
														<select class="select">
															<option>Select</option>
															<option>05 May 2024 - 07 may 2024</option>
															<option>07 May 2024 - 07 may 2024</option>
															<option>08 May 2024 - 19 may 2024</option>
														</select>
													</div>
												</div>
												<div class="col-md-6">
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
						<!-- Approve List -->
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
										<th>Submitted By</th>
										<th>Leave Type</th>
										<th>Role</th>
										<th>Leave Date</th>
										<th>No of Days</th>
										<th>Applied On</th>
										<th>Authority</th>
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
										<td>James Deckar (9004)</td>
										<td>Medical Leave</td>
										<td>Student</td>
										<td>05 May 2024 - 07 may 2024</td>
										<td>5</td>
										<td>05 May 2024 </td>
										<td>Jacquelin</td>
										<td>
											<span class="badge badge-soft-success d-inline-flex align-items-center"><i
													class="ti ti-circle-filled fs-5 me-1"></i>Approved</span>
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
															<a class="dropdown-item rounded-1"
																href="#" data-bs-toggle="modal"data-bs-target="#leave_request"><i
																	class="ti ti-menu me-2"></i>Leave Request</a>
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
										<td>Richard (2145)</td>
										<td>Casual Leave</td>
										<td>Teacher</td>
										<td>07 May 2024 - 07 may 2024</td>
										<td>1</td>
										<td>07 May 2024</td>
										<td>Elizabeth</td>
										<td>
											<span class="badge badge-soft-success d-inline-flex align-items-center"><i
													class="ti ti-circle-filled fs-5 me-1"></i>Approved</span>
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
															<a class="dropdown-item rounded-1"
																href="#" data-bs-toggle="modal"data-bs-target="#leave_request"><i
																	class="ti ti-menu me-2"></i>Leave Request</a>
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
										<td>Susan (4147)</td>
										<td>Maternity Leave</td>
										<td>Admin</td>
										<td>08 May 2024 - 19 may 2024</td>
										<td>10</td>
										<td>02 May 2024</td>
										<td>Teresa</td>
										<td>
											<span class="badge badge-soft-success d-inline-flex align-items-center"><i
													class="ti ti-circle-filled fs-5 me-1"></i>Approved</span>
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
															<a class="dropdown-item rounded-1"
																href="#" data-bs-toggle="modal"data-bs-target="#leave_request"><i
																	class="ti ti-menu me-2"></i>Leave Request</a>
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
										<td>Lisa (2145)</td>
										<td>Sick Leave</td>
										<td>Librarian</td>
										<td>05 May 2024 - 07 may 2024</td>
										<td>1</td>
										<td>04 May 2024</td>
										<td>Edward</td>
										<td>
											<span class="badge badge-soft-success d-inline-flex align-items-center"><i
													class="ti ti-circle-filled fs-5 me-1"></i>Approved</span>
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
															<a class="dropdown-item rounded-1"
																href="#" data-bs-toggle="modal"data-bs-target="#leave_request"><i
																	class="ti ti-menu me-2"></i>Leave Request</a>
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
										<td>Janet (1457)</td>
										<td>Paternity Leave</td>
										<td>Driver</td>
										<td>07 May 2024 - 07 may 2024</td>
										<td>1</td>
										<td>06 May 2024</td>
										<td>Daniel</td>
										<td>
											<span class="badge badge-soft-danger d-inline-flex align-items-center"><i
													class="ti ti-circle-filled fs-5 me-1"></i>Disapproved</span>
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
															<a class="dropdown-item rounded-1"
																href="#" data-bs-toggle="modal"data-bs-target="#leave_request"><i
																	class="ti ti-menu me-2"></i>Leave Request</a>
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
										<td>Ryan (9784)</td>
										<td>Special Leave</td>
										<td>Student</td>
										<td>08 May 2024 - 19 may 2024</td>
										<td>1</td>
										<td>12 May 2024</td>
										<td>Hellana</td>
										<td>
											<span class="badge badge-soft-pending d-inline-flex align-items-center"><i
													class="ti ti-circle-filled fs-5 me-1"></i>Pending</span>
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
															<a class="dropdown-item rounded-1"
																href="#" data-bs-toggle="modal"data-bs-target="#leave_request"><i
																	class="ti ti-menu me-2"></i>Leave Request</a>
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
										<td>Gifford (7457)</td>
										<td>Medical Leave</td>
										<td>Student</td>
										<td>07 May 2024 - 07 may 2024</td>
										<td>1</td>
										<td>04 May 2024</td>
										<td>Erickson</td>
										<td>
											<span class="badge badge-soft-pending d-inline-flex align-items-center"><i
													class="ti ti-circle-filled fs-5 me-1"></i>Pending</span>
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
															<a class="dropdown-item rounded-1"
																href="#" data-bs-toggle="modal"data-bs-target="#leave_request"><i
																	class="ti ti-menu me-2"></i>Leave Request</a>
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
										<td>Julie (4655)</td>
										<td>Casual Leave</td>
										<td>Student</td>
										<td>05 May 2024 - 07 may 2024</td>
										<td>1</td>
										<td>04 May 2024</td>
										<td>Raul</td>
										<td>
											<span class="badge badge-soft-success d-inline-flex align-items-center"><i
													class="ti ti-circle-filled fs-5 me-1"></i>Approved</span>
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
															<a class="dropdown-item rounded-1"
																href="#" data-bs-toggle="modal"data-bs-target="#leave_request"><i
																	class="ti ti-menu me-2"></i>Leave Request</a>
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
										<td>Joann (4178)</td>
										<td>Medical Leave</td>
										<td>Student</td>
										<td>08 May 2024 - 19 may 2024</td>
										<td>1</td>
										<td>04 May 2024</td>
										<td>Aaron</td>
										<td>
											<span class="badge badge-soft-pending d-inline-flex align-items-center"><i
													class="ti ti-circle-filled fs-5 me-1"></i>Pending</span>
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
															<a class="dropdown-item rounded-1"
																href="#" data-bs-toggle="modal"data-bs-target="#leave_request"><i
																	class="ti ti-menu me-2"></i>Leave Request</a>
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
										<td>Kathleen (5898)</td>
										<td>Casual Leave</td>
										<td>Student</td>
										<td>07 May 2024 - 07 may 2024</td>
										<td>1</td>
										<td>04 May 2024</td>
										<td>Morgan</td>
										<td>
											<span class="badge badge-soft-pending d-inline-flex align-items-center"><i
													class="ti ti-circle-filled fs-5 me-1"></i>Pending</span>
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
															<a class="dropdown-item rounded-1"
																href="#" data-bs-toggle="modal"data-bs-target="#leave_request"><i
																	class="ti ti-menu me-2"></i>Leave Request</a>
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
						<!-- /Approve List -->
					</div>
				</div>
			</div>
		</div>
		<!-- /Page Wrapper -->


    @component('components.modal-popup')
    @endcomponent
@endsection
