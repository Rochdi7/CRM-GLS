<?php $page = 'class-home-work'; ?>
@extends('layout.mainlayout')
@section('content')
  <!-- Page Wrapper -->
  <div class="page-wrapper">
	<div class="content">

		<!-- Page Header -->
		<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
			<div class="my-auto mb-2">
				<h3 class="page-title mb-1">Class Work</h3>
				<nav>
					<ol class="breadcrumb mb-0">
						<li class="breadcrumb-item">
							<a href="{{url('index')}}">Dashboard</a>
						</li>
						<li class="breadcrumb-item">
							<a href="javascript:void(0);">Academic </a>
						</li>
						<li class="breadcrumb-item active" aria-current="page">Class Work</li>
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
				<div class="mb-2">
					<a href="#" class="btn btn-primary" data-bs-toggle="modal"
						data-bs-target="#add_home_work"><i class="ti ti-square-rounded-plus-filled me-2"></i>Add
						Home Work</a>
				</div>
			</div>
		</div>
		<!-- /Page Header -->

		<!-- Guardians List -->
		<div class="card">
			<div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
				<h4 class="mb-3">Class Home Work</h4>
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
							<form action="{{url('class-home-work')}}">
								<div class="d-flex align-items-center border-bottom p-3">
									<h4>Filter</h4>
								</div>
								<div class="p-3 border-bottom pb-0">
									<div class="row">
										<div class="col-md-12">
											<div class="mb-3">
												<label class="form-label">Subject</label>
												<select class="select">
													<option>Select</option>
													<option>English</option>
													<option>Maths</option>
													<option>Physics</option>
												</select>
											</div>
										</div>
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
												<label class="form-label">Date</label>
												<select class="select">
													<option>Select</option>
													<option>Monday</option>
													<option>Tuesday</option>
													<option>Wednesday</option>
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
								<th>ID</th>
								<th>Class</th>
								<th>Section</th>
								<th>Subject</th>
								<th>Homework Date</th>
								<th>Submission Date</th>
								<th>Created By</th>
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
								<td><a href="#" class="link-primary">HW1783929</a></td>
								<td>I</td>
								<td>A</td>
								<td>English</td>
								<td>10 May 2024</td>
								<td>12 May 2024</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="#" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-01.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="#">Janet</a></p>
										</div>
									</div>
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
													<a class="dropdown-item rounded-1" href="#"
														data-bs-toggle="modal" data-bs-target="#edit_home_work"><i
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
								<td><a href="#" class="link-primary">HW1783928</a></td>
								<td>I</td>
								<td>B</td>
								<td>Math</td>
								<td>11 May 2024</td>
								<td>13 May 2024</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="#" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-02.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="#">Joann</a></p>
										</div>
									</div>
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
													<a class="dropdown-item rounded-1" href="#"
														data-bs-toggle="modal" data-bs-target="#edit_home_work"><i
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
								<td><a href="#" class="link-primary">HW1783927</a></td>
								<td>II</td>
								<td>A</td>
								<td>Physics</td>
								<td>12 May 2024</td>
								<td>14 May 2024</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="#" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-03.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="#">Kathleen</a></p>
										</div>
									</div>
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
													<a class="dropdown-item rounded-1" href="#"
														data-bs-toggle="modal" data-bs-target="#edit_home_work"><i
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
								<td><a href="#" class="link-primary">HW1783926</a></td>
								<td>II</td>
								<td>B</td>
								<td>Chemsitry</td>
								<td>13 May 2024</td>
								<td>15 May 2024</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="#" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-04.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="#">Gifford</a></p>
										</div>
									</div>
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
													<a class="dropdown-item rounded-1" href="#"
														data-bs-toggle="modal" data-bs-target="#edit_home_work"><i
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
								<td><a href="#" class="link-primary">HW1783925</a></td>
								<td>II</td>
								<td>C</td>
								<td>Biology</td>
								<td>14 May 2024</td>
								<td>16 May 2024</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="#" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-05.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="#">Lisa</a></p>
										</div>
									</div>
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
													<a class="dropdown-item rounded-1" href="#"
														data-bs-toggle="modal" data-bs-target="#edit_home_work"><i
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
								<td><a href="#" class="link-primary">HW1783924</a></td>
								<td>III</td>
								<td>A</td>
								<td>Higher Math</td>
								<td>15 May 2024</td>
								<td>17 May 2024</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="#" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-06.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="#">Ralph</a></p>
										</div>
									</div>
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
													<a class="dropdown-item rounded-1" href="#"
														data-bs-toggle="modal" data-bs-target="#edit_home_work"><i
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
								<td><a href="#" class="link-primary">HW1783923</a></td>
								<td>III</td>
								<td>B</td>
								<td>Information Technology</td>
								<td>16 May 2024</td>
								<td>18 May 2024</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="#" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-07.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="#">Julie</a></p>
										</div>
									</div>
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
													<a class="dropdown-item rounded-1" href="#"
														data-bs-toggle="modal" data-bs-target="#edit_home_work"><i
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
								<td><a href="#" class="link-primary">HW1783922</a></td>
								<td>IV</td>
								<td>A</td>
								<td>Moral Education</td>
								<td>17 May 2024</td>
								<td>19 May 2024</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="#" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-08.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="#">Ryan</a></p>
										</div>
									</div>
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
													<a class="dropdown-item rounded-1" href="#"
														data-bs-toggle="modal" data-bs-target="#edit_home_work"><i
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
								<td><a href="#" class="link-primary">HW1783921</a></td>
								<td>IV</td>
								<td>B</td>
								<td>Finance</td>
								<td>18 May 2024</td>
								<td>20 May 2024</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="#" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-09.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="#">Susan</a></p>
										</div>
									</div>
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
													<a class="dropdown-item rounded-1" href="#"
														data-bs-toggle="modal" data-bs-target="#edit_home_work"><i
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
								<td><a href="#" class="link-primary">HW1783920</a></td>
								<td>V</td>
								<td>A</td>
								<td>Economics</td>
								<td>19 May 2024</td>
								<td>21 May 2024</td>
								<td>
									<div class="d-flex align-items-center">
										<a href="#" class="avatar avatar-md"><img
												src="{{URL::asset('build/img/students/student-12.jpg')}}"
												class="img-fluid rounded-circle" alt="img"></a>
										<div class="ms-2">
											<p class="text-dark mb-0"><a href="#">Richard</a></p>
										</div>
									</div>
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
													<a class="dropdown-item rounded-1" href="#"
														data-bs-toggle="modal" data-bs-target="#edit_home_work"><i
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
