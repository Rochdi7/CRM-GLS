<?php $page = 'leave-report'; ?>
@extends('layout.mainlayout')
@section('content')
  <!-- Page Wrapper -->
  <div class="page-wrapper">
	<div class="content">
		@component('components.breadcrumb')
        @slot('title')
		Leave Report
        @endslot
        @slot('item1')
        Dashboard
        @endslot
        @slot('item2')
        Report
        @endslot
        @slot('item3')
        Leave Report
        @endslot
       @endcomponent
		

		<!-- Student List -->
		<div class="card">
			<div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
				<h4 class="mb-3">Leave Report List</h4>
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
							<form action="{{url('leave-report')}}">
								<div class="d-flex align-items-center border-bottom p-3">
									<h4>Filter</h4>
								</div>
								<div class="p-3 border-bottom">
									<div class="row">
										<div class="col-md-12">
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
										<div class="col-md-12">
											<div class="mb-0">
												<label class="form-label">Section</label>
												<select class="select">
													<option>Select</option>
													<option>A</option>
													<option>B</option>
													<option>C</option>
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
							<tr class="report-first-head">
								<th></th>
								<th></th>
								<th colspan="2">Medical Leave(10)</th>
								<th colspan="2">Casual Leave(12)</th>
								<th colspan="2">Maternity Leave(10)</th>
								<th colspan="2">Paternity Leave(10)</th>
								<th colspan="2">Special Leave(10)</th>
							</tr>
							<tr>
								<th>Admission No</th>
								<th>Student Name</th>
								<th>Used</th>
								<th>Available</th>
								<th>Used</th>
								<th>Available</th>
								<th>Used</th>
								<th>Available</th>
								<th>Used</th>
								<th>Available</th>
								<th>Used</th>
								<th>Available</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td><a href="#" class="link-primary">AD9892434</a></td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-01.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('student-details')}}">Janet</a>
											</p>
											<span class="fs-12">Roll No : 35013</span>
										</div>
									</div>
								</td>
								<td>02</td>
								<td>08</td>
								<td>04</td>
								<td>08</td>
								<td>0</td>
								<td>10</td>
								<td>0</td>
								<td>10</td>
								<td>0</td>
								<td>10</td>
							</tr>
							<tr>
								<td><a href="#" class="link-primary">AD9892433</a></td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-02.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('student-details')}}">Joann</a>
											</p>
											<span class="fs-12">Roll No : 35012</span>
										</div>
									</div>
								</td>
								<td>02</td>
								<td>08</td>
								<td>04</td>
								<td>08</td>
								<td>0</td>
								<td>10</td>
								<td>0</td>
								<td>10</td>
								<td>0</td>
								<td>10</td>
							</tr>
							<tr>
								<td><a href="#" class="link-primary">AD9892432</a></td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-03.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a
													href="{{url('student-details')}}">Kathleen</a></p>
											<span class="fs-12">Roll No : 35011</span>
										</div>
									</div>
								</td>
								<td>02</td>
								<td>08</td>
								<td>04</td>
								<td>08</td>
								<td>0</td>
								<td>10</td>
								<td>0</td>
								<td>10</td>
								<td>0</td>
								<td>10</td>
							</tr>
							<tr>
								<td><a href="#" class="link-primary">AD9892431</a></td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-04.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('student-details')}}">Gifford</a>
											</p>
											<span class="fs-12">Roll No : 35010</span>
										</div>
									</div>
								</td>
								<td>02</td>
								<td>08</td>
								<td>04</td>
								<td>08</td>
								<td>0</td>
								<td>10</td>
								<td>0</td>
								<td>10</td>
								<td>0</td>
								<td>10</td>
							</tr>
							<tr>
								<td><a href="#" class="link-primary">AD9892430</a></td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-05.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('student-details')}}">Lisa</a>
											</p>
											<span class="fs-12">Roll No : 35009</span>
										</div>
									</div>
								</td>
								<td>02</td>
								<td>08</td>
								<td>04</td>
								<td>08</td>
								<td>0</td>
								<td>10</td>
								<td>0</td>
								<td>10</td>
								<td>0</td>
								<td>10</td>
							</tr>
							<tr>
								<td><a href="#" class="link-primary">AD9892429</a></td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-06.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('student-details')}}">Ralph</a>
											</p>
											<span class="fs-12">Roll No : 35008</span>
										</div>
									</div>
								</td>
								<td>02</td>
								<td>08</td>
								<td>04</td>
								<td>08</td>
								<td>0</td>
								<td>10</td>
								<td>0</td>
								<td>10</td>
								<td>0</td>
								<td>10</td>
							</tr>
							<tr>
								<td><a href="#" class="link-primary">AD9892428</a></td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-07.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('student-details')}}">Julie</a>
											</p>
											<span class="fs-12">Roll No : 35007</span>
										</div>
									</div>
								</td>
								<td>02</td>
								<td>08</td>
								<td>04</td>
								<td>08</td>
								<td>0</td>
								<td>10</td>
								<td>0</td>
								<td>10</td>
								<td>0</td>
								<td>10</td>
							</tr>
							<tr>
								<td><a href="#" class="link-primary">AD9892427</a></td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-08.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('student-details')}}">Ryan</a>
											</p>
											<span class="fs-12">Roll No : 35006</span>
										</div>
									</div>
								</td>
								<td>02</td>
								<td>08</td>
								<td>04</td>
								<td>08</td>
								<td>0</td>
								<td>10</td>
								<td>0</td>
								<td>10</td>
								<td>0</td>
								<td>10</td>
							</tr>
							<tr>
								<td><a href="#" class="link-primary">AD9892426</a></td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-09.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('student-details')}}">Susan</a>
											</p>
											<span class="fs-12">Roll No : 35004</span>
										</div>
									</div>
								</td>
								<td>02</td>
								<td>08</td>
								<td>04</td>
								<td>08</td>
								<td>0</td>
								<td>10</td>
								<td>0</td>
								<td>10</td>
								<td>0</td>
								<td>10</td>
							</tr>
							<tr>
								<td><a href="#" class="link-primary">AD9892425</a></td>
								<td>
									<div class="d-flex align-items-center">
										<a href="{{url('student-details')}}" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-10.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="{{url('student-details')}}">Richard</a>
											</p>
											<span class="fs-12">Roll No : 35003</span>
										</div>
									</div>
								</td>
								<td>02</td>
								<td>08</td>
								<td>04</td>
								<td>08</td>
								<td>0</td>
								<td>10</td>
								<td>0</td>
								<td>10</td>
								<td>0</td>
								<td>10</td>
							</tr>
						</tbody>
					</table>
				</div>
				<!-- /Student List -->
			</div>
		</div>
		<!-- /Student List -->

	</div>
</div>
<!-- /Page Wrapper -->

@endsection
