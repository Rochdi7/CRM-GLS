<?php $page = 'student-attendance-type'; ?>
@extends('layout.mainlayout')
@section('content')
  <!-- Page Wrapper -->
  <div class="page-wrapper">
	<div class="content content-two">

		<!-- Page Header -->
		<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
			<div class="my-auto mb-2">
				<h3 class="page-title mb-1">Students Attendance Type</h3>
				<nav>
					<ol class="breadcrumb mb-0">
						<li class="breadcrumb-item">
							<a href="{{url('index')}}">Dashboard</a>
						</li>
						<li class="breadcrumb-item">
							<a href="javascript:void(0);">Report</a>
						</li>
						<li class="breadcrumb-item active" aria-current="page">Students Attendance Type</li>
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
						<a href="{{url('student-attendance-type')}}" class="active">Students Attendance Type</a>
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
				<h4 class="mb-3">Students Attendance Type</h4>
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
							<form action="{{url('student-attendance-type')}}">
								<div class="d-flex align-items-center border-bottom p-3">
									<h4>Filter</h4>
								</div>
								<div class="p-3 border-bottom">
									<div class="row">
										<div class="col-md-6">
											<div class="mb-3">
												<label class="form-label">Class</label>
												<select class="select">
													<option>Select</option>
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
													<option>Select</option>
													<option>A</option>
													<option>B</option>
													<option>C</option>
												</select>
											</div>
										</div>
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
												<label class="form-label">Attendance Type</label>
												<select class="select">
													<option>Select</option>
													<option>Present</option>
													<option>Absent</option>
													<option>Half Day</option>
													<option>Holiday</option>
												</select>
											</div>
										</div>
										<div class="col-md-6">
											<div class="mb-3">
												<label class="form-label">Count</label>
												<select class="select">
													<option>Select</option>
													<option>24</option>
													<option>24</option>
													<option>24</option>
												</select>
											</div>
										</div>
										<div class="col-md-12">
											<div class="mb-0">
												<label class="form-label">Date of Admission</label>
												<input type="date" class="form-control">
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
								<th>Admission No</th>
								<th>Date of Admission</th>
								<th>Student Name</th>
								<th>Class</th>
								<th>Date of Birth</th>
								<th>Father Name</th>
								<th>Count</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td><a href="#" class="link-primary">AD9892434</a></td>
								<td>25 Mar 2024</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-01.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('student-details')}}">Janet</a>
											</p>
										</div>
									</div>
								</td>
								<td>III</td>
								<td>10 Jan 2015</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="javascript:void(0);" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/parents/parent-10.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="javascript:void(0);">Mary</a></p>
										</div>
									</div>
								</td>
								<td>22</td>
							</tr>
							<tr>
								<td><a href="#" class="link-primary">AD9892433</a></td>
								<td>18 Mar 2024</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-02.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('student-details')}}">Joann</a>
											</p>
										</div>
									</div>
								</td>
								<td>IV</td>
								<td>19 Aug 2014</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="javascript:void(0);" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/parents/parent-09.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="javascript:void(0);">Michael </a>
											</p>
										</div>
									</div>
								</td>
								<td>15</td>
							</tr>
							<tr>
								<td><a href="#" class="link-primary">AD9892432</a></td>
								<td>14 Mar 2024</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-03.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a
													href="{{url('student-details')}}">Kathleen</a></p>
										</div>
									</div>
								</td>
								<td>II</td>
								<td>05 Dec 2017</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="javascript:void(0);" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/parents/parent-08.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="javascript:void(0);">Jessie</a>
											</p>
										</div>
									</div>
								</td>
								<td>24</td>
							</tr>
							<tr>
								<td><a href="#" class="link-primary">AD9892431</a></td>
								<td>27 Feb 2024</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-04.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('student-details')}}">Gifford</a>
											</p>
										</div>
									</div>
								</td>
								<td>I</td>
								<td>22 Mar 2018</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="javascript:void(0);" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/parents/parent-07.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="javascript:void(0);">Robert </a>
											</p>
										</div>
									</div>
								</td>
								<td>22</td>
							</tr>
							<tr>
								<td><a href="#" class="link-primary">AD9892430</a></td>
								<td>13 Feb 2024</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-05.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('student-details')}}">Gifford</a>
											</p>
										</div>
									</div>
								</td>
								<td>II</td>
								<td>13 May 2017</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="javascript:void(0);" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/parents/parent-06.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="javascript:void(0);">Colleen</a>
											</p>
										</div>
									</div>
								</td>
								<td>22</td>
							</tr>
							<tr>
								<td><a href="#" class="link-primary">AD9892429</a></td>
								<td>11 Feb 2024</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-06.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('student-details')}}">Ralph</a>
											</p>
										</div>
									</div>
								</td>
								<td>III</td>
								<td>20 Jun 2015</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="javascript:void(0);" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/parents/parent-05.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="javascript:void(0);">Arthur</a>
											</p>
										</div>
									</div>
								</td>
								<td>24</td>
							</tr>
							<tr>
								<td><a href="#" class="link-primary">AD9892428</a></td>
								<td>24 Jan 2024</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-07.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('student-details')}}">Julie</a>
											</p>
										</div>
									</div>
								</td>
								<td>V</td>
								<td>18 Sep 2013</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="javascript:void(0);" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/parents/parent-04.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="javascript:void(0);">Claudia</a>
											</p>
										</div>
									</div>
								</td>
								<td>24</td>
							</tr>
							<tr>
								<td><a href="#" class="link-primary">AD9892427</a></td>
								<td>19 Jan 2024</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-08.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('student-details')}}">Ryan</a>
											</p>
										</div>
									</div>
								</td>
								<td>VI</td>
								<td>26 Nov 2012</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="javascript:void(0);" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/parents/parent-03.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="javascript:void(0);">Johnson</a>
											</p>
										</div>
									</div>
								</td>
								<td>21</td>
							</tr>
							<tr>
								<td><a href="#" class="link-primary">AD9892426</a></td>
								<td>08 Jan 2024</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-09.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('student-details')}}">Susan</a>
											</p>
										</div>
									</div>
								</td>
								<td>VIII</td>
								<td>26 May 2010</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="javascript:void(0);" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/parents/parent-02.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="javascript:void(0);">Marquita</a>
											</p>
										</div>
									</div>
								</td>
								<td>24</td>
							</tr>
							<tr>
								<td><a href="#" class="link-primary">AD9892425</a></td>
								<td>22 Dec 2024</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-10.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('student-details')}}">Richard</a>
											</p>
										</div>
									</div>
								</td>
								<td>VII</td>
								<td>06 Oct 2011</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="javascript:void(0);" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/parents/parent-01.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="javascript:void(0);">Thomas</a>
											</p>
										</div>
									</div>
								</td>
								<td>24</td>
							</tr>
							<tr>
								<td><a href="#" class="link-primary">AD9892424</a></td>
								<td>15 Dec 2024</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-11.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a
													href="{{url('student-details')}}">Veronica</a></p>
										</div>
									</div>
								</td>
								<td>IX</td>
								<td>27 Dec 2009</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="javascript:void(0);" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/parents/parent-14.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="javascript:void(0);">Jessie </a>
											</p>
										</div>
									</div>
								</td>
								<td>24</td>
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
