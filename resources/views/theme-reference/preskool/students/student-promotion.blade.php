<?php $page = 'student-promotion'; ?>
@extends('layout.mainlayout')
@section('content')
   <!-- Page Wrapper -->
		<div class="page-wrapper">
			<div class="content">
				<div class="row">
					<div class="col-md-12">
						<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
							<div class="my-auto mb-2">
								<h3 class="page-title mb-1">Student Promotion</h3>
								<nav>
									<ol class="breadcrumb mb-0">
										<li class="breadcrumb-item">
											<a href="javascript:void(0);">Dashboard</a>
										</li>
										<li class="breadcrumb-item">
											<a href="javascript:void(0);">Students</a>
										</li>
										<li class="breadcrumb-item active" aria-current="page">Student Promotion</li>
									</ol>
								</nav>
							</div>
							<div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
								<div class="pe-1 mb-2">
									<a href="#" class="btn btn-outline-light bg-white btn-icon me-1"
										data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Refresh"
										data-bs-original-title="Refresh">
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
						<div
							class="alert alert-outline-primary bg-primary-transparent p-2 d-flex align-items-center flex-wrap row-gap-2 mb-4">
							<i class="ti ti-info-circle me-1"></i><strong>Note :</strong> Prompting Student from the
							Present class to the Next Class will Create an enrollment of the student to the next Session
						</div>
						<div class="card">
							<div class="card-header border-0 pb-0">
								<div class="bg-light-gray p-3 rounded">
									<h4>Promotion</h4>
									<p>Select a Class to Promote next session and new class</p>
								</div>
							</div>
							<div class="card-body">
								<form action="{{url('student-promotion')}}">
									<div class="d-md-flex align-items-center justify-content-between">
										<div class="card flex-fill w-100">
											<div class="card-body pb-1">
												<div class="mb-3">
													<label class="form-label">Current Session <span class="text-danger">
															*</span></label>
													<div class="form-control-plaintext p-0">2024 - 2025</div>
												</div>
												<div>
													<label class="form-label mb-2">Promotion from Class<span
															class="text-danger"> *</span></label>
													<div class="d-block d-md-flex">
														<div class="mb-3 flex-fill me-md-3 me-0">
															<label class="form-label">Class</label>
															<select class="select">
																<option>Select</option>
																<option>II</option>
																<option>III</option>
																<option>IV</option>
															</select>
														</div>
														<div class="mb-3 flex-fill">
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
										</div>
										<a href="#"
											class="badge bg-primary badge-xl exchange-link text-white d-flex align-items-center justify-content-center mx-md-4 mx-auto my-md-0 my-4 flex-shrink-0">
											<span><i class="ti ti-arrows-exchange fs-16"></i></span>
										</a>
										<div class="card flex-fill w-100">
											<div class="card-body pb-1">
												<div class="mb-3">
													<label class="form-label">Promote to Session <span
															class="text-danger"> *</span></label>
													<select class="select">
														<option>Select</option>
														<option selected>2025 - 2026</option>
													</select>
												</div>
												<div>
													<label class="form-label mb-2">Promotion from Class<span
															class="text-danger"> *</span></label>
													<div class="d-block d-md-flex">
														<div class="mb-3 flex-fill me-md-3 me-0">
															<label class="form-label">Class</label>
															<select class="select">
																<option>Select</option>
																<option>II</option>
																<option>III</option>
																<option>IV</option>
															</select>
														</div>
														<div class="mb-3 flex-fill">
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
										</div>
									</div>
									<div class="col-md-12">
										<div
											class="manage-promote-btn d-flex justify-content-center flex-wrap row-gap-2">
											<button type="reset" class="btn btn-light reset-promote me-3">Reset
												Promotion</button>
											<button type="button" class="btn btn-primary promote-students-btn">Manage
												Promotion</button>
										</div>
									</div>
								</form>
							</div>
						</div>
						<div class="promote-card-main">
							<div class="card">
								<div class="card-header border-0 pb-0">
									<div class="bg-light-gray p-3 rounded">
										<h4>Map Class Sections</h4>
										<p>Select section mapping of old class to new class</p>
									</div>
								</div>
								<div class="card-body pb-2">
									<form action="{{url('student-promotion')}}">
										<div class="d-flex align-items-center justify-content-between">
											<div class="card w-100">
												<div class="card-body pb-1">
													<div class="mb-3">
														<label class="form-label">From Class<span class="text-danger">
																*</span></label>
														<div class="form-control-plaintext p-0">III</div>
													</div>
													<div class="mb-3">
														<label class="form-label d-block mb-3">Promotion from
															Section<span class="text-danger"> *</span></label>
														<label class="form-label d-block mb-2">Student From Section
															<span class="text-danger"> *</span></label>
														<div class="form-control-plaintext p-0">A</div>
													</div>
												</div>
											</div>
											<a href="#"
												class="badge bg-primary badge-xl exchange-link text-white d-flex align-items-center justify-content-center mx-md-4 mx-auto my-md-0 my-4 flex-shrink-0">
												<span><i class="ti ti-arrows-exchange fs-16"></i></span>
											</a>
											<div class="card w-100">
												<div class="card-body pb-1">
													<div class="mb-3">
														<label class="form-label">Promote to Session <span
																class="text-danger"> *</span></label>
														<div class="form-control-plaintext p-0">IV</div>
													</div>
													<div>
														<label class="form-label mb-2">Assign to Section<span
																class="text-danger"> *</span></label>
														<div class="d-block d-md-flex">
															<div class="mb-3 flex-fill me-0">
																<label class="form-label">Class</label>
																<select class="select">
																	<option>Select</option>
																	<option selected>A</option>
																	<option>B</option>
																	<option>C</option>
																</select>
															</div>
														</div>
													</div>
												</div>
											</div>
										</div>
									</form>
								</div>
							</div>
							<!-- Students List -->
							<div class="card">
								<div
									class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
									<h4 class="mb-3">Students List</h4>
									<div class="d-flex align-items-center flex-wrap">
										<div class="input-icon-start mb-3 me-2 position-relative">
											<span class="icon-addon">
												<i class="ti ti-calendar"></i>
											</span>
											<input type="text" class="form-control  date-range bookingrange"
												placeholder="Select" value="Academic Year : 2024 / 2025">
										</div>
										<div class="dropdown mb-3">
											<a href="javascript:void(0);"
												class="btn btn-outline-light bg-white dropdown-toggle"
												data-bs-toggle="dropdown"><i
													class="ti ti-sort-ascending-2 me-2"></i>Sort by A-Z </a>
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
									<div class="table-responsive">
										<table class="table datatable">
											<thead class="thead-light">
												<tr>
													<th class="no-sort">
														<div class="form-check form-check-md">
															<input class="form-check-input" type="checkbox"
																id="select-all">
														</div>
													</th>
													<th>Admission No</th>
													<th>Roll No</th>
													<th>Name</th>
													<th>Class </th>
													<th>Section</th>
													<th>Exam Result</th>
													<th class="no-sort">Action</th>
												</tr>
											</thead>
											<tbody>
												<tr>
													<td>
														<div class="form-check form-check-md">
															<input class="form-check-input" type="checkbox">
														</div>
													</td>
													<td><a href="{{url('student-details')}}"
															class="link-primary">AD9892434</a></td>
													<td>35013</td>
													<td>
														<div class="d-flex align-items-center">
															<a href="{{url('student-details')}}" class="avatar avatar-md"><img
																	src="{{URL::asset('build/img/students/student-01.jpg')}}"
																	class="img-fluid rounded-circle" alt="img"></a>
															<div class="ms-2">
																<p class="text-dark mb-0"><a
																		href="{{url('student-details')}}">Janet</a></p>
															</div>
														</div>
													</td>
													<td>III</td>
													<td>A</td>
													<td>
														<span
															class="badge badge-soft-success d-inline-flex align-items-center"><i
																class="ti ti-circle-filled fs-5 me-1"></i>Pass</span>
													</td>
													<td>
														<div class="table-select mb-0">
															<select class="select">
																<option>Select</option>
																<option selected>Promote to IV</option>
																<option>No Promotion</option>
															</select>
														</div>
													</td>
												</tr>
												<tr>
													<td>
														<div class="form-check form-check-md">
															<input class="form-check-input" type="checkbox">
														</div>
													</td>
													<td><a href="{{url('student-details')}}"
															class="link-primary">AD9892433</a></td>
													<td>35013</td>
													<td>
														<div class="d-flex align-items-center">
															<a href="{{url('student-details')}}" class="avatar avatar-md"><img
																	src="{{URL::asset('build/img/students/student-02.jpg')}}"
																	class="img-fluid rounded-circle" alt="img"></a>
															<div class="ms-2">
																<p class="text-dark mb-0"><a
																		href="{{url('staff-details')}}">Joann</a></p>
															</div>
														</div>
													</td>
													<td>IV</td>
													<td>B</td>
													<td>
														<span
															class="badge badge-soft-success d-inline-flex align-items-center"><i
																class="ti ti-circle-filled fs-5 me-1"></i>Pass</span>
													</td>
													<td>
														<div class="table-select mb-0">
															<select class="select">
																<option>Select</option>
																<option selected>Promote to IV</option>
																<option>No Promotion</option>
															</select>
														</div>
													</td>
												</tr>
												<tr>
													<td>
														<div class="form-check form-check-md">
															<input class="form-check-input" type="checkbox">
														</div>
													</td>
													<td><a href="{{url('student-details')}}"
															class="link-primary">AD9892432</a></td>
													<td>35011</td>
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
													<td>A</td>
													<td>
														<span
															class="badge badge-soft-success d-inline-flex align-items-center"><i
																class="ti ti-circle-filled fs-5 me-1"></i>Pass</span>
													</td>
													<td>
														<div class="table-select mb-0">
															<select class="select">
																<option>Select</option>
																<option selected>Promote to IV</option>
																<option>No Promotion</option>
															</select>
														</div>
													</td>
												</tr>
												<tr>
													<td>
														<div class="form-check form-check-md">
															<input class="form-check-input" type="checkbox">
														</div>
													</td>
													<td><a href="{{url('student-details')}}"
															class="link-primary">AD9892431</a></td>
													<td>35010</td>
													<td>
														<div class="d-flex align-items-center">
															<a href="{{url('student-details')}}" class="avatar avatar-md"><img
																	src="{{URL::asset('build/img/students/student-04.jpg')}}"
																	class="img-fluid rounded-circle" alt="img"></a>
															<div class="ms-2">
																<p class="text-dark mb-0"><a
																		href="{{url('student-details')}}">Gifford</a></p>
															</div>
														</div>
													</td>
													<td>I</td>
													<td>B</td>
													<td>
														<span
															class="badge badge-soft-success d-inline-flex align-items-center"><i
																class="ti ti-circle-filled fs-5 me-1"></i>Pass</span>
													</td>
													<td>
														<div class="table-select mb-0">
															<select class="select">
																<option>Select</option>
																<option selected>Promote to IV</option>
																<option>No Promotion</option>
															</select>
														</div>
													</td>
												</tr>
												<tr>
													<td>
														<div class="form-check form-check-md">
															<input class="form-check-input" type="checkbox">
														</div>
													</td>
													<td><a href="{{url('student-details')}}"
															class="link-primary">AD9892430</a></td>
													<td>35009</td>
													<td>
														<div class="d-flex align-items-center">
															<a href="{{url('student-details')}}" class="avatar avatar-md"><img
																	src="{{URL::asset('build/img/students/student-05.jpg')}}"
																	class="img-fluid rounded-circle" alt="img"></a>
															<div class="ms-2">
																<p class="text-dark mb-0"><a
																		href="{{url('student-details')}}">Lisa</a></p>
															</div>
														</div>
													</td>
													<td>II</td>
													<td>B</td>
													<td>
														<span
															class="badge badge-soft-danger d-inline-flex align-items-center"><i
																class="ti ti-circle-filled fs-5 me-1"></i>Fail</span>
													</td>
													<td>
														<div class="table-select mb-0">
															<select class="select">
																<option>Select</option>
																<option>Promote to IV</option>
																<option selected>No Promotion</option>
															</select>
														</div>
													</td>
												</tr>
												<tr>
													<td>
														<div class="form-check form-check-md">
															<input class="form-check-input" type="checkbox">
														</div>
													</td>
													<td><a href="{{url('student-details')}}"
															class="link-primary">AD9892429</a></td>
													<td>35008</td>
													<td>
														<div class="d-flex align-items-center">
															<a href="{{url('student-details')}}" class="avatar avatar-md"><img
																	src="{{URL::asset('build/img/students/student-06.jpg')}}"
																	class="img-fluid rounded-circle" alt="img"></a>
															<div class="ms-2">
																<p class="text-dark mb-0"><a
																		href="{{url('student-details')}}">Ralph</a></p>
															</div>
														</div>
													</td>
													<td>III</td>
													<td>B</td>
													<td>
														<span
															class="badge badge-soft-success d-inline-flex align-items-center"><i
																class="ti ti-circle-filled fs-5 me-1"></i>Pass</span>
													</td>
													<td>
														<div class="table-select mb-0">
															<select class="select">
																<option>Select</option>
																<option selected>Promote to IV</option>
																<option>No Promotion</option>
															</select>
														</div>
													</td>
												</tr>
												<tr>
													<td>
														<div class="form-check form-check-md">
															<input class="form-check-input" type="checkbox">
														</div>
													</td>
													<td><a href="{{url('student-details')}}"
															class="link-primary">AD9892428</a></td>
													<td>35007</td>
													<td>
														<div class="d-flex align-items-center">
															<a href="{{url('student-details')}}" class="avatar avatar-md"><img
																	src="{{URL::asset('build/img/students/student-07.jpg')}}"
																	class="img-fluid rounded-circle" alt="img"></a>
															<div class="ms-2">
																<p class="text-dark mb-0"><a
																		href="{{url('student-details')}}">Julie</a></p>
															</div>
														</div>
													</td>
													<td>V</td>
													<td>A</td>
													<td>
														<span
															class="badge badge-soft-success d-inline-flex align-items-center"><i
																class="ti ti-circle-filled fs-5 me-1"></i>Pass</span>
													</td>
													<td>
														<div class="table-select mb-0">
															<select class="select">
																<option>Select</option>
																<option selected>Promote to IV</option>
																<option>No Promotion</option>
															</select>
														</div>
													</td>
												</tr>
												<tr>
													<td>
														<div class="form-check form-check-md">
															<input class="form-check-input" type="checkbox">
														</div>
													</td>
													<td><a href="{{url('student-details')}}"
															class="link-primary">AD9892427</a></td>
													<td>35006</td>
													<td>
														<div class="d-flex align-items-center">
															<a href="{{url('student-details')}}" class="avatar avatar-md"><img
																	src="{{URL::asset('build/img/students/student-08.jpg')}}"
																	class="img-fluid rounded-circle" alt="img"></a>
															<div class="ms-2">
																<p class="text-dark mb-0"><a
																		href="{{url('student-details')}}">Ryan</a></p>
															</div>
														</div>
													</td>
													<td>VI</td>
													<td>A</td>
													<td>
														<span
															class="badge badge-soft-danger d-inline-flex align-items-center"><i
																class="ti ti-circle-filled fs-5 me-1"></i>Fail</span>
													</td>
													<td>
														<div class="table-select mb-0">
															<select class="select">
																<option>Select</option>
																<option>Promote to IV</option>
																<option selected>No Promotion</option>
															</select>
														</div>
													</td>
												</tr>
												<tr>
													<td>
														<div class="form-check form-check-md">
															<input class="form-check-input" type="checkbox">
														</div>
													</td>
													<td><a href="{{url('student-details')}}"
															class="link-primary">AD9892426</a></td>
													<td>35005</td>
													<td>
														<div class="d-flex align-items-center">
															<a href="{{url('student-details')}}" class="avatar avatar-md"><img
																	src="{{URL::asset('build/img/students/student-09.jpg')}}"
																	class="img-fluid rounded-circle" alt="img"></a>
															<div class="ms-2">
																<p class="text-dark mb-0"><a
																		href="{{url('student-details')}}">Susan</a></p>
															</div>
														</div>
													</td>
													<td>VIII</td>
													<td>B</td>
													<td>
														<span
															class="badge badge-soft-danger d-inline-flex align-items-center"><i
																class="ti ti-circle-filled fs-5 me-1"></i>Fail</span>
													</td>
													<td>
														<div class="table-select mb-0">
															<select class="select">
																<option>Select</option>
																<option>Promote to IV</option>
																<option selected>No Promotion</option>
															</select>
														</div>
													</td>
												</tr>
												<tr>
													<td>
														<div class="form-check form-check-md">
															<input class="form-check-input" type="checkbox">
														</div>
													</td>
													<td><a href="{{url('student-details')}}"
															class="link-primary">AD9892425</a></td>
													<td>35004</td>
													<td>
														<div class="d-flex align-items-center">
															<a href="{{url('student-details')}}" class="avatar avatar-md"><img
																	src="{{URL::asset('build/img/students/student-10.jpg')}}"
																	class="img-fluid rounded-circle" alt="img"></a>
															<div class="ms-2">
																<p class="text-dark mb-0"><a
																		href="{{url('student-details')}}">Richard</a></p>
															</div>
														</div>
													</td>
													<td>VII</td>
													<td>B</td>
													<td>
														<span
															class="badge badge-soft-success d-inline-flex align-items-center"><i
																class="ti ti-circle-filled fs-5 me-1"></i>Pass</span>
													</td>
													<td>
														<div class="table-select mb-0">
															<select class="select">
																<option>Select</option>
																<option selected>Promote to IV</option>
																<option>No Promotion</option>
															</select>
														</div>
													</td>
												</tr>
												<tr>
													<td>
														<div class="form-check form-check-md">
															<input class="form-check-input" type="checkbox">
														</div>
													</td>
													<td><a href="{{url('student-details')}}"
															class="link-primary">AD9892424</a></td>
													<td>35003</td>
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
													<td>A</td>
													<td>
														<span
															class="badge badge-soft-success d-inline-flex align-items-center"><i
																class="ti ti-circle-filled fs-5 me-1"></i>Pass</span>
													</td>
													<td>
														<div class="table-select mb-0">
															<select class="select">
																<option>Select</option>
																<option selected>Promote to IV</option>
																<option>No Promotion</option>
															</select>
														</div>
													</td>
												</tr>
											</tbody>
										</table>
									</div>
									<!-- /Student List -->
								</div>
							</div>
							<!-- /Students List -->
							<div class="promoted-year text-center">
								<p>Selected Students will be prormoted to 2025 - 2026 Academic Session</p>
								<a href="#" class="btn btn-primary" data-bs-toggle="modal"
									data-bs-target="#student_promote">Promote Students</a>
							</div>
							<div class="toast-container success-msg-toast position-fixed">
								<div id="topright-Toast" class="toast" role="alert" aria-live="assertive"
									aria-atomic="true">
									<div class="toast-header">
										<p class="me-auto"><span><i
													class="ti ti-square-check-filled text-success"></i></span>Successfully
											Promoted</p>
										<a href="#" class="toast-close" data-bs-dismiss="toast"
											aria-label="Close"><span><i class="ti ti-x"></i></span>
										</a>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- /Page Wrapper -->

		<div class="modal fade" id="student_promote">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-body text-center">
						<h4>Confirm Promotion</h4>
						<p>Are you Sure, want to promote all 57 selected students to the next academic session</p>
						<div class="d-flex justify-content-center">
							<a href="javascript:void(0);" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</a>
							<a href="#" class="btn btn-danger" id="toprightToastBtn" data-bs-dismiss="modal">Promote</a>
						</div>
					</div>
				</div>
			</div>
		</div>
@endsection
