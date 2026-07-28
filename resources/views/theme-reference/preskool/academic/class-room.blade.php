<?php $page = 'class-room'; ?>
@extends('layout.mainlayout')
@section('content')
  <!-- Page Wrapper -->
  <div class="page-wrapper">
	<div class="content">

		@component('components.breadcrumb')
		@slot('title')
		Class Room
		@endslot
		@slot('item1')
		Dashboard
		@endslot
		@slot('item2')
		Academic
        @endslot
		@slot('item3')
	    Class Room
		@endslot
	@endcomponent
		<!-- Guardians List -->
		<div class="card">
			<div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
				<h4 class="mb-3">Class Room</h4>
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
							<form action="{{url('class-room')}}">
								<div class="d-flex align-items-center border-bottom p-3">
									<h4>Filter</h4>
								</div>
								<div class="p-3 border-bottom pb-0">
									<div class="row">
										<div class="col-md-12">
											<div class="mb-3">
												<label class="form-label">Room No</label>
												<select class="select">
													<option>Select</option>
													<option>101</option>
													<option>102</option>
													<option>103</option>
												</select>
											</div>
										</div>
										<div class="col-md-12">
											<div class="mb-3">
												<label class="form-label">Capacity</label>
												<select class="select">
													<option>Select</option>
													<option>50</option>
													<option>40</option>
													<option>60</option>
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
								<th>Room No</th>
								<th>Capacity</th>
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
								<td><a href="#" class="link-primary">R167648</a></td>
								<td>101</td>
								<td>50</td>
								<td>
									<span class="badge badge-soft-success d-inline-flex align-items-center"><i
											class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
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
														data-bs-toggle="modal" data-bs-target="#edit_class_room"><i
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
								<td><a href="#" class="link-primary">R167647</a></td>
								<td>102</td>
								<td>40</td>
								<td>
									<span class="badge badge-soft-success d-inline-flex align-items-center"><i
											class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
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
														data-bs-toggle="modal" data-bs-target="#edit_class_room"><i
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
								<td><a href="#" class="link-primary">R167646</a></td>
								<td>103</td>
								<td>60</td>
								<td>
									<span class="badge badge-soft-success d-inline-flex align-items-center"><i
											class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
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
														data-bs-toggle="modal" data-bs-target="#edit_class_room"><i
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
								<td><a href="#" class="link-primary">R167645</a></td>
								<td>104</td>
								<td>50</td>
								<td>
									<span class="badge badge-soft-success d-inline-flex align-items-center"><i
											class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
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
														data-bs-toggle="modal" data-bs-target="#edit_class_room"><i
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
								<td><a href="#" class="link-primary">R167644</a></td>
								<td>105</td>
								<td>40</td>
								<td>
									<span class="badge badge-soft-success d-inline-flex align-items-center"><i
											class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
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
														data-bs-toggle="modal" data-bs-target="#edit_class_room"><i
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
								<td><a href="#" class="link-primary">R167643</a></td>
								<td>106</td>
								<td>50</td>
								<td>
									<span class="badge badge-soft-success d-inline-flex align-items-center"><i
											class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
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
														data-bs-toggle="modal" data-bs-target="#edit_class_room"><i
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
								<td><a href="#" class="link-primary">R167642</a></td>
								<td>107</td>
								<td>40</td>
								<td>
									<span class="badge badge-soft-success d-inline-flex align-items-center"><i
											class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
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
														data-bs-toggle="modal" data-bs-target="#edit_class_room"><i
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
								<td><a href="#" class="link-primary">R167641</a></td>
								<td>108</td>
								<td>40</td>
								<td>
									<span class="badge badge-soft-success d-inline-flex align-items-center"><i
											class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
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
														data-bs-toggle="modal" data-bs-target="#edit_class_room"><i
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
								<td><a href="#" class="link-primary">R167640</a></td>
								<td>109</td>
								<td>40</td>
								<td>
									<span class="badge badge-soft-success d-inline-flex align-items-center"><i
											class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
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
														data-bs-toggle="modal" data-bs-target="#edit_class_room"><i
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
								<td><a href="#" class="link-primary">R167639</a></td>
								<td>110</td>
								<td>50</td>
								<td>
									<span class="badge badge-soft-success d-inline-flex align-items-center"><i
											class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
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
														data-bs-toggle="modal" data-bs-target="#edit_class_room"><i
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
