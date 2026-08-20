<?php $page = 'teacher-attendance'; ?>
@extends('layout.mainlayout')
@section('content')
 	<!-- Page Wrapper -->
	 <div class="page-wrapper">
		<div class="content">
			@component('components.breadcrumb')
			@slot('title')
			Teacher Attendance
			@endslot
			@slot('item1')
			Dashboard
			@endslot
			@slot('item2')
			Report
			@endslot
			@slot('item3')
			Teacher Attendance
			@endslot
		   @endcomponent
			

			<!-- Student List -->
			<div class="card">
				<div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
					<h4 class="mb-3">Teacher Attendance List</h4>
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
								<form action="{{url('teacher-attendance')}}">
									<div class="d-flex align-items-center border-bottom p-3">
										<h4>Filter</h4>
									</div>
									<div class="p-3 border-bottom">
										<div class="row">
											<div class="col-md-12">
												<div class="mb-3">
													<label class="form-label">ID</label>
													<select class="select">
														<option>Select</option>
														<option>T849127</option>
														<option>T849125</option>
														<option>T849126</option>
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
												<div class="mb-0">
													<label class="form-label">Class</label>
													<select class="select">
														<option>Select</option>
														<option>XI</option>
														<option>VII</option>
														<option>VIII</option>
													</select>
												</div>
											</div>
											<div class="col-md-6">
												<div class="mb-0">
													<label class="form-label">Attendance</label>
													<select class="select">
														<option>Select</option>
														<option>Present</option>
														<option>Absent</option>
														<option>Late</option>
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
										<div class="form-check form-check-md">
											<input class="form-check-input" type="checkbox" id="select-all">
										</div>
									</th>
									<th>ID</th>
									<th>Name</th>
									<th>Class </th>
									<th>Attendance</th>
									<th style="min-width: 200px;">Notes</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td>
										<div class="form-check form-check-md">
											<input class="form-check-input" type="checkbox">
										</div>
									</td>
									<td><a href="#" class="link-primary">T849127</a></td>
									<td>
										<div class="d-flex align-items-center">
											<a href="javascript:void(0);" class="avatar avatar-md"><img
													src="{{URL::asset('build/img/teachers/teacher-01.jpg')}}"
													class="img-fluid rounded-circle" alt="img"></a>
											<div class="ms-2">
												<p class="text-dark mb-0"><a href="javascript:void(0);">Janet</a>
												</p>
											</div>
										</div>
									</td>
									<td>III A</td>
									<td>
										<div class="d-flex align-items-center check-radio-group flex-nowrap">
											<label class="custom-radio">
												<input type="radio" name="teacher1" checked="">
												<span class="checkmark"></span>
												Present
											</label>
											<label class="custom-radio">
												<input type="radio" name="teacher1">
												<span class="checkmark"></span>
												Late
											</label>
											<label class="custom-radio">
												<input type="radio" name="teacher1">
												<span class="checkmark"></span>
												Absent
											</label>
											<label class="custom-radio">
												<input type="radio" name="teacher1">
												<span class="checkmark"></span>
												Holiday
											</label>
											<label class="custom-radio">
												<input type="radio" name="teacher1">
												<span class="checkmark"></span>
												Halfday
											</label>
										</div>
									</td>
									<td>

										<input type="text" class="form-control" placeholder="Enter Name">

									</td>
								</tr>
								<tr>
									<td>
										<div class="form-check form-check-md">
											<input class="form-check-input" type="checkbox">
										</div>
									</td>
									<td><a href="#" class="link-primary">T849126</a></td>
									<td>
										<div class="d-flex align-items-center">
											<a href="javascript:void(0);" class="avatar avatar-md"><img
													src="{{URL::asset('build/img/teachers/teacher-02.jpg')}}"
													class="img-fluid rounded-circle" alt="img"></a>
											<div class="ms-2">
												<p class="text-dark mb-0"><a href="javascript:void(0);">Daniel</a>
												</p>
											</div>
										</div>
									</td>
									<td>II (A)</td>
									<td>
										<div class="d-flex align-items-center check-radio-group flex-nowrap">
											<label class="custom-radio">
												<input type="radio" name="teacher2" checked="">
												<span class="checkmark"></span>
												Present
											</label>
											<label class="custom-radio">
												<input type="radio" name="teacher2">
												<span class="checkmark"></span>
												Late
											</label>
											<label class="custom-radio">
												<input type="radio" name="teacher2">
												<span class="checkmark"></span>
												Absent
											</label>
											<label class="custom-radio">
												<input type="radio" name="teacher2">
												<span class="checkmark"></span>
												Holiday
											</label>
											<label class="custom-radio">
												<input type="radio" name="teacher2">
												<span class="checkmark"></span>
												Halfday
											</label>
										</div>
									</td>
									<td>

										<input type="text" class="form-control" placeholder="Enter Name">

									</td>
								</tr>
								<tr>
									<td>
										<div class="form-check form-check-md">
											<input class="form-check-input" type="checkbox">
										</div>
									</td>
									<td><a href="#" class="link-primary">T849125</a></td>
									<td>
										<div class="d-flex align-items-center">
											<a href="javascript:void(0);" class="avatar avatar-md"><img
													src="{{URL::asset('build/img/teachers/teacher-03.jpg')}}"
													class="img-fluid rounded-circle" alt="img"></a>
											<div class="ms-2">
												<p class="text-dark mb-0"><a href="javascript:void(0);">Hellana</a>
												</p>
											</div>
										</div>
									</td>
									<td>VI (A)</td>
									<td>
										<div class="d-flex align-items-center check-radio-group flex-nowrap">
											<label class="custom-radio">
												<input type="radio" name="teacher3" checked="">
												<span class="checkmark"></span>
												Present
											</label>
											<label class="custom-radio">
												<input type="radio" name="teacher3">
												<span class="checkmark"></span>
												Late
											</label>
											<label class="custom-radio">
												<input type="radio" name="teacher3">
												<span class="checkmark"></span>
												Absent
											</label>
											<label class="custom-radio">
												<input type="radio" name="teacher3">
												<span class="checkmark"></span>
												Holiday
											</label>
											<label class="custom-radio">
												<input type="radio" name="teacher3">
												<span class="checkmark"></span>
												Halfday
											</label>
										</div>
									</td>
									<td>

										<input type="text" class="form-control" placeholder="Enter Name">

									</td>
								</tr>
								<tr>
									<td>
										<div class="form-check form-check-md">
											<input class="form-check-input" type="checkbox">
										</div>
									</td>
									<td><a href="#" class="link-primary">T849124</a></td>
									<td>
										<div class="d-flex align-items-center">
											<a href="javascript:void(0);" class="avatar avatar-md"><img
													src="{{URL::asset('build/img/teachers/teacher-04.jpg')}}"
													class="img-fluid rounded-circle" alt="img"></a>
											<div class="ms-2">
												<p class="text-dark mb-0"><a href="javascript:void(0);">Erickson</a>
												</p>
											</div>
										</div>
									</td>
									<td>VI (B) , V (A)</td>
									<td>
										<div class="d-flex align-items-center check-radio-group flex-nowrap">
											<label class="custom-radio">
												<input type="radio" name="teacher4" checked="">
												<span class="checkmark"></span>
												Present
											</label>
											<label class="custom-radio">
												<input type="radio" name="teacher4">
												<span class="checkmark"></span>
												Late
											</label>
											<label class="custom-radio">
												<input type="radio" name="teacher4">
												<span class="checkmark"></span>
												Absent
											</label>
											<label class="custom-radio">
												<input type="radio" name="teacher4">
												<span class="checkmark"></span>
												Holiday
											</label>
											<label class="custom-radio">
												<input type="radio" name="teacher4">
												<span class="checkmark"></span>
												Halfday
											</label>
										</div>
									</td>
									<td>

										<input type="text" class="form-control" placeholder="Enter Name">

									</td>
								</tr>
								<tr>
									<td>
										<div class="form-check form-check-md">
											<input class="form-check-input" type="checkbox">
										</div>
									</td>
									<td><a href="#" class="link-primary">T849123</a></td>
									<td>
										<div class="d-flex align-items-center">
											<a href="javascript:void(0);" class="avatar avatar-md"><img
													src="{{URL::asset('build/img/teachers/teacher-05.jpg')}}"
													class="img-fluid rounded-circle" alt="img"></a>
											<div class="ms-2">
												<p class="text-dark mb-0"><a href="javascript:void(0);">Morgan</a>
												</p>
											</div>
										</div>
									</td>
									<td>VIII</td>
									<td>
										<div class="d-flex align-items-center check-radio-group flex-nowrap">
											<label class="custom-radio">
												<input type="radio" name="teacher5" checked="">
												<span class="checkmark"></span>
												Present
											</label>
											<label class="custom-radio">
												<input type="radio" name="teacher5">
												<span class="checkmark"></span>
												Late
											</label>
											<label class="custom-radio">
												<input type="radio" name="teacher5">
												<span class="checkmark"></span>
												Absent
											</label>
											<label class="custom-radio">
												<input type="radio" name="teacher5">
												<span class="checkmark"></span>
												Holiday
											</label>
											<label class="custom-radio">
												<input type="radio" name="teacher5">
												<span class="checkmark"></span>
												Halfday
											</label>
										</div>
									</td>
									<td>

										<input type="text" class="form-control" placeholder="Enter Name">

									</td>
								</tr>
								<tr>
									<td>
										<div class="form-check form-check-md">
											<input class="form-check-input" type="checkbox">
										</div>
									</td>
									<td><a href="#" class="link-primary">T849122</a></td>
									<td>
										<div class="d-flex align-items-center">
											<a href="javascript:void(0);" class="avatar avatar-md"><img
													src="{{URL::asset('build/img/teachers/teacher-06.jpg')}}"
													class="img-fluid rounded-circle" alt="img"></a>
											<div class="ms-2">
												<p class="text-dark mb-0"><a href="javascript:void(0);">Aaron</a>
												</p>
											</div>
										</div>
									</td>
									<td>I (A)</td>
									<td>
										<div class="d-flex align-items-center check-radio-group flex-nowrap">
											<label class="custom-radio">
												<input type="radio" name="teacher6" checked="">
												<span class="checkmark"></span>
												Present
											</label>
											<label class="custom-radio">
												<input type="radio" name="teacher6">
												<span class="checkmark"></span>
												Late
											</label>
											<label class="custom-radio">
												<input type="radio" name="teacher6">
												<span class="checkmark"></span>
												Absent
											</label>
											<label class="custom-radio">
												<input type="radio" name="teacher6">
												<span class="checkmark"></span>
												Holiday
											</label>
											<label class="custom-radio">
												<input type="radio" name="teacher6">
												<span class="checkmark"></span>
												Halfday
											</label>
										</div>
									</td>
									<td>

										<input type="text" class="form-control" placeholder="Enter Name">

									</td>
								</tr>
								<tr>
									<td>
										<div class="form-check form-check-md">
											<input class="form-check-input" type="checkbox">
										</div>
									</td>
									<td><a href="#" class="link-primary">T849121</a></td>
									<td>
										<div class="d-flex align-items-center">
											<a href="javascript:void(0);" class="avatar avatar-md"><img
													src="{{URL::asset('build/img/teachers/teacher-07.jpg')}}"
													class="img-fluid rounded-circle" alt="img"></a>
											<div class="ms-2">
												<p class="text-dark mb-0"><a
														href="javascript:void(0);">Jacquelin</a></p>
											</div>
										</div>
									</td>
									<td>IV</td>
									<td>
										<div class="d-flex align-items-center check-radio-group flex-nowrap">
											<label class="custom-radio">
												<input type="radio" name="teacher7" checked="">
												<span class="checkmark"></span>
												Present
											</label>
											<label class="custom-radio">
												<input type="radio" name="teacher7">
												<span class="checkmark"></span>
												Late
											</label>
											<label class="custom-radio">
												<input type="radio" name="teacher7">
												<span class="checkmark"></span>
												Absent
											</label>
											<label class="custom-radio">
												<input type="radio" name="teacher7">
												<span class="checkmark"></span>
												Holiday
											</label>
											<label class="custom-radio">
												<input type="radio" name="teacher7">
												<span class="checkmark"></span>
												Halfday
											</label>
										</div>
									</td>
									<td>

										<input type="text" class="form-control" placeholder="Enter Name">

									</td>
								</tr>
								<tr>
									<td>
										<div class="form-check form-check-md">
											<input class="form-check-input" type="checkbox">
										</div>
									</td>
									<td><a href="#" class="link-primary">T849120</a></td>
									<td>
										<div class="d-flex align-items-center">
											<a href="javascript:void(0);" class="avatar avatar-md"><img
													src="{{URL::asset('build/img/teachers/teacher-08.jpg')}}"
													class="img-fluid rounded-circle" alt="img"></a>
											<div class="ms-2">
												<p class="text-dark mb-0"><a href="javascript:void(0);">Raul</a></p>
											</div>
										</div>
									</td>
									<td>IX</td>
									<td>
										<div class="d-flex align-items-center check-radio-group flex-nowrap">
											<label class="custom-radio">
												<input type="radio" name="teacher8" checked="">
												<span class="checkmark"></span>
												Present
											</label>
											<label class="custom-radio">
												<input type="radio" name="teacher8">
												<span class="checkmark"></span>
												Late
											</label>
											<label class="custom-radio">
												<input type="radio" name="teacher8">
												<span class="checkmark"></span>
												Absent
											</label>
											<label class="custom-radio">
												<input type="radio" name="teacher8">
												<span class="checkmark"></span>
												Holiday
											</label>
											<label class="custom-radio">
												<input type="radio" name="teacher8">
												<span class="checkmark"></span>
												Halfday
											</label>
										</div>
									</td>
									<td>

										<input type="text" class="form-control" placeholder="Enter Name">

									</td>
								</tr>
								<tr>
									<td>
										<div class="form-check form-check-md">
											<input class="form-check-input" type="checkbox">
										</div>
									</td>
									<td><a href="#" class="link-primary">T849119</a></td>
									<td>
										<div class="d-flex align-items-center">
											<a href="javascript:void(0);" class="avatar avatar-md"><img
													src="{{URL::asset('build/img/teachers/teacher-09.jpg')}}"
													class="img-fluid rounded-circle" alt="img"></a>
											<div class="ms-2">
												<p class="text-dark mb-0"><a
														href="javascript:void(0);">Elizabeth</a></p>
											</div>
										</div>
									</td>
									<td>VII</td>
									<td>
										<div class="d-flex align-items-center check-radio-group flex-nowrap">
											<label class="custom-radio">
												<input type="radio" name="teacher9" checked="">
												<span class="checkmark"></span>
												Present
											</label>
											<label class="custom-radio">
												<input type="radio" name="teacher9">
												<span class="checkmark"></span>
												Late
											</label>
											<label class="custom-radio">
												<input type="radio" name="teacher9">
												<span class="checkmark"></span>
												Absent
											</label>
											<label class="custom-radio">
												<input type="radio" name="teacher9">
												<span class="checkmark"></span>
												Holiday
											</label>
											<label class="custom-radio">
												<input type="radio" name="teacher9">
												<span class="checkmark"></span>
												Halfday
											</label>
										</div>
									</td>
									<td>

										<input type="text" class="form-control" placeholder="Enter Name">

									</td>
								</tr>
								<tr>
									<td>
										<div class="form-check form-check-md">
											<input class="form-check-input" type="checkbox">
										</div>
									</td>
									<td><a href="#" class="link-primary">T849118</a></td>
									<td>
										<div class="d-flex align-items-center">
											<a href="javascript:void(0);" class="avatar avatar-md"><img
													src="{{URL::asset('build/img/teachers/teacher-10.jpg')}}"
													class="img-fluid rounded-circle" alt="img"></a>
											<div class="ms-2">
												<p class="text-dark mb-0"><a href="javascript:void(0);">Edward</a>
												</p>
											</div>
										</div>
									</td>
									<td>IX (C) , X (C)</td>
									<td>
										<div class="d-flex align-items-center check-radio-group flex-nowrap">
											<label class="custom-radio">
												<input type="radio" name="teacher10" checked="">
												<span class="checkmark"></span>
												Present
											</label>
											<label class="custom-radio">
												<input type="radio" name="teacher10">
												<span class="checkmark"></span>
												Late
											</label>
											<label class="custom-radio">
												<input type="radio" name="teacher10">
												<span class="checkmark"></span>
												Absent
											</label>
											<label class="custom-radio">
												<input type="radio" name="teacher10">
												<span class="checkmark"></span>
												Holiday
											</label>
											<label class="custom-radio">
												<input type="radio" name="teacher10">
												<span class="checkmark"></span>
												Halfday
											</label>
										</div>
									</td>
									<td>
										<input type="text" class="form-control" placeholder="Enter Name">
									</td>
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
