<?php $page = 'teacher-report'; ?>
@extends('layout.mainlayout')
@section('content')
 <!-- Page Wrapper -->
 <div class="page-wrapper">
	<div class="content">

		<!-- Page Header -->
		<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
			<div class="my-auto mb-2">
				<h3 class="page-title mb-1">Teacher's Report</h3>
				<nav>
					<ol class="breadcrumb mb-0">
						<li class="breadcrumb-item">
							<a href="{{url('index')}}">Dashboard</a>
						</li>
						<li class="breadcrumb-item">
							<a href="javascript:void(0);">Report</a>
						</li>
						<li class="breadcrumb-item active" aria-current="page">Teacher's Report</li>
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
						<a href="{{url('student-day-wise')}}">Student Day Wise</a>
					</li>
					<li>
						<a href="{{url('teacher-day-wise')}}">Teacher Day Wise</a>
					</li>
					<li>
						<a href="{{url('teacher-report')}}" class="active">Teacher Report</a>
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

		<div class="attendance-types page-header justify-content-end">
			<ul class="attendance-type-list">
				<li>
					<span class="attendance-icon bg-success"><i class="ti ti-checks"></i></span>
					Present
				</li>
				<li>
					<span class="attendance-icon bg-danger"><i class="ti ti-x"></i></span>
					Absent
				</li>
				<li>
					<span class="attendance-icon bg-pending"><i class="ti ti-clock-x"></i></span>
					Late
				</li>
				<li>
					<span class="attendance-icon bg-dark"><i class="ti ti-calendar-event"></i></span>
					Halfday
				</li>
				<li>
					<span class="attendance-icon bg-info"><i class="ti ti-clock-up"></i></span>
					Holiday
				</li>
			</ul>
		</div>

		<!-- Attendance List -->
		<div class="card">
			<div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
				<h4 class="mb-3">Teacher Day Wise Report</h4>
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
							<form action="{{url('teacher-report')}}">
								<div class="d-flex align-items-center border-bottom p-3">
									<h4>Filter</h4>
								</div>
								<div class="p-3 border-bottom">
									<div class="row">
										<div class="col-md-12">
											<div class="mb-3">
												<label class="form-label">Name</label>
												<select class="select">
													<option>Select</option>
													<option>Janet</option>
													<option>Joann</option>
													<option>Kathleen</option>
												</select>
											</div>
										</div>
										<div class="col-md-6">
											<div class="mb-3">
												<label class="form-label">Month</label>
												<select class="select">
													<option>Select</option>
													<option>March</option>
													<option>May</option>
												</select>
											</div>
										</div>
										<div class="col-md-6">
											<div class="mb-0">
												<label class="form-label">Date</label>
												<select class="select">
													<option>Date</option>
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
								<th>Student / Date</th>
								<th>%</th>
								<th class="no-sort">P</th>
								<th class="no-sort">L</th>
								<th class="no-sort">A</th>
								<th class="no-sort">H</th>
								<th class="no-sort">F</th>
								<th class="no-sort">
									<div class="text-center"><span
											class="day-num d-block">01</span><span>M</span></div>
								</th>
								<th class="no-sort">
									<div class="text-center"><span
											class="day-num d-block">02</span><span>T</span></div>
								</th>
								<th class="no-sort">
									<div class="text-center"><span
											class="day-num d-block">03</span><span>W</span></div>
								</th>
								<th class="no-sort">
									<div class="text-center"><span
											class="day-num d-block">04</span><span>T</span></div>
								</th>
								<th class="no-sort">
									<div class="text-center"><span
											class="day-num d-block">05</span><span>F</span></div>
								</th>
								<th class="no-sort">
									<div class="text-center"><span
											class="day-num d-block">06</span><span>S</span></div>
								</th>
								<th class="no-sort">
									<div class="text-center"><span
											class="day-num d-block">07</span><span>S</span></div>
								</th>
								<th class="no-sort">
									<div class="text-center"><span
											class="day-num d-block">08</span><span>M</span></div>
								</th>
								<th class="no-sort">
									<div class="text-center"><span
											class="day-num d-block">09</span><span>T</span></div>
								</th>
								<th class="no-sort">
									<div class="text-center"><span
											class="day-num d-block">10</span><span>W</span></div>
								</th>
								<th class="no-sort">
									<div class="text-center"><span
											class="day-num d-block">11</span><span>T</span></div>
								</th>
								<th class="no-sort">
									<div class="text-center"><span
											class="day-num d-block">12</span><span>F</span></div>
								</th>
								<th class="no-sort">
									<div class="text-center"><span
											class="day-num d-block">13</span><span>S</span></div>
								</th>
								<th class="no-sort">
									<div class="text-center"><span
											class="day-num d-block">14</span><span>S</span></div>
								</th>
								<th class="no-sort">
									<div class="text-center"><span
											class="day-num d-block">15</span><span>M</span></div>
								</th>
								<th class="no-sort">
									<div class="text-center"><span
											class="day-num d-block">16</span><span>T</span></div>
								</th>
								<th class="no-sort">
									<div class="text-center"><span
											class="day-num d-block">17</span><span>W</span></div>
								</th>
								<th class="no-sort">
									<div class="text-center"><span
											class="day-num d-block">18</span><span>T</span></div>
								</th>
								<th class="no-sort">
									<div class="text-center"><span
											class="day-num d-block">19</span><span>F</span></div>
								</th>
								<th class="no-sort">
									<div class="text-center"><span
											class="day-num d-block">20</span><span>S</span></div>
								</th>
								<th class="no-sort">
									<div class="text-center"><span
											class="day-num d-block">21</span><span>S</span></div>
								</th>
								<th class="no-sort">
									<div class="text-center"><span
											class="day-num d-block">22</span><span>M</span></div>
								</th>
								<th class="no-sort">
									<div class="text-center"><span
											class="day-num d-block">23</span><span>T</span></div>
								</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('teacher-report')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/teachers/teacher-01.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('teacher-report')}}">Janet</a>
											</p>
										</div>
									</div>
								</td>
								<td><span class="badge  badge-soft-info">87</span></td>
								<td>22</td>
								<td>1</td>
								<td>1</td>
								<td>6</td>
								<td>1</td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-dark"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-danger"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-pending"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
							</tr>
							<tr>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('teacher-report')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/teachers/teacher-02.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('teacher-report')}}">Daniel</a>
											</p>
										</div>
									</div>
								</td>
								<td><span class="badge  badge-soft-info">95</span></td>
								<td>23</td>
								<td>1</td>
								<td>2</td>
								<td>6</td>
								<td>1</td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-dark"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-danger"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-pending"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
							</tr>
							<tr>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('teacher-report')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/teachers/teacher-03.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('teacher-report')}}">Hellana</a>
											</p>
										</div>
									</div>
								</td>
								<td><span class="badge  badge-soft-info">94</span></td>
								<td>23</td>
								<td>1</td>
								<td>3</td>
								<td>6</td>
								<td>1</td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-dark"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-danger"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-pending"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
							</tr>
							<tr>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('teacher-report')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/teachers/teacher-04.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('teacher-report')}}">Erickson</a>
											</p>
										</div>
									</div>
								</td>
								<td><span class="badge  badge-soft-danger">45</span></td>
								<td>16</td>
								<td>2</td>
								<td>1</td>
								<td>6</td>
								<td>1</td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-dark"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-danger"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-pending"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
							</tr>
							<tr>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('teacher-report')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/teachers/teacher-06.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('teacher-report')}}">Morgan</a>
											</p>
										</div>
									</div>
								</td>
								<td><span class="badge  badge-soft-success">100</span></td>
								<td>16</td>
								<td>2</td>
								<td>1</td>
								<td>6</td>
								<td>1</td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-dark"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-danger"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-pending"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
							</tr>
							<tr>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('teacher-report')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/teachers/teacher-07.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('teacher-report')}}">Aaron</a>
											</p>
										</div>
									</div>
								</td>
								<td><span class="badge  badge-soft-info">95</span></td>
								<td>21</td>
								<td>2</td>
								<td>1</td>
								<td>6</td>
								<td>2</td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-dark"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-danger"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-pending"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
							</tr>
							<tr>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('teacher-report')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/teachers/teacher-08.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a
													href="{{url('teacher-report')}}">Jacquelin</a></p>
										</div>
									</div>
								</td>
								<td><span class="badge  badge-soft-info">99</span></td>
								<td>22</td>
								<td>0</td>
								<td>4</td>
								<td>6</td>
								<td>1</td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-dark"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-danger"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-pending"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
							</tr>
							<tr>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('teacher-report')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/teachers/teacher-09.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('teacher-report')}}">Raul</a></p>
										</div>
									</div>
								</td>
								<td><span class="badge  badge-soft-info">98</span></td>
								<td>23</td>
								<td>0</td>
								<td>2</td>
								<td>6</td>
								<td>1</td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-dark"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-danger"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-pending"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
							</tr>
							<tr>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('teacher-report')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/teachers/teacher-10.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a
													href="{{url('teacher-report')}}">Elizabeth</a></p>
										</div>
									</div>
								</td>
								<td><span class="badge  badge-soft-danger">32</span></td>
								<td>20</td>
								<td>3</td>
								<td>1</td>
								<td>6</td>
								<td>4</td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-dark"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-danger"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-pending"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
							</tr>
							<tr>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('teacher-report')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/teachers/teacher-09.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('teacher-report')}}">Edward</a>
											</p>
										</div>
									</div>
								</td>
								<td><span class="badge  badge-soft-info">97</span></td>
								<td>15</td>
								<td>1</td>
								<td>1</td>
								<td>6</td>
								<td>1</td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-dark"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-danger"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-pending"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-info"></span></td>
								<td><span class="attendance-range bg-success"></span></td>
							</tr>
						</tbody>
					</table>
				</div>
				<!-- /Student List -->
			</div>
		</div>
		<!-- /Attendance List -->

		<div class="row align-items-center">
			<div class="col-md-12">
				<div class="datatable-paginate mt-4"></div>
			</div>
		</div>

	</div>
</div>
<!-- /Page Wrapper -->  

@endsection
