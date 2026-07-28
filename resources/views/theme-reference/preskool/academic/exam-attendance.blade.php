<?php $page = 'exam-attendance'; ?>
@extends('layout.mainlayout')
@section('content')
   
		<!-- Page Wrapper -->
		<div class="page-wrapper">
			<div class="content">

		@component('components.breadcrumb')
		@slot('title')
		Exam Attendance
		@endslot
		@slot('item1')
		Dashboard
		@endslot
		@slot('item2')
		Report
		@endslot
		@slot('item3')
		Exam-attendance
		@endslot
	   @endcomponent

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
					</ul>
				</div>

				<!-- Attendance List -->
				<div class="card">
					<div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
						<h4 class="mb-3">Exam Attendance</h4>
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
									<form action="{{url('attendance-report')}}">
										<div class="d-flex align-items-center border-bottom p-3">
											<h4>Filter</h4>
										</div>
										<div class="p-3 border-bottom pb-0">
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
														<label class="form-label">Exam Type</label>
														<select class="select">
															<option>Select</option>
															<option>Weekly Test</option>
															<option>Monthly Test</option>
															<option>Chapter Wise Test</option>
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
										<th class="no-sort">
											<label class="checkboxs"><input type="checkbox" id="select-all"><span
													class="checkmarks"></span></label>
										</th>
										<th>ID</th>
										<th>Student Name</th>
										<th>English</th>
										<th>Spanish</th>
										<th>Physics </th>
										<th>Chemistry</th>
										<th>Maths</th>
										<th>Computer</th>
										<th>Env Science</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td>
											<div class="form-check form-check-md">
												<input class="form-check-input" type="checkbox">
											</div>
										</td>
										<td><a href="#" class="link-primary">EA123794</a></td>
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
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
									</tr>
									<tr>
										<td>
											<div class="form-check form-check-md">
												<input class="form-check-input" type="checkbox">
											</div>
										</td>
										<td><a href="#" class="link-primary">EA123793</a></td>
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
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-danger"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
									</tr>
									<tr>
										<td>
											<div class="form-check form-check-md">
												<input class="form-check-input" type="checkbox">
											</div>
										</td>
										<td><a href="#" class="link-primary">EA123792</a></td>
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
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-danger"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
									</tr>
									<tr>
										<td>
											<div class="form-check form-check-md">
												<input class="form-check-input" type="checkbox">
											</div>
										</td>
										<td><a href="#" class="link-primary">EA123791</a></td>
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
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
									</tr>
									<tr>
										<td>
											<div class="form-check form-check-md">
												<input class="form-check-input" type="checkbox">
											</div>
										</td>
										<td><a href="#" class="link-primary">EA123790</a></td>
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
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-pending"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
									</tr>
									<tr>
										<td>
											<div class="form-check form-check-md">
												<input class="form-check-input" type="checkbox">
											</div>
										</td>
										<td><a href="#" class="link-primary">EA123789</a></td>
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
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
									</tr>
									<tr>
										<td>
											<div class="form-check form-check-md">
												<input class="form-check-input" type="checkbox">
											</div>
										</td>
										<td><a href="#" class="link-primary">EA123788</a></td>
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
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-pending"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
									</tr>
									<tr>
										<td>
											<div class="form-check form-check-md">
												<input class="form-check-input" type="checkbox">
											</div>
										</td>
										<td><a href="#" class="link-primary">EA123787</a></td>
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
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-danger"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
									</tr>
									<tr>
										<td>
											<div class="form-check form-check-md">
												<input class="form-check-input" type="checkbox">
											</div>
										</td>
										<td><a href="#" class="link-primary">EA123786</a></td>
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
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-danger"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
									</tr>
									<tr>
										<td>
											<div class="form-check form-check-md">
												<input class="form-check-input" type="checkbox">
											</div>
										</td>
										<td><a href="#" class="link-primary">EA123785</a></td>
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
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
										<td><span class="attendance-range bg-success"></span></td>
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
    @component('components.modal-popup')
    @endcomponent
@endsection
