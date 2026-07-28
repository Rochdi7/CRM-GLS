<?php $page = 'exam-schedule'; ?>
@extends('layout.mainlayout')
@section('content')
   <!-- Page Wrapper -->
		<div class="page-wrapper">
			<div class="content">
				@component('components.breadcrumb')
				@slot('title')
				Exam Schedule
				@endslot
				@slot('item1')
				Dashboard
				@endslot
				@slot('item2')
				Academic
				@endslot
				@slot('item3')
				Exam Schedule
				@endslot
			@endcomponent
				

				<!-- Guardians List -->
				<div class="card">
					<div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
						<h4 class="mb-3">Exam Schedule</h4>
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
									<form action="{{url('exam-schedule')}}">
										<div class="d-flex align-items-center border-bottom p-3">
											<h4>Filter</h4>
										</div>
										<div class="p-3 border-bottom pb-0">
											<div class="row">
												<div class="col-md-12">
													<div class="mb-3">
														<label class="form-label">Class 1-A</label>
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
														<label class="form-label">Exam Date</label>
														<select class="select">
															<option>Select</option>
															<option>13 May 2024</option>
															<option>14 May 2024</option>
															<option>15 May 2024</option>
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

						<!-- Guardians List -->
						<div class="custom-datatable-filter table-responsive">
							<table class="table datatable">
								<thead class="thead-light">
									<tr>
										<th class="no-sort">
											<div class="form-check form-check-md">
												<input class="form-check-input" type="checkbox" id="select-all">
											</div>
										</th>
										<th>Subject </th>
										<th>Exam Date</th>
										<th>Start Time</th>
										<th>End Time</th>
										<th>Duration</th>
										<th>Room No</th>
										<th>Max Marks</th>
										<th>Min Marks</th>
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
										<td><a href="#" class="link-primary">English</a></td>
										<td>13 May 2024 </td>
										<td>09:30 AM </td>
										<td>10:45 AM</td>
										<td>3 hrs</td>
										<td>101</td>
										<td>100</td>
										<td>35</td>
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
															<a class="dropdown-item rounded-1" href="#"
																data-bs-toggle="modal"
																data-bs-target="#edit_exam_schedule"><i
																	class="ti ti-edit-circle me-2"></i>Edit</a>
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
										<td><a href="#" class="link-primary">Spanish</a></td>
										<td>14 May 2024 </td>
										<td>09:30 AM</td>
										<td>10:45 AM</td>
										<td>3 hrs</td>
										<td>104</td>
										<td>100</td>
										<td>35</td>
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
															<a class="dropdown-item rounded-1" href="#"
																data-bs-toggle="modal"
																data-bs-target="#edit_exam_schedule"><i
																	class="ti ti-edit-circle me-2"></i>Edit</a>
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
										<td><a href="#" class="link-primary">Physics</a></td>
										<td>15 May 2024 </td>
										<td>09:30 AM</td>
										<td>10:45 AM</td>
										<td>3 hrs</td>
										<td>103</td>
										<td>100</td>
										<td>35</td>
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
															<a class="dropdown-item rounded-1" href="#"
																data-bs-toggle="modal"
																data-bs-target="#edit_exam_schedule"><i
																	class="ti ti-edit-circle me-2"></i>Edit</a>
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
										<td><a href="#" class="link-primary">Chemistry</a></td>
										<td>16 May 2024 </td>
										<td>09:30 AM </td>
										<td>10:45 AM</td>
										<td>3 hrs</td>
										<td>105</td>
										<td>100</td>
										<td>35</td>
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
															<a class="dropdown-item rounded-1" href="#"
																data-bs-toggle="modal"
																data-bs-target="#edit_exam_schedule"><i
																	class="ti ti-edit-circle me-2"></i>Edit</a>
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
										<td><a href="#" class="link-primary">Maths</a></td>
										<td>17 May 2024 </td>
										<td>09:30 AM</td>
										<td>10:45 AM</td>
										<td>3 hrs</td>
										<td>106</td>
										<td>100</td>
										<td>35</td>
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
															<a class="dropdown-item rounded-1" href="#"
																data-bs-toggle="modal"
																data-bs-target="#edit_exam_schedule"><i
																	class="ti ti-edit-circle me-2"></i>Edit</a>
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
										<td><a href="#" class="link-primary">Computer</a></td>
										<td>18 May 2024 </td>
										<td>09:30 AM</td>
										<td>10:45 AM</td>
										<td>3 hrs</td>
										<td>102</td>
										<td>100</td>
										<td>35</td>
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
															<a class="dropdown-item rounded-1" href="#"
																data-bs-toggle="modal"
																data-bs-target="#edit_exam_schedule"><i
																	class="ti ti-edit-circle me-2"></i>Edit</a>
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
										<td><a href="#" class="link-primary">Env Science</a></td>
										<td>19 May 2024 </td>
										<td>09:30 AM</td>
										<td>10:45 AM</td>
										<td>3 hrs</td>
										<td>107</td>
										<td>100</td>
										<td>35</td>
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
															<a class="dropdown-item rounded-1" href="#"
																data-bs-toggle="modal"
																data-bs-target="#edit_exam_schedule"><i
																	class="ti ti-edit-circle me-2"></i>Edit</a>
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
						<!-- /Guardians List -->
					</div>
				</div>
				<!-- /Guardians List -->

			</div>
		</div>
		<!-- /Page Wrapper -->
    @component('components.modal-popup')
    @endcomponent
@endsection
