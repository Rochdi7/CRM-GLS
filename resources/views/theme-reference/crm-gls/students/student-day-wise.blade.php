<?php $page = 'student-day-wise'; ?>
@extends('layout.mainlayout')
@section('content')
   <!-- Page Wrapper -->
		<div class="page-wrapper">
			<div class="content">

				<!-- Page Header -->
				<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
					<div class="my-auto mb-2">
						<h3 class="page-title mb-1">Student Day Wise Report</h3>
						<nav>
							<ol class="breadcrumb mb-0">
								<li class="breadcrumb-item">
									<a href="javascript:void(0);">Dashboard</a>
								</li>
								<li class="breadcrumb-item">
									<a href="javascript:void(0);">Report</a>
								</li>
								<li class="breadcrumb-item active" aria-current="page">Student Day Wise Report</li>
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
				<!-- /Page Header -->

				<!-- Filter Section -->
				<div class="filter-wrapper">
					<!-- List Tab -->
					<div class="list-tab">
						<ul>
							<li>
								<a href="{{url('attendance-report')}}">Attendance Report</a>
							</li>
							<li>
								<a href="{{url('student-attendance-type')}}">Students Attendance Type</a>
							</li>
							<li>
								<a href="{{url('daily-attendance')}}">Daily Attendance</a>
							</li>
							<li>
								<a href="{{url('student-day-wise')}}" class="active">Student Day Wise</a>
							</li>
							<li>
								<a href="{{url('teacher-day-wise')}}">Teacher Day Wise</a>
							</li>
							<li>
								<a href="{{url('teacher-report')}}">Teacher Report</a>
							</li>
							<li>
								<a href="{{url('staff-day-wise')}}">Staff Day Wise</a>
							</li>
							<li>
								<a href="{{url('staff-report')}}">Staff Report</a>
							</li>
						</ul>
					</div>
					<!-- /List Tab -->
				</div>
				<!-- /Filter Section -->

				<!-- Attendance List -->
				<div class="card">
					<div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
						<h4 class="mb-3">Student Day Wise Report</h4>
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
									<form action="{{url('student-day-wise')}}">
										<div class="d-flex align-items-center border-bottom p-3">
											<h4>Filter</h4>
										</div>
										<div class="p-3 border-bottom">
											<div class="row">
												<div class="col-md-6">
													<div class="mb-3">
														<label class="form-label">Class</label>
														<select class="select">
															<option>Class</option>
															<option>I</option>
															<option>II</option>
															<option>III</option>
														</select>
													</div>
												</div>
												<div class="col-md-6">
													<div class="mb-3">
														<label class="form-label">Section</label>
														<select class="select">
															<option>Section</option>
															<option>A</option>
															<option>B</option>
															<option>C</option>
														</select>
													</div>
												</div>
												<div class="col-md-12">
													<div class="mb-0">
														<label class="form-label">Attendance Date</label>
														<select class="select">
															<option>Attendance Date</option>
															<option>15 May 2024</option>
															<option>16 May 2024</option>
															<option>17 May 2024</option>
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

						<!-- Student List -->
						<div class="custom-datatable-filter table-responsive">
							<table class="table datatable">
								<thead class="thead-light">
									<tr>
										<th>S.No</th>
										<th>Admission No</th>
										<th>Roll No</th>
										<th>Name</th>
										<th>Attendance</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td>1</td>
										<td>
											<a href="javascript:void(0);" class="link-primary">AD9892434</a>
										</td>
										<td>35013</td>
										<td>
											<div class="d-flex align-items-center">
												<a href="javascript:void(0);" class="avatar avatar-md"><img
														src="{{URL::asset('build/img/students/student-01.jpg')}}"
														class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="javascript:void(0);">Janet</a>
													</p>
												</div>
											</div>
										</td>
										<td>
											<span class="badge badge-soft-success d-inline-flex align-items-center"><i
													class="ti ti-circle-filled fs-5 me-1"></i>Present</span>
										</td>
									</tr>
									<tr>
										<td>2</td>
										<td>
											<a href="javascript:void(0);" class="link-primary">AD9892433</a>
										</td>
										<td>35012</td>
										<td>
											<div class="d-flex align-items-center">
												<a href="javascript:void(0);" class="avatar avatar-md"><img
														src="{{URL::asset('build/img/students/student-02.jpg')}}"
														class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="javascript:void(0);">Joann</a>
													</p>
												</div>
											</div>
										</td>
										<td>
											<span class="badge badge-soft-success d-inline-flex align-items-center"><i
													class="ti ti-circle-filled fs-5 me-1"></i>Present</span>
										</td>
									</tr>
									<tr>
										<td>3</td>
										<td>
											<a href="javascript:void(0);" class="link-primary">AD9892432</a>
										</td>
										<td>35011</td>
										<td>
											<div class="d-flex align-items-center">
												<a href="javascript:void(0);" class="avatar avatar-md"><img
														src="{{URL::asset('build/img/students/student-03.jpg')}}"
														class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="javascript:void(0);">Kathleen</a>
													</p>
												</div>
											</div>
										</td>
										<td>
											<span
												class="badge text-dark bg-transparent-dark d-inline-flex align-items-center"><i
													class="ti ti-circle-filled fs-5 me-1"></i>Half Day</span>
										</td>
									</tr>
									<tr>
										<td>4</td>
										<td>
											<a href="javascript:void(0);" class="link-primary">AD9892431</a>
										</td>
										<td>35010</td>
										<td>
											<div class="d-flex align-items-center">
												<a href="javascript:void(0);" class="avatar avatar-md"><img
														src="{{URL::asset('build/img/students/student-04.jpg')}}"
														class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="javascript:void(0);">Gifford</a>
													</p>
												</div>
											</div>
										</td>
										<td>
											<span class="badge badge-soft-success d-inline-flex align-items-center"><i
													class="ti ti-circle-filled fs-5 me-1"></i>Present</span>
										</td>
									</tr>
									<tr>
										<td>5</td>
										<td>
											<a href="javascript:void(0);" class="link-primary">AD9892430</a>
										</td>
										<td>35009</td>
										<td>
											<div class="d-flex align-items-center">
												<a href="javascript:void(0);" class="avatar avatar-md"><img
														src="{{URL::asset('build/img/students/student-05.jpg')}}"
														class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="javascript:void(0);">Lisa</a></p>
												</div>
											</div>
										</td>
										<td>
											<span class="badge badge-soft-danger d-inline-flex align-items-center"><i
													class="ti ti-circle-filled fs-5 me-1"></i>Absent</span>
										</td>
									</tr>
									<tr>
										<td>6</td>
										<td>
											<a href="javascript:void(0);" class="link-primary">AD9892429</a>
										</td>
										<td>35008</td>
										<td>
											<div class="d-flex align-items-center">
												<a href="javascript:void(0);" class="avatar avatar-md"><img
														src="{{URL::asset('build/img/students/student-06.jpg')}}"
														class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="javascript:void(0);">Ralph</a>
													</p>
												</div>
											</div>
										</td>
										<td>
											<span class="badge badge-soft-pending d-inline-flex align-items-center"><i
													class="ti ti-circle-filled fs-5 me-1"></i>Late</span>
										</td>
									</tr>
									<tr>
										<td>7</td>
										<td>
											<a href="javascript:void(0);" class="link-primary">AD9892428</a>
										</td>
										<td>35007</td>
										<td>
											<div class="d-flex align-items-center">
												<a href="javascript:void(0);" class="avatar avatar-md"><img
														src="{{URL::asset('build/img/students/student-07.jpg')}}"
														class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="javascript:void(0);">Julie</a>
													</p>
												</div>
											</div>
										</td>
										<td>
											<span class="badge badge-soft-success d-inline-flex align-items-center"><i
													class="ti ti-circle-filled fs-5 me-1"></i>Present</span>
										</td>
									</tr>
									<tr>
										<td>8</td>
										<td>
											<a href="javascript:void(0);" class="link-primary">AD9892427</a>
										</td>
										<td>35006</td>
										<td>
											<div class="d-flex align-items-center">
												<a href="javascript:void(0);" class="avatar avatar-md"><img
														src="{{URL::asset('build/img/students/student-08.jpg')}}"
														class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="javascript:void(0);">Ryan</a></p>
												</div>
											</div>
										</td>
										<td>
											<span class="badge badge-soft-success d-inline-flex align-items-center"><i
													class="ti ti-circle-filled fs-5 me-1"></i>Present</span>
										</td>
									</tr>
									<tr>
										<td>9</td>
										<td>
											<a href="javascript:void(0);" class="link-primary">AD9892426</a>
										</td>
										<td>35005</td>
										<td>
											<div class="d-flex align-items-center">
												<a href="javascript:void(0);" class="avatar avatar-md"><img
														src="{{URL::asset('build/img/students/student-09.jpg')}}"
														class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="javascript:void(0);">Susan</a>
													</p>
												</div>
											</div>
										</td>
										<td>
											<span class="badge badge-soft-danger d-inline-flex align-items-center"><i
													class="ti ti-circle-filled fs-5 me-1"></i>Absent</span>
										</td>
									</tr>
									<tr>
										<td>10</td>
										<td>
											<a href="javascript:void(0);" class="link-primary">AD9892425</a>
										</td>
										<td>35004</td>
										<td>
											<div class="d-flex align-items-center">
												<a href="javascript:void(0);" class="avatar avatar-md"><img
														src="{{URL::asset('build/img/students/student-10.jpg')}}"
														class="img-fluid rounded-circle" alt="img"></a>
												<div class="ms-2">
													<p class="text-dark mb-0"><a href="javascript:void(0);">Richard</a>
													</p>
												</div>
											</div>
										</td>
										<td>
											<span class="badge badge-soft-success d-inline-flex align-items-center"><i
													class="ti ti-circle-filled fs-5 me-1"></i>Present</span>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
						<!-- /Student List -->
					</div>
				</div>
				<!-- /Attendance List -->

			</div>
		</div>
		<!-- /Page Wrapper -->
@endsection
